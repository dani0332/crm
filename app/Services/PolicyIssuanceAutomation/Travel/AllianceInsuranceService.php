<?php

namespace App\Services\PolicyIssuanceAutomation\Travel;

use App\Enums\ApplicationStorageEnums;
use App\Enums\GenericRequestEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TravelQuoteEnum;
use App\Interfaces\PolicyIssuanceInterface;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Jobs\SendTravelAllianceFailedAllocationEmailJob;
use App\Models\DocumentType;
use App\Models\PolicyIssuanceLog;
use App\Models\TravelQuote;
use App\Repositories\PaymentRepository;
use App\Services\ApplicationStorageService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\SageApiService;
use App\Services\SplitPaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Storage;

class AllianceInsuranceService implements PolicyIssuanceInterface
{
    private $className = 'allianceInsuranceService';
    private mixed $baseUrl;
    private mixed $authParam;

    public const INSURER_CODE = InsuranceProvidersEnum::ALNC;
    public const TYPE = quoteTypeCode::Travel;
    public const TYPE_ID = QuoteTypeId::Travel;

    public mixed $vat = null;
    public $policyIssuance = null;
    public $currentInsurerApiStatus = null;
    public function __construct()
    {
        $this->vat = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::VAT_VALUE);

        $this->baseUrl = config('constants.ALLIANCE_API_BASE_URL').'/api';
        $this->authParam = [
            'agency_id' => config('constants.ALLIANCE_AGENCY_ID'),
            'agency_code' => config('constants.ALLIANCE_AGENCY_CODE'),
        ];

    }

    public function createPolicyIssuanceSchedule($quote, $insurer)
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        if ($this->isPolicyIssuanceAutomationEnabled()) {

            $this->policyIssuance = (new PolicyIssuanceService)->schedulePolicyIssuance($quote, $insurer, self::TYPE, $this->className);

        } else {
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Alliance Travel Automation is disabled');
        }

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        return $this->policyIssuance;
    }

    public function executeSteps($process)
    {
        $response = ['status' => false, 'error' => null, 'message' => null];

        $this->policyIssuance = $process;
        $quote = $process->model;

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' started');

        try {
            if ($this->isPolicyIssuanceAutomationEnabled()) {
                info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Plan ID : '.$quote->plan_id);

                $payment = PaymentRepository::mainQuotePayment($quote);

                $selectedPlan = $quote->travelQuotePlanDetails()->where('plan_id', $payment->plan_id)->first();

                info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - TravelQuotePlanDetails ID : '.$selectedPlan?->id);

                $travelType = TravelQuoteEnum::ALLIANCE_IN_BOUND;
                $directionCode = $quote->direction_code;
                if ($directionCode === TravelQuoteEnum::TRAVEL_UAE_OUTBOUND) {
                    $travelType = TravelQuoteEnum::ALLIANCE_OUT_BOUND;
                }

                info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Travel Type : '.$travelType);
                $lastCompletedStep = $process->completed_step;
                $nextStepToBeExecuted = $lastCompletedStep ? $this->getNextStep($lastCompletedStep) : PolicyIssuanceEnum::ALLIANCE_TRAVEL_ISSUE_POLICY;
                info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Next Step : '.$nextStepToBeExecuted);

                if ($nextStepToBeExecuted) {
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_ISSUE_POLICY) {
                        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID;
                        $policyIssuanceResponse = $this->issuePolicyAndFillPolicyDetails($quote, $selectedPlan, $travelType);
                        if (! $policyIssuanceResponse['status']) {
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $policyIssuanceResponse;
                        }
                        $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
                    }

                    $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_PURCHASE_POLICY) {
                        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID;
                        $policyPurchaseResponse = $this->policyPurchase($quote, $payment, $travelType);
                        if (! $policyPurchaseResponse['status']) {
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $policyPurchaseResponse;
                        }
                        $process->update(['completed_step' => $policyPurchaseResponse['completed_step']]);
                    }

                    $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_UPLOAD_POLICY_DOCUMENTS) {
                        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID;
                        $uploadPolicyDocumentResponse = $this->fetchAndUploadDocument($quote, $travelType);
                        if (! $uploadPolicyDocumentResponse['status']) {
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $uploadPolicyDocumentResponse;
                        }
                        $quote->update(['quote_status_id' => QuoteStatusEnum::PolicyIssued]);
                        $process->update(['completed_step' => $uploadPolicyDocumentResponse['completed_step']]);
                    }

                    $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_FILL_POLICY_BOOKING_DETAILS) {
                        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID;
                        $fillPolicyDetailsResponse = $this->uploadBuyerTaxInvoiceAndFillBookingDetails($quote, $payment);
                        if (! $fillPolicyDetailsResponse['status']) {
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $fillPolicyDetailsResponse;
                        }
                        $process->update(['completed_step' => $fillPolicyDetailsResponse['completed_step']]);
                    }

                    $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                    if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_BOOK_POLICY) {
                        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                        $this->currentInsurerApiStatus = PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID;
                        $fillPolicyDetailsResponse = $this->triggerBookPolicyProcess($quote);
                        if (! $fillPolicyDetailsResponse['status']) {
                            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);

                            return $fillPolicyDetailsResponse;
                        }
                        $process->update(['completed_step' => $fillPolicyDetailsResponse['completed_step']]);
                    }
                } else {
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Last Completed Step : '.$lastCompletedStep);
                }

                $response['status'] = true;
                $response['message'] = 'Sage Booking of policy triggered successfully';
            } else {

                info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Alliance Travel Automation is disabled');
                $response['error'] = 'Alliance Travel Automation is disabled';
                $response['message'] = 'Alliance Travel Automation is disabled';

            }
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
            $this->updateQuoteApiIssuanceStatusAndAllocate($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Exception : '.$e->getMessage());

            return $response;
        }

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' ended');

        return $response;
    }

    public function issuePolicyAndFillPolicyDetails($quote, $selectedPlan, $travelType): array
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $response = ['status' => false, 'completed_step' => PolicyIssuanceEnum::ALLIANCE_TRAVEL_ISSUE_POLICY, 'error' => null, 'message' => null];

        $title = $this->getTitle($quote->gender);

        $titleTraveller = [];
        $firstNameTraveller = [];
        $lastNameTraveller = [];
        $dobTraveller = [];
        $passportTraveller = [];
        $nationalityTraveller = [];

        $customerMember = $quote->customerMembers;
        foreach ($customerMember as $member) {

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
            'title_customer' => $title,
            'first_name_customer' => $quote->first_name,
            'last_name_customer' => $quote->last_name,
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
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payload));

        $issuePolicy = $this->allianceHttpCall($endPoint, $payload);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$issuePolicy);

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
        $policyExpiryDate = Carbon::parse($quote->policy_start_date)->addDays($coverDays)->subDay();  /* Last Cover day should be the expiry date as per business requirement */

        $quote->update([
            'insurer_policy_id' => $insurerPolicyId,
            'price_with_vat' => $premium,
            'vat' => $premium - $priceVatApplicable,
            'price_vat_applicable' => $priceVatApplicable,
            'policy_issuance_date' => $policyIssuanceDate,
            'policy_expiry_date' => $policyExpiryDate,
        ]);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy Issued Api called successfully and Quote is updated');

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Policy Issued Api called successfully and Quote is updated.';

        return $response;
    }

    public function policyPurchase($quote, $payment, $travelType): array
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $response = ['status' => false, 'completed_step' => PolicyIssuanceEnum::ALLIANCE_TRAVEL_PURCHASE_POLICY, 'error' => null, 'message' => null];
        $endPoint = '/v1/quote/'.$travelType.'/purchase';

        $payload = [
            'policy_id' => $quote->insurer_policy_id,
        ];
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payload));

        $policyPurchase = $this->allianceHttpCall($endPoint, $payload);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$policyPurchase);

        $policyPurchaseResponse = $policyPurchase->object();
        $this->storePolicyIssuanceLog($quote, $payload, $policyPurchaseResponse, $this->baseUrl.$endPoint, $response['completed_step'], $policyPurchase->failed() ? PolicyIssuanceEnum::FAILED_STATUS : PolicyIssuanceEnum::SUCCESS_STATUS);

        if ($policyPurchase->failed()) {
            $response['error'] = $policyPurchaseResponse?->errors;

            return $response;
        }
        $policyPurchaseResult = $policyPurchaseResponse?->result;

        $insurerPolicyNumber = $policyPurchaseResult->policy_number;
        $insurerTaxNumber = $policyPurchaseResult->tax_invoice_number;

        $quote->update(['policy_number' => $insurerPolicyNumber, 'quote_status_id' => QuoteStatusEnum::PolicyIssued, 'quote_status_date' => now()]);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy Purchase Api called successfully and Quote is updated');

        $payment->update(['insurer_tax_number' => $insurerTaxNumber]);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Insurer Tax Invoice number is updated to : '.$insurerTaxNumber);

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Policy Purchase Api called successfully and Quote is updated.';

        return $response;
    }

    public function fetchAndUploadDocument($quote, $travelType): array
    {
        $maxRetries = 5;
        $retryDelay = 10; // seconds
        $retryCount = 0;

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');
        $response = ['status' => false, 'completed_step' => PolicyIssuanceEnum::ALLIANCE_TRAVEL_UPLOAD_POLICY_DOCUMENTS, 'error' => null, 'message' => null];

        $payload = [
            'policy_id' => $quote->insurer_policy_id,
        ];
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payload));

        $endPoint = '/v1/policy/'.$travelType.'/documents';

        do {
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Waiting for 10 seconds so  Provider can generate the policy documents');
            sleep($retryDelay);

            $policyDocuments = $this->allianceHttpCall($endPoint, $payload);
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$policyDocuments);

            $policyDocumentsResponse = $policyDocuments->object();
            if ($policyDocuments->failed() && $policyDocumentsResponse?->errors && str_contains($policyDocumentsResponse?->errors[0], 'Policy documents are still generating')) {
                $retryCount++;
            } else {
                break;
            }
        } while ($retryCount < $maxRetries);

        $this->storePolicyIssuanceLog($quote, $payload, $policyDocumentsResponse, $this->baseUrl.$endPoint, $response['completed_step'], $policyDocuments->failed() ? PolicyIssuanceEnum::FAILED_STATUS : PolicyIssuanceEnum::SUCCESS_STATUS);
        if ($policyDocuments->failed()) {
            $response['error'] = $policyDocumentsResponse?->errors;

            return $response;
        }

        $policyDocumentsResult = $policyDocumentsResponse?->result;
        $issuePolicyDocuments = $policyDocumentsResult?->policy_documents;
        foreach ($issuePolicyDocuments as $policyDocument) {
            $policyDocumentCode = $this->getTravelDocumentMapping($policyDocument->name);
            if ($policyDocumentCode) {
                $this->uploadAndAttachToQuoteDocuments($quote, $policyDocument->url, $policyDocumentCode['code'], $policyDocument->name);
            } else {
                info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy Document Mapping not found for : '.$policyDocument->name);
            }
        }

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy documents are uploaded');

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Policy documents are uploaded';

        return $response;

    }

    public function uploadBuyerTaxInvoiceAndFillBookingDetails($quote, $payment): array
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');
        $response = ['status' => false, 'completed_step' => PolicyIssuanceEnum::ALLIANCE_TRAVEL_FILL_POLICY_BOOKING_DETAILS, 'error' => null, 'message' => null];

        $payload = [
            'policy_id' => $quote->insurer_policy_id,
            'policy_number' => $quote->policy_number,
        ];
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payload));

        $endPoint = '/v1/agency/buyer-tax-invoices';
        $buyerTaxInvoice = $this->allianceHttpCall($endPoint, $payload);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$buyerTaxInvoice);

        $buyerTaxInvoiceResponse = $buyerTaxInvoice->object();
        $this->storePolicyIssuanceLog($quote, $payload, $buyerTaxInvoiceResponse, $this->baseUrl.$endPoint, $response['completed_step'], $buyerTaxInvoice->failed() ? PolicyIssuanceEnum::FAILED_STATUS : PolicyIssuanceEnum::SUCCESS_STATUS);

        if ($buyerTaxInvoice->failed()) {
            $response['error'] = $buyerTaxInvoiceResponse?->errors;

            return $response;
        }

        $buyerTaxInvoiceResult = $buyerTaxInvoiceResponse?->result;
        $bookingDetails = $buyerTaxInvoiceResult?->buyer_tax_invoices[0];

        $insurerInvoiceDate = Carbon::createFromFormat('d-M-y', $bookingDetails->tax_invoice_date)->format('Y-m-d');
        $buyerTaxInvoiceURL = $bookingDetails->url;
        $buyerTaxInvoiceDocumentCode = QuoteDocumentsEnum::TRAVEL_TAX_INVOICE_RAISE_BY_BUYER;

        $this->uploadAndAttachToQuoteDocuments($quote, $buyerTaxInvoiceURL, $buyerTaxInvoiceDocumentCode);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Buyer Tax Invoice is uploaded');

        $payment->update([
            'commission' => roundNumber($bookingDetails->agency_commission_inc_tax),
            'commission_vat' => roundNumber($bookingDetails->agency_commission_tax),
            'commission_vat_applicable' => roundNumber($bookingDetails->agency_commission),
            'commmission_percentage' => roundNumber((($bookingDetails->agency_commission / ($bookingDetails->premium / (1 + (float) $bookingDetails->tax_rate))) * 100)),
            'insurer_commmission_invoice_number' => $bookingDetails->tax_invoice_number,
            'insurer_invoice_date' => $insurerInvoiceDate,
            'invoice_description' => (new PaymentRepository)->generateInvoiceDescription($payment, self::TYPE, $quote),
        ]);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy Details filled.');

        (new SplitPaymentService)->updateCommissionSchedule($payment);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Commission Schedule updated');

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Policy Details filled and Buyer Tax Invoice is uploaded';

        return $response;
    }

    public function triggerBookPolicyProcess($quote): array
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $response = ['status' => false, 'completed_step' => PolicyIssuanceEnum::ALLIANCE_TRAVEL_BOOK_POLICY, 'error' => null, 'message' => null];

        $request = new \stdClass;
        $request->quote_id = $quote->id;
        $request->modelType = self::TYPE;
        $request->model_type = self::TYPE;
        $request->is_send_policy = false;
        $request->send_policy_type = SendPolicyTypeEnum::SAGE;
        $request->transaction_payment_status = null;

        $createSageProcessResponse = (new SageApiService)->postBookPolicyToSage($request, $quote);

        if (! $createSageProcessResponse['status']) {
            $response['error'] = $createSageProcessResponse['message'];

            return $response;
        }
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Sage Process Created : '.$createSageProcessResponse['message']);

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Booking process in started! It will take some time to Complete. Come Back in a while to check the status!';

        return $response;
    }

    public function getNextStep($completedStep = null): ?string
    {
        $allSteps = PolicyIssuanceEnum::getPolicyIssuanceSteps(self::INSURER_CODE, self::TYPE);

        if (! $completedStep) {
            return $allSteps[0]; // Return the first step if completedStep is null
        }

        $completedStepIndex = array_search($completedStep, $allSteps);
        if ($completedStepIndex === false || $completedStepIndex === count($allSteps) - 1) {
            return null; // No next step or last step reached
        }

        return $allSteps[$completedStepIndex + 1];
    }

    public function isPolicyIssuanceAutomationEnabled()
    {
        return app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_ALLIANCE_TRAVEL_POLICY_ISSUANCE);
    }

    public function isPolicyIssuanceAutomationRetryEnabledForTimeout()
    {
        return app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_ALLIANCE_TRAVEL_POLICY_ISSUANCE);
    }

    public function getStepsLockingStatus($quote): array
    {
        $policyIssuance = $quote->policyIssuance;
        $response = [
            'policyIssuance' => $policyIssuance,
            'isEditPolicyDetailsDisabled' => true,
            'isPolicyDocumentUploadDisabled' => true,
            'isEditBookingDetailsDisabled' => true,
            'message' => 'All steps are locked',
            'insurer_api_status' => $quote->insurer_api_status,
        ];

        if ($policyIssuance?->status === PolicyIssuanceEnum::FAILED_STATUS) {
            if (! $policyIssuance->completed_step || $policyIssuance->completed_step === PolicyIssuanceEnum::ALLIANCE_TRAVEL_ISSUE_POLICY) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isPolicyDocumentUploadDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'All Steps are editable';

                return $response;
            }
            if ($policyIssuance->completed_step === PolicyIssuanceEnum::ALLIANCE_TRAVEL_PURCHASE_POLICY) {
                $response['isPolicyDocumentUploadDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'Upload Documents and Update Booking Details are editable';

                return $response;
            }
            if ($policyIssuance->completed_step == PolicyIssuanceEnum::ALLIANCE_TRAVEL_UPLOAD_POLICY_DOCUMENTS) {
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'Booking Details is editable';

                return $response;
            }

            return $response;
        } elseif (! $policyIssuance) { /* && $quote->insurer_api_status_id */
            $response['isEditPolicyDetailsDisabled'] = false;
            $response['isPolicyDocumentUploadDisabled'] = false;
            $response['isEditBookingDetailsDisabled'] = false;
            $response['message'] = 'All Steps are editable';
        }

        return $response;
    }

    public function updateQuoteApiIssuanceStatusAndAllocate(TravelQuote $quote, $newInsurerApiStatus = null, $newApiIssuanceStatus = null)
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Start');

        $policyIssuanceAutomation = $quote->policyIssuance;
        $isPolicyBooked = $quote->quote_status_id === QuoteStatusEnum::PolicyBooked;
        $isPolicyBookingFailed = $quote->quote_status_id === QuoteStatusEnum::POLICY_BOOKING_FAILED;

        $isPolicyAutomationStatusCompleted = $policyIssuanceAutomation?->status == PolicyIssuanceEnum::COMPLETED_STATUS;
        $insurerApiStatus = $quote?->insurer_api_status;
        $apiIssuanceStatus = $quote?->api_issuance_status;

        $isInsurerApiStatusAlreadyFailed = $quote->isBookingFailed() || $quote->isPolicyIssuanceFailed();
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Existing Insurer API Status : '.$isInsurerApiStatusAlreadyFailed);

        /* If Quote API issuance status is not already set, than set insurer api and api issuance status */
        if (! $apiIssuanceStatus) {
            if ($isPolicyAutomationStatusCompleted && $isPolicyBooked && ! $insurerApiStatus) {
                $newApiIssuanceStatus = PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_YES_ID;

            } elseif ($isPolicyAutomationStatusCompleted && $isPolicyBookingFailed) {
                if (! $insurerApiStatus) {
                    $newInsurerApiStatus = PolicyIssuanceEnum::BOOKING_DETAILS_API_FAILED_STATUS_ID;
                }
                $newApiIssuanceStatus = PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID;
            }
        }
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code, [
            'insurerApiStatus' => $insurerApiStatus, 'apiIssuanceStatus' => $apiIssuanceStatus,
            'isPolicyAutomationStatusCompleted' => $isPolicyAutomationStatusCompleted, 'isPolicyBooked' => $isPolicyBooked,
            'isPolicyBookingFailed' => $isPolicyBookingFailed, 'newInsurerApiStatus' => $newInsurerApiStatus,
            'newApiIssuanceStatus' => $newApiIssuanceStatus,
        ]);

        $this->updateQuoteInsurerApiStatus($quote, $newInsurerApiStatus);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' updateQuoteInsurerApiStatus executed');

        $this->updateQuoteApiIssuanceStatus($quote, $newApiIssuanceStatus);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' updateQuoteApiIssuanceStatus executed');

        $this->allocateLead($quote, $isInsurerApiStatusAlreadyFailed);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.'  allocation of failed lead started');

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');
    }

    private function updateQuoteInsurerApiStatus($quote, $newInsurerApiStatus)
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' update Quote Insurer API  Status : '.$newInsurerApiStatus);
        if ($newInsurerApiStatus) {
            $quote->update(['insurer_api_status_id' => $newInsurerApiStatus]);
        }
    }

    private function updateQuoteApiIssuanceStatus($quote, $newApiIssuanceStatus)
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' update Quote API Issuance Status : '.$newApiIssuanceStatus);
        if ($newApiIssuanceStatus) {
            $quote->update(['api_issuance_status_id' => $newApiIssuanceStatus]);
        }
    }

    public function allocateLead($quote, $isInsurerApiStatusAlreadyFailed)
    {
        $uuid = $quote->uuid;
        info(self::class.' fn:'.__FUNCTION__.' - Going to allocate failed lead ................ Ref-ID: '.$uuid);
        $unassistedTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);

        $response = QuoteTypes::TRAVEL->allocate($uuid, $unassistedTeamId);
        if ($response && $response['advisorId']) {
            info(self::class.' fn:'.__FUNCTION__.' - Going to dispatch SendTravelAllianceFailedAllocationEmailJob & SendBookPolicyDocumentsJob ................ Ref-ID: '.$uuid);
            /* Send Failed notification only when Insurer API status is not failed already to prevent multiple email triggers and Insurer API Status is not null */
            if (! $isInsurerApiStatusAlreadyFailed && $quote?->insurer_api_status != null) {
                SendTravelAllianceFailedAllocationEmailJob::dispatch($uuid)->delay(now()->addSeconds(30));
            }

            if ($quote->quote_status_id === QuoteStatusEnum::PolicyBooked) {
                // Here we need to dispatch document email
                $data = new \stdClass;
                $data->model_type = self::TYPE;
                $data->quote_id = $quote->id;
                info(self::class.' fn:'.__FUNCTION__.' - Quote Code : '.$quote->code.' - Dispatching SendBookPolicyDocumentsJob advisor id '.$quote->advisor_id);
                SendBookPolicyDocumentsJob::dispatch($data, $quote->code);
            }

        }
    }

    private function uploadAndAttachToQuoteDocuments($quote, $documentUrl, $documentCode, $originalName = null)
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $documentType = DocumentType::where(['quote_type_id' => self::TYPE_ID, 'code' => $documentCode, 'is_active' => true])->first();

        $fileContents = Http::get($documentUrl);
        [$mimeType , $docName] = $this->getMimeTypeAndFileName($documentUrl);

        // upload file to azure
        $fileNameAzure = uniqid().'_'.$quote->uuid.'_'.$docName;
        $filePathAzure = 'documents/'.ucwords(self::TYPE).'/'.$fileNameAzure;
        Storage::disk('azureIM')->put($filePathAzure, $fileContents);

        $quote->documents()->create([
            'doc_name' => $docName,
            'original_name' => $originalName ?? $docName,
            'doc_url' => $filePathAzure,
            'doc_mime_type' => $mimeType,
            'document_type_code' => $documentType->code,
            'document_type_text' => $documentType->text,
            'doc_uuid' => generateUUID(),
        ]);

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Uploaded Document Name : '.$docName);

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');
    }

    private function getMimeTypeAndFileName($documentUrl): array
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' start');
        $httpHeaders = Http::head($documentUrl);

        $mimeType = $httpHeaders->header('Content-Type');
        $contentDisposition = $httpHeaders->header('Content-Disposition');

        $docName = basename(parse_url($documentUrl, PHP_URL_PATH));
        if ($contentDisposition && preg_match('/filename\*?=(?:UTF-\d\'\')?["\']?([^"\';\r\n]+)/', $contentDisposition, $matches)) {
            $docName = urldecode($matches[1]);
        }

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' ended');

        return [$mimeType, $docName];

    }

    private function getTravelDocumentMapping($docName): ?array
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Document Name : '.$docName.' started');
        if ($docName === 'Policy Tax Invoice') {
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Document Name : '.$docName.' - ', ['key' => $docName, 'code' => QuoteDocumentsEnum::TRAVEL_TAX_INVOICE]);

            return ['key' => $docName, 'code' => QuoteDocumentsEnum::TRAVEL_TAX_INVOICE];
        } elseif (str_contains($docName, 'Certificate of Insurance')) {
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Document Name : '.$docName.' - ', ['key' => $docName, 'code' => QuoteDocumentsEnum::TRAVEL_POLICY_SCHEDULE]);

            return ['key' => $docName, 'code' => QuoteDocumentsEnum::TRAVEL_POLICY_SCHEDULE];
        }

        return null;

    }

    private function storePolicyIssuanceLog($quote, $payload, $response, $endPoint, $step, $status = 'success')
    {
        $log = PolicyIssuanceLog::create([
            'policy_issuance_id' => $this->policyIssuance->id,
            'model_type' => $quote->getMorphClass(),
            'model_id' => $quote->id,
            'step' => $step,
            'endPoint' => $endPoint,
            'payload' => json_encode($payload),
            'response' => json_encode($response),
            'status' => $status,
        ]);

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Policy Issuance ID : '.$this->policyIssuance?->id.' Log ID : '.$log->id);
    }

    private function getTitle($gender)
    {
        $title = 'Mr';
        if (in_array($gender, [GenericRequestEnum::FEMALE, strtolower(GenericRequestEnum::FEMALE), GenericRequestEnum::FEMALE_SHORT_VALUE, GenericRequestEnum::FEMALE_SINGLE, GenericRequestEnum::FEMALE_SINGLE_VALUE])) {
            $title = 'Ms';
        }
        if (in_array($gender, [GenericRequestEnum::FEMALE_MARRIED, GenericRequestEnum::FEMALE_MARRIED_VALUE])) {
            $title = 'Mrs';
        }

        return $title;
    }

    private function allianceHttpCall($endPoint, $param)
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        $payload = array_merge($this->authParam, $param);
        $url = $this->baseUrl.$endPoint;

        return Http::timeout(20)->withHeaders($headers)->post($url, $payload);
    }

    private function calculateCoverDaysForExpiryDate($quote, $travelType): mixed
    {
        $coverDays = $quote->days_cover_for;
        $isInboundLead = $travelType === TravelQuoteEnum::ALLIANCE_IN_BOUND;
        $isMultiTripLead = $quote->coverage_code === TravelQuoteEnum::COVERAGE_CODE_MULTI_TRIP;
        if ($isInboundLead && $isMultiTripLead) { /* Multi Trip Inbound should have maximum 180 days cover */
            $coverDays = 180;
        }

        return $coverDays;
    }

}
