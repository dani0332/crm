<?php

namespace App\Jobs;

use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Controllers\V2\SendUpdateLogController;
use App\Http\Requests\SendUpdateRequest;
use App\Models\SendUpdateLog;
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
        info('job: SendUpdateToCustomerJob started - Send Update UUID: '.$sendUpdateLog->uuid);

        if ($sendUpdateLog->is_email_sent) {
            info('job: SendUpdateToCustomerJob email process skipped - Send Update UUID: '.$sendUpdateLog->uuid.' as email already sent');
        } else {
            @[$templateId, $emailData, $tag, $quoteTypeId] = $sendUpdateLogServices->sendUpdateToCustomerEmailData($this->sendUpdate, $this->payload['action']);
            if (! empty($templateId)) {
                info('Send Update to Customer - Send Update UUID: '.$sendUpdateLog->uuid.' - Job Email Data '.json_encode($emailData));
                $response = $sendEmailCustomerService->sendUpdateToCustomerEmail($templateId, $emailData, $tag, $quoteTypeId);
                info('Send Update to Customer - Send Update UUID: '.$sendUpdateLog->uuid.' - Job Response '.json_encode($response));

                if ($response == 201) {
                    $sendUpdateLog->update([
                        'status' => SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER,
                        'is_email_sent' => true,
                    ]);
                    info('Send Update to Customer - Updating status to: '.SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER.' - Send Update UUID: '.$sendUpdateLog->uuid);
                    $sendUpdateLog->refresh();
                } else {
                    info('Send Update to Customer - Send Update UUID: '.$sendUpdateLog->uuid.' - Job failed - Send Update UUID: '.$sendUpdateLog->uuid);
                }
            }
        }

        if ($sendUpdateLog->is_email_sent && $this->payload['action'] == SendUpdateLogStatusEnum::ACTION_SNBU) {
            info('Send Update to Customer - Calling sendUpdate function through sendUpdateToCustomer - Send Update UUID: '.$sendUpdateLog->uuid);
            app(SendUpdateLogController::class)->sendUpdate((new SendUpdateRequest)->merge($this->payload));
        }

        info('Send Update to Customer - Job success - Send Update UUID: '.$sendUpdateLog->uuid);
    }

    public function failed(Throwable $exception)
    {
        info('job: SendUpdateToCustomerJob - Send Update UUID: '.$this->sendUpdate->uuid.' Error: '.$exception->getMessage());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->sendUpdate->id))->dontRelease()];
    }
}
