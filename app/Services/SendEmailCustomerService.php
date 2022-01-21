<?php

namespace App\Services;

use App\Models\EmailActivity;
use Config;
use Exception;
use Illuminate\Support\Facades\Log;

class SendEmailCustomerService extends BaseService
{
	public static function sendEmail($emailTemplateId, $emailData)
    {
        try {

            $apiKey = Config::get('constants.SENDINBLUE_KEY');
            $url = Config::get('constants.SIB_URL');

            $headers = [
                'Accept' => 'application/json',
                'api-key' => $apiKey,
                'Content-Type' => 'application/json'
            ];

            $body = json_encode([
                "to" => array([
                    "email" => $emailData['customerEmail'],
                    "name" => $emailData['customerName'],
                ]),
                "templateId" => $emailTemplateId,
                "params" => [
                    "customerName" => $emailData['customerName'],
                    "customerEmail" => $emailData['customerEmail'],
                    "signUpButtonUrl" => $emailData['signUpButtonUrl'],
                ],
            ]);

            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->post(
                $url,
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10000,
                ]
            );

            $getStatusCode = $clientRequest->getStatusCode();
            $getResponse = json_encode($clientRequest->getStatusCode()." ".$clientRequest->getBody()->getContents());

            if($getStatusCode == 201) {
                $isEmailSent = 1;
            }
            else {
                $errorMessage = "SIB Error:  ".$getStatusCode." ".$emailData['customerEmail']." ".get_class();
                Log::error($errorMessage);
                $isEmailSent = 0;
            }
        }
        catch(Exception $ex) {
            $errorMessage = "SIB Failed Error: ".$ex->getCode()." ".$ex->getMessage()." ".get_class();
            Log::error($errorMessage);
            $getStatusCode = $ex->getCode();
            $getResponse = json_encode($ex->getCode()." ".$ex->getMessage());
            $isEmailSent = 0;
        }

        $newEmailActivity = new EmailActivity;
        $newEmailActivity->api_response = $getResponse;
        $newEmailActivity->successful = $isEmailSent;
        $newEmailActivity->email = $emailData['customerEmail'];
        $newEmailActivity->save();

        return $getStatusCode;
    }
}
