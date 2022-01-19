<?php

namespace App\Services;

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
            $capiRequest = $client->post(
                $url,
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10000,
                ]
            );

            $getStatusCode = $capiRequest->getStatusCode();

            if($getStatusCode == 201) {


            } else {
                $errorMessage = "SIB Error:  ".$getStatusCode." ".$emailData['customerEmail']." ".get_class();
                Log::error($errorMessage);
            }
        }
        catch(Exception $ex) {
            $errorMessage = "SIB Failed Error: ".$ex->getCode()." ".$ex->getMessage()." ".get_class();
            Log::error($errorMessage);
        }

        return $getStatusCode;

        // SAVE RESPONSE to email_activity table
    }
}
