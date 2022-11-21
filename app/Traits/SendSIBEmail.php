<?php

namespace App\Traits;

use App\Enums\EnvEnum;
use App\Models\EmailActivity;
use Exception;
use Illuminate\Support\Facades\Log;

trait SendSIBEmail
{
    public $to;
    public function sendEmailUsingSIB($emailTemplateId, $emailData, $tag, $emailTo)
    {
        info('sendEmailUsingSIB -- start');
        info('sendEmailUsingSIB data : '.json_encode($emailData).' , templateId :'.$emailTemplateId.' , email To : '.$emailTo);
        try {
            $apiKey = config('constants.SENDINBLUE_KEY');
            $url = config('constants.SIB_URL');
            $appEnv = config('constants.APP_ENV');

            $tag = $appEnv == EnvEnum::PRODUCTION ? $tag : $appEnv.'-'.$tag;

            $headers = [
                'Accept' => 'application/json',
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ];

            $emailAttachments = isset($emailData->documentUrl) ? $emailData->documentUrl : null;

            if ($emailAttachments) {
                $attachments = [];
                foreach ($emailAttachments as $emailAttachment) {
                    $attachments[] = [
                        'url' => $emailAttachment,
                        'name' => basename($emailAttachment),
                    ];
                }
            }

            if(str_contains($emailTo, ','))
            {
                $emails = [];
                foreach (explode(',',$emailTo) as $email) {
                    array_push($emails, ['email' => $email]);
                }
                $to = $emails;
            }else
            {
                $to = $emailTo;
            }
            info('Lead send email json : '.json_encode($to));
            $body = json_encode([
                'to' => [$to],
                'templateId' => $emailTemplateId,
                'params' => $emailData,
                'tags' => [
                    $tag,
                ],
                'attachment' => isset($attachments) ? $attachments : null,
            ], JSON_UNESCAPED_SLASHES);

            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->post(
                $url,
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10000,
                ]
            );
            info('email is sent');
            $messageId = json_decode($clientRequest->getBody()->getContents())->messageId;
            $response = json_decode(json_encode($clientRequest->getStatusCode().' '.$clientRequest->getBody()->getContents()), true);
            $responseCode = $clientRequest->getStatusCode();

            if ($responseCode == 201) {
                $isEmailSent = 1;
            }
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $responseDetail = 'SIB Send Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' email data : '.json_encode($emailData);
            Log::error($responseDetail);
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
            $isEmailSent = 0;
        }
        info('sendEmailUsingSIB - email sent');
        if (str_contains($emailTo, ',')) {
            $emails = explode(',', $emailTo);
            info('sendEmailUsingSIB - emails contain comma');
            foreach ($emails as $email) {
                info('sendEmailUsingSIB - current email is : '.$email);
                $this->addEmailActivity($response, $isEmailSent, $email);
            }
        } else {
            info('sendEmailUsingSIB - email is : '.$emailTo);
            $this->addEmailActivity($response, $isEmailSent, $emailTo);
        }

        return $responseCode;
    }

    public function addEmailActivity($response, $isEmailSent, $customerEmail)
    {
        $newEmailActivity = new EmailActivity();
        $newEmailActivity->api_response = $response;
        $newEmailActivity->successful = $isEmailSent;
        $newEmailActivity->email = $customerEmail;
        $newEmailActivity->save();

        return $newEmailActivity->id;
    }
}
