<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Config;

class FTCMailerService extends Mailable
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
        $email = $this
            ->subject($this->request['subject'])
            ->from($fromEmail, $fromName)
            ->view('email.'. $this->request['templateName'].'', collect($this->request['templateParams'])->toArray());

        $attachment =   collect($this->request['templateParams']['attachment'])->toArray();
        foreach($attachment as $filePath){
            $email->attach($filePath);
        }

        return $email;
    }
}
