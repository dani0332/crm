<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use SendinBlue;

class SendInBlueMail extends Mailable {

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
    public function build() {
        return $this
            ->view([])
            ->subject('Welcome to myAlfred by InsuranceMarket.ae')
            ->to($this->request->to)
            ->sendinblue(
                [
                    'template_id' => $this->request->templateId,
                ]
            );
    }
}