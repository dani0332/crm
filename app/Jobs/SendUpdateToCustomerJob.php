<?php

namespace App\Jobs;

use App\Enums\QuoteTypeId;
use App\Enums\SendUpdateLogStatusEnum;
use App\Jobs\EP\SendEPJob;
use App\Models\SendUpdateLog;
use App\Services\CentralService;
use App\Services\SageApiService;
use App\Services\SendEmailCustomerService;
use App\Services\SendUpdateLogService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendUpdateToCustomerJob implements ShouldQueue
{
    use Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 100;
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    private $sendUpdate;

    private $payload;
    public function __construct($sendUpdateLog, $payload)
    {
        $this->sendUpdate = $sendUpdateLog;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(SendEmailCustomerService $sendEmailCustomerService, SendUpdateLogService $sendUpdateLogServices)
    {
        $sendUpdateLog = SendUpdateLog::find($this->sendUpdate->id);
        info('job:SendUpdateToCustomerJob - Job started - SendUpdateCode: '.$sendUpdateLog->code);

        if ($sendUpdateLog->is_email_sent) {
            info('job:SendUpdateToCustomerJob - Email process skipped - Email already sent to SendUpdateCode: '.$sendUpdateLog->code);
        } else {
            @[$templateId, $emailData, $tag, $quoteTypeId] = $sendUpdateLogServices->sendUpdateToCustomerEmailData($this->sendUpdate, $this->payload['action']);
            if (! empty($templateId)) {
                info('job:SendUpdateToCustomerJob - SendUpdateCode: '.$sendUpdateLog->code.' - Job Email Data '.json_encode($emailData));
                $response = $sendEmailCustomerService->sendUpdateToCustomerEmail($templateId, $emailData, $tag, $quoteTypeId);
                info('job: SendUpdateToCustomerJob - SendUpdateCode: '.$sendUpdateLog->code.' - Job Response '.json_encode($response));

                if ($response == 201) {
                    info('job:SendUpdateToCustomerJob - Updating status to: '.SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER.' - SendUpdateCode: '.$sendUpdateLog->code);
                    app(CentralService::class)->updateSendUpdateStatusLogs($sendUpdateLog->id, $sendUpdateLog->status, SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER);
                    $sendUpdateLog->update([
                        'status' => SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER,
                        'is_email_sent' => true,
                    ]);
                    $sendUpdateLog->refresh();

                    if ($sendUpdateLog->category->code === SendUpdateLogStatusEnum::EN) {
                        $quoteType = QuoteTypeId::getOptions()[$sendUpdateLog->quote_type_id];
                        $quote = $this->getQuoteObject($quoteType, $sendUpdateLog->quote_uuid);
                        SendEPJob::dispatch($quote->id, $quoteType, null, true);
                    }

                } else {
                    info('job:SendUpdateToCustomerJob - SendUpdateCode: '.$sendUpdateLog->uuid.' - Job failed - SendUpdateCode: '.$sendUpdateLog->code);
                }
            }
        }

        if ($this->payload['action'] == SendUpdateLogStatusEnum::ACTION_SNBU && isset($this->payload['dispatchSageCall'])) {
            info('job:SendUpdateToCustomerJob - Calling updateSageProcessForDispatching function through sendUpdateToCustomer - SendUpdateCode: '.$sendUpdateLog->code);
            $sageRequestPayload = $this->payload['sageRequestPayload'];
            unset($this->payload['sageRequestPayload']);
            $sendUpdateLogServices->updateSageProcessForDispatching($this->payload, $sendUpdateLog, $sageRequestPayload);

            (new SageApiService)->scheduleSageProcesses($sageRequestPayload->insurerID);
            info('job:SendUpdateToCustomerJob - fn:scheduleSageProcesses triggered for Insurer - '.$sageRequestPayload->insurerID.' - SendUpdateCode: '.$sendUpdateLog->code);
        }

        info('job:SendUpdateToCustomerJob - Job completed - SendUpdateCode: '.$sendUpdateLog->code);
    }

    public function failed(Throwable $exception)
    {
        info('job:SendUpdateToCustomerJob - SendUpdateCode: '.$this->sendUpdate->code.' Error: '.$exception->getMessage());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->sendUpdate->id))->dontRelease()];
    }
}
