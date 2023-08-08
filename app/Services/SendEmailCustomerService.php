<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EnvEnum;
use App\Jobs\UpdateSendPolicySubjectJob;
use App\Models\ApplicationStorage;
use Exception;
use Illuminate\Support\Facades\Log;

class SendEmailCustomerService extends BaseService
{
    protected $emailActivityService;
    protected $emailStatusService;
    protected $customerService;
    protected $apiKey = '';
    protected $url = '';
    protected $appEnv = '';

    public function __construct(
        EmailActivityService $emailActivityService,
        EmailStatusService $emailStatusService,
        CustomerService $customerService
    ) {
        $this->emailActivityService = $emailActivityService;
        $this->emailStatusService = $emailStatusService;
        $this->customerService = $customerService;
        $this->apiKey = config('constants.SENDINBLUE_KEY');
        $this->url = config('constants.SIB_URL');
        $this->appEnv = config('constants.APP_ENV');
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
                    'inviteCode' => isset($emailData->inviteCode) ? $emailData->inviteCode : null,
                    'buttonUrl' => isset($emailData->buttonUrl) ? $emailData->buttonUrl : null,
                    'cdbId' => isset($emailData->quoteCdbId) ? $emailData->quoteCdbId : null,
                    'advisorName' => isset($emailData->advisorName) ? $emailData->advisorName : null,
                    'advisorLandlineNo' => isset($emailData->advisorLandlineNo) ? $emailData->advisorLandlineNo : null,
                    'advisorMobileNo' => isset($emailData->advisorMobileNo) ? $emailData->advisorMobileNo : null,
                    'advisorEmailAddress' => isset($emailData->advisorEmailAddress) ? $emailData->advisorEmailAddress : null,
                    'notesForCustomer' => isset($emailData->notesForCustomer) ? nl2br(htmlentities(str_replace('<br />', '', $emailData->notesForCustomer))) : null,
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
            $quoteCdbId = isset($emailData->quoteCdbId) ? $emailData->quoteCdbId : null;
            $responseDetail = 'SIB Send Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' CustomerEmail: '.$emailData->customerEmail.' QuoteCdbId: '.$quoteCdbId.' Class: '.get_class();
            Log::error($responseDetail);
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
            $isEmailSent = 0;
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        // addEmailStatus is for quote modules only
        if (isset($messageId) && isset($emailData->quoteTypeId) && isset($emailData->quoteId)) {
            // UpdateSendPolicySubjectJob::dispatch($emailData, $messageId)->delay(now()->addSeconds(7));
        }

        return $responseCode;
    }

    public function sendOcbEmail($emailTemplateId, $emailData, $tag)
    {
        try {
            $tag = $this->appEnv == EnvEnum::PRODUCTION ? $tag : $this->appEnv.'-'.$tag;

            $headers = [
                'Accept' => 'application/json',
                'api-key' => $this->apiKey,
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

            $body = [
                'sender' => [
                    'email' => strstr($emailData->advisorEmailAddress, '@', true).'@renewals.insurancemarket.ae',
                    'name' => $emailData->advisorName,
                ],
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
                    'advisorName' => isset($emailData->advisorName) ? $emailData->advisorName : null,
                    'advisorLandlineNo' => isset($emailData->advisorLandlineNo) ? $emailData->advisorLandlineNo : null,
                    'advisorMobileNo' => isset($emailData->advisorMobileNo) ? $emailData->advisorMobileNo : null,
                    'advisorEmailAddress' => isset($emailData->advisorEmailAddress) ? $emailData->advisorEmailAddress : null,
                    'notesForCustomer' => isset($emailData->notesForCustomer) ? nl2br(htmlentities(str_replace('<br />', '', $emailData->notesForCustomer))) : null,
                    'providerSupportNumber' => isset($emailData->providerSupportNumber) ? $emailData->providerSupportNumber : null,
                    'previousPolicyExpiryDate' => isset($emailData->previousPolicyExpiryDate) ? date('l', strtotime($emailData->previousPolicyExpiryDate)).', '.date('d-M-Y', strtotime($emailData->previousPolicyExpiryDate)) : null,
                    'currentlyInsuredWith' => isset($emailData->currentlyInsuredWith) ? $emailData->currentlyInsuredWith : null,
                    'carMake' => isset($emailData->carMake) ? $emailData->carMake : null,
                    'carModel' => isset($emailData->carModel) ? $emailData->carModel : null,
                    'carManufactureYear' => isset($emailData->carManufactureYear) ? $emailData->carManufactureYear : null,
                    'previousPolicyNumber' => isset($emailData->previousPolicyNumber) ? $emailData->previousPolicyNumber : null,
                    'listQuotePlans' => isset($emailData->listQuotePlans) ? $emailData->listQuotePlans : null,
                    'multipleQuoteUrl' => isset($emailData->multipleQuoteUrl) ? $emailData->multipleQuoteUrl : null,
                    'quotePlansCount' => isset($emailData->quotePlansCount) ? $emailData->quotePlansCount : 0,
                ],
                'tags' => [
                    $tag,
                ],
                'attachment' => isset($attachments) ? $attachments : null,
            ];

            $ccAdvisor = [];
            if (isset($emailData->advisorEmailAddress) && isset($emailData->advisorName)) {
                $ccAdvisor = [[
                    'email' => $emailData->advisorEmailAddress,
                    'name' => $emailData->advisorName,
                ]];
                $body['replyTo'] = [
                    'email' => $emailData->advisorEmailAddress,
                    'name' => $emailData->advisorName,
                ];
            }

            $customer = $this->customerService->getCustomerByEmail($emailData->customerEmail);
            $ccAdditional = [];
            if ($customer) {
                $additionalContacts = $this->customerService->getAdditionalContactByKey($customer->id, 'email');
                foreach ($additionalContacts as $additionalContact) {
                    $ccAdditional[] = [
                        'email' => $additionalContact->value,
                        'name' => $emailData->customerName,
                    ];
                }
            }

            $body['cc'] = array_merge($ccAdditional, $ccAdvisor);

            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->post(
                $this->url,
                [
                    'headers' => $headers,
                    'body' => json_encode($body),
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
            $quoteCdbId = isset($emailData->quoteCdbId) ? $emailData->quoteCdbId : null;
            $responseDetail = 'SIB Send Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' CustomerEmail: '.$emailData->customerEmail.' QuoteCdbId: '.$quoteCdbId.' Class: '.get_class();
            info($responseDetail);
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
            $isEmailSent = 0;
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        // addEmailStatus is for quote modules only
        if (isset($messageId) && isset($emailData->quoteTypeId) && isset($emailData->quoteId)) {
            // UpdateSendPolicySubjectJob::dispatch($emailData, $messageId)->delay(now()->addSeconds(7));
        }

        return $responseCode;
    }

    public function getEmailSubjectFromSib($messageId)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request(
                'GET',
                $this->url.'s?messageId='.$messageId.'&sort=desc&limit=1&offset=0',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'api-key' => $this->apiKey,
                    ],
                ]
            );
            $content = json_decode($response->getBody()->getContents());
            if ($content && isset($content->count) && $content->count > 0 && isset($content->transactionalEmails[0])) {
                $emailSubject = $content->transactionalEmails[0]->subject;
            } else {
                $emailSubject = null;
            }
        } catch (Exception $ex) {
            $emailSubject = null;
            $responseDetail = 'SIB Get Email Subject: Code/Message: '.$ex->getCode().'/'.$ex->getMessage().' messageId: '.$messageId.' Class: '.get_class();
            Log::error($responseDetail);
        }

        return $emailSubject;
    }

    public function sendMyAlfredWelcomeEmail($emailData, $tag, $source = '')
    {
        try {
            $appEnv = config('constants.APP_ENV');
            //Todo: Remove SIB_MYALFRED_CUSTOMER_WE_TEMPLATE_ID from doppler
            if ($source == 'CORPORATE') {
                $emailTemplateId = (int) config('constants.MA_POSTMARK_CORPORATE_TEMPLATE');
            } else {
                $emailTemplateId = (int) config('constants.MA_POSTMARK_TEMPLATE');
            }

            info('sendMyAlfredWelcomeEmail data: '.json_encode($emailData).' , emailTemplateId:'.$emailTemplateId);
            $tag = $appEnv == EnvEnum::PRODUCTION ? $tag : $appEnv.'-'.$tag;

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Postmark-Server-Token' => config('constants.POSTMARK_TOKEN'),
                'Content-Type' => 'application/json',
            ];

            $body = json_encode([
                'From' => config('constants.MA_FROM_EMAIL'),
                'ReplyTo' => config('constants.MAIL_MYALFRED_SUPPORT_REPLY_TO'),
                'To' => $emailData->customerEmail,
                'Tag' => $tag,
                'TemplateId' => $emailTemplateId,
                'TemplateModel' => [
                    'params' => [
                        'firstName' => $emailData->customerFirstName,
                        'lastName' => $emailData->customerLastName,
                        'inviteCode' => isset($emailData->inviteCode) ? $emailData->inviteCode : null,
                        'email' => $emailData->customerEmail,
                    ],
                    'subject' => config('constants.MA_WELCOME_SUBJECT'),
                ],
                'MessageStream' => config('constants.MA_POSTMARK_STREAM'),
            ], JSON_UNESCAPED_SLASHES);

            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->post(
                config('constants.POSTMARK_URL'),
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10,
                ]
            );

            $response = json_decode(json_encode($clientRequest->getStatusCode().' '.$clientRequest->getBody()->getContents()), true);
            $responseCode = $clientRequest->getStatusCode();

            if ($responseCode == 200) {
                $isEmailSent = 1;
            }
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $quoteCdbId = isset($emailData->quoteCdbId) ? $emailData->quoteCdbId : null;
            $responseDetail = 'PostMark Send Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' CustomerEmail: '.$emailData->customerEmail.' QuoteCdbId: '.$quoteCdbId.' Class: '.get_class();
            Log::error($responseDetail);
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
            $isEmailSent = 0;
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        return $responseCode;
    }

    public function sendLMSIntroEmail($emailTemplateId, $emailData, $tag)
    {
        try {
            $tag = $this->appEnv == EnvEnum::PRODUCTION ? $tag : $this->appEnv.'-'.$tag;
            info('sendLMSIntroEmail ---- Tag : '.$tag);
            $headers = [
                'Accept' => 'application/json',
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ];

            $emailAttachments = isset($emailData->documentUrl) ? $emailData->documentUrl : null;
            info('sendLMSIntroEmail ---- emailAttachments : '.json_encode($emailAttachments));
            if ($emailAttachments) {
                $attachments = [];
                foreach ($emailAttachments as $emailAttachment) {
                    $attachments[] = [
                        'url' => $emailAttachment,
                        'name' => basename($emailAttachment),
                    ];
                }
            }

            $bcc = [[
                'email' => $emailData->advisorEmail,
                'name' => $emailData->advisorName,
            ]];
            $bccAdditional = [];
            $additionalBcc = ApplicationStorage::where('key_name', ApplicationStorageEnums::LMS_INTRO_EMAIL_BCC)->first()->value;
            foreach (explode(',', $additionalBcc) as $additionalContact) {
                $bccAdditional[] = [
                    'email' => $additionalContact,
                ];
            }
            info('sendLMSIntroEmail ---- additional : '.json_encode($additionalBcc));
            $advisorCustomEmail = strstr($emailData->advisorEmail, '@', true).'@notify.insurancemarket.ae';

            info('advisor custom email is : '.$advisorCustomEmail.' for lead : '.$emailData->carQuoteId);

            $body = json_encode([
                'sender' => ['name' => $emailData->advisorName, 'email' => $advisorCustomEmail],
                'to' => [[
                    'email' => $emailData->customerEmail,
                    'name' => $emailData->clientFullName,
                ]],
                'replyTo' => ['name' => $emailData->advisorName, 'email' => $emailData->advisorEmail],
                'bcc' => array_merge($bccAdditional, $bcc),
                'templateId' => $emailTemplateId,
                'params' => [
                    'clientFullName' => $emailData->clientFullName,
                    'advisorName' => isset($emailData->advisorName) ? $emailData->advisorName : null,
                    'landLine' => isset($emailData->landLine) ? $emailData->landLine : null,
                    'mobilePhone' => isset($emailData->mobilePhone) ? preg_replace('/\s+/', '', $emailData->mobilePhone) : null,
                    'carQuoteId' => isset($emailData->carQuoteId) ? $emailData->carQuoteId : null,
                    'quoteLink' => isset($emailData->quoteLink) ? $emailData->quoteLink : null,
                ],
                'tags' => [
                    $tag,
                ],
                'attachment' => isset($attachments) ? $attachments : null,
            ], JSON_UNESCAPED_SLASHES);

            info('sendLMSIntroEmail ---- body :  '.json_encode($body));
            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->post(
                $this->url,
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10,
                ]
            );
            info('sendLMSIntroEmail ---- Request Sent');
            $responseCode = $clientRequest->getStatusCode();
            info('sendLMSIntroEmail ---- Received Code : '.$responseCode);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $responseDetail = 'SIB Send sendLMSIntroEmail: Code/Message: '.$responseCode.'/'.$ex->getMessage();
            Log::error($responseDetail);
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
        }

        return $responseCode;
    }

    public function sendDttEmail($emailData, $tag)
    {
        try {
            $tag = $this->appEnv == EnvEnum::PRODUCTION ? $tag : $this->appEnv.'-'.$tag;

            $headers = [
                'Accept' => 'application/json',
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ];

            $body = [
                'subject' => $emailData->customerName."'s".' Car Insurance with Alfred '.$emailData->quoteCdbId,
                'sender' => [
                    'email' => 'no-reply@alert.insurancemarket.email',
                    'name' => 'insurance market',
                ],
                'params' => [
                    'customerName' => $emailData->customerName,
                    'quotePlanLink' => $emailData->buttonUrl,
                ],
                'to' => [[
                    'email' => $emailData->customerEmail,
                    'name' => $emailData->customerName,
                ]],
                'templateId' => $emailData->templateId,
            ];

            $body['replyTo'] = [
                'email' => 'b6eb50415ef5751212bee3b17240ee7c@inbound.postmarkapp.com',
                'name' => 'Post mark',
            ];

            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->post(
                $this->url,
                [
                    'headers' => $headers,
                    'body' => json_encode($body),
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
            $responseDetail = 'Dtt Send Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' CustomerEmail: '.$emailData->customerEmail.' QuoteCdbId: '.$emailData->code.' Class: '.get_class();
            info($responseDetail);
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
            $isEmailSent = 0;
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        return $responseCode;
    }
}
