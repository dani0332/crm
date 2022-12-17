<?php

namespace App\Mail;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeadAllocationFailedNotification extends Mailable
{
    use Queueable, SerializesModels;

    private $exception;

    public function __construct(Exception $exception)
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
        return $this->subject(env('APP_ENV'). ' - Lead Allocation Job Failed')
                ->to(['ahsan.ashfaq@insurancemarket.ae', 'hussain.fakhruddin@insurancemarket.ae'])
                ->view('email.lead-allocation-job-failed',[
            'exception' => $this->exception,
        ]);
    }
}
