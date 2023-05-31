<?php

namespace App\Mail;

use App\Enums\ApplicationStorageEnums;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class TierAssignmentFailedNotification extends Mailable
{
    use Queueable, SerializesModels;

    private $exception;

    public function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(env('APP_ENV').' - Tier Assignment Job Failed')
            ->to(explode(',', ApplicationStorageEnums::JOB_FAILED_EMAIL_RECIPIENTS))
            ->view('email.lead-allocation-job-failed', [
                'exception' => $this->exception,
            ]);
    }
}
