<?php

namespace App\Jobs;

use App\Services\EmailStatusService;
use App\Services\SendEmailCustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateSendPolicySubjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;
    public $backoff = 3;
    private $sendEmailCustomerService;
    private $messageId;
    private $emailData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($emailData, $messageId)
    {
        $this->emailData = $emailData;
        $this->messageId = $messageId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SendEmailCustomerService $sendEmailCustomerService, EmailStatusService $emailStatusService)
    {
        if ($this->messageId) {
            $emailSubject = $sendEmailCustomerService->getEmailSubjectFromSib($this->messageId);
            if ($emailSubject) {
                $emailStatusService->addEmailStatus($this->emailData, $this->messageId, $emailSubject);
            }
        }
    }
}
