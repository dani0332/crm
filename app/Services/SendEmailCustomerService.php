<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DefaultAdvisorEnum;
use App\Enums\EnvEnum;
use App\Enums\QuoteTypeId;
use App\Facades\Capi;
use App\Jobs\UpdateSendPolicySubjectJob;
use App\Models\ApplicationStorage;
use App\Models\Customer;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendEmailCustomerService extends BaseService
{
    protected $emailActivityService;
    protected $emailStatusService;
    protected $customerService;
    protected $apiKey = '';
    protected $url = '';
    protected $appEnv = '';
    protected $appUrl = '';
    private $accept = 'application/json';

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
        $this->appUrl = config('constants.APP_URL');
    }

    public function sendMail(
        array $body,
        ?array $headers = null,
    ) {
        if (! $headers) {
            $headers = [
                'Accept' => $this->accept,
                'api-key' => $this->apiKey,
                'Content-Type' => $this->accept,
            ];
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $fnName = '';
        if (isset($backtrace[1]['function'])) {
            $fnName = $backtrace[1]['function'];
        }

        try {
            $response = Http::withHeaders($headers)
                ->beforeSending(function () use ($fnName) {
                    info("{$fnName} ---- Mail Request is Sending");
                })
                ->timeout(config('constants.LMS_EMAILS_TIMEOUT'))
                ->retry(3, 90000)
                ->post($this->url, $body);

            $result = [
                'headers' => $headers,
                'ok' => $response->ok(),
                'code' => $response->status(),
                'object' => $response->object(),
                'respBody' => $response->body(),
                'response' => "{$response->status()} {$response->body()}",
            ];

            if ($result['code'] == 201) {
                $result['sent'] = 1;
                info("{$fnName} ---- Mail Sent Successfully");
            }

            return $result;
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $responseDetail = "{$fnName}: Code/Message: {$responseCode}/{$ex->getMessage()}";
            $response = json_encode($ex->getCode().' '.$ex->getMessage());

            Log::error($responseDetail);

            return [
                'headers' => $headers,
                'ok' => false,
                'code' => $responseCode,
                'object' => (object) [],
                'respBody' => $response,
                'sent' => 0,
                'response' => $response,
            ];
        }
    }

    public function sendEmail($emailTemplateId, $emailData, $tag)
    {
        try {
            $appEnv = config('constants.APP_ENV');

            $tag = $appEnv == EnvEnum::PRODUCTION ? $tag : $appEnv.'-'.$tag;

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
            ];

            ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);
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

            if (! empty($emailData->pdfAttachment->pdf) && ! empty($emailData->pdfAttachment->name)) {
                $attachments[] = [
                    'content' => chunk_split(base64_encode($emailData->pdfAttachment->pdf->stream())),
                    'name' => $emailData->pdfAttachment->name,
                ];
            }
            if ($emailData->advisorEmailAddress == null || $emailData->advisorName == null) {
                $emailData->advisorEmailAddress = DefaultAdvisorEnum::ADVISOREMAIL;
                $emailData->advisorName = DefaultAdvisorEnum::ADVISORNAME;
                $emailData->advisorMobileNo = DefaultAdvisorEnum::ADVISORMOBILENO;
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
                    if (! empty($additionalContact->value)) {
                        $ccAdditional[] = [
                            'email' => $additionalContact->value,
                            'name' => $emailData->customerName,
                        ];
                    }
                }
            }

            $body['cc'] = array_merge($ccAdditional, $ccAdvisor);

            ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $quoteCdbId = isset($emailData->quoteCdbId) ? $emailData->quoteCdbId : null;
            $responseDetail = 'SIB Send Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' CustomerEmail: '.$emailData->customerEmail.' QuoteCdbId: '.$quoteCdbId.' Class: '.get_class();
            info($responseDetail);
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
            $isEmailSent = 0;
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        return $responseCode;
    }

    public function sendRenewalsOcbEmail($emailTemplateId, $emailData, $tag)
    {
        try {
            info('fn: sendRenewalsOcbEmail, email sending started. emailTemplateId: '.$emailTemplateId.', tag: '.$tag);

            $tag = $this->appEnv == EnvEnum::PRODUCTION ? $tag : $this->appEnv.'-'.$tag;

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

            if (! empty($emailData->pdfAttachment->pdf) && ! empty($emailData->pdfAttachment->name)) {
                $attachments[] = [
                    'content' => chunk_split(base64_encode($emailData->pdfAttachment->pdf->stream())),
                    'name' => $emailData->pdfAttachment->name,
                ];
            }

            $body = [
                'sender' => [
                    'email' => strstr($emailData->advisorEmail, '@', true).'@renewals.insurancemarket.ae',
                    'name' => $emailData->advisorName,
                ],
                'to' => [[
                    'email' => $emailData->customerEmail,
                    'name' => $emailData->customerName,
                ]],
                'templateId' => $emailTemplateId,
                'params' => $emailData,
                'tags' => [
                    $tag,
                ],
                'attachment' => isset($attachments) ? $attachments : null,
            ];

            $ccAdvisor = [];
            if (isset($emailData->advisorEmail) && isset($emailData->advisorName)) {
                $ccAdvisor = [[
                    'email' => $emailData->advisorEmail,
                    'name' => $emailData->advisorName,
                ]];
                $body['replyTo'] = [
                    'email' => $emailData->advisorEmail,
                    'name' => $emailData->advisorName,
                ];
            }

            $customer = $this->customerService->getCustomerByEmail($emailData->customerEmail);
            $ccAdditional = [];
            if ($customer) {
                $additionalContacts = $this->customerService->getAdditionalContactByKey($customer->id, 'email');
                foreach ($additionalContacts as $additionalContact) {
                    if (! empty($additionalContact->value)) {
                        $ccAdditional[] = [
                            'email' => $additionalContact->value,
                            'name' => $emailData->customerName,
                        ];
                    }
                }
            }

            $body['cc'] = array_merge($ccAdditional, $ccAdvisor);

            ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $quoteCdbId = isset($emailData->carQuoteId) ? $emailData->carQuoteId : null;
            $responseDetail = 'SIB Send Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' CustomerEmail: '.$emailData->customerEmail.' QuoteCdbId: '.$quoteCdbId.' Class: '.get_class();
            info($responseDetail);
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
            $isEmailSent = 0;
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

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
                        'Accept' => $this->accept,
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
        $isEmailSent = 0;
        try {
            $appEnv = config('constants.APP_ENV');
            //Todo: Remove SIB_MYALFRED_CUSTOMER_WE_TEMPLATE_ID from doppler
            if ($source == 'CORPORATE') {
                $emailTemplateId = (int) config('constants.SIB_CORPORATE_TEMPLATE');
            } else {
                $emailTemplateId = (int) config('constants.SIB_MYALFRED_CUSTOMER_WE_TEMPLATE_ID');
            }

            $wfsBanner = null;
            $wfsBannerRedirectUrl = null;

            $campaign = getMyAlfredCampaign(getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN));
            if ($campaign) {
                $emailTemplateId = (int) getAppStorageValueByKey(ApplicationStorageEnums::INVITATION_EMAIL_TEMPLATE_FOR_CAMPAIGN);
                if (property_exists($campaign, 'banners') && property_exists($campaign->banners, 'buyPolicy')) {
                    $wfsBanner = $campaign->banners->buyPolicy;
                }
                if (property_exists($campaign, 'landingPage')) {
                    $wfsBannerRedirectUrl = $campaign->landingPage;
                }
            }

            info('sendMyAlfredWelcomeEmail  , emailTemplateId: '.$emailTemplateId);
            $tag = $appEnv == EnvEnum::PRODUCTION ? $tag : $appEnv.'-'.$tag;

            $body = [
                'to' => [[
                    'email' => $emailData->customerEmail,
                    'name' => $emailData->customerFirstName.' '.$emailData->customerLastName,
                ]],
                'templateId' => $emailTemplateId,
                'params' => [
                    'customerName' => $emailData->customerFirstName.' '.$emailData->customerLastName,
                    'customerEmail' => $emailData->customerEmail,
                    'inviteCode' => isset($emailData->inviteCode) ? $emailData->inviteCode : null,
                    'email' => $emailData->customerEmail,
                    'wfsBanner' => $wfsBanner,
                    'wfsBannerRedirectUrl' => $wfsBannerRedirectUrl,
                ],
                'tags' => [
                    $tag,
                ],
            ];

            ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $responseDetail = 'Brevo Send Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' CustomerEmail: '.$emailData->customerEmail.' Class: '.get_class();
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
            info('sendLMSIntroEmail ---- Tag : '.$tag.' for ID : '.$emailData->carQuoteId);
            $subjectEnvTag = $this->appEnv == EnvEnum::PRODUCTION ? '' : $this->appEnv.' - ';
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
            if (property_exists($emailData, 'pdfAttachment') && ! empty($emailData->pdfAttachment->pdf) && ! empty($emailData->pdfAttachment->name)) {
                $attachments[] = [
                    'content' => chunk_split(base64_encode($emailData->pdfAttachment->pdf->stream())),
                    'name' => $emailData->pdfAttachment->name,
                ];
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

            $advisorCustomEmail = strstr($emailData->advisorEmail, '@', true).'@notify.insurancemarket.ae';
            $emailData->env = $subjectEnvTag;

            $body = [
                'to' => [[
                    'email' => $emailData->customerEmail,
                    'name' => $emailData->clientFullName,
                ]],
                'replyTo' => ['name' => $emailData->advisorName, 'email' => $emailData->advisorEmail],
                'bcc' => array_merge($bccAdditional, $bcc),
                'templateId' => $emailTemplateId,
                'params' => $emailData,
                'tags' => [
                    $tag,
                ],
                'attachment' => isset($attachments) ? $attachments : null,
            ];

            // Conditionally add 'sender' key if advisorName and $advisorCustomEmail are not null
            if ($emailData->advisorName !== null && $advisorCustomEmail !== null) {
                $body['sender'] = ['name' => $emailData->advisorName, 'email' => $advisorCustomEmail];
            }

            ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $responseDetail = 'SIB Send sendLMSIntroEmail: Code/Message: '.$responseCode.'/'.$ex->getMessage().' '.$emailData->carQuoteId;
            Log::error($responseDetail);
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        return $responseCode;
    }

    public function sendDttEmail($emailData)
    {
        $tag = $this->appEnv == EnvEnum::PRODUCTION ? $emailData->tag : $this->appEnv.'-'.$emailData->tag;
        $body = [
            'subject' => $this->appEnv == EnvEnum::PRODUCTION ? $emailData->subject : $this->appEnv.' - '.$emailData->subject,
            'sender' => [
                'email' => 'no-reply@alert.insurancemarket.email',
                'name' => 'InsuranceMarket.ae',
            ],
            'params' => $emailData,
            'tags' => [
                $tag,
            ],
            'to' => [[
                'email' => $emailData->customerEmail,
                'name' => $emailData->customerName,
            ]],
            'templateId' => $emailData->templateId,
        ];

        $replyToEmail = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_REPLY_TO);
        $body['replyTo'] = [
            'email' => $replyToEmail,
            'name' => 'InsuranceMarket.ae',
        ];

        ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        return $responseCode;
    }

    public function sendRMIntroEmail($quoteUuid, $previousAdvisorId, $isReassignment)
    {
        $dataArr = [
            'quoteUID' => $quoteUuid,
            'resend' => false,
        ];
        if ($isReassignment) {
            $dataArr['isReassigned'] = true;
            $dataArr['previousAdvisorId'] = $previousAdvisorId;
        }
        info('Params for intro email are : '.json_encode($dataArr));
        $response = Capi::request('/api/v1-send-health-quote-plan-email', 'post', $dataArr);
        if ($response && isset($response->status)) {
            $msg = '';
            if (isset($response->msg)) {
                $msg = $response->msg;
            }
            info('RM Intro Email Error for HEA-'.$quoteUuid.' - Response Code: '.$response->status.' - Message: '.$msg);
        } elseif ($response && isset($response->message)) {
            info('RM Intro Email Triggered to CAPI for HEA-'.$quoteUuid.' - Message: '.$response->message);
        }
    }

    public function sendNonAdvisorIntroEmail($emailData, $tag, $emailTemplateId)
    {
        try {
            $appEnv = config('constants.APP_ENV');

            info('sendNonAdvisorIntroEmail  , emailTemplateId: '.$emailTemplateId.' '.$emailData->carQuoteId);
            $tag = $appEnv == EnvEnum::PRODUCTION ? $tag : $appEnv.'-'.$tag;

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
            $additionalBcc = ApplicationStorage::where('key_name', ApplicationStorageEnums::LMS_INTRO_EMAIL_BCC)->first()->value;
            foreach (explode(',', $additionalBcc) as $additionalContact) {
                $bccAdditional[] = [
                    'email' => $additionalContact,
                ];
            }
            if (property_exists($emailData, 'pdfAttachment') && ! empty($emailData->pdfAttachment->pdf) && ! empty($emailData->pdfAttachment->name)) {
                $attachments[] = [
                    'content' => chunk_split(base64_encode($emailData->pdfAttachment->pdf->stream())),
                    'name' => $emailData->pdfAttachment->name,
                ];
            }
            $subjectEnvTag = $this->appEnv == EnvEnum::PRODUCTION ? '' : $this->appEnv.' - ';
            $emailData->env = $subjectEnvTag;

            $body = [
                'to' => [[
                    'email' => $emailData->customerEmail,
                    'name' => $emailData->clientFullName,
                ]],
                'templateId' => $emailTemplateId,
                'params' => $emailData,
                'bcc' => $bccAdditional,
                'tags' => [
                    $tag,
                ],
                'attachment' => isset($attachments) ? $attachments : null,
            ];

            ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $responseDetail = 'SIB Send sendNonAdvisorIntroEmail: Code/Message: '.$responseCode.'/'.$ex->getMessage().' '.$emailData->carQuoteId;
            Log::error($responseDetail);
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        return $responseCode;
    }

    public function sendActivityAlertEmail($user)
    {
        $emailEnable = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::ADVISOR_ONLINE_NOTIFICATION_EMAILS_ENABLE)->first();
        if ($emailEnable && $emailEnable->value == 0) {
            info('sendActivityAlertEmail is Disable');

            return false;
        }
        $emailTemplateId = ApplicationStorage::where('key_name', '=', 'ADVISOR_NOTIFICATION_TEMPLATE')->value('value');
        $advisorEmail = '';
        try {
            $tag = $this->appEnv == EnvEnum::PRODUCTION ? '' : $this->appEnv.'-';

            $user->advisor = [
                'name' => $user->name,
            ];

            $roles = $user->usersroles->pluck('name');

            $emailMapping = [
                'CAR_ADVISOR',
                'HEALTH_ADVISOR',
            ];

            $advisorName = '';

            foreach ($emailMapping as $role) {
                if ($roles->contains($role)) {
                    $HealthEmail = ApplicationStorage::where('key_name', '=', 'ADVISOR_NOTIFICATION_'.$role)->value('value');
                    $health = explode(',', $HealthEmail);
                    $advisorEmail = $health[1];
                    $advisorName = $health[0];
                    break;
                }
            }
            if ($advisorEmail == '') {
                return;
            }
            $BccEmail = ApplicationStorage::where('key_name', '=', 'ADVISOR_NOTIFICATION_BCC_EMAILS')->value('value');
            $bcc = explode(',', $BccEmail);
            $bccAdditional = [];

            $i = 0;
            foreach ($bcc as $pair) {
                if (isset($bcc[$i])) {
                    $bccAdditional[] = ['email' => $bcc[$i + 1], 'name' => $bcc[$i]];
                    $i++;
                }
                $i++;
            }
            $body = [
                'sender' => ['name' => $tag.' '.' Urgent: '.$user->name.' IMCRM Inactivity Alert', 'email' => $advisorEmail],
                'to' => [[
                    'email' => $advisorEmail,
                    'name' => $advisorName,
                ]],
                'replyTo' => [
                    'email' => $advisorEmail,
                    'name' => $advisorName,
                ],
                'bcc' => array_merge($bccAdditional),  //    'bcc' => array_merge($bccAdditional, $bcc),
                'templateId' => intval($emailTemplateId),
                'params' => $user,
            ];
            ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $responseDetail = 'sendActivityAlertEmail: Code/Message: '.$responseCode.'/'.$ex->getMessage();
        }
    }
    public function sendBookPolicyDocumentsEmail($emailData, $tag, $source = '')
    {
        $isEmailSent = 0;
        try {
            info('sendBookPolicyDocumentsEmail  , emailTemplateId: '.$emailData->emailTemplateId.' LOB Code '.$emailData->code);

            $websiteURL = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
            $documents = $emailData->quoteDocuments;
            $attachments = [];
            if (! empty($documents)) {
                foreach ($documents as $document) {
                    $path = $document->doc_url;
                    $documentURL = $path !== '' ? $websiteURL.$path : '';
                    $attachments[] = [
                        'url' => $documentURL,
                        'name' => basename($documentURL),
                    ];
                }
            }

            $headers = [
                'Accept' => 'application/json',
                'api-key' => config('constants.SENDINBLUE_KEY'),
                'Content-Type' => 'application/json',
            ];

            $bodyData = [
                'to' => [[
                    'email' => $emailData->customerEmail,
                    'name' => $emailData->clientFullName,
                ]],
                'templateId' => (int) $emailData->emailTemplateId,
                'params' => [
                    'clientFullName' => $emailData->clientFullName,
                    'carQuoteId' => $emailData->code,
                    'currentInsurer' => $emailData->currentInsurer,
                    'renewalDueDate' => $emailData->renewalDueDate,
                    'policyNumber' => $emailData->policy_number,
                    'advisor' => (object) [
                        'name' => $emailData->advisorName,
                        'email' => $emailData->advisorEmail,
                    ],
                ],
                'tags' => [
                    $tag,
                ],
                'attachment' => isset($attachments) ? $attachments : null,
            ];

            if ($emailData->advisorEmail) {
                $bodyData['cc'] = [
                    [
                        'email' => $emailData->advisorEmail,
                        'name' => $emailData->advisorName,
                    ],
                ];
            }
            $body = json_encode($bodyData, JSON_UNESCAPED_SLASHES);

            $client = new \GuzzleHttp\Client();
            $clientRequest = $client->post(
                config('constants.SIB_URL'),
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 20,
                ]
            );

            $response = json_decode($clientRequest->getStatusCode().' '.$clientRequest->getBody()->getContents(), true);
            $responseCode = $clientRequest->getStatusCode();
            $isEmailSent = 1;
            info('sendBookPolicyDocumentsEmail ---- Request Sent '.$emailData->code);
            info('sendBookPolicyDocumentsEmail ---- response object : '.json_encode($clientRequest->getBody()->getContents()));
        } catch (Exception $ex) {
            $response = '';
            $responseCode = $ex->getCode();
            $responseDetail = 'Brevo Send Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' CustomerEmail: '.$emailData->customerEmail.' Class: '.get_class();
            Log::error($responseDetail);
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        return $responseCode;
    }

    public function sendSICNotificationToAdvisor($lead, $user)
    {
        info('sendSICNotificationToAdvisor ---- Start');

        try {
            $subjectEnvTag = $this->appEnv == EnvEnum::PRODUCTION ? '' : $this->appEnv.' - ';

            $subject = $subjectEnvTag.'CALL NOW! Customer with REF-ID '.$lead->code.' has requested for an advisor right now!';

            $htmlContent = '<html>
            <head></head>
            <body>
              <p>Dear <b>'.$user->name.'</b>,</p>
              <p>
                  A customer with REF-ID <a href="'.$this->appUrl.'/quotes/car/'.$lead->uuid.'"><b>'.$lead->code.'</b></a> has requested for an advisor and we need you to contact them urgently.
              </p>
              <p>
                Please call the customer urgently as they have requested for an advisor right now.
              </p>
              <p>
                Regards,<br>
                Alfred
              </p>
            </body>
          </html>';

            $body = [
                'to' => [(object) [
                    'email' => $user->email, // advsior email
                    'name' => $user->name, // advsior name
                ]],
                'sender' => [
                    'email' => 'no-reply@alert.insurancemarket.email',
                    'name' => 'InsuranceMarket.ae',
                ],
                'subject' => $subject,
                'htmlContent' => $htmlContent,
            ];

            ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $responseDetail = 'sendSICNotificationToAdvisor: Code/Message: '.$responseCode.'/'.$ex->getMessage();
            Log::error($responseDetail);
        }
        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $user->email);

        return $responseCode;
    }

    public function sendUpdateToCustomerEmail($emailTemplateId, $emailData, $tag, $quoteTypeId)
    {
        try {
            info('fn: sendUpdateEmail, email sending started. emailTemplateId: '.$emailTemplateId.', tag: '.$tag);

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
                        'url' => storageUrl().$emailAttachment,
                        'name' => basename($emailAttachment),
                    ];
                }
            }

            /* if (! empty($emailData->pdfAttachment->pdf) && ! empty($emailData->pdfAttachment->name)) {
                $attachments[] = [
                    'content' => chunk_split(base64_encode($emailData->pdfAttachment->pdf->stream())),
                    'name' => $emailData->pdfAttachment->name,
                ];
            } */

            $sendUpdateEmail = getAppStorageValueByKey(ApplicationStorageEnums::SEND_UPDATE_EMAIL);
            info('send update email fetched. email: '.$sendUpdateEmail);
            info('template id is : '.$emailTemplateId);

            $body = [
                'sender' => [
                    'email' => $sendUpdateEmail,
                    'name' => $emailData->advisor->name,
                ],
                'to' => [[
                    'email' => $emailData->customerEmail,
                    'name' => $emailData->clientFullName,
                ]],
                'templateId' => (int) $emailTemplateId,
                'params' => $emailData,
                'tags' => [
                    $tag,
                ],
                'attachment' => isset($attachments) ? $attachments : null,
            ];

            $checkIsHealthOrGroupMedical = in_array($quoteTypeId, [QuoteTypeId::Health, QuoteTypeId::GroupMedical]);

            $ebServiceTeam = [];
            $ebServiceEmail = getAppStorageValueByKey(ApplicationStorageEnums::IM_EB_SERVICE_TEAM_EMAIL);
            info('IM EB Service team email fetched. email: '.$ebServiceEmail);
            if ($checkIsHealthOrGroupMedical) {
                $ebServiceTeam = [[
                    'email' => $ebServiceEmail,
                    'name' => 'IM EB Service',
                ]];
                $body['replyTo'] = [
                    'email' => $ebServiceEmail,
                    'name' => 'IM EB Service',
                ];
            }

            $ccAdvisor = [];
            if (isset($emailData->advisor->email) && isset($emailData->advisor->name)) {
                $ccAdvisor = [[
                    'email' => $emailData->advisor->email,
                    'name' => $emailData->advisor->name,
                ]];

                if (! $checkIsHealthOrGroupMedical) {
                    $body['replyTo'] = [
                        'email' => $emailData->advisor->email,
                        'name' => $emailData->advisor->name,
                    ];
                }
            }

            $customer = $this->customerService->getCustomerByEmail($emailData->customerEmail);
            $ccAdditional = [];
            if ($customer) {
                $additionalContacts = $this->customerService->getAdditionalContactByKey($customer->id, 'email');
                foreach ($additionalContacts as $additionalContact) {
                    if (! empty($additionalContact->value)) {
                        $ccAdditional[] = [
                            'email' => $additionalContact->value,
                            'name' => $emailData->clientFullName,
                        ];
                    }
                }
            }

            // $body['cc'] = array_merge($ccAdditional, $ccAdvisor, $ebServiceTeam);
            $body['cc'] = array_merge($ccAdditional, $ccAdvisor);

            // need to discuss this.
            $sendPolicyUpdateEmail = getAppStorageValueByKey(ApplicationStorageEnums::SEND_POLICY_UPDATE_EMAIL);
            info('Send Policy Update email fetched. email: '.$sendPolicyUpdateEmail);

            $body['bcc'] = [[
                'email' => $sendPolicyUpdateEmail,
            ]];

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
            info('fn: sendUpdateToCustomerEmail, email sending completed. messageId: '.$messageId);
            $response = json_decode(json_encode($clientRequest->getStatusCode().' '.$clientRequest->getBody()->getContents()), true);
            $responseCode = $clientRequest->getStatusCode();

            if ($responseCode == 201) {
                $isEmailSent = 1;
            }
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $quoteCdbId = isset($emailData->carQuoteId) ? $emailData->carQuoteId : null;
            $responseDetail = 'Send Update Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' CustomerEmail: '.$emailData->customerEmail.' QuoteCdbId: '.$quoteCdbId.' Class: '.get_class();
            info($responseDetail);
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
            $isEmailSent = 0;
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        // addEmailStatus is for quote modules only
        if (isset($messageId) && isset($emailData->quoteTypeId) && isset($emailData->quoteId)) {
            // UpdateSendPolicySubjectJob::dispatch($emailData, $messageId)->delay(now()->addSeconds(7));
        }
    }

    public function sendingAlfredFollowupEmail($customer)
    {
        $emailTemplateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::ALFRED_FOLLOWUP_TEMPLATE)->first();

        $apiKey = config('constants.SENDINBLUE_KEY');
        $url = config('constants.SIB_URL');
        try {
            info('AlfredFollowUpEmail Starting');
            $headers = [
                'Accept' => 'application/json',
                'api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ];
            $body = [
                'to' => [[
                    'email' => $customer->email,
                    'name' => $customer->name,
                ]],
                'templateId' => (int) $emailTemplateId->value,
                'params' => ['email' => $customer->email, 'customerName' => $customer->name],
            ];
            $response = Http::withHeaders($headers)
                ->post($url, $body);

            info('AlfredFollowUpEmail ---- Request Sent '.$customer->email);

            $responseCode = $response->status();
            if ($responseCode == 200 || $responseCode == 201) {
                $isCustomer = Customer::where('id', $customer->customer_id)->first();
                if ($isCustomer->campaign_followups < 3) {
                    $isCustomer->increment('campaign_followups');
                    $isCustomer->last_followup_sent_at = Carbon::now();
                    $isCustomer->save();
                }
            }

            info('AlfredFollowUpEmail ---- Received Code : '.$responseCode.' '.$customer->email);
            info('AlfredFollowUpEmail ---- response object : '.json_encode($response->object()).'--'.$customer->email);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            Log::error($responseCode);
        }

        return $responseCode;
    }

    public function sendSICFollowupEmail($emailData)
    {
        $emailTemplateId = getAppStorageValueByKey(ApplicationStorageEnums::SIC_FOLLOWUP_TEMPLATE_ID);
        if (! $emailTemplateId || ! $emailData || ! $emailData->email) {
            return false;
        }

        try {
            $headers = [
                'Accept' => 'application/json',
                'api-key' => config('constants.SENDINBLUE_KEY'),
                'Content-Type' => 'application/json',
            ];
            $body = [
                'to' => [[
                    'email' => $emailData->email,
                    'name' => $emailData->first_name.' '.$emailData->last_name,
                ]],
                'templateId' => (int) $emailTemplateId,
                'params' => [
                    'requestAdvisorLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$emailData->uuid.'/?assignAdvisor=true',
                    'carQuoteLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$emailData->uuid.'/?IA=true',
                    'carQuoteId' => $emailData->code,
                    'email' => $emailData->email,
                    'clientFullName' => $emailData->first_name.' '.$emailData->last_name],
            ];
            $response = Http::withHeaders($headers)
                ->timeout(config('constants.LMS_EMAILS_TIMEOUT'))
                ->retry(3, 90000)
                ->post(config('constants.SIB_URL'), $body);

            info('SICFollowupEmail ---- Request Sent '.$emailData->email);

            $responseCode = $response->status();
            if ($responseCode == 200 || $responseCode == 201) {
                info('SICFollowupEmail ---- | Response Code: '.$responseCode.' | Response Received  : '.json_encode($response->object()).'--'.$emailData->email);
            }
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            Log::error(sprintf('SICFollowupEmail failed: Brevo API call failed for %s | Exception: %s', $emailData->email, $ex->getMessage()));
        }

        return $responseCode;
    }
}
