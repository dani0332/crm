<?php

namespace App\Services;
use Illuminate\Support\Facades\Mail;


class MailService extends BaseService
{

	public static function sendEmail($templateName, $templateParams, $subject , $to, )
	{
		Mail::send(['html' => $templateName], $templateParams, function ($message) use ($subject, $to) {
            $message->to($to)->subject($subject);
            $message->from('alfred@insurancemarket.ae', 'Alfred');
        });
	}
}
