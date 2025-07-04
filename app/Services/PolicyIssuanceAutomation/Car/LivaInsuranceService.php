<?php

namespace App\Services\PolicyIssuanceAutomation\Car;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\TravelQuoteEnum;
use App\Interfaces\PolicyIssuanceInterface;
use App\Repositories\PaymentRepository;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class LivaInsuranceService implements PolicyIssuanceInterface
{
    private $className = 'livaInsuranceService';
    private mixed $baseUrl;
    private $headers = [];

    public const INSURER_CODE = InsuranceProvidersEnum::ALNC;
    public const TYPE = quoteTypeCode::Car;
    public const TYPE_ID = QuoteTypeId::Car;

    public mixed $vat = null;
    public $policyIssuance = null;
    public $currentInsurerApiStatus = null;
    public function __construct()
    {
        $this->baseUrl = config('constants.LIVA_API_BASE_URL');
        $this->headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic '.config('constants.LIVA_BASIC_AUTH'),
            'PartnerId' => config('constants.LIVA_PARENT_ID'),
            'location' => config('constants.LIVA_LOCATION'),
            'Authentication' => 'Bearer '.config('constants.LIVA_AUTHENTICATION'),
            'SubscriptionKey' => config('constants.LIVA_SUBSCRIPTION_KEY'),
        ];
    }

    private function livaHttpCall($endPoint, $payload)
    {
        $url = $this->baseUrl.$endPoint;

        return Http::timeout(20)->withHeaders($this->headers)->post($url, $payload);
    }

    public function retrieveQuoteRequest()
    {
        $endPoint = 'transactions/retrieve/v2';
        $payload['RetrieveRequest'] = [
            'RetrieveType' => '2', // Retrive Type 2 = New Business Quote Retrival
            'TransactionNumber' => '7860255', // Quote number
            'EmailId' => 'john.doe@example.com',
            'PartnerTrnReferenceNumber' => 'Q1aw2bvcvT',
            'ProposalForm' => true,
        ];

        $response = $this->livaHttpCall($endPoint, $payload);
        dd($response->object());

        return $response;
    }

    public function createPolicyIssuanceSchedule($quote, $insurer)
    {

        LoggerService::startQuoteLogging($quote);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        /* if ($this->isPolicyIssuanceAutomationEnabled()) {

            $this->policyIssuance = (new PolicyIssuanceService)->schedulePolicyIssuance($quote, $insurer, self::TYPE, $this->className);

        } else {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Alliance Travel Automation is disabled');
        } */

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        return $this->policyIssuance;
    }

    public function executeSteps($process)
    {
        $response = ['status' => false, 'error' => null, 'message' => null];

        $this->policyIssuance = $process;
        $quote = $process->model;
        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::POLICY_AUTOMATION);

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' started');

        /* try {
            if ($this->isPolicyIssuanceAutomationEnabled()) {
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Plan ID : '.$quote->plan_id);

                $payment = PaymentRepository::mainQuotePayment($quote);

                $selectedPlan = $quote->travelQuotePlanDetails()->where('plan_id', $payment->plan_id)->first();

                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - TravelQuotePlanDetails ID : '.$selectedPlan?->id);

                $travelType = TravelQuoteEnum::ALLIANCE_IN_BOUND;
                $directionCode = $quote->direction_code;
                if ($directionCode === TravelQuoteEnum::TRAVEL_UAE_OUTBOUND) {
                    $travelType = TravelQuoteEnum::ALLIANCE_OUT_BOUND;
                }

                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Travel Type : '.$travelType);
                $lastCompletedStep = $process->completed_step;
                $nextStepToBeExecuted = $lastCompletedStep ? $this->getNextStep($lastCompletedStep) : PolicyIssuanceEnum::ALLIANCE_TRAVEL_ISSUE_POLICY;
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Next Step : '.$nextStepToBeExecuted);

                if ($nextStepToBeExecuted) {
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_ISSUE_POLICY) {
                        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID;
                        $policyIssuanceResponse = $this->issuePolicyAndFillPolicyDetails($quote, $selectedPlan, $travelType);
                        if (! $policyIssuanceResponse['status']) {
                            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - trigger fn:updateQuoteApiIssuanceStatusAndAllocate for step '.$nextStepToBeExecuted, extra: ['response' => $policyIssuanceResponse]);
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $policyIssuanceResponse;
                        }
                        $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
                    }

                    $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_PURCHASE_POLICY) {
                        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID;
                        $policyPurchaseResponse = $this->policyPurchase($quote, $payment, $travelType);
                        if (! $policyPurchaseResponse['status']) {
                            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - trigger fn:updateQuoteApiIssuanceStatusAndAllocate for step '.$nextStepToBeExecuted, extra: ['response' => $policyPurchaseResponse]);
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $policyPurchaseResponse;
                        }
                        $process->update(['completed_step' => $policyPurchaseResponse['completed_step']]);
                    }

                    $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_UPLOAD_POLICY_DOCUMENTS) {
                        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID;
                        $uploadPolicyDocumentResponse = $this->fetchAndUploadDocument($quote, $travelType);
                        if (! $uploadPolicyDocumentResponse['status']) {
                            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - trigger fn:updateQuoteApiIssuanceStatusAndAllocate for step '.$nextStepToBeExecuted, extra: ['response' => $uploadPolicyDocumentResponse]);
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $uploadPolicyDocumentResponse;
                        }
                        $quote->update(['quote_status_id' => QuoteStatusEnum::PolicyIssued, 'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued, 'quote_status_date' => now()]);
                        $process->update(['completed_step' => $uploadPolicyDocumentResponse['completed_step']]);
                    }

                    $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_FILL_POLICY_BOOKING_DETAILS) {
                        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID;
                        $fillPolicyDetailsResponse = $this->uploadBuyerTaxInvoiceAndFillBookingDetails($quote, $payment);
                        if (! $fillPolicyDetailsResponse['status']) {
                            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - trigger fn:updateQuoteApiIssuanceStatusAndAllocate for step '.$nextStepToBeExecuted, extra: ['response' => $fillPolicyDetailsResponse]);
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $fillPolicyDetailsResponse;
                        }
                        $process->update(['completed_step' => $fillPolicyDetailsResponse['completed_step']]);
                    }

                    $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_BOOK_POLICY) {
                        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID;
                        $triggerBookPolicyResponse = $this->triggerBookPolicyProcess($quote);
                        if (! $triggerBookPolicyResponse['status']) {
                            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - trigger fn:updateQuoteApiIssuanceStatusAndAllocate for step '.$nextStepToBeExecuted, extra: ['response' => $triggerBookPolicyResponse]);
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $triggerBookPolicyResponse;
                        }
                        $process->update(['completed_step' => $triggerBookPolicyResponse['completed_step']]);
                    }
                } else {
                    LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Last Completed Step : '.$lastCompletedStep);
                }

                $response['status'] = true;
                $response['message'] = 'Sage Booking of policy triggered successfully';
            } else {

                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Alliance Travel Automation is disabled');
                $response['error'] = 'Alliance Travel Automation is disabled';
                $response['message'] = 'Alliance Travel Automation is disabled';

            }
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Exception : '.$e->getMessage());

            return $response;
        } */

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' ended');

        return $response;
    }

    /* public function issuePolicyAndFillPolicyDetails($quote, $selectedPlan, $travelType): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $response = ['status' => false, 'completed_step' => PolicyIssuanceEnum::ALLIANCE_TRAVEL_ISSUE_POLICY, 'error' => null, 'message' => null];

        $titleTraveller = [];
        $firstNameTraveller = [];
        $lastNameTraveller = [];
        $dobTraveller = [];
        $passportTraveller = [];
        $nationalityTraveller = [];

        $customerMembers = $quote->customerMembers;
        $primaryMember = $this->getPrimaryMember($quote, $customerMembers);
        foreach ($customerMembers as $member) {

            $titleTraveller[] = $this->getTitle($member->gender);
            $firstNameTraveller[] = $member->first_name;
            $lastNameTraveller[] = $member->last_name ?? ' ';
            $dobTraveller[] = $member->dob ? Carbon::parse($member->dob)->format('Y-m-d') : null;
            $passportTraveller[] = $member->passport;
            $nationalityTraveller[] = $member->nationality->alliance_nationality_id;
        }

        $endPoint = '/v1/quote/'.$travelType.'/finalise';
        $payload = [
            'quote_id' => $selectedPlan?->insurer_quote_id,
            'scheme_id' => $selectedPlan?->alliance_scheme_id,
            'title_customer' => $this->getTitle($primaryMember->gender),
            'first_name_customer' => $primaryMember->first_name,
            'last_name_customer' => $primaryMember->last_name,
            'title_traveller' => $titleTraveller,
            'first_name_traveller' => $firstNameTraveller,
            'last_name_traveller' => $lastNameTraveller,
            'dob' => $dobTraveller,
            'passport_number' => $passportTraveller,
            'nationality_traveller' => $nationalityTraveller,
            'email' => 'happiness@support.insurancemarket.ae', // will be static, as we dont share customer contact details outside organization
            'mobile' => '971502245943', // will be static, as we dont share customer contact details outside organization
            'agency_reference' => 'asc',
        ];
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payload));

        $issuePolicy = $this->livaHttpCall($endPoint, $payload);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$issuePolicy);

        $issuePolicyResponse = $issuePolicy->object();
        $this->storePolicyIssuanceLog($quote, $payload, $issuePolicyResponse, $this->baseUrl.$endPoint, $response['completed_step'], $issuePolicy->failed() ? PolicyIssuanceEnum::FAILED_STATUS : PolicyIssuanceEnum::SUCCESS_STATUS);

        if ($issuePolicy->failed()) {
            $response['error'] = $issuePolicyResponse?->errors;

            return $response;
        }
        $issuePolicyResult = $issuePolicyResponse?->result;
        $insurerPolicyId = $issuePolicyResult?->policy_id;
        $premium = $issuePolicyResult?->premium;
        $priceVatApplicable = $premium / (1 + ((float) $this->vat / 100));
        $policyIssuanceDate = Carbon::now();
        $coverDays = $this->calculateCoverDaysForExpiryDate($quote, $travelType);
        $policyExpiryDate = Carbon::parse($quote->policy_start_date)->addDays($coverDays)->subDay();

        $quote->update([
            'insurer_policy_id' => $insurerPolicyId,
            'price_with_vat' => $premium,
            'vat' => $premium - $priceVatApplicable,
            'price_vat_applicable' => $priceVatApplicable,
            'policy_issuance_date' => $policyIssuanceDate,
            'policy_expiry_date' => $policyExpiryDate,
        ]);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy Issued Api called successfully and Quote is updated');

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Policy Issued Api called successfully and Quote is updated.';

        return $response;
    } */
}
