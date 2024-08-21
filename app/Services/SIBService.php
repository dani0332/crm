<?php

namespace App\Services;

use App\Enums\EnvEnum;
use App\Models\EmailActivity;
use Exception;
use Illuminate\Support\Facades\Log;

class SIBService extends BaseService
{
    public static function contactCreateUpdate($listId, $firstName, $lastName, $email, $signupLink, $data = [])
    {
        $CDBID = isset($data['cdbid']) ? $data['cdbid'] : 'None';
        info('Sync Contact SIB - Ref-ID: '.$CDBID.' - Data: '.json_encode($data));
        $endPointUrl = config('constants.SIB_CONTACTS_API_ENDPOINT_URL');
        $apiKey = config('constants.SENDINBLUE_KEY');

        $customerData = json_encode([
            'email' => $email,
            'attributes' => [
                'FIRSTNAME' => $firstName,
                'LASTNAME' => $lastName,
                'WEBSITE' => $signupLink,
                'ADVISOREMAIL' => isset($data['advisorEmail']) ? $data['advisorEmail'] : null,
                'ADVISORMOBILE' => isset($data['advisorMobile']) ? $data['advisorMobile'] : null,
                'CUSTOMERNAME' => isset($data['customerName']) ? $data['customerName'] : null,
                'ADVISORNAME' => isset($data['advisorName']) ? $data['advisorName'] : null,
                'LEAD_STATUS' => isset($data['leadStatus']) ? $data['leadStatus'] : null,
                'CDBID' => isset($data['cdbid']) ? $data['cdbid'] : null,
                'QUOTEPLANLINK' => isset($data['link']) ? $data['link'] : null,
                'HEALTH_WEBHOOK_URL' => null,
                'ADVISORLANDLINE' => isset($data['advisorLandline']) ? $data['advisorLandline'] : null,
            ],
            'listIds' => [(int) $listId],
            'updateEnabled' => true,
        ]);
        $clientExtendSubscription = new \GuzzleHttp\Client;
        $apiResponse = null;
        try {
            $requestExtendSubscription = $clientExtendSubscription->post(
                $endPointUrl,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'api-key' => $apiKey,
                    ],
                    'body' => $customerData,
                    'timeout' => 10000,
                ]
            );

            $apiResponse = $requestExtendSubscription->getStatusCode();
        } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
            Log::error('Sync Contact SIB - Error: '.$exception->getMessage());
        }

        return $apiResponse;
    }

    public static function sendEmailUsingSIB($emailTemplateId, $emailData, $tag, $emailTo, $renewalEmailCcRecipients)
    {
        info('sendEmailUsingSIB -- start');
        info('sendEmailUsingSIB -- templateId :'.$emailTemplateId);
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

            if (str_contains($emailTo, ',')) {
                $emails = [];
                foreach (explode(',', $emailTo) as $email) {
                    array_push($emails, ['email' => $email]);
                }
                $to = $emails;
            } else {
                $to = [['email' => $emailTo]];
            }
            info('sendEmailUsingSIB -- to recipients are : '.json_encode($to));

            $cc = null;

            if (! empty($renewalEmailCcRecipients)) {
                if (str_contains($renewalEmailCcRecipients, ',')) {
                    $cc = [];
                    foreach (explode(',', $renewalEmailCcRecipients) as $ccEmail) {
                        $cc[] = ['email' => $ccEmail];
                    }
                } else {
                    $cc[] = ['email' => $renewalEmailCcRecipients];
                }
            }

            info('sendEmailUsingSIB -- CC recipients are : '.json_encode($cc));

            $body = [
                'to' => $to,
                'templateId' => $emailTemplateId,
                'params' => $emailData,
                'tags' => [
                    $tag,
                ],
                'attachment' => isset($attachments) ? $attachments : null,
            ];

            if ($cc != null) {
                $body['cc'] = $cc;
            }

            $body = json_encode($body, JSON_UNESCAPED_SLASHES);

            $client = new \GuzzleHttp\Client;
            $clientRequest = $client->post(
                $url,
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10000,
                ]
            );
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
        if (str_contains($emailTo, ',')) {
            $emails = explode(',', $emailTo);
            foreach ($emails as $email) {
                SIBService::addEmailActivity($response, $isEmailSent, $email);
            }
        } else {
            SIBService::addEmailActivity($response, $isEmailSent, $emailTo);
        }

        return $responseCode;
    }

    public static function addEmailActivity($response, $isEmailSent, $customerEmail)
    {
        info('addEmailActivity -- adding email activity for '.$customerEmail);
        $newEmailActivity = new EmailActivity;
        $newEmailActivity->api_response = $response;
        $newEmailActivity->successful = $isEmailSent;
        $newEmailActivity->email = $customerEmail;
        $newEmailActivity->save();
        info('addEmailActivity -- done adding email activity for '.$customerEmail);

        return $newEmailActivity->id;
    }

    public static function createWorkflowEvent($eventName, $quote, $eventProperties = [], $eventData = [])
    {
        $appEnv = config('constants.APP_ENV');
        $eventQualifiedName = ($appEnv == EnvEnum::PRODUCTION ? '' : "{$appEnv}_").$eventName;
        $endPointUrl = config('constants.SIB_AUTOMATE_URL').'/trackEvent';
        $apiKey = config('constants.SIB_WORKFLOW_CLIENT_KEY');
        if (empty($eventProperties)) {
            $eventProperties = [
                'default_key' => 'default_value',
            ];
        }
        if (empty($eventData)) {
            $eventData = [
                'default_key' => 'default_value',
            ];
        }
        $eventData = json_encode([
            'event' => $eventQualifiedName,
            'email' => $quote ? $quote->email : null,
            'properties' => $eventProperties,
            'eventdata' => [
                'data' => $eventData,
            ],
        ]);

        $client = new \GuzzleHttp\Client;
        $apiResponse = null;
        try {
            $request = $client->post(
                $endPointUrl,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'ma-key' => $apiKey,
                    ],
                    'body' => $eventData,
                    'timeout' => 10000,
                ]
            );

            $apiResponse = $request->getStatusCode();
        } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
            Log::error('Create Event SIB - Error: '.$exception->getMessage());
        }

        return $apiResponse;
    }
}
