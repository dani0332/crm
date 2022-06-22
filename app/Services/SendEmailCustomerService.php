<?php

namespace App\Services;

use App\Enums\EnvEnum;
use Config;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\EmailActivityService;
use App\Services\EmailStatusService;

class SendEmailCustomerService extends BaseService
{
    protected $emailActivityService;
    protected $emailStatusService;

    public function __construct(
        EmailActivityService $emailActivityService,
        EmailStatusService $emailStatusService
    ) {
        $this->emailActivityService = $emailActivityService;
        $this->emailStatusService = $emailStatusService;
    }

	public function sendEmail($emailTemplateId, $emailData, $tag)
    {
        try {
            $apiKey = Config::get('constants.SENDINBLUE_KEY');
            $url = Config::get('constants.SIB_URL');
            $appEnv = Config::get('constants.APP_ENV');

            $tag = $appEnv == EnvEnum::PRODUCTION ? $tag : $appEnv.'-'.$tag;

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
                    "signUpButtonUrl" => isset($emailData['signUpButtonUrl']) ? $emailData['signUpButtonUrl'] : NULL,
                    "buttonUrl" => isset($emailData['buttonUrl']) ? $emailData['buttonUrl'] : NULL,
					"cdbId" => isset($emailData['quoteCdbId']) ? $emailData['quoteCdbId'] : NULL,
                    "notesForCustomer" => isset($emailData['notesForCustomer']) ? nl2br(htmlentities(str_replace("<br />", "", $emailData['notesForCustomer']))) : NULL,
                ],
                "tags" => [
                    $tag,
                ],
            ]);

            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->post(
                $url, [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10000,
                ]
            );

			$messageId = json_decode($clientRequest->getBody()->getContents())->messageId;
			$getResponse = json_decode(json_encode($clientRequest->getStatusCode()." ".$clientRequest->getBody()->getContents()),true);
            $getStatusCode = $clientRequest->getStatusCode();

            if($getStatusCode == 201) {
                $isEmailSent = 1;
            }
        }
        catch(Exception $ex) {
            $errorMessage = "SIB:  getCode/getMessage: ".$ex->getCode()."/".$ex->getMessage()." customerEmail: ".$emailData['customerEmail']." quoteCdbId: ".$emailData['quoteCdbId']." get_class: ".get_class();
            Log::channel('daily')->error($errorMessage);
            $getStatusCode = $ex->getCode();
            $getResponse = json_encode($ex->getCode()." ".$ex->getMessage());
            $isEmailSent = 0;
        }

        $this->emailActivityService->addEmailActivity($getResponse, $isEmailSent, $emailData['customerEmail']);

        // addEmailStatus is for quote modules only
        if(isset($messageId) && isset($emailData['quoteTypeId']) && isset($emailData['quoteId'])) {
            $this->emailStatusService->addEmailStatus($emailData, $messageId);
        }

        return $getStatusCode;
    }
}
