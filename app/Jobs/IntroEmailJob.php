<?php

namespace App\Jobs;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
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
    private $previousAdvisorId = null;
    private $isReassignment = false;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($quoteType, $emailTemplateId, $emailData, $tag, $previousAdvisorId, $isReassignment)
    {
        $this->quoteType = $quoteType;
        $this->emailTemplateId = $emailTemplateId;
        $this->emailData = $emailData;
        $this->tag = $tag;
        $this->previousAdvisorId = $previousAdvisorId;
        $this->isReassignment = $isReassignment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SendEmailCustomerService $sendEmailCustomerService)
    {
        if (! $this->quoteType || ! $this->emailTemplateId) {
            info('Parameter data not found . QuoteType : '.$this->quoteType.' , EmailTemplateId : '.$this->emailTemplateId);

            return false;
        }
        switch ($this->quoteType) {
            case quoteTypeCode::Car:
                info('Inside car check for sending email');
                $sendEmailCustomerService->sendLMSIntroEmail($this->emailTemplateId, $this->emailData, 'lms-intro-email', QuoteTypes::CAR);
                break;
            case quoteTypeCode::Health:
                $sendEmailCustomerService->sendRMIntroEmail($this->emailData, $this->previousAdvisorId, $this->isReassignment);
                break;
            default:
                break;
        }
    }
}
