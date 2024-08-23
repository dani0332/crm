<?php

namespace App\Jobs;

use App\Services\SendEmailCustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user = null;
    private $totalLead = null;
    private $userData = null;
    public $tries = 3;
    public $timeout = 30;
    public $backoff = 10;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $userData, $totalLead)
    {
        $this->user = $user;
        $this->userData = $userData;
        $this->totalLead = $totalLead;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SendEmailCustomerService $sendEmailCustomerService)
    {
        if (! $this->user) {
            info('PaymentNotificationEmailJob: Email data is not found');

            return false;
        }
        if (! $this->userData) {
            info('PaymentNotificationEmailJob: User data is not found');

            return false;
        }
        $sendEmailCustomerService->sendPaymentNotificationEmail($this->user, $this->userData, $this->totalLead);
    }
}
