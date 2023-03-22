<?php

namespace App\Jobs;

use App\Enums\quoteTypeCode;
use App\Services\SendEmailCustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IntroEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 300;
    private $quoteType = null;
    private $emailTemplateId = null;
    private $emailData = null;
    private $tag = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($quoteType, $emailTemplateId, $emailData, $tag)
    {
        $this->quoteType = $quoteType;
        $this->emailTemplateId = $emailTemplateId;
        $this->emailData = $emailData;
        $this->tag = $tag;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SendEmailCustomerService $sendEmailCustomerService)
    {
        if (! $this->quoteType || ! $this->emailTemplateId) {
            return false;
        }
        switch($this->quoteType) {
            case quoteTypeCode::Car:
                $sendEmailCustomerService->sendLMSIntroEmail($this->emailTemplateId, $this->emailData, 'send-lms-intro-email');
                break;
            default:
                break;
        }
    }
}
