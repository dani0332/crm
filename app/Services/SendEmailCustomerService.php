<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DefaultAdvisorEnum;
use App\Enums\EnvEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\UserStatusEnum;
use App\Enums\WorkflowTypeEnum;
use App\Facades\Capi;
use App\Jobs\OCAHealthFollowupEmailJob;
use App\Jobs\UpdateSendPolicySubjectJob;
use App\Models\ApplicationStorage;
use App\Models\Customer;
use App\Models\HealthQuote;
use App\Models\InsuranceProvider;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use League\CommonMark\Extension\SmartPunct\Quote;

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

    private function getAdditionalEmails(string $additionalEmails): array
    {
        $emails = [];
        if ($additionalEmails) {
            foreach (explode(',', $additionalEmails) as $additionalEmail) {
                $emails[] = [
                    'email' => $additionalEmail,
                ];
            }
        }

        return $emails;
    }

    private function getEmailAttachments(object $emailData, $quoteId)
    {
        $attachments = [];

        if (isset($emailData->documentUrl)) {
            foreach ($emailData->documentUrl as $emailAttachment) {
                $attachments[] = [
                    'url' => $emailAttachment,
                    'name' => basename($emailAttachment),
                ];
            }
        }

        if (property_exists($emailData, 'pdfAttachment') && ! empty($emailData->pdfAttachment->pdf) && ! empty($emailData->pdfAttachment->name)) {
            info(self::class." - Going to stream email attachments for uuid: {$quoteId}");
            $attachments[] = [
                'content' => chunk_split(base64_encode($emailData->pdfAttachment->pdf->stream())),
                'name' => $emailData->pdfAttachment->name,
            ];
            info(self::class." - Streamed email attachments for uuid: {$quoteId}");
        }

        return $attachments;
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
                ->beforeSending(function () use ($body) {
                    // info("{$fnName} ---- Mail Request is Sending");
                    $sender = $body['sender'] ?? null;
                    $replyTo = $body['replyTo'] ?? null;
                    $to = $body['to'] ?? null;
                    $sender != null && info('Mail Request sender details ----- '.json_encode($sender));
                    $replyTo != null && info('Mail Request replyTo details ----- '.json_encode($replyTo));
                    $to != null && info('Mail Request to details ----- '.json_encode($to));
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

    public function sendEmail($emailTemplateId, $emailData, $tag, $cc = [])
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
                'cc' => count($cc) > 0 ? $cc : null,
                'templateId' => (int) $emailTemplateId,
                'params' => [
                    'customerName' => $emailData->customerName,
                    'customerEmail' => $emailData->customerEmail,
                    'signUpButtonUrl' => isset($emailData->signUpButtonUrl) ? $emailData->signUpButtonUrl : null,
                    'inviteCode' => isset($emailData->inviteCode) ? $emailData->inviteCode : null,
                    'buttonUrl' => isset($emailData->buttonUrl) ? $emailData->buttonUrl : null,
                    'cdbId' => isset($emailData->quoteCdbId) ? $emailData->quoteCdbId : null,
                    'productName' => isset($emailData->productName) ? $emailData->productName : null,
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

        if (isset($messageId) && isset($emailData->quoteTypeId) && ($emailData->quoteTypeId == QuoteTypeId::Health) && isset($emailData->quoteId)) {
            UpdateSendPolicySubjectJob::dispatch($emailData, $messageId)->delay(now()->addSeconds(7));
        }

        return $responseCode;
    }

    public function getEmailSubjectFromSib($messageId)
    {
        try {
            $client = new \GuzzleHttp\Client;
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
            // Todo: Remove SIB_MYALFRED_CUSTOMER_WE_TEMPLATE_ID from doppler
            if ($source == 'CORPORATE') {
                $emailTemplateId = (int) config('constants.SIB_CORPORATE_TEMPLATE');
            } else {
                $emailTemplateId = (int) config('constants.SIB_MYALFRED_CUSTOMER_WE_TEMPLATE_ID');
            }

            [$emailCampaignBanner, $emailCampaignBannerRedirectUrl] = getEmailCampaignBanner();

            if ($emailCampaignBanner) {
                $emailTemplateId = (int) getAppStorageValueByKey(ApplicationStorageEnums::INVITATION_EMAIL_TEMPLATE_FOR_CAMPAIGN);
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
                    'wfsBanner' => $emailCampaignBanner,
                    'wfsBannerRedirectUrl' => $emailCampaignBannerRedirectUrl,
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

    public function sendLMSIntroEmail($emailTemplateId, $emailData, $tag, QuoteTypes $quoteType = QuoteTypes::CAR)
    {
        $quoteId = match ($quoteType) {
            QuoteTypes::CAR => $emailData->carQuoteId,
            QuoteTypes::TRAVEL => $emailData->travelQuoteId,
            QuoteTypes::BIKE => $emailData->bikeQuoteId,
            default => $emailData->quoteId,
        };

        try {
            $tag = $this->appEnv == EnvEnum::PRODUCTION ? $tag : $this->appEnv.'-'.$tag;
            info("sendLMSIntroEmail ---- Tag : {$tag} for ID : {$quoteId}");
            $subjectEnvTag = $this->appEnv == EnvEnum::PRODUCTION ? '' : $this->appEnv.' - ';
            if ($emailData->customerEmail === '0' || $emailData->customerEmail === 0) {
                info("Customer email is missing or invalid for ID : {$quoteId}");

                return false;
            }
            $attachments = $this->getEmailAttachments($emailData, $quoteId);
            $bcc = [];
            if ($emailData->advisorEmail) {
                $bcc[] = [
                    'email' => $emailData->advisorEmail,
                    'name' => $emailData->advisorName,
                ];
            }

            $bccAdditional = $this->getBccAdditionalEmails($quoteType);

            $advisorCustomEmail = strstr($emailData->advisorEmail, '@', true).'@notify.insurancemarket.ae';
            $emailData->env = $subjectEnvTag;

            $bcc = array_merge($bccAdditional, $bcc);

            $body = [
                'to' => [[
                    'email' => $emailData->customerEmail,
                    'name' => $emailData->clientFullName,
                ]],
                'replyTo' => ['name' => getAppStorageValueByKey(ApplicationStorageEnums::CAR_DISPLAY_NAME), 'email' => getAppStorageValueByKey(ApplicationStorageEnums::CAR_EMAIL_REPLY_TO)],
                'templateId' => (int) $emailTemplateId,
                'params' => $emailData,
                'tags' => [
                    $tag,
                ],
                'attachment' => ! empty($attachments) ? $attachments : null,
            ];

            if (! empty($bcc)) {
                $body['bcc'] = $bcc;
            }

            if ($quoteType === QuoteTypes::TRAVEL) {
                $body['cc'] = $this->getAdditionalEmails(getAppStorageValueByKey(ApplicationStorageEnums::SIC_TRAVEL_EMAIL_CC));
                $body['replyTo'] = ['email' => getAppStorageValueByKey(ApplicationStorageEnums::TRAVEL_EMAIL_REPLY_TO), 'name' => getAppStorageValueByKey(ApplicationStorageEnums::TRAVEL_DISPLAY_NAME)];
            }
            if ($quoteType === QuoteTypes::BIKE) {
                $body['replyTo'] = ['name' => $emailData->advisorName, 'email' => $emailData->advisorEmail];
            }
            // Conditionally add 'sender' key if advisorName and $advisorCustomEmail are not null
            if ($emailData->advisorName !== null && $advisorCustomEmail !== null) {
                $body['sender'] = ['name' => $emailData->advisorName, 'email' => $advisorCustomEmail];
            }

            info(self::class." - Going to call final sendMail for uuid: {$quoteId}");
            ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);
            info(self::class." - Email response code {$responseCode} received for uuid: {$quoteId}");
        } catch (Exception $ex) {
            $isEmailSent = false;
            $responseCode = $ex->getCode();
            $responseDetail = 'SIB Send sendLMSIntroEmail: Code/Message: '.$responseCode.'/'.$ex->getMessage().' '.$quoteId;
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
                'email' => $emailData->fromEmail ?? 'no-reply@alert.insurancemarket.email',
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

        if ($emailData->lob == QuoteTypeId::Health) {

            $replyToEmail = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_HEALTH_REPLY_TO);
        } else {
            $replyToEmail = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_REPLY_TO);
        }

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
        $healthQuote = HealthQuote::where('uuid', $quoteUuid)->first();
        if ($healthQuote && $healthQuote->isApplicationPending()) {
            info('sendRMIntroEmail: Health quote is Application Pending, skipping RM Intro Email for uuid: '.$quoteUuid);

            return;
        }

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
            $healthAutoFollowupSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::HEALTH_AUTOMATED_FOLLOWUPS_SWITCH)->first();
            // Send Automated Followup Email Job if Health Auto-Followups is enabled.
            if ($healthAutoFollowupSwitch && $healthAutoFollowupSwitch->value == 1) {
                $delayDays = isLeadSic($quoteUuid) ? 3 : 2;
                OCAHealthFollowupEmailJob::dispatch($quoteUuid)->delay(Carbon::now()->addDays($delayDays));
                info('OCAHealthFollowupEmailJob dispatched for HEA-'.$quoteUuid.' - Time: '.now());
            }
        }
    }

    public function sendNonAdvisorIntroEmail($emailData, $tag, $emailTemplateId, QuoteTypes $quoteType = QuoteTypes::CAR)
    {
        $quoteId = match ($quoteType) {
            QuoteTypes::CAR => $emailData->carQuoteId,
            QuoteTypes::TRAVEL => $emailData->travelQuoteId,
            default => $emailData->quoteId,
        };

        try {
            $appEnv = config('constants.APP_ENV');

            info("sendNonAdvisorIntroEmail  , emailTemplateId: {$emailTemplateId} with QuoteId: {$quoteId}");
            $tag = $appEnv == EnvEnum::PRODUCTION ? $tag : $appEnv.'-'.$tag;
            if ($emailData->customerEmail === '0' || $emailData->customerEmail === 0) {
                info("Customer email is missing or invalid for QuoteId: {$quoteId}");

                return false;
            }
            $attachments = $this->getEmailAttachments($emailData, $quoteId);

            $bccAdditional = [];
            if ($quoteType === QuoteTypes::CAR) {
                $additionalBcc = ApplicationStorage::where('key_name', ApplicationStorageEnums::LMS_INTRO_EMAIL_BCC)->first()->value;
                foreach (explode(',', $additionalBcc) as $additionalContact) {
                    $bccAdditional[] = [
                        'email' => $additionalContact,
                    ];
                }
            }
            $subjectEnvTag = $this->appEnv == EnvEnum::PRODUCTION ? '' : $this->appEnv.' - ';
            $emailData->env = $subjectEnvTag;

            $body = [
                'to' => [[
                    'email' => $emailData->customerEmail,
                    'name' => $emailData->clientFullName,
                ]],
                'replyTo' => ['name' => getAppStorageValueByKey(ApplicationStorageEnums::CAR_DISPLAY_NAME), 'email' => getAppStorageValueByKey(ApplicationStorageEnums::CAR_EMAIL_REPLY_TO)],
                'templateId' => (int) $emailTemplateId,
                'params' => $emailData,
                'tags' => [
                    $tag,
                ],
                'attachment' => ! empty($attachments) ? $attachments : null,
            ];

            if (! empty($bccAdditional)) {
                $body['bcc'] = $bccAdditional;
            }

            if ($quoteType === QuoteTypes::TRAVEL) {
                $body['cc'] = $this->getAdditionalEmails(getAppStorageValueByKey(ApplicationStorageEnums::SIC_TRAVEL_EMAIL_CC));
                $body['replyTo'] = ['email' => getAppStorageValueByKey(ApplicationStorageEnums::TRAVEL_EMAIL_REPLY_TO), 'name' => getAppStorageValueByKey(ApplicationStorageEnums::TRAVEL_DISPLAY_NAME)];
            }

            ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);
        } catch (Exception $ex) {
            $isEmailSent = 0;
            $responseCode = $ex->getCode();
            $responseDetail = 'SIB Send sendNonAdvisorIntroEmail: Code/Message: '.$responseCode.'/'.$ex->getMessage().' '.$quoteId;
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
        info('Quote Code: '.$emailData->code.' fn: sendBookPolicyDocumentsEmail called');

        $isEmailSent = 0;
        try {
            info('Quote Code: '.$emailData->code.' sendBookPolicyDocumentsEmail , emailTemplateId: '.$emailData->emailTemplateId);

            $websiteURL = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
            $documents = $emailData->quoteDocuments;
            $attachments = [];
            if (! empty($documents)) {
                foreach ($documents as $document) {
                    $path = $document->watermarked_doc_url ?? $document->doc_url;
                    $documentURL = $path !== '' ? $websiteURL.$path : '';
                    $attachments[] = [
                        'url' => $this->encodeUrl($documentURL),
                        'name' => 'InsuranceMarket.ae™ '.$document->document_type_text.' for Policy Number '.$emailData->policy_number.'.'.pathinfo($documentURL, PATHINFO_EXTENSION),
                    ];
                }
            }
            if (is_array($emailData->handBookDocuments) && ! empty($emailData->handBookDocuments)) {
                $attachments = array_merge($attachments, $emailData->handBookDocuments);
            }

            info('Quote Code: '.$emailData->code.' Attachments: '.json_encode($attachments));

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
                    'clientFirstName' => $emailData->clientFirstName,
                    'clientFullName' => $emailData->clientFullName,
                    'carQuoteId' => $emailData->code,
                    'currentInsurer' => $emailData->currentInsurer,
                    'renewalDueDate' => $emailData->renewalDueDate,
                    'policyStartDate' => $emailData->policyStartDate,
                    'policyNumber' => $emailData->policy_number,
                    'roadsideAssistance' => $emailData->roadsideAssistance,
                    'googleMeet' => $emailData->googleMeet,
                    'appDownloadLink' => $emailData->appDownloadLink,
                    'insuranceType' => $emailData->insuranceType,
                    'planName' => $emailData->planName,
                    'advisor' => (object) [
                        'name' => $emailData->advisorName,
                        'email' => $emailData->advisorEmail,
                        'mobileNo' => $emailData->advisorMobileNo,
                        'landLine' => $emailData->advisorLandlineNo,
                        'profilePicture' => $emailData->profilePicture,
                        'isChsAdvisor' => $emailData->isChsAdvisor,
                    ],
                ],
                'tags' => [
                    $tag,
                ],
                'attachment' => isset($attachments) && count($attachments) > 0 ? $attachments : null,
                'bcc' => [],
            ];

            $additionalBcc = ApplicationStorage::where('key_name', ApplicationStorageEnums::DIS_INBOX_EMAIL_BCC)->first();
            if ($additionalBcc) {
                $bodyData['bcc'][] = [
                    'email' => $additionalBcc->value,
                ];
            }
            info('Quote Code: '.$emailData->code.' sendBookPolicyDocumentsEmail ---- bcc '.$additionalBcc->value);

            if ($emailData->advisorEmail) {
                $bodyData['cc'] = [
                    [
                        'email' => $emailData->advisorEmail,
                        'name' => $emailData->advisorName,
                    ],
                ];
            }

            $body = json_encode($bodyData, JSON_UNESCAPED_SLASHES);
            // Its a temp log and will be removed iin future
            info('Quote Code: '.$emailData->code.' sendBookPolicyDocumentsEmail ---- body '.$body);

            $client = new \GuzzleHttp\Client;
            $clientResponse = $client->post(
                config('constants.SIB_URL'),
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 20,
                ]
            );

            $response = json_decode($clientResponse->getStatusCode().' '.$clientResponse->getBody()->getContents(), true);
            $responseCode = $clientResponse->getStatusCode();
            $isEmailSent = 1;
            info('Quote Code: '.$emailData->code.' sendBookPolicyDocumentsEmail ---- response object : '.json_encode($clientResponse->getBody()->getContents()));
            info('Quote Code: '.$emailData->code.' Email sent successfully to '.$emailData->customerEmail.' with template ID '.$emailData->emailTemplateId);
        } catch (Exception $ex) {
            $response = '';
            $responseCode = $ex->getCode();
            $responseDetail = 'Brevo Send error for: '.$emailData->code.' Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' CustomerEmail: '.$emailData->customerEmail.' Class: '.get_class();
            Log::error($responseDetail);
            info('Quote Code: '.$emailData->code.' Error sending email to '.$emailData->customerEmail.' with template ID '.$emailData->emailTemplateId.': '.$ex->getMessage());
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        return $responseCode;
    }

    public function sendSICNotificationToAdvisor($lead, $user, $quoteType)
    {
        info('sendSICNotificationToAdvisor ---- Start');

        try {
            $subjectEnvTag = $this->appEnv == EnvEnum::PRODUCTION ? '' : $this->appEnv.' - ';

            $subject = $subjectEnvTag.'CALL NOW! Customer with REF-ID '.$lead->code.' has requested for an advisor right now!';

            $quoteTypeCode = strtolower($quoteType);

            if ($quoteType == quoteTypeCode::Business) {
                $path = "quotes/business/$lead->uuid";
            } elseif (checkPersonalQuotes($quoteType)) {
                $path = "personal-quotes/$quoteTypeCode/$lead->uuid";
            } else {
                $path = "quotes/$quoteTypeCode/$lead->uuid";
            }

            $htmlContent = '<html>
            <head></head>
            <body>
              <p>Dear <b>'.$user->name.'</b>,</p>
              <p>
                  A customer with REF-ID <a href="'.$this->appUrl.'/'.$path.'"><b>'.$lead->code.'</b></a> has requested for an advisor and we need you to contact them urgently.
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

            $websiteURL = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';

            $documents = $emailData->documents;
            $attachments = [];
            if (! empty($documents)) {
                foreach ($documents as $document) {
                    $path = $document['watermarked_doc_url'] ?? $document['doc_url'];
                    $documentURL = $path !== '' ? $websiteURL.$path : '';
                    $attachments[] = [
                        'url' => $documentURL,
                        'name' => 'InsuranceMarket.ae™ '.$document['document_type_text'].' for Policy Number '.$emailData->policyNumber.' - '.$emailData->carQuoteId.'.'.pathinfo($documentURL, PATHINFO_EXTENSION),
                    ];
                }
            }

            $sendUpdateEmail = getAppStorageValueByKey(ApplicationStorageEnums::SEND_UPDATE_EMAIL);
            info('send update email fetched. email: '.$sendUpdateEmail);
            info('template id is : '.$emailTemplateId);

            $body = [
                'sender' => [
                    'email' => $sendUpdateEmail,
                    'name' => 'InsuranceMarket.ae',
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
                'attachment' => $attachments ?? null,
            ];

            $checkIsHealthOrGroupMedical = $quoteTypeId == QuoteTypeId::Health || isset($emailData->isGroupMedical);

            $ebServiceTeam = [];
            if ($checkIsHealthOrGroupMedical) {
                $ebServiceEmail = getAppStorageValueByKey(ApplicationStorageEnums::IM_EB_SERVICE_TEAM_EMAIL);
                info('IM EB Service team email fetched. email: '.$ebServiceEmail);
                $ebServiceTeam = [[
                    'email' => $ebServiceEmail,
                    'name' => 'IM EB Service',
                ]];
            }

            $ccAdvisor = [];
            if (isset($emailData->advisor->email) && isset($emailData->advisor->name)) {
                $ccAdvisor = [[
                    'email' => $emailData->advisor->email,
                    'name' => $emailData->advisor->name,
                ]];

                $body['replyTo'] = [
                    'email' => $emailData->advisor->email,
                    'name' => $emailData->advisor->name,
                ];
            }

            $body['cc'] = array_merge($ccAdvisor, $ebServiceTeam);

            $sendPolicyUpdateEmail = getAppStorageValueByKey(ApplicationStorageEnums::SEND_POLICY_UPDATE_EMAIL);
            info('Send Policy Update email fetched. email: '.$sendPolicyUpdateEmail);

            $body['bcc'] = [[
                'email' => $sendPolicyUpdateEmail,
            ]];

            $client = new \GuzzleHttp\Client;
            $clientRequest = $client->post(
                $this->url,
                [
                    'headers' => $headers,
                    'body' => json_encode($body),
                    'timeout' => 10000,
                ]
            );

            $message = json_decode($clientRequest->getBody()->getContents());
            if (isset($message->messageId)) {
                info('fn: sendUpdateToCustomerEmail, email sending completed. messageId: '.$message->messageId);
                $response = json_decode(json_encode($clientRequest->getStatusCode().' '.$clientRequest->getBody()->getContents()), true);
                $responseCode = $clientRequest->getStatusCode();

                if ($responseCode == 201) {
                    $isEmailSent = 1;
                }
            } else {
                $isEmailSent = 0;
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

        return $responseCode;
    }

    public function sendPaymentNotificationEmail($lead, $user)
    {
        $emailTemplateId = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_NOTIFICATION_EMAIL_TEMPLATE)->value('value');
        try {
            $tag = $this->appEnv == EnvEnum::PRODUCTION ? '' : $this->appEnv.'-';
            $headers = [
                'Accept' => 'application/json',
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ];
            $url = url('/');
            $url .= '/reports/payment-summary';
            $advisorData = [];
            if ($user) {
                $advisor = (object) [];
                $advisor->name = $user->name;
                $advisor->email = $user->email;
                $advisorData[] = $advisor;
            }
            $params = [
                'advisor_name' => $user->name,
                'total_leads' => $lead['total_leads'] ? $lead['total_leads'] : 0,
                'total_premium' => $lead['total_premium'] ? sprintf('%.2f', $lead['total_premium']) : 0,
                'leads_expire' => $lead['leads_expire'] ? $lead['leads_expire'] : 0,
                'date' => Carbon::now()->toDateString(),
                'paymentDoc' => $url,
            ];
            if (empty($params['total_leads']) || empty($params['total_premium']) || empty($params['date'])) {
                return;
            }
            if (isset($advisorData) && empty($advisorData)) {
                info('Advisor Email or Data Not Found');

                return;
            }
            $replyTo = [
                'email' => $advisorData[0]->email,
                'name' => $advisorData[0]->name,
            ];

            $body = json_encode([
                'sender' => ['name' => $tag.' '.'IMCRM Payment Notification Alert', 'email' => 'no-reply@alert.insurancemarket.email'],
                'to' => $advisorData,
                'replyTo' => $replyTo,
                //  'bcc' => array_merge($bccAdditional),  //    'bcc' => array_merge($bccAdditional, $bcc),
                'templateId' => intval($emailTemplateId),
                'params' => $params,
            ], JSON_UNESCAPED_SLASHES);
            $client = new \GuzzleHttp\Client;
            $clientRequest = $client->post(
                $this->url,
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10,
                ]
            );
            $responseCode = $clientRequest->getStatusCode();
            info('sendPaymentNotificationEmail ---- response object : '.json_encode($clientRequest->getBody()->getContents()));
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $responseDetail = 'sendPaymentNotificationEmail: Code/Message: '.$responseCode.'/'.$ex->getMessage();
            Log::error($responseDetail);
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

    public function sendSICFollowupEmail($lead, ?QuoteTypes $quoteType = null, array $extraParams = [])
    {
        if ($quoteType === null) {
            $quoteType = QuoteTypes::CAR;
        }

        if ($quoteType === QuoteTypes::CAR) {
            $emailTemplateId = getAppStorageValueByKey(ApplicationStorageEnums::SIC_FOLLOWUP_TEMPLATE_ID);
        }
        if ($quoteType === QuoteTypes::TRAVEL) {
            $emailTemplateId = getAppStorageValueByKey(ApplicationStorageEnums::SIC_TRAVEL_FOLLOWUP_TEMPLATE_ID);
        }

        if (! $emailTemplateId || ! $lead || ! $lead->email) {
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
                    'email' => $lead->email,
                    'name' => "{$lead->first_name} {$lead->last_name}",
                ]],
                'replyTo' => ['name' => getAppStorageValueByKey(ApplicationStorageEnums::CAR_DISPLAY_NAME), 'email' => getAppStorageValueByKey(ApplicationStorageEnums::CAR_EMAIL_REPLY_TO)],
                'templateId' => (int) $emailTemplateId,
                'params' => [
                    'requestAdvisorLink' => $quoteType?->ecomUrl().$lead->uuid.'/?assignAdvisor=true',
                    strtolower($quoteType?->value).'QuoteLink' => $quoteType?->ecomUrl().$lead->uuid.'/?IA=true',
                    strtolower($quoteType?->value).'QuoteId' => $lead->code,
                    'email' => $lead->email,
                    'clientFullName' => "{$lead->first_name} {$lead->last_name}",
                    ...$extraParams,
                ],
            ];

            if ($quoteType === QuoteTypes::TRAVEL) {
                $body['cc'] = $this->getAdditionalEmails(getAppStorageValueByKey(ApplicationStorageEnums::SIC_TRAVEL_EMAIL_CC));
                $body['replyTo'] = ['email' => getAppStorageValueByKey(ApplicationStorageEnums::TRAVEL_EMAIL_REPLY_TO), 'name' => 'InsuranceMarket.ae'];
            }

            $response = Http::withHeaders($headers)
                ->timeout(config('constants.LMS_EMAILS_TIMEOUT'))
                ->retry(3, 90000)
                ->post(config('constants.SIB_URL'), $body);

            info('SICFollowupEmail ---- Request Sent '.$lead->email);

            $responseCode = $response->status();
            if ($responseCode == 200 || $responseCode == 201) {
                info('SICFollowupEmail ---- | Response Code: '.$responseCode.' | Response Received  : '.json_encode($response->object()).'--'.$lead->email);
            }
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            Log::error(sprintf('SICFollowupEmail failed: Brevo API call failed for %s | Exception: %s', $lead->email, $ex->getMessage()));
        }

        return $responseCode;
    }

    private function buildPlansEmailData($healthQuote, $plans, $previousAdvisor, $request, $emailTemplateId)
    {
        $advisor = User::find($healthQuote->advisor_id);
        $insurerPlans = [];
        foreach ($plans as $plan) {
            $premium = 0;
            $discountPremium = 0;
            if (isset($plan->ratesPerCopay) && ! empty($plan->ratesPerCopay)) {
                foreach ($plan->ratesPerCopay as $rate) {
                    if (isset($rate->premium)) {
                        $premium = $rate->premium;
                        $discountPremium = $rate->discountPremium;
                        break;
                    }
                }
            }
            $regionCoverText = null;
            $annualLimitText = null;
            $medicineText = null;
            $outpatientConsultationText = null;
            if (isset($plan->benefits->regionCover) && ! empty($plan->benefits->regionCover)) {
                foreach ($plan->benefits->regionCover as $regionCover) {
                    $regionCoverText = $regionCover->value;
                    break;
                }
            }
            if (isset($plan->benefits->feature) && ! empty($plan->benefits->feature)) {
                foreach ($plan->benefits->feature as $annualLimit) {
                    $annualLimitText = $annualLimit->text;
                    break;
                }
            }
            if (isset($plan->benefits->outpatient) && ! empty($plan->benefits->outpatient)) {
                foreach ($plan->benefits->outpatient as $outPatient) {
                    if ($outPatient->code === 'medicine') {
                        $medicineText = $outPatient->value;
                    }
                }
            }
            if (isset($plan->benefits->feature) && ! empty($plan->benefits->feature)) {
                foreach ($plan->benefits->feature as $outpatientConsultation) {
                    if ($outpatientConsultation->code === 'outpatientConsultation') {
                        $outpatientConsultationText = $outpatientConsultation->value;
                    }
                }
            }

            $insurerPlans[] = [
                'planCode' => $plan->eligibilityName ? $plan->eligibilityName : 'N/A',
                'eligibilityName' => $plan->eligibilityName ? $plan->eligibilityName : 'N/A',
                'name' => $plan->providerName ? $plan->providerName : 'N/A',
                'total' => $discountPremium ? number_format($discountPremium, 2) : '',
                'providerCode' => strtolower($plan->providerCode),
                'planBenefit' => [
                    'annualLimit' => ['text' => $annualLimitText],
                    'regionsCovered' => ['text' => $regionCoverText],
                    'medicine' => ['text' => $medicineText],
                    'outpatientConsultation' => ['text' => $outpatientConsultationText],
                ],
                'buyNowLink' => $this->getPlanBuyNowLink($plan, $healthQuote->uuid),
                'buynowURL' => $this->getPlanBuyNowLink($plan, $healthQuote->uuid),
            ];
        }

        $emailData = $this->buildCommonEmailData($healthQuote, $advisor, $previousAdvisor, $request, $emailTemplateId);
        $emailData->plans = $insurerPlans;
        $emailData->totalPlans = count($insurerPlans);
        $emailData->isReAssignment = ! empty($previousAdvisor);
        $emailData->isRenewal = true;
        $emailData->policyNumber = $healthQuote->previous_quote_policy_number;
        $carbonDate = Carbon::parse($healthQuote->previous_policy_expiry_date)->format('jS F Y');
        $emailData->renewalDueDate = $carbonDate;

        return $emailData;
    }

    private function buildCommonEmailData($healthQuote, $advisor, $previousAdvisor, $request, $emailTemplateId)
    {
        $whatsAppNumber = ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '';

        $isRevivalLead = $healthQuote->source == LeadSourceEnum::REVIVAL || $healthQuote->source == LeadSourceEnum::REVIVAL_PAID || $healthQuote->source == LeadSourceEnum::REVIVAL_REPLIED;
        $currentInsurer = null;
        if (isset($healthQuote->currently_insured_with_id)) {
            $currentInsurer = InsuranceProvider::find($healthQuote->currently_insured_with_id);
        }

        return (object) [
            'clientFullName' => $healthQuote->first_name.' '.$healthQuote->last_name,
            'customerName' => $healthQuote->first_name.' '.$healthQuote->last_name,
            'customerEmail' => $healthQuote->email,
            'customerId' => $request->customer_id,
            'mobilePhone' => (! empty($advisor->mobile_no) ? formatMobileNoDisplay($advisor->mobile_no) : ''),
            'whatsAppNumber' => $whatsAppNumber,
            'landLine' => (! empty($advisor->landline_no) ? formatLandlineDisplay($advisor->landline_no) : ''),
            'advisorDetails' => [
                'name' => (! empty($advisor->name) ? $advisor->name : ''),
                'email' => (! empty($advisor->email) ? $advisor->email : ''),
                'mobileNo' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
                'landlineNo' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            ],
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'healthQuoteId' => $healthQuote->code,
            'quoteId' => $healthQuote->code,
            'quoteTypeId' => QuoteTypeId::Health,
            'currentInsurer' => $currentInsurer ? $currentInsurer->text : null,
            'quotePlanLink' => url(config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$healthQuote->uuid.($isRevivalLead ? '?dla=true' : '')), // DLA = Disable Lead Assignment
            'requestAdvisorLink' => url(config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$healthQuote->uuid.'/?assignAdvisor=true'),
            'assignmentType' => getAssignmentTypeText($healthQuote->assignment_type),
            'previousAdvisorName' => ! empty($previousAdvisor) ? $previousAdvisor->name : '',
            'previousAdvisorStatus' => ! empty($previousAdvisor) ? UserStatusEnum::getUserStatusText($previousAdvisor->status) : '',
            'isReAssignment' => ! empty($previousAdvisor),
            'templateId' => $emailTemplateId,
        ];
    }

    public function buildEmailData($lead, $plans, $previousAdvisor, $request, $emailTemplateId)
    {
        if (isset($plans) && is_array($plans)) {
            return $this->buildPlansEmailData($lead, $plans, $previousAdvisor, $request, $emailTemplateId);
        } else {
            $advisor = User::where('id', $lead->advisor_id)->first();

            return $this->buildCommonEmailData($lead, $advisor, $previousAdvisor, $request, $emailTemplateId);
        }
    }

    private function getPlanBuyNowLink($plan, $uuid)
    {
        $buyNowLink = url(config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$uuid.'/payment', ['providerCode' => $plan->providerCode, 'planId' => $plan->id, 'selectedCopayId' => $plan->selectedCopayId]);

        return $buyNowLink;
    }

    public function buildDedicatedTravelEmailData($lead, $quoteType)
    {
        return [
            'customerEmail' => $lead->email,
            'customerName' => "{$lead->first_name} {$lead->last_name}",
            'customerMobile' => (! empty($lead->mobile_no) ? $lead->mobile_no : ''),
            'instantAlfredLink' => $quoteType->quoteLink($lead->uuid, ['IA' => 'true']),
            'quoteUUID' => $lead->uuid,
            'requestForAdvisor' => $quoteType->quoteLink($lead->uuid, ['assignAdvisor' => 'true']),
            'quoteTypeId' => $quoteType->id(),
            'refID' => $lead->code,
            'whatsappConsent' => getWhatsappConsent($quoteType, $lead->uuid),
            'workflowType' => WorkflowTypeEnum::TRAVEL_SIC_FOLLOWUPS,
        ];
    }

    public function sendSICDedicatedEmail($lead, $quoteType)
    {
        $url = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_TRAVEL_FLLOWUP_DEDICATED_WORKFLOW_URL);
        $sicDedicatedEmailPayload = $this->buildDedicatedTravelEmailData($lead, $quoteType);
        info("sendSICDedicatedEmail - Sending webhook request to: {$url} with Ref-ID: {$lead->uuid} | Time:".now());
        app(BirdService::class)->triggerWebHookRequest($url, (object) $sicDedicatedEmailPayload);
        info("sendSICDedicatedEmail - Webhook request sent to: {$url} with Ref-ID: {$lead->uuid} | Time:".now());
    }

    private function encodeUrl($url)
    {
        $fileName = basename($url);
        $encodedFileName = urlencode($fileName);

        return str_replace($fileName, $encodedFileName, $url);
    }

    public function sendApplyNowEmail($emailData, bool $sendToAdvisorOnly = false)
    {
        $body = [
            'to' => [[
                'email' => $emailData->email,
                'name' => $emailData->customerName,
            ]],
            'templateId' => (int) getAppStorageValueByKey(ApplicationStorageEnums::HEALTH_APPLY_NOW_EMAIL_TEMPLATE_ID),
            'params' => $emailData,
            'tags' => ['health-apply-now'],
        ];

        if (property_exists($emailData, 'advisorDetails')) {
            if ($sendToAdvisorOnly) {
                $body['to'] = [[
                    'email' => $emailData->advisorDetails['email'],
                    'name' => $emailData->advisorDetails['name'],
                ]];
            } else {
                $body['replyTo'] = ['name' => $emailData->advisorDetails['name'], 'email' => $emailData->advisorDetails['email']];
                $body['cc'] = [[
                    'email' => $emailData->advisorDetails['email'],
                    'name' => $emailData->advisorDetails['name'],
                ]];
            }
        }

        ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->email);

        return $responseCode;
    }

    public function sendWhatsappNotificationToCustomer($quote, $advisorId = null)
    {

        $advisor = User::where('id', $advisorId)->first();
        $payload = [
            'customerEmail' => $quote->email,
            'customerName' => $quote->first_name.' '.$quote->last_name,
            'customerMobile' => (! empty($quote->mobile_no) ? formatMobileNo($quote->mobile_no) : ''),
            'advisor' => $advisor ?? null,
            'advisorName' => $advisor?->name ?? '',
            'advisorEmail' => $advisor?->email ?? '',
            'advisorLandLine' => (! empty($advisor?->landline_no) ? $advisor->landline_no : ''),
            'advisorMobilePhone' => (! empty($advisor?->mobile_no) ? $advisor->mobile_no : ''),
            'advisorWhatsAppNumber' => ! empty($advisor?->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'advisorMobileNoWithoutSpaces' => (! empty($advisor?->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'quoteUID' => $quote->uuid,
            'refID' => $quote->code,
            'CarMake' => $quote->carMake->text ?? null,
            'CarModel' => $quote->carModel->text ?? null,
            'workflowType' => workflowTypeEnum::WHATSAPP_NOTIFICATION_TO_CUSTOMER_NO_PLANS,
        ];
        $customerWANotificationWorkflow = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_WHATSAPP_NO_PLANS_ASSIGNMENT_WORKFLOW);
        if (! empty($customerWANotificationWorkflow)) {
            app(BirdService::class)->triggerWebHookRequest($customerWANotificationWorkflow, (object) $payload);
            info('sendWhatsappNotificationToCustomer - Webhook request sent to: '.$customerWANotificationWorkflow.' with Ref-ID: '.$quote->uuid.' | Time:'.now());
        } else {
            info('sendWhatsappNotificationToCustomer - Webhook URL not found in storage with Ref-ID:'.$quote->uuid.' | Time:'.now());
        }
    }

    public function getBccAdditionalEmails(QuoteTypes $quoteType): array
    {
        $bccAdditional = [];

        $storageEnum = match ($quoteType) {
            QuoteTypes::CAR => ApplicationStorageEnums::LMS_INTRO_EMAIL_BCC,
            QuoteTypes::BIKE => ApplicationStorageEnums::LMS_INTRO_BIKE_EMAIL_BCC,
            default => null,
        };

        if ($storageEnum === null) {
            return $bccAdditional;
        }

        $additionalBcc = ApplicationStorage::where('key_name', $storageEnum)->first();

        if ($additionalBcc === null) {
            return $bccAdditional;
        }

        foreach (explode(',', $additionalBcc->value) as $additionalContact) {
            $email = trim($additionalContact);
            if (! empty($email)) {
                $bccAdditional[] = [
                    'email' => $email,
                ];
            }
        }

        return $bccAdditional;
    }
}
