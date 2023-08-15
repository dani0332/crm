<?php

namespace App\Mail;

use App\Enums\ApplicationStorageEnums;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HealthAssignmentIssueEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $quoteCode;
    public $priceStartingFrom;

    /**
     * Create a new message instance .
     *
     * @return void
     */
    public function __construct($quoteCode, $priceStartingFrom)
    {
        $this->quoteCode = $quoteCode;
        $this->priceStartingFrom = $priceStartingFrom;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(env('APP_ENV').' - Health Sub Team assignment Failed')
            ->to(explode(',', ApplicationStorageEnums::JOB_FAILED_EMAIL_RECIPIENTS))
            ->view('email.health_assignment_issue', [
                'quoteCode' => $this->quoteCode,
                'priceStartingFrom' => $this->priceStartingFrom,
            ]);
    }
}
