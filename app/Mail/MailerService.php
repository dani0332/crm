<?php

namespace App\Mail;

use Config;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailerService extends Mailable
{
    use Queueable, SerializesModels;

    protected $request;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $fromEmail = Config::get('constants.MAIL_FROM_ADDRESS');
        $fromName = Config::get('constants.MAIL_FROM_NAME');

        return $this
            ->subject($this->request->subject)
            ->from($fromEmail, $fromName)
            ->view($this->request->templateName.'', collect($this->request->templateParams)->toArray());
    }
}
