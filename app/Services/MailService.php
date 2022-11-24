<?php

namespace App\Services;

use Config;
use Illuminate\Support\Facades\Mail;

class MailService extends BaseService
{
    public static function sendEmail($templateName, $templateParams, $subject, $to)
    {
        $fromEmail = Config::get('constants.MAIL_FROM_ADDRESS');
        $fromName = Config::get('constants.MAIL_FROM_NAME');
        Mail::send(['html' => $templateName], $templateParams, function ($message) use ($subject, $to, $fromName, $fromEmail) {
            $message->to($to)->subject($subject);
            $message->from($fromEmail, $fromName);
        });
    }
}
