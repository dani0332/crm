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

            $body = json_encode([
                'to' => [[
                    'email' => $emailData->customerEmail,
                    'name' => $emailData->customerName,
                ]],
                'templateId' => $emailTemplateId,
                'params' => [
                    'customerName' => $emailData->customerName,
                    'customerEmail' => $emailData->customerEmail,
                    'signUpButtonUrl' => isset($emailData->signUpButtonUrl) ? $emailData->signUpButtonUrl : null,
                    'buttonUrl' => isset($emailData->buttonUrl) ? $emailData->buttonUrl : null,
                    'cdbId' => isset($emailData->quoteCdbId) ? $emailData->quoteCdbId : null,
                    'notesForCustomer' => isset($emailData->notesForCustomer) ? nl2br(htmlentities(str_replace('<br />', '', $emailData->notesForCustomer))) : null,
                    'advisorName' => isset($emailData->advisorName) ? $emailData->advisorName : null,
                    'advisorLandlineNo' => isset($emailData->advisorLandlineNo) ? $emailData->advisorLandlineNo : null,
                    'advisorMobileNo' => isset($emailData->advisorMobileNo) ? $emailData->advisorMobileNo : null,
                    'providerSupportNumber' => isset($emailData->providerSupportNumber) ? $emailData->providerSupportNumber : null,
                ],
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

            $messageId = json_decode($clientRequest->getBody()->getContents())->messageId;
            $response = json_decode(json_encode($clientRequest->getStatusCode().' '.$clientRequest->getBody()->getContents()), true);
            $responseCode = $clientRequest->getStatusCode();

            if ($responseCode == 201) {
                $isEmailSent = 1;
            }
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $responseDetail = 'SIB Send Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' CustomerEmail: '.$emailData->customerEmail.' QuoteCdbId: '.$emailData->quoteCdbId.' Class: '.get_class();
            Log::error($responseDetail);
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
            $isEmailSent = 0;
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        // addEmailStatus is for quote modules only
        if (isset($messageId) && isset($emailData->quoteTypeId) && isset($emailData->quoteId)) {
            $emailSubject = $this->getEmailSubjectFromSib($emailTemplateId, $emailData->quoteCdbId);
            $this->emailStatusService->addEmailStatus($emailData, $messageId, $emailSubject);
        }

        return $responseCode;
    }

    public function getEmailSubjectFromSib($templateId, $quoteCdbId)
    {
        $apiKey = config('constants.SENDINBLUE_KEY');
        $url = config('constants.SIB_URL');

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request(
                'GET',
                $url.'s?templateId='.$templateId.'&sort=desc&limit=1&offset=0',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'api-key' => $apiKey,
                    ],
                ]
            );
            $content = json_decode($response->getBody()->getContents());
            $emailSubject = $content->transactionalEmails[0]->subject;
        } catch (Exception $ex) {
            $emailSubject = null;
            $responseDetail = 'SIB Get Email Subject: Code/Message: '.$ex->getCode().'/'.$ex->getMessage().' templateId: '.$templateId.' QuoteCdbId: '.$quoteCdbId.' Class: '.get_class();
            Log::error($responseDetail);
        }

        return $emailSubject;
    }
}
