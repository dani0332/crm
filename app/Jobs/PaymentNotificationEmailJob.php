<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Services\SendEmailCustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $lead = null;
    private $user = null;
    public $tries = 3;
    public $timeout = 30;
    public $backoff = 10;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($lead, $user)
    {
        $this->lead = $lead;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SendEmailCustomerService $sendEmailCustomerService)
    {
        $emailEnable = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::ENABLE_PAYMENT_NOTIFICATION_EMAIL)->first();
        if ($emailEnable && $emailEnable->value == 0) {
            info('ENABLE_PAYMENT_NOTIFICATION_EMAIL is Disable');

            return false;
        }
        if (! $this->lead) {
            info('PaymentNotificationEmailJob: Email data is not found');

            return false;
        }
        if (! $this->user) {
            info('PaymentNotificationEmailJob: User data is not found');

            return false;
        }
        $sendEmailCustomerService->sendPaymentNotificationEmail($this->lead, $this->user);
    }
}
