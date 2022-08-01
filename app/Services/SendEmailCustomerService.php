<?php

namespace App\Services;

use App\Enums\EnvEnum;
use Exception;
use Illuminate\Support\Facades\Log;

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
            $apiKey = config('constants.SENDINBLUE_KEY');
            $url = config('constants.SIB_URL');
            $appEnv = config('constants.APP_ENV');

            $tag = $appEnv == EnvEnum::PRODUCTION ? $tag : $appEnv.'-'.$tag;

            $headers = [
                'Accept' => 'application/json',
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ];

            $body = json_encode([
                'to' => [[
                    'email' => $emailData['customerEmail'],
                    'name' => $emailData['customerName'],
                ]],
                'templateId' => $emailTemplateId,
                'params' => [
                    'customerName' => $emailData['customerName'],
                    'customerEmail' => $emailData['customerEmail'],
                    'signUpButtonUrl' => isset($emailData['signUpButtonUrl']) ? $emailData['signUpButtonUrl'] : null,
                    'buttonUrl' => isset($emailData['buttonUrl']) ? $emailData['buttonUrl'] : null,
                    'cdbId' => isset($emailData['quoteCdbId']) ? $emailData['quoteCdbId'] : null,
                    'notesForCustomer' => isset($emailData['notesForCustomer']) ? nl2br(htmlentities(str_replace('<br />', '', $emailData['notesForCustomer']))) : null,
                    'advisorName' => isset($emailData['advisorName']) ? $emailData['advisorName'] : null,
                    'advisorLandlineNo' => isset($emailData['advisorLandlineNo']) ? $emailData['advisorLandlineNo'] : null,
                    'advisorMobileNo' => isset($emailData['advisorMobileNo']) ? $emailData['advisorMobileNo'] : null,
                ],
                'tags' => [
                    $tag,
                ],
                'attachment' => [[
                    'url' => 'https://insurancemarket.ae/wp-content/uploads/2022/07/myAlfred-Offers-July-2022.pdf',
                    'name' => 'myAlfred-Offers-July-2022.pdf',
                ]],
            ], JSON_UNESCAPED_SLASHES);
            //dd($body);

            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->post(
                $url, [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10000,
                ]
            );

            $messageId = json_decode($clientRequest->getBody()->getContents())->messageId;
            $getResponse = json_decode(json_encode($clientRequest->getStatusCode().' '.$clientRequest->getBody()->getContents()), true);
            $getStatusCode = $clientRequest->getStatusCode();

            if ($getStatusCode == 201) {
                $isEmailSent = 1;
            }
        } catch (Exception $ex) {
            $errorMessage = 'SIB:  getCode/getMessage: '.$ex->getCode().'/'.$ex->getMessage().' customerEmail: '.$emailData['customerEmail'].' quoteCdbId: '.$emailData['quoteCdbId'].' get_class: '.get_class();
            Log::channel('daily')->error($errorMessage);
            $getStatusCode = $ex->getCode();
            $getResponse = json_encode($ex->getCode().' '.$ex->getMessage());
            $isEmailSent = 0;
        }

        $this->emailActivityService->addEmailActivity($getResponse, $isEmailSent, $emailData['customerEmail']);

        // addEmailStatus is for quote modules only
        if (isset($messageId) && isset($emailData['quoteTypeId']) && isset($emailData['quoteId'])) {
            $this->emailStatusService->addEmailStatus($emailData, $messageId);
        }

        return $getStatusCode;
    }
}
