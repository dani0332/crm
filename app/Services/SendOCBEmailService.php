<?php

namespace App\Services;

use App\Enums\EnvEnum;
use App\Models\CarQuote;
use Exception;
use Illuminate\Support\Facades\Log;

class SendOCBEmailService
{
    protected $carQuoteService;
    protected $crudService;
    protected $userService;
    protected $customerService;
    protected $emailActivityService;
    protected $lookupService;

    protected $apiKey = '';
    protected $url = '';
    protected $appEnv = '';

    public function __construct(
        CarQuoteService $carQuoteService,
        CRUDService $crudService,
        UserService $userService,
        LookupService $lookupService,
        CustomerService $customerService,
        EmailActivityService $emailActivityService,
    ) {
        $this->carQuoteService = $carQuoteService;
        $this->crudService = $crudService;
        $this->userService = $userService;
        $this->customerService = $customerService;
        $this->lookupService = $lookupService;
        $this->emailActivityService = $emailActivityService;
        $this->apiKey = config('constants.SENDINBLUE_KEY');
        $this->url = config('constants.SIB_URL');
        $this->appEnv = config('constants.APP_ENV');
    }

    public function processOCBEmail($quoteUuid)
    {
        Log::info('OCB Email Process START');
        $carQuote = CarQuote::find($quoteUuid);

        if ($carQuote->previous_quote_policy_number != null) {
            // CHECK NUMBER OF PLAN AND SEND RESPECTIVE 'ONE CLICK BUY' EMAIL TO CUSTOMER
            $listQuotePlans = $this->carQuoteService->getPlans($quoteUuid, true, true);
            $quotePlansCount = is_countable($listQuotePlans) ? count($listQuotePlans) : 0;
            $emailTemplateId = (int) $this->crudService->getOcbCustomerEmailTemplate($quotePlansCount);

            if (isset($carQuote->advisor_id)) {
                $advisor = $this->userService->getUserById($carQuote->advisor_id);
                $advisorName = $advisor->name;
                $advisorEmail = $advisor->email;
                $advisorMobile = $advisor->mobile_no;
                $advisorLandline = $advisor->landline_no;
            }

            // Send Email Data
            $carMake = $this->lookupService->getCarMake($carQuote->car_make_id);
            $carModel = $this->lookupService->getCarModel($carQuote->car_model_id);
            $emailData = (object) [
                'quoteId' => $carQuote->id,
                'templateId' => $emailTemplateId,
                'quoteCdbId' => $carQuote->code,
                'customerName' => $carQuote->first_name.' '.$carQuote->last_name,
                'customerEmail' => $carQuote->email,
                'previousPolicyExpiryDate' => $carQuote->previous_policy_expiry_date,
                'currentlyInsuredWith' => $carQuote->currently_insured_with,
                'carMake' => isset($carMake->text) ? $carMake->text : null,
                'carModel' => isset($carModel->text) ? $carModel->text : null,
                'carManufactureYear' => $carQuote->year_of_manufacture,
                'previousPolicyNumber' => $carQuote->previous_quote_policy_number,
                'advisorName' => $advisorName ?? null,
                'advisorEmailAddress' => $advisorEmail ?? null,
                'advisorMobileNo' => $advisorMobile ?? null,
                'advisorLandlineNo' => $advisorLandline ?? null,
                'buttonUrl' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$carQuote->uuid,
                'listQuotePlans' => $listQuotePlans,
                'multipleQuoteUrl' => config('constants.AFIA_WEBSITE_DOMAIN').'/car-insurance/quote/'.$carQuote->uuid.'/'.'payment/?providerCode=',
                'quotePlansCount' => $quotePlansCount ?? 0,
            ];

            $responseCode = $this->sendOcbEmail($emailTemplateId, $emailData, 'car-quote-one-click-buy-batch');

            if ($responseCode == 201) {
                Log::info('OCB EmailSent: '.$responseCode);
            } else {
                Log::error('OCB EmailNotSent: '.$responseCode.' Customer EmailAddress:'.$carQuote->email);
            }
        }

        Log::info('OCB Email Process END');
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

            $emailAttachments = $emailData->documentUrl ?? null;

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
                    'signUpButtonUrl' => $emailData->signUpButtonUrl ?? null,
                    'buttonUrl' => $emailData->buttonUrl ?? null,
                    'cdbId' => $emailData->quoteCdbId ?? null,
                    'advisorName' => $emailData->advisorName ?? null,
                    'advisorLandlineNo' => $emailData->advisorLandlineNo ?? null,
                    'advisorMobileNo' => $emailData->advisorMobileNo ?? null,
                    'advisorEmailAddress' => $emailData->advisorEmailAddress ?? null,
                    'notesForCustomer' => isset($emailData->notesForCustomer) ? nl2br(htmlentities(str_replace('<br />', '', $emailData->notesForCustomer))) : null,
                    'providerSupportNumber' => $emailData->providerSupportNumber ?? null,
                    'previousPolicyExpiryDate' => isset($emailData->previousPolicyExpiryDate) ? date('l', strtotime($emailData->previousPolicyExpiryDate)).', '.date('d-M-Y', strtotime($emailData->previousPolicyExpiryDate)) : null,
                    'currentlyInsuredWith' => $emailData->currentlyInsuredWith ?? null,
                    'carMake' => $emailData->carMake ?? null,
                    'carModel' => $emailData->carModel ?? null,
                    'carManufactureYear' => $emailData->carManufactureYear ?? null,
                    'previousPolicyNumber' => $emailData->previousPolicyNumber ?? null,
                    'listQuotePlans' => $emailData->listQuotePlans ?? null,
                    'multipleQuoteUrl' => $emailData->multipleQuoteUrl ?? null,
                    'quotePlansCount' => $emailData->quotePlansCount ?? 0,
                ],
                'tags' => [
                    $tag,
                ],
                'attachment' => $attachments ?? null,
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

            $response = json_decode(json_encode($clientRequest->getStatusCode().' '.$clientRequest->getBody()->getContents()), true);
            $responseCode = $clientRequest->getStatusCode();

            if ($responseCode == 201) {
                $isEmailSent = 1;
            }
        } catch (Exception $ex) {
            $responseCode = $ex->getCode();
            $quoteCdbId = $emailData->quoteCdbId ?? null;
            $responseDetail = 'SIB Send Email: Code/Message: '.$responseCode.'/'.$ex->getMessage().' CustomerEmail: '.$emailData->customerEmail.' QuoteCdbId: '.$quoteCdbId.' Class: '.get_class();
            info($responseDetail);
            $response = json_encode($ex->getCode().' '.$ex->getMessage());
            $isEmailSent = 0;
        }

        $this->emailActivityService->addEmailActivity($response, $isEmailSent, $emailData->customerEmail);

        return $responseCode;
    }
}
