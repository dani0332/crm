<?php

namespace App\Jobs;

use App\Enums\SendUpdateLogStatusEnum;
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
        info('job: SendUpdateToCustomerJob started');

        @[$templateId, $emailData, $tag, $quoteTypeId] = $sendUpdateLogServices->sendUpdateToCustomerEmail($this->sendUpdate, $this->payload['action']);

        if (! empty($templateId)) {
            info('Send Update to Customer Job Email Data '.json_encode($emailData));
            $response = $sendEmailCustomerService->sendUpdateToCustomerEmail($templateId, $emailData, $tag, $quoteTypeId);
            info('Send Update to Customer Job Response '.json_encode($response));

            if ($response == 201) {
                SendUpdateLog::find($this->sendUpdate->id)->update([
                    'status' => SendUpdateLogStatusEnum::UPDATE_SENT_TO_CUSTOMER,
                    // 'is_email_sent' => true,
                ]);

                return true;

                /*if ($this->payload['action'] == SendUpdateLogStatusEnum::ACTION_SNBU) {
                    $sendUpdateRequest = new SendUpdateRequest();
                    app(SendUpdateLogController::class)->sendUpdate($sendUpdateRequest->merge($this->payload));
                }*/
                info('Send Update to Customer Job success, send update id -> '.$this->sendUpdate->id);
            } else {
                info('Send Update to Customer Job failed, send update id -> '.$this->sendUpdate->id);
            }

            return false;
        }
    }

    public function failed(Throwable $exception)
    {
        info('SendUpdateToCustomerJob -: '.$this->sendUpdate->id.' Error: '.$exception->getMessage());
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->sendUpdate->id))->dontRelease()];
    }
}
