<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DefaultAdvisorEnum;
use App\Enums\EnvEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\ProcessStatusCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Facades\Capi;
use App\Jobs\OCAHealthFollowupEmailJob;
use App\Jobs\UpdateSendPolicySubjectJob;
use App\Models\ApplicationStorage;
use App\Models\Customer;
use App\Models\HealthQuote;
use App\Models\InsuranceProvider;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

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
            LoggerService::info(self::class." - Going to stream email attachments for uuid: {$quoteId}");
            $attachments[] = [
                'content' => chunk_split(base64_encode($emailData->pdfAttachment->pdf->stream())),
                'name' => $emailData->pdfAttachment->name,
            ];
            LoggerService::info(self::class." - Streamed email attachments for uuid: {$quoteId}");
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
                    // LoggerService::info("{$fnName} ---- Mail Request is Sending");
                    $sender = $body['sender'] ?? null;
                    $replyTo = $body['replyTo'] ?? null;
                    $to = $body['to'] ?? null;
                    $sender != null && LoggerService::info('Mail Request sender details ----- '.json_encode($sender));
                    $replyTo != null && LoggerService::info('Mail Request replyTo details ----- '.json_encode($replyTo));
                    $to != null && LoggerService::info('Mail Request to details ----- '.json_encode($to));
                })
                ->timeout(config('constants.LMS_EMAILS_TIMEOUT'))
                // ->retry(3, 90000)
                ->post($this->url, $body);

            $result = [
                'headers' => $headers,
                'ok' => $response->ok(),
                'code' => $response->status(),
                'object' => $response->object(),
                'respBody' => $response->body(),
                'response' => "{$response->status()} {$response->body()}",
                'sent' => 0,
            ];

            if ($result['code'] == 201) {
                $result['sent'] = 1;
                LoggerService::info("{$fnName} ---- Mail Sent Successfully");
            } else {
                LoggerService::error("{$fnName} ---- Mail Sent Failed: ", ['result' => $result, 'response' => $response]);
            }

            return $result;
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $responseDetail = "{$fnName}: Code/Message: {$responseCode}/{$ex->getMessage()}";
            $response = json_encode($ex->getCode().' '.$ex->getMessage());

            LoggerService::error($responseDetail);

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
            LoggerService::error($responseDetail);
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
                    $value = EmailValidationService::sanitize($additionalContact->value ?? '');
                    if ($value !== null) {
                        $ccAdditional[] = [
                            'email' => $value,
                            'name' => $emailData->customerName,
                        ];
                    }
                }
            }

            $body['cc'] = array_merge($ccAdditional, $ccAdvisor);

            LoggerService::info('OCB Email CC Additional Contacts: ', extra: [
                'ccAdditional' => $ccAdditional,
                'ccAdvisor' => $ccAdvisor,
                'cc' => $body['cc'],
            ]);

            ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $quoteCdbId = isset($emailData->quoteCdbId) ? $emailData->quoteCdbId : null;
            $responseDetail = 'SIB Send Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' CustomerEmail: '.$emailData->customerEmail.' QuoteCdbId: '.$quoteCdbId.' Class: '.get_class();
            LoggerService::info($responseDetail);
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
            $isEmailSent = 0;
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        return $responseCode;
    }

    public function sendRenewalsOcbEmail($emailTemplateId, $emailData, $tag)
    {
        try {
            LoggerService::info(self::class.' - sendRenewalsOcbEmail - Starting email sending process', extra: [
                'email_template_id' => $emailTemplateId,
                'tag' => $tag,
            ]);

            $attachments = [];
            $tag = $this->appEnv == EnvEnum::PRODUCTION ? $tag : $this->appEnv.'-'.$tag;

            // Clean document URLs to avoid attachment name errors brevo send attachment error
            $cleanedDocumentUrls = [];
            if (! empty($emailData->documentUrl) && $emailData->documentUrl !== null) {
                $documentUrls = is_array($emailData->documentUrl) ? $emailData->documentUrl : [$emailData->documentUrl];
                foreach ($documentUrls as $documentURL) {
                    if (! empty($documentURL)) {
                        $cleanUrl = strtok($documentURL, '?');
                        $cleanUrl = preg_replace('/\s+$/m', '', $cleanUrl);
                        $cleanedDocumentUrls[] = $cleanUrl;
                    }
                }
            }

            $emailAttachments = ! empty($cleanedDocumentUrls) ? $cleanedDocumentUrls : null;

            if ($emailAttachments) {
                LoggerService::info(self::class.' - sendRenewalsOcbEmail - Processing email attachments', extra: [
                    'attachments_count' => count($emailAttachments),
                ]);

                foreach ($emailAttachments as $emailAttachment) {
                    $attachments[] = [
                        'url' => $emailAttachment,
                        'name' => basename($emailAttachment),
                    ];
                }
            }

            if (! empty($emailData->pdfAttachment->pdf) && ! empty($emailData->pdfAttachment->name)) {
                LoggerService::info(self::class.' - sendRenewalsOcbEmail - Processing PDF attachment', extra: [
                    'pdf_name' => $emailData->pdfAttachment->name,
                ]);

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
                'attachment' => ! empty($attachments) ? $attachments : null,
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
                    $value = EmailValidationService::sanitize($additionalContact->value ?? '');
                    if ($value !== null) {
                        $ccAdditional[] = [
                            'email' => $value,
                            'name' => $emailData->customerName,
                        ];
                    }
                }
            }

            $body['cc'] = array_merge($ccAdditional, $ccAdvisor);

            LoggerService::info(self::class.' - sendRenewalsOcbEmail - Calling sendMail method', extra: [
                'ccAdditional' => $ccAdditional,
                'ccAdvisor' => $ccAdvisor,
                'cc' => $body['cc'],
            ]);

            ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);

            LoggerService::info(self::class.' - sendRenewalsOcbEmail - Email sent successfully', extra: [
                'response_code' => $responseCode,
                'is_email_sent' => $isEmailSent,
            ]);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $quoteCdbId = isset($emailData->carQuoteId) ? $emailData->carQuoteId : null;

            LoggerService::warning(self::class.' - sendRenewalsOcbEmail - Email sending failed with exception', extra: [
                'error_code' => $responseCode,
                'error_message' => $ex->getMessage(),
            ]);

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
            $client = new Client;
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
            LoggerService::error($responseDetail);
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

            LoggerService::info('sendMyAlfredWelcomeEmail  , emailTemplateId: '.$emailTemplateId);
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
            LoggerService::error($responseDetail);
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
            LoggerService::info("sendLMSIntroEmail ---- Tag : {$tag} for ID : {$quoteId}");
            $subjectEnvTag = $this->appEnv == EnvEnum::PRODUCTION ? '' : $this->appEnv.' - ';
            if ($emailData->customerEmail === '0' || $emailData->customerEmail === 0) {
                LoggerService::info("Customer email is missing or invalid for ID : {$quoteId}");

                return false;
            }
            $attachments = $this->getEmailAttachments($emailData, $quoteId);
            $bcc = [];
            if ($emailData->advisorEmail) {
                LoggerService::info("Adding BCC for Advisor Email: {$emailData->advisorEmail} and Name: {$emailData->advisorName}");
                $bcc[] = [
                    'email' => $emailData->advisorEmail,
                    'name' => $emailData->advisorName,
                ];
            } else {
                LoggerService::info("No Advisor Email found for ID : {$quoteId}");
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

            LoggerService::info(self::class." - Going to call final sendMail for uuid: {$quoteId}");
            ['code' => $responseCode, 'response' => $response, 'sent' => $isEmailSent] = $this->sendMail($body);
            LoggerService::info(self::class." - Email response code {$responseCode} received for uuid: {$quoteId}");
        } catch (Exception $ex) {
            $isEmailSent = false;
            $responseCode = $ex->getCode();
            $responseDetail = 'SIB Send sendLMSIntroEmail: Code/Message: '.$responseCode.'/'.$ex->getMessage().' '.$quoteId;
            LoggerService::error($responseDetail);
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
            LoggerService::info('sendRMIntroEmail: Health quote is Application Pending, skipping RM Intro Email for uuid: '.$quoteUuid);

            return;
        }
        if ($healthQuote->isSuppressIntroEmail()) {
            LoggerService::info('sendRMIntroEmail: Health quote is suppressed, skipping RM Intro Email');

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
        LoggerService::info('Params for intro email are : '.json_encode($dataArr));
        $response = Capi::request('/api/v1-send-health-quote-plan-email', 'post', $dataArr);
        if ($response && isset($response->status)) {
            $msg = '';
            if (isset($response->msg)) {
                $msg = $response->msg;
            }
            LoggerService::info('RM Intro Email Error for HEA-'.$quoteUuid.' - Response Code: '.$response->status.' - Message: '.$msg);
        } elseif ($response && isset($response->message)) {

            LoggerService::info('RM Intro Email Triggered to CAPI for HEA-'.$quoteUuid.' - Message: '.$response->message);
            $healthAutoFollowupSwitch = ApplicationStorage::where('key_name', ApplicationStorageEnums::HEALTH_AUTOMATED_FOLLOWUPS_SWITCH)->first();
            // Send Automated Followup Email Job if Health Auto-Followups is enabled.
            if ($healthAutoFollowupSwitch && $healthAutoFollowupSwitch->value == 1) {
                $delayDays = isLeadSic($quoteUuid) ? 3 : 2;
                OCAHealthFollowupEmailJob::dispatch($quoteUuid)->delay(Carbon::now()->addMinutes($delayDays));
                LoggerService::info('OCAHealthFollowupEmailJob dispatched for HEA-'.$quoteUuid.' - Time: '.now());
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

            LoggerService::info("sendNonAdvisorIntroEmail  , emailTemplateId: {$emailTemplateId} with QuoteId: {$quoteId}");
            $tag = $appEnv == EnvEnum::PRODUCTION ? $tag : $appEnv.'-'.$tag;
            if ($emailData->customerEmail === '0' || $emailData->customerEmail === 0) {
                LoggerService::info("Customer email is missing or invalid for QuoteId: {$quoteId}");

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
            LoggerService::error($responseDetail);
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        return $responseCode;
    }

    public function sendActivityAlertEmail($user)
    {
        $emailEnable = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::ADVISOR_ONLINE_NOTIFICATION_EMAILS_ENABLE)->first();
        if ($emailEnable && $emailEnable->value == 0) {
            LoggerService::info('sendActivityAlertEmail is Disable');

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
        LoggerService::info('Policy documents email sending started for quote code: '.$emailData->code, extra: [
            'template_id' => $emailData->emailTemplateId,
        ]);

        $isEmailSent = 0;
        $messageId = null;
        $subject = $emailData->clientFullName.'\'s Savings with Alfred - '.$emailData->code;

        try {
            LoggerService::info('Processing attachments for policy documents for quote code: '.$emailData->code);

            $websiteURL = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/';
            $documents = $emailData->quoteDocuments;
            $attachments = [];
            if (! empty($documents)) {
                foreach ($documents as $document) {
                    $path = ! empty($document->watermarked_doc_url) ? $document->watermarked_doc_url : $document->doc_url;
                    if (empty($path)) {
                        LoggerService::warning('Main lead document not found for quote code: '.$emailData->code, extra: [
                            'document_id' => $document->id,
                            'watermarked_url' => $document->watermarked_doc_url ?? null,
                            'doc_url' => $document->doc_url ?? null,
                        ]);

                        continue;
                    }
                    $documentExtension = app(QuoteDocumentService::class)->getDocumentExtension($path);
                    $documentName = 'InsuranceMarket.ae™ '.$document->document_type_text.' for Policy Number '.$emailData->policy_number.'.'.$documentExtension;
                    $documentURL = app(QuoteDocumentService::class)->getDocumentUrl($path);
                    if ($documentURL) {
                        $attachments[] = [
                            'url' => $documentURL,
                            'name' => $documentName,
                        ];
                    }
                }
            }

            if (is_array($emailData->handBookDocuments) && ! empty($emailData->handBookDocuments)) {
                $attachments = array_merge($attachments, $emailData->handBookDocuments);
            }

            if ($emailData->quoteTypeId == QuoteTypeId::Savings) {
                if (! empty($emailData->policyWordingHandbook)) {
                    $policyHandbookUrl = str_starts_with($emailData->policyWordingHandbook['watermarked_doc_url'], 'https:')
                                ? $emailData->policyWordingHandbook['watermarked_doc_url']
                                : config('constants.AZURE_IM_STORAGE_URL').$emailData->policyWordingHandbook['watermarked_doc_url'];
                    $attachments[] = [
                        'url' => $this->encodeUrl($policyHandbookUrl),
                        'name' => 'InsuranceMarket.ae™ '.$emailData->policyWordingHandbook['document_type_text'].' for Policy Number '.$emailData->policy_number.'.'.pathinfo($emailData->policyWordingHandbook['watermarked_doc_url'], PATHINFO_EXTENSION),
                    ];
                }

                $bodyData['sender'] = [
                    'email' => 'alfred@notify.insurancemarket.ae',
                    'name' => $emailData->advisorName,
                ];

                $bodyData['subject'] = $this->appEnv == EnvEnum::PRODUCTION ? $subject : $this->appEnv.' - '.$subject;

                $newLeadPool = ApplicationStorage::where('key_name', ApplicationStorageEnums::NEW_LEAD_POOL_BCC)->first();
                $bodyData['bcc'][] = ['email' => $newLeadPool->value];
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
                    'refID' => $emailData->code,
                    'isHealthAUH' => $emailData->isHealthAUH,
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

            if ($emailData->advisorEmail) {
                $bodyData['cc'][] = [
                    'email' => $emailData->advisorEmail,
                    'name' => $emailData->advisorName,
                ];

                if ($emailData->quoteTypeId == QuoteTypeId::Savings) {
                    if ($this->appEnv != EnvEnum::PRODUCTION) {
                        $bodyData['replyTo'] = [
                            'email' => 'test.emails@insurancemarket.ae',
                            'name' => 'test.emails@insurancemarket.ae',
                        ];
                    } else {
                        $bodyData['replyTo'] = [
                            'email' => $emailData->advisorEmail,
                            'name' => $emailData->advisorName,
                        ];
                        $bodyData['cc'][] = [
                            'email' => 'life@insurancemarket.ae',
                            'name' => 'life@insurancemarket.ae',
                        ];
                    }
                }
            }

            if ($this->appEnv == EnvEnum::PRODUCTION && $emailData->quoteTypeId == QuoteTypeId::Travel) {
                $bodyData['bcc'][] = [
                    'email' => getAppStorageValueByKey(ApplicationStorageEnums::TRAVEL_ENQUIRIES_EMAIL),
                ];
            }

            $body = json_encode($bodyData, JSON_UNESCAPED_SLASHES);
            LoggerService::info('Policy documents email payload for quote code: '.$emailData->code.' ---- body '.$body);

            $client = new Client;
            $clientResponse = $client->post(
                config('constants.SIB_URL'),
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 20,
                ]
            );

            $responseBody = trim($clientResponse->getBody()->getContents());
            $message = json_decode($responseBody);
            if (isset($message->messageId)) {
                $messageId = $message->messageId;
            }
            $responseCode = $clientResponse->getStatusCode();
            $response = "{$responseCode} {$responseBody}";
            $isEmailSent = 1;

            LoggerService::info('Policy documents email sent successfully for quote code: '.$emailData->code, extra: [
                'message_id' => $messageId,
                'response_code' => $responseCode,
            ]);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $response = "{$responseCode} {$ex->getMessage()}";
            LoggerService::error('Failed to send policy documents email for quote code: '.$emailData->code, extra: [
                'error_code' => $responseCode,
                'error_message' => $ex->getMessage(),
                'message_id' => $messageId ?? null,
            ]);
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);
        if ($emailData->quoteTypeId == QuoteTypeId::Savings) {
            $status = $responseCode == 201 ? ProcessStatusCode::SENT : ProcessStatusCode::FAILED;
            $this->emailStatusService->addEmailStatus($emailData, $messageId, $subject, $status, 'Send Policy to Customer');
        }

        return $responseCode;
    }

    public function sendSICNotificationToAdvisor($lead, $user, $quoteType)
    {
        LoggerService::info('sendSICNotificationToAdvisor ---- Start');

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
            LoggerService::error($responseDetail);
        }
        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $user->email);

        return $responseCode;
    }

    public function sendUpdateToCustomerEmail($emailTemplateId, $emailData, $tag, $quoteTypeId)
    {
        LoggerService::info('fn:sendUpdateToCustomerEmail - SendEmailCustomerService, email sending started', extra: [
            'emailTemplateId' => $emailTemplateId,
            'tag' => $tag,
        ]);

        $messageId = null;
        $response = null;
        $isEmailSent = 0;
        $responseCode = 0;
        $subject = $emailData->customerName.'\'s Savings with Alfred - '.$emailData->code;

        try {
            LoggerService::info('fn: sendUpdateEmail, email sending started. emailTemplateId: '.$emailTemplateId.', tag: '.$tag);

            $tag = $this->appEnv == EnvEnum::PRODUCTION ? $tag : $this->appEnv.'-'.$tag;

            $headers = [
                'Accept' => 'application/json',
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ];

            $documents = $emailData->documents;
            $attachments = [];
            if (! empty($documents)) {
                foreach ($documents as $document) {
                    if (isset($document['isPolicyWording']) && $document['isPolicyWording']) {
                        $attachments[] = [
                            'url' => $document['doc_url'],
                            'name' => $document['document_type_text'],
                        ];

                        continue;
                    }
                    $path = ! empty($document['watermarked_doc_url']) ? $document['watermarked_doc_url'] : $document['doc_url'];
                    if (empty($path)) {
                        LoggerService::warning("Send lead document not found for document ID: {$document['id']} Error Code: 404");

                        continue;
                    }
                    $documentExtension = app(QuoteDocumentService::class)->getDocumentExtension($path);
                    $documentName = 'InsuranceMarket.ae™ '.$document['document_type_text'].' for Policy Number '.$emailData->policyNumber.' - '.$emailData->code.'.'.$documentExtension;
                    $documentURL = app(QuoteDocumentService::class)->getDocumentUrl($path);
                    if ($documentURL) {
                        $attachments[] = [
                            'url' => $documentURL,
                            'name' => $documentName,
                        ];
                    }
                }
            }

            if ($quoteTypeId == QuoteTypeId::Savings) {
                $senderEmail = 'alfred@notify.insurancemarket.ae';
            } else {
                $senderEmail = getAppStorageValueByKey(ApplicationStorageEnums::SEND_UPDATE_EMAIL);
            }
            LoggerService::info('Send Update email and templateId fetched', extra: [
                'email' => $senderEmail,
                'templateId' => $emailTemplateId,
            ]);

            $body = [
                'sender' => [
                    'email' => $senderEmail,
                    'name' => 'InsuranceMarket.ae',
                ],
                'to' => [[
                    'email' => $emailData->customerEmail,
                    'name' => $emailData->customerName,
                ]],
                'templateId' => (int) $emailTemplateId,
                'params' => $emailData,
                'tags' => [
                    $tag,
                ],
            ];

            if (count($attachments) > 0) {
                $body['attachment'] = $attachments;
            }

            $checkIsHealthOrGroupMedical = $quoteTypeId == QuoteTypeId::Health || isset($emailData->isGroupMedical);

            $ebServiceTeam = [];
            if ($checkIsHealthOrGroupMedical) {
                $ebServiceEmail = getAppStorageValueByKey(ApplicationStorageEnums::IM_EB_SERVICE_TEAM_EMAIL);
                LoggerService::info('IM EB Service team email fetched', extra: ['email' => $ebServiceEmail]);
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
            LoggerService::info('Send Policy Update email fetched', extra: ['email' => $sendPolicyUpdateEmail]);

            $body['bcc'] = [[
                'email' => $sendPolicyUpdateEmail,
            ]];

            if ($quoteTypeId == QuoteTypeId::Savings) {
                $body['subject'] = $this->appEnv == EnvEnum::PRODUCTION ? $subject : $this->appEnv.' - '.$subject;

                $newLeadPool = ApplicationStorage::where('key_name', ApplicationStorageEnums::NEW_LEAD_POOL_BCC)->first();
                $body['bcc'][] = ['email' => $newLeadPool->value];

                $body['cc'][] = [
                    'email' => 'life@insurancemarket.ae',
                    'name' => 'life@insurancemarket.ae',
                ];

                $body['replyTo'] = [
                    'email' => 'life@insurancemarket.ae',
                    'name' => 'life@insurancemarket.ae',
                ];
            }

            if ($this->appEnv == EnvEnum::PRODUCTION && $quoteTypeId == QuoteTypeId::Travel) {
                $body['bcc'][] = [
                    'email' => getAppStorageValueByKey(ApplicationStorageEnums::TRAVEL_ENQUIRIES_EMAIL),
                ];
            }

            LoggerService::info('Send Policy Update email payload', extra: ['payload' => json_encode($body)]);

            $client = new Client;
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
                $messageId = $message->messageId;
                LoggerService::info('fn:sendUpdateToCustomerEmail, email sending completed', extra: ['messageId' => $message->messageId]);
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
            $quoteCdbId = isset($emailData->code) ? $emailData->code : null;
            LoggerService::error('Send Update Email failed', extra: [
                'Code/Message' => $responseCode,
                'CustomerEmail' => $emailData->customerEmail,
                'QuoteCdbId' => $quoteCdbId,
                'Class' => get_class(),
                'line' => $ex->getLine(),
            ], exception: $ex);
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
            $isEmailSent = 0;
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);
        if ($quoteTypeId == QuoteTypeId::Savings) {
            $status = $responseCode == 201 ? ProcessStatusCode::SENT : ProcessStatusCode::FAILED;
            $this->emailStatusService->addEmailStatus($emailData, $messageId, $subject, $status, 'Send Update to Customer');
        }

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
                LoggerService::info('Advisor Email or Data Not Found');

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
            $client = new Client;
            $clientRequest = $client->post(
                $this->url,
                [
                    'headers' => $headers,
                    'body' => $body,
                    'timeout' => 10,
                ]
            );
            $responseCode = $clientRequest->getStatusCode();
            LoggerService::info('sendPaymentNotificationEmail ---- response object : '.json_encode($clientRequest->getBody()->getContents()));
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $responseDetail = 'sendPaymentNotificationEmail: Code/Message: '.$responseCode.'/'.$ex->getMessage();
            LoggerService::error($responseDetail);
        }
    }

    public function sendingAlfredFollowupEmail($customer)
    {
        $emailTemplateId = ApplicationStorage::where('key_name', ApplicationStorageEnums::ALFRED_FOLLOWUP_TEMPLATE)->first();

        $apiKey = config('constants.SENDINBLUE_KEY');
        $url = config('constants.SIB_URL');
        try {
            LoggerService::info('AlfredFollowUpEmail Starting');
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

            LoggerService::info('AlfredFollowUpEmail ---- Request Sent '.$customer->email);

            $responseCode = $response->status();
            if ($responseCode == 200 || $responseCode == 201) {
                $isCustomer = Customer::where('id', $customer->customer_id)->first();
                if ($isCustomer->campaign_followups < 3) {
                    $isCustomer->increment('campaign_followups');
                    $isCustomer->last_followup_sent_at = Carbon::now();
                    $isCustomer->save();
                }
            }

            LoggerService::info('AlfredFollowUpEmail ---- Received Code : '.$responseCode.' '.$customer->email);
            LoggerService::info('AlfredFollowUpEmail ---- response object : '.json_encode($response->object()).'--'.$customer->email);
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            LoggerService::error($responseCode);
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
                // ->retry(3, 90000)
                ->post(config('constants.SIB_URL'), $body);

            LoggerService::info('SICFollowupEmail ---- Request Sent '.$lead->email);

            $responseCode = $response->status();
            if ($responseCode == 200 || $responseCode == 201) {
                LoggerService::info('SICFollowupEmail ---- | Response Code: '.$responseCode.' | Response Received  : '.json_encode($response->object()).'--'.$lead->email);
            }
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            LoggerService::error(sprintf('SICFollowupEmail failed: Brevo API call failed for %s | Exception: %s', $lead->email, $ex->getMessage()));
        }

        return $responseCode;
    }

    private function buildPlansEmailData($healthQuote, $plans, $previousAdvisor, $request, $emailTemplateId)
    {
        $advisor = User::find($healthQuote->advisor_id);
        $insurerPlans = [];

        foreach ($plans as $plan) {
            // Safely extract premium data with null checks
            $premium = 0;
            $discountPremium = 0;
            if (isset($plan->ratesPerCopay) && is_array($plan->ratesPerCopay) && ! empty($plan->ratesPerCopay)) {
                foreach ($plan->ratesPerCopay as $rate) {
                    if (isset($rate->premium) && isset($rate->discountPremium)) {
                        $premium = $rate->premium;
                        $discountPremium = $rate->discountPremium;
                        break;
                    }
                }
            }

            // Initialize benefit texts
            $regionCoverText = '';
            $annualLimitText = '';
            $medicineText = '';
            $outpatientConsultationText = '';

            // Get outpatient consultation text from coPayments if available (with proper null safety)
            if (isset($plan->coPayments) && is_array($plan->coPayments) && count($plan->coPayments) > 0) {
                $firstCopay = $plan->coPayments[0] ?? null;
                if ($firstCopay && isset($firstCopay->text)) {
                    $outpatientConsultationText = $firstCopay->text;
                }
            }

            // Extract region cover from benefits
            if (isset($plan->benefits) && isset($plan->benefits->regionCover) && is_array($plan->benefits->regionCover) && ! empty($plan->benefits->regionCover)) {
                foreach ($plan->benefits->regionCover as $regionCover) {
                    if (isset($regionCover->value)) {
                        $regionCoverText = $regionCover->value ?? '';
                        break;
                    }
                }
            }

            // Extract annual limit from features
            if (isset($plan->benefits) && isset($plan->benefits->feature) && is_array($plan->benefits->feature) && ! empty($plan->benefits->feature)) {
                foreach ($plan->benefits->feature as $feature) {
                    if (isset($feature->code) && $feature->code === 'annualLimit') {
                        $annualLimitText = $feature->value ?? $feature->text ?? '';
                        break;
                    }
                }
            }

            // Extract medicine text from outpatient benefits
            if (isset($plan->benefits) && isset($plan->benefits->outpatient) && is_array($plan->benefits->outpatient) && ! empty($plan->benefits->outpatient)) {
                foreach ($plan->benefits->outpatient as $outPatient) {
                    if (isset($outPatient->code) && $outPatient->code === 'medicine') {
                        $medicineText = $outPatient->value ?? '';
                        break;
                    }
                }
            }

            // Build hospital and clinic data from healthNetwork with comprehensive null checks
            $hospitalData = ['count' => 0, 'text' => ''];
            $clinicData = ['count' => 0, 'text' => ''];

            if (isset($plan->healthNetwork) && is_object($plan->healthNetwork)) {
                // Safely get counts
                $hospitalData['count'] = isset($plan->healthNetwork->noOfHospitals) ? (int) $plan->healthNetwork->noOfHospitals : 0;
                $clinicData['count'] = isset($plan->healthNetwork->noOfClinics) ? (int) $plan->healthNetwork->noOfClinics : 0;

                // Build hospital and clinic text from featured facilities
                if (isset($plan->healthNetwork->featuredFacilities) && is_array($plan->healthNetwork->featuredFacilities) && ! empty($plan->healthNetwork->featuredFacilities)) {
                    $hospitals = [];
                    $clinics = [];

                    foreach ($plan->healthNetwork->featuredFacilities as $facility) {
                        if (! isset($facility->type) || ! isset($facility->text)) {
                            continue;
                        }

                        $facilityType = strtoupper(trim($facility->type));
                        $facilityText = trim($facility->text);

                        if ($facilityType === 'HOSPITAL') {
                            $hospitals[] = $facilityText;
                        } elseif ($facilityType === 'CLINIC' || $facilityType === 'CLINC') {
                            $clinics[] = $facilityText;
                        }
                    }

                    $hospitalData['text'] = ! empty($hospitals) ? implode(', ', $hospitals) : '';
                    $clinicData['text'] = ! empty($clinics) ? implode(', ', $clinics) : '';
                }
            }

            // Build the plan array with all null safety checks
            $insurerPlans[] = [
                'id' => $plan->id ?? null,
                'name' => $plan->name ?? 'N/A',
                'providerName' => $plan->providerName ?? 'N/A',
                'eligibilityName' => $plan->eligibilityName ?? 'N/A',
                'planCode' => $plan->planCode ?? 'N/A',
                'providerCode' => isset($plan->providerCode) ? strtolower($plan->providerCode) : '',
                'total' => $discountPremium > 0 ? number_format($discountPremium, 2) : '0.00',
                'planBenefit' => [
                    'annualLimit' => ['text' => $annualLimitText],
                    'outpatientConsultation' => ['text' => $outpatientConsultationText],
                    'medicine' => ['text' => $medicineText],
                    'regionsCovered' => ['text' => $regionCoverText],
                ],
                'hospital' => $hospitalData,
                'clinic' => $clinicData,
                'buyNowLink' => $this->getPlanBuyNowLink($plan, $healthQuote->uuid ?? ''),
                'buynowURL' => $this->getPlanBuyNowLink($plan, $healthQuote->uuid ?? ''),
            ];
        }

        $emailData = $this->buildCommonEmailData($healthQuote, $advisor, $previousAdvisor, $request, $emailTemplateId);
        $emailData->plans = $insurerPlans;
        $emailData->totalPlans = count($insurerPlans);
        $emailData->isReAssignment = ! empty($previousAdvisor);
        $emailData->isRenewal = true;
        $emailData->policyNumber = $healthQuote->previous_quote_policy_number ?? null;

        // Safely handle previous policy expiry date
        $renewalDueDate = '';
        if (isset($healthQuote->previous_policy_expiry_date) && ! empty($healthQuote->previous_policy_expiry_date)) {
            try {
                $carbonDate = Carbon::parse($healthQuote->previous_policy_expiry_date);
                $renewalDueDate = $carbonDate->format('jS F Y');
            } catch (Exception $e) {
                $renewalDueDate = '';
            }
        }
        $emailData->renewalDueDate = $renewalDueDate;

        return $emailData;
    }

    private function buildCommonEmailData($healthQuote, $advisor, $previousAdvisor, $request, $emailTemplateId)
    {
        // Null safety for advisor
        if (! $advisor) {
            $advisor = new \stdClass;
            $advisor->id = null;
            $advisor->name = '';
            $advisor->email = '';
            $advisor->mobile_no = '';
            $advisor->landline_no = '';
        }

        $whatsAppNumber = ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '';
        $mobileNoWithoutSpaces = ! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : '';

        $isRevivalLead = isset($healthQuote->source) && ($healthQuote->source == LeadSourceEnum::REVIVAL || $healthQuote->source == LeadSourceEnum::REVIVAL_PAID || $healthQuote->source == LeadSourceEnum::REVIVAL_REPLIED);
        $currentInsurer = null;
        if (isset($healthQuote->currently_insured_with_id) && ! empty($healthQuote->currently_insured_with_id)) {
            $currentInsurer = InsuranceProvider::find($healthQuote->currently_insured_with_id);
        }

        // Build advisor details with null safety
        $advisorDetails = [
            'id' => $advisor->id ?? null,
            'name' => $advisor->name ?? '',
            'email' => $advisor->email ?? '',
            'landlineNo' => isset($advisor->landline_no) && ! empty($advisor->landline_no) ? formatLandlineDisplay($advisor->landline_no) : '',
            'mobileNo' => isset($advisor->mobile_no) && ! empty($advisor->mobile_no) ? formatMobileNoDisplay($advisor->mobile_no) : '',
            'whatsAppNumber' => $whatsAppNumber,
            'mobileNoWithoutSpaces' => $mobileNoWithoutSpaces,
            'profilePicture' => $advisor->profile_photo_path ?? '',
        ];

        LoggerService::info(self::class.' - buildCommonEmailData - Advisor details built successfully', extra: [
            'advisor_details' => $advisorDetails,
        ]);

        // Build previous advisor details with comprehensive null safety
        $previousAdvisorDetails = [];
        if (! empty($previousAdvisor) && is_object($previousAdvisor)) {
            $prevWhatsAppNumber = isset($previousAdvisor->mobile_no) && ! empty($previousAdvisor->mobile_no) ? formatMobileNo($previousAdvisor->mobile_no) : '';
            $prevMobileNoWithoutSpaces = isset($previousAdvisor->mobile_no) && ! empty($previousAdvisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($previousAdvisor->mobile_no)) : '';

            $previousAdvisorDetails = [
                'id' => $previousAdvisor->id ?? null,
                'name' => $previousAdvisor->name ?? '',
                'email' => $previousAdvisor->email ?? '',
                'landLine' => isset($previousAdvisor->landline_no) && ! empty($previousAdvisor->landline_no) ? formatLandlineDisplay($previousAdvisor->landline_no) : '',
                'mobilePhone' => isset($previousAdvisor->mobile_no) && ! empty($previousAdvisor->mobile_no) ? formatMobileNoDisplay($previousAdvisor->mobile_no) : '',
                'whatsAppNumber' => $prevWhatsAppNumber,
                'mobileNoWithoutSpaces' => $prevMobileNoWithoutSpaces,
                'profilePicture' => $previousAdvisor->profile_photo_path ?? '',
            ];
        }

        // Build customer full name with null safety
        $firstName = $healthQuote->first_name ?? '';
        $lastName = $healthQuote->last_name ?? '';
        $customerFullName = trim($firstName.' '.$lastName);

        // Build quote UUID safely
        $quoteUuid = $healthQuote->uuid ?? '';
        $quoteCode = $healthQuote->code ?? '';

        return (object) [
            'clientFullName' => $customerFullName,
            'customerName' => $customerFullName,
            'customerEmail' => $healthQuote->email ?? '',
            'customerId' => $request->customer_id ?? null,
            'mobilePhone' => isset($advisor->mobile_no) && ! empty($advisor->mobile_no) ? formatMobileNoDisplay($advisor->mobile_no) : '',
            'whatsAppNumber' => $whatsAppNumber,
            'landLine' => isset($advisor->landline_no) && ! empty($advisor->landline_no) ? formatLandlineDisplay($advisor->landline_no) : '',
            'advisorDetails' => $advisorDetails,
            'advisorEmail' => $advisor->email ?? '',
            'advisorName' => $advisor->name ?? '',
            'healthQuoteId' => $quoteCode,
            'quoteId' => $quoteCode,
            'quoteTypeId' => QuoteTypeId::Health,
            'currentInsurer' => $currentInsurer && isset($currentInsurer->text) ? $currentInsurer->text : null,
            'emirateOfYourVisaId' => $healthQuote->emirate_of_your_visa_id ?? null,
            'quotePlanLink' => ! empty($quoteUuid) ? url(config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$quoteUuid.($isRevivalLead ? '?dla=true' : '')) : '', // DLA = Disable Lead Assignment
            'requestAdvisorLink' => ! empty($quoteUuid) ? url(config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$quoteUuid.'/?assignAdvisor=true') : '',
            'assignmentType' => isset($healthQuote->assignment_type) ? getAssignmentTypeText($healthQuote->assignment_type) : '',
            'previousAdvisorDetails' => $previousAdvisorDetails,
            'isReAssignment' => ! empty($previousAdvisor),
            'templateId' => $emailTemplateId ?? null,
        ];
    }

    public function buildEmailData($lead, $plans, $previousAdvisor, $request, $emailTemplateId)
    {
        LoggerService::info(self::class.' - buildEmailData - Building email data', extra: [
            'has_plans' => isset($plans) && is_array($plans),
            'plans_count' => is_array($plans) ? count($plans) : 0,
            'email_template_id' => $emailTemplateId,
        ]);

        if (isset($plans) && is_array($plans)) {
            return $this->buildPlansEmailData($lead, $plans, $previousAdvisor, $request, $emailTemplateId);
        } else {
            $advisor = User::where('id', $lead->advisor_id)->first();

            return $this->buildCommonEmailData($lead, $advisor, $previousAdvisor, $request, $emailTemplateId);
        }
    }

    private function getPlanBuyNowLink($plan, $uuid)
    {
        // Null safety checks for plan properties
        $providerCode = $plan->providerCode ?? '';
        $planId = $plan->id ?? null;
        $selectedCopayId = $plan->selectedCopayId ?? null;

        if (empty($uuid) || empty($providerCode) || empty($planId) || empty($selectedCopayId)) {
            return '';
        }

        $baseUrl = config('constants.ECOM_HEALTH_INSURANCE_QUOTE_URL').$uuid.'/payment/';
        $queryParams = http_build_query([
            'planId' => $planId,
            'providerCode' => strtoupper($providerCode),
            'selectedCopayId' => $selectedCopayId,
        ]);

        $buyNowLink = $baseUrl.'?'.$queryParams;

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
        LoggerService::info("sendSICDedicatedEmail - Sending webhook request to: {$url} with Ref-ID: {$lead->uuid} | Time:".now());
        app(BirdService::class)->triggerWebHookRequest($url, (object) $sicDedicatedEmailPayload);
        LoggerService::info("sendSICDedicatedEmail - Webhook request sent to: {$url} with Ref-ID: {$lead->uuid} | Time:".now());
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
            'workflowType' => WorkflowTypeEnum::WHATSAPP_NOTIFICATION_TO_CUSTOMER_NO_PLANS,
        ];
        $customerWANotificationWorkflow = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_WHATSAPP_NO_PLANS_ASSIGNMENT_WORKFLOW);
        if (! empty($customerWANotificationWorkflow)) {
            app(BirdService::class)->triggerWebHookRequest($customerWANotificationWorkflow, (object) $payload);
            LoggerService::info('sendWhatsappNotificationToCustomer - Webhook request sent to: '.$customerWANotificationWorkflow.' with Ref-ID: '.$quote->uuid.' | Time:'.now());
        } else {
            LoggerService::info('sendWhatsappNotificationToCustomer - Webhook URL not found in storage with Ref-ID:'.$quote->uuid.' | Time:'.now());
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

    public function sendIntroAndReassignEmail($quote, $quoteType = null, $oldAdvisorId = null, $shortenedBusinessType = null, bool $isNonAdvisorEmail = false)
    {
        $advisor = User::where('id', $quote->advisor_id)->first() ?? null;
        $previousAdvisor = User::where('id', $oldAdvisorId)->first();
        $logMessage = empty($oldAdvisorId) ? 'old Advisor is not available' : "old Advisor {$oldAdvisorId} is available";
        LoggerService::info(self::class." - {$logMessage} for the quote: {$quote->uuid}");

        $workflowType = empty($oldAdvisorId) ? WorkflowTypeEnum::INTRODUCTORY_EMAIL_TO_CUSTOMER : WorkflowTypeEnum::CUSTOMER_NOTIFY_UNAVAILABLE_ADVIOSR;
        if ($isNonAdvisorEmail) {
            $workflowType = WorkflowTypeEnum::INTRODUCTORY_EMAIL_TO_CUSTOMER;
        }

        $bccEmails = $this->getBCCEmails($quoteType, $quote->source);
        $payload = [
            'customerEmail' => $quote->email,
            'customerName' => $quote->first_name.' '.$quote->last_name,
            'quoteUID' => $quote->uuid,
            'refID' => $quote->code,
            'quoteType' => $quoteType,
            'advisor' => $advisor,
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'advisorProfilePath' => (! empty($advisor->profile_photo_path) ? $advisor->profile_photo_path : ''),
            'landLine' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'previousAdvisorName' => ! empty($previousAdvisor) ? $previousAdvisor->name : '',
            'businessTypeInsurance' => $shortenedBusinessType ?? null,
            'workflowType' => $workflowType,
            'bccEmails' => $bccEmails,
        ];

        $customerNotificationWorkflow = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_CUSTOMER_NOTIFY_UNAVAILABLE_ADVIOSR_WORKFLOW);
        if (! empty($customerNotificationWorkflow)) {
            app(BirdService::class)->triggerWebHookRequest($customerNotificationWorkflow, (object) $payload);
            LoggerService::info(self::class.' - sendIntroAndReassignEmail - Webhook request sent to: '.$customerNotificationWorkflow.' with Ref-ID: '.$quote->uuid.' | Time:'.now());
        } else {
            LoggerService::info(self::class.'- sendIntroAndReassignEmail - Webhook URL not found in storage');
        }
    }

    public function sendSupportUserAssignmentEmail($emailData)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::SUPPORT_USER_ASSIGNMENT);

        // Convert leads array to HTML list
        $leads = collect($emailData->get('params')['leads'] ?? []);
        $quotesHtml = '';

        if ($leads->isNotEmpty()) {
            $quotesHtml = $leads->map(function ($lead) {
                $url = $lead['lead_url'] ?? '#';
                $refId = $lead['code'] ?? 'Lead';

                return "<li><a href='{$url}' target='_blank'>{$refId}</a></li>";
            })->pipe(function ($items) {
                return '<ul>'.$items->implode('').'</ul>';
            });
        }

        $birdEmailData = (object) [
            'supportUserEmail' => $emailData->get('to')['email'] ?? '',
            'supportUserName' => $emailData->get('to')['name'] ?? '',
            'assignerName' => $emailData->get('params')['assignerName'] ?? '',
            'assignerEmail' => $emailData->get('params')['assignerEmail'] ?? '',
            'ccEmails' => $emailData->get('cc') ?? [],
            'quoteTypeName' => $emailData->get('params')['quoteTypeName'] ?? '',
            'quotes' => $quotesHtml,
            'workflowType' => WorkflowTypeEnum::OE_ASSIGNMENT,
        ];

        $oeAssignmentEvent = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_OE_ASSIGNMENT_WORKFLOW)->first();

        if ($oeAssignmentEvent) {
            info('Support User (OE) Assignment: workflow trigger on BIRD, BIRD_OE_ASSIGNMENT_WORKFLOW value: '.$oeAssignmentEvent->value);
            $response = app(BirdService::class)->triggerWebHookRequest($oeAssignmentEvent->value, $birdEmailData);
        } else {
            LoggerService::error('Support User (OE) Assignment: BIRD_OE_ASSIGNMENT_WORKFLOW not found in ApplicationStorage. Bird request has not been triggered.');
        }
    }

    public function getBCCEmails(string $quoteType, ?string $source = null)
    {
        $bccEmails = [];

        switch ($quoteType) {
            case QuoteTypes::HOME->value:
                $bccEmails[] = getAppStorageValueByKey(ApplicationStorageEnums::HOME_LEAD_POOL_BCC);
                if ($source === LeadSourceEnum::CPA_AUSTRALIA_HOME) {
                    $bccEmails = array_merge($bccEmails, explode(',', getAppStorageValueByKey(ApplicationStorageEnums::CPA_AUSTRALIA_HOME_BCC_EMAILS)));
                }
                break;
            case QuoteTypes::SAVINGS->value:
                $bccEmails[] = getAppStorageValueByKey(ApplicationStorageEnums::SAVINGS_LEAD_POOL_BCC);
                if ($source === LeadSourceEnum::CPA_AUSTRALIA_SAVINGS) {
                    $bccEmails = array_merge($bccEmails, explode(',', getAppStorageValueByKey(ApplicationStorageEnums::CPA_AUSTRALIA_SAVINGS_BCC_EMAILS)));
                }
                break;

            default:
                break;
        }

        return $bccEmails;
    }

    public function sendCarIntroEmailWithAdvisor($quote)
    {
        $carIntroEmailWorkflowUrl = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW);
        if (! empty($carIntroEmailWorkflowUrl)) {
            $advisor = User::where('id', $quote->advisor_id)->first() ?? null;
            $workflowType = ! empty($quote->car_make_id) ? WorkflowTypeEnum::CAR_INTRO_EMAIL : WorkflowTypeEnum::CAR_INTRO_EMAIL_WITHOUT_VEHICLE_DETAILS;
            LoggerService::info('sendCarIntroEmailWithAdvisor - Workflow type: '.$workflowType.' with Car Make ID: '.$quote->car_make_id.' with Ref-ID: '.$quote->uuid);
            $emailData = $this->buildEmailDataForBirdFlow($quote, $advisor, $workflowType);
            app(BirdService::class)->triggerWebHookRequest($carIntroEmailWorkflowUrl, (object) $emailData);
            LoggerService::info('sendCarIntroEmailWithAdvisor - Webhook request sent to: '.$carIntroEmailWorkflowUrl.' with Ref-ID: '.$quote->uuid.' | Time:'.now());
        } else {
            LoggerService::info('sendCarIntroEmailWithAdvisor - Webhook URL not found in storage with Ref-ID:'.$quote->uuid.' | Time:'.now());
        }

        return true;
    }

    public function sendCarIntroEmailWithoutAdvisor($quote)
    {
        $carIntroEmailWorkflowUrl = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW);
        if (! empty($carIntroEmailWorkflowUrl)) {
            $emailData = $this->buildEmailDataForBirdFlow($quote, null, WorkflowTypeEnum::CAR_INTRO_EMAIL);
            app(BirdService::class)->triggerWebHookRequest($carIntroEmailWorkflowUrl, (object) $emailData);
            LoggerService::info('sendCarIntroEmailWithoutAdvisor - Webhook request sent to: '.$carIntroEmailWorkflowUrl);
        } else {
            LoggerService::info('sendCarIntroEmailWithoutAdvisor - Webhook URL not found in storage');
        }

        return true;
    }

    public function buildEmailDataForBirdFlow($lead, $advisor, $workflowType)
    {
        $documentUrl = getAppStorageValueByKey(ApplicationStorageEnums::LMS_INTRO_EMAIL_ATTACHMENT_URL);

        return (object) [
            'quoteUID' => $lead->uuid,
            'customerEmail' => $lead->email,
            'refID' => $lead->code,
            'customerFullName' => $lead->first_name.' '.$lead->last_name,
            'companyName' => $lead->company_name ?? '',
            'advisorId' => $advisor->id ?? null,
            'advisorName' => (! empty($advisor->name) ? $advisor->name : ''),
            'advisorEmail' => (! empty($advisor->email) ? $advisor->email : ''),
            'carMakeId' => ! empty($lead->car_make_id) ? true : false,
            'advisorDetails' => $advisor ?? null,
            'documentUrl' => $documentUrl ?? null,
            'quotePlanLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$lead->uuid,
            'requestAdvisorLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$lead->uuid.'/?assignAdvisor=true',
            'quotePlanApiLink' => config('constants.KEN_API_ENDPOINT').'/get-health-quote-plans-order-priority?'.$lead->uuid.'&lang=en&isModified=true',
            'landLine' => (! empty($advisor->landline_no) ? $advisor->landline_no : ''),
            'mobilePhone' => (! empty($advisor->mobile_no) ? $advisor->mobile_no : ''),
            'whatsAppNumber' => ! empty($advisor->mobile_no) ? formatMobileNo($advisor->mobile_no) : '',
            'mobileNoWithoutSpaces' => (! empty($advisor->mobile_no) ? removeSpaces(formatMobileNoDisplay($advisor->mobile_no)) : ''),
            'workflowType' => $workflowType,
            'whatsappConsent' => getWhatsappConsent(QuoteTypes::CAR, $lead->uuid),
            'customerMobile' => (! empty($lead->mobile_no) ? $lead->mobile_no : ''),
            'instantAlfredLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$lead->uuid.'/?IA=true',
            'createdAt' => $lead->created_at,
        ];
    }

    public function sendCarIntroEmailWithAIAdvisor($quote)
    {
        $carIntroEmailWorkflowUrl = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_NB_MOTOR_WORKFLOW);
        if (! empty($carIntroEmailWorkflowUrl)) {
            $aiAdvisor = $quote->aiAdvisor;
            $emailData = $this->buildEmailDataForBirdFlow($quote, $aiAdvisor, WorkflowTypeEnum::CAR_INTRO_EMAIL);
            app(BirdService::class)->triggerWebHookRequest($carIntroEmailWorkflowUrl, (object) $emailData);
            LoggerService::info("sendCarIntroEmailWithAIAdvisor - Webhook request sent to: {$carIntroEmailWorkflowUrl} with Ref-ID: {$quote->uuid}");
        } else {
            LoggerService::info("sendCarIntroEmailWithAIAdvisor - Webhook URL not found in storage with Ref-ID: {$quote->uuid}");
        }

        return true;
    }

    public function sendManagerDeactivationAttemptEmail(Collection $baseManagers, $attemptedBy): ?object
    {
        $workflowUrl = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_MANAGER_DEACTIVATION_ATTEMPT_WORKFLOW);
        $itSupportEmail = getAppStorageValueByKey(ApplicationStorageEnums::IT_SUPPORT_EMAIL);

        if (empty($workflowUrl)) {
            LoggerService::error('Manager Deactivation Attempt: BIRD_MANAGER_DEACTIVATION_ATTEMPT_WORKFLOW not found in ApplicationStorage.');

            return null;
        }

        if (empty($itSupportEmail)) {
            LoggerService::error('Manager Deactivation Attempt: IT_SUPPORT_EMAIL not found in ApplicationStorage.');

            return null;
        }

        $emailData = (object) [
            'recipientEmail' => $itSupportEmail,
            'recipientName' => 'IT Support AFIA',
            'managerIds' => $baseManagers->pluck('id')->filter()->values()->implode(','),
            'workflowType' => WorkflowTypeEnum::MANAGER_DEACTIVATION_EMAIL,
            'timestamp' => now()->toDateTimeString(),
        ];

        /* Base user's manager's */
        $managerEmails = $baseManagers
            ->flatMap(fn ($manager) => collect(data_get($manager, 'managers', [])))
            ->pluck('email')
            ->filter()
            ->values()
            ->all();

        if (! empty($managerEmails)) {
            $emailData->managerEmails = $managerEmails;
        }

        LoggerService::info('Sending manager deactivation attempt email via Bird', [
            'manager_ids' => $emailData->managerIds,
            'attempted_by' => $attemptedBy->id,
            'emailData' => $emailData,
        ]);

        return app(BirdService::class)->triggerWebHookRequest($workflowUrl, $emailData);
    }
}
