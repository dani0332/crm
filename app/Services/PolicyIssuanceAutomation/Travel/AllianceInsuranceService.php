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
use App\Enums\SendPolicyTypeEnum;
use App\Enums\TravelQuoteEnum;
use App\Interfaces\PolicyIssuanceInterface;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\PolicyIssuanceLog;
use App\Repositories\PolicyIssuanceRepository;
use App\Services\ApplicationStorageService;
use App\Services\SageApiService;
use App\Services\SplitPaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Storage;

class AllianceInsuranceService implements PolicyIssuanceInterface
{
    private $className = null;
    private mixed $baseUrl;
    private mixed $agencyId;
    private mixed $agencyCode;

    public const INSURER_CODE = InsuranceProvidersEnum::ALNC;
    public const TYPE = quoteTypeCode::Travel;
    public const TYPE_ID = QuoteTypeId::Travel;
    public const ISSUE_POLICY = 'IssuePolicy';
    public const PURCHASE_POLICY = 'PurchasePolicy';
    public const UPLOAD_POLICY_DOCUMENTS = 'UploadPolicyDocuments';
    public const FILL_POLICY_BOOKING_DETAILS = 'FillPolicyBookingDetails';
    public const BOOK_POLICY = 'BookPolicy';

    public mixed $vat = null;
    public $policyIssuance = null;
    public function __construct()
    {
        $this->className = basename(__CLASS__);
        $this->vat = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::VAT_VALUE);

        $this->baseUrl = config('constants.ALLIANCE_API_BASE_URL');
        $this->agencyId = config('constants.ALLIANCE_AGENCY_ID');
        $this->agencyCode = config('constants.ALLIANCE_AGENCY_CODE');

    }

    public function createPolicyIssuanceSchedule($quote, $insurer)
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        if (isAllianceTravelAutomationEnabled()) {

            $this->policyIssuance = PolicyIssuanceRepository::schedulePolicyIssuance($quote, $insurer, self::TYPE, $this->className);

        } else {
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' -  Alliance Travel Automation is disabled');
        }

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        return $this->policyIssuance;
    }

    public function handle($process)
    {
        $this->policyIssuance = $process;
        $quote = $process->model;

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' started');

        if (isAllianceTravelAutomationEnabled()) {
            $response = ['status' => false, 'error' => null, 'message' => null];
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Plan ID : '.$quote->plan_id);

            $selectedPlan = $quote->travelQuotePlanDetails()->where('plan_id', $quote->plan_id)->first();
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - TravelQuotePlanDetails ID : '.$selectedPlan->id);

            $isDuplicateOrCIRLead = ! empty($quote->parent_duplicate_quote_id);
            $payment = Payment::where('code', $quote->code)->mainLeadPayment()->first();

            if ($isDuplicateOrCIRLead && empty($payment)) {
                $payment = Payment::where([
                    'paymentable_id' => $quote->id, 'paymentable_type' => $quote->getMorphClass(),
                ])->mainLeadPayment()->first();
            }

            $travelType = TravelQuoteEnum::ALLIANCE_IN_BOUND;
            $directionCode = $quote->direction_code;
            if ($directionCode === TravelQuoteEnum::TRAVEL_UAE_OUTBOUND) {
                $travelType = TravelQuoteEnum::ALLIANCE_OUT_BOUND;
            }

            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Travel Type : '.$travelType);
            $lastCompletedStep = $process->completed_step;
            $nextStepToBeExecuted = $lastCompletedStep ? $this->getNextStep($lastCompletedStep) : PolicyIssuanceEnum::ALLIANCE_TRAVEL_ISSUE_POLICY;

            if ($nextStepToBeExecuted) {
                if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_ISSUE_POLICY) {
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                    $policyIssuanceResponse = $this->issuePolicyAndFillPolicyDetails($quote, $selectedPlan,
                        $travelType);
                    if (! $policyIssuanceResponse['status']) {
                        return $policyIssuanceResponse;
                    }
                    $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
                }

                $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_PURCHASE_POLICY) {
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                    $policyPurchaseResponse = $this->policyPurchase($quote, $payment, $travelType);
                    if (! $policyPurchaseResponse['status']) {
                        return $policyPurchaseResponse;
                    }
                    $process->update(['completed_step' => $policyPurchaseResponse['completed_step']]);
                }

                $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_UPLOAD_POLICY_DOCUMENTS) {
                    // Add Sleep because after purchase policy we need to wait for some time to get the policy documents generated. -- Policy documents are still generating
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted.' - Waiting for 10 seconds so  Provider can generate the policy documents');
                    sleep(10);
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                    $uploadPolicyDocumentResponse = $this->fetchAndUploadDocument($quote, $travelType);
                    if (! $uploadPolicyDocumentResponse['status']) {
                        return $uploadPolicyDocumentResponse;
                    }
                    $process->update(['completed_step' => $uploadPolicyDocumentResponse['completed_step']]);
                }

                $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_FILL_POLICY_BOOKING_DETAILS) {
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                    $fillPolicyDetailsResponse = $this->uploadBuyerTaxInvoiceAndFillBookingDetails($quote, $payment);
                    if (! $fillPolicyDetailsResponse['status']) {
                        return $fillPolicyDetailsResponse;
                    }
                    $process->update(['completed_step' => $fillPolicyDetailsResponse['completed_step']]);
                }
                $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
                if ($nextStepToBeExecuted === PolicyIssuanceEnum::ALLIANCE_TRAVEL_BOOK_POLICY) {
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Step Executing : '.$nextStepToBeExecuted);
                    $fillPolicyDetailsResponse = $this->triggerBookPolicyProcess($quote);
                    if (! $fillPolicyDetailsResponse['status']) {
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
            $response['status'] = false;
            $response['message'] = 'Alliance Travel Automation is disabled';

        }

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' ended');

        return $response;
    }

    public function issuePolicyAndFillPolicyDetails($quote, $selectedPlan, $travelType): array
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $response = ['status' => false, 'completed_step' => PolicyIssuanceEnum::ALLIANCE_TRAVEL_ISSUE_POLICY, 'error' => null, 'message' => null];

        $title = 'Mr';
        $dateOfBirth = $quote->dob ? Carbon::parse($quote->dob)->format('Y-m-d') : null;
        if (in_array($quote->gender, [GenericRequestEnum::FEMALE, strtolower(GenericRequestEnum::FEMALE), GenericRequestEnum::FEMALE_SHORT_VALUE])) {
            $title = 'Mrs';
        }

        $customerMember = $quote->customerMembers()->where('customer_entity_id', $quote->customer_id)->first();

        $endpoint = $this->baseUrl.'/v1/quote/'.$travelType.'/finalise';
        $payload = [
            'agency_id' => $this->agencyId,
            'agency_code' => $this->agencyCode,
            'quote_id' => $selectedPlan->insurer_quote_id,
            'scheme_id' => $selectedPlan->alliance_scheme_id,
            'title_customer' => $title,
            'first_name_customer' => $quote->first_name,
            'last_name_customer' => $quote->last_name,
            'title_traveller' => [$title],
            'first_name_traveller' => [$quote->first_name],
            'last_name_traveller' => [$quote->last_name],
            'dob' => [$dateOfBirth],
            'passport_number' => [$customerMember->passport],
            'nationality_traveller' => [$quote->nationality_id],
            'email' => $quote->email,
            'mobile' => $quote->mobile_no,
            'agency_reference' => 'asc',
        ];
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payload));

        $issuePolicy = Http::post($endpoint, $payload);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$issuePolicy);

        $issuePolicyResponse = $issuePolicy->object();
        $this->storePolicyIssuanceLog($quote, $payload, $issuePolicyResponse, $response['completed_step']);

        if ($issuePolicy->failed()) {
            $response['error'] = $issuePolicyResponse?->errors;

            return $response;
        }
        $issuePolicyResult = $issuePolicyResponse?->result;
        $insurerPolicyId = $issuePolicyResult?->policy_id;
        $premium = $issuePolicyResult?->premium;
        $priceVatApplicable = $premium / (1 + ((float) $this->vat / 100));
        $policyIssuanceDate = Carbon::now();
        $policyExpiryDate = Carbon::parse($quote->policy_start_date)->addDays($quote->days_cover_for);

        $quote->update([
            'insurer_policy_id' => $insurerPolicyId,
            'price_without_vat' => $priceVatApplicable,
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
        $endpoint = $this->baseUrl.'/v1/quote/'.$travelType.'/purchase';

        $payload = [
            'agency_id' => $this->agencyId,
            'agency_code' => $this->agencyCode,
            'policy_id' => $quote->insurer_policy_id,
        ];
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payload));

        $policyPurchase = Http::post($endpoint, $payload);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$policyPurchase);

        $policyPurchaseResponse = $policyPurchase->object();
        $this->storePolicyIssuanceLog($quote, $payload, $policyPurchaseResponse, $response['completed_step']);

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
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');
        $response = ['status' => false, 'completed_step' => PolicyIssuanceEnum::ALLIANCE_TRAVEL_UPLOAD_POLICY_DOCUMENTS, 'error' => null, 'message' => null];

        $payload = [
            'agency_id' => $this->agencyId,
            'agency_code' => $this->agencyCode,
            'policy_id' => $quote->insurer_policy_id,
        ];
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payload));

        $endpoint = $this->baseUrl.'/v1/policy/'.$travelType.'/documents';
        $policyDocuments = Http::post($endpoint, $payload);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$policyDocuments);

        $policyDocumentsResponse = $policyDocuments->object();
        $this->storePolicyIssuanceLog($quote, $payload, $policyDocumentsResponse, $response['completed_step']);
        if ($policyDocuments->failed()) {
            $response['error'] = $policyDocumentsResponse?->errors;

            return $response;
        }

        $policyDocumentsResult = $policyDocumentsResponse?->result;
        $policyDocuments = $policyDocumentsResult?->policy_documents;
        foreach ($policyDocuments as $policyDocument) {
            $policyDocumentCode = $this->getTravelDocumentMapping($policyDocument->name);
            if ($policyDocumentCode) {
                $this->uploadAndAttachToQuoteDocuments($quote, $policyDocument->url, $policyDocumentCode['code'], $policyDocument->name);
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
            'agency_id' => $this->agencyId,
            'agency_code' => $this->agencyCode,
            'policy_id' => $quote->insurer_policy_id,
            'policy_number' => $quote->policy_number,
        ];
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PayLoad : '.json_encode($payload));

        $endpoint = $this->baseUrl.'/v1/agency/buyer-tax-invoices';
        $buyerTaxInvoice = Http::post($endpoint, $payload);
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Response : '.$buyerTaxInvoice);

        $buyerTaxInvoiceResponse = $buyerTaxInvoice->object();
        $this->storePolicyIssuanceLog($quote, $payload, $buyerTaxInvoiceResponse, $response['completed_step']);

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
            'commission' => $bookingDetails->agency_commission_inc_tax,
            'commission_vat' => $bookingDetails->agency_commission_tax,
            'commission_vat_applicable' => $bookingDetails->agency_commission,
            'commmission_percentage' => ($bookingDetails->agency_commission / ($bookingDetails->premium / (1 + (float) $bookingDetails->tax_rate))) * 100,
            'insurer_commmission_invoice_number' => $bookingDetails->tax_invoice_number,
            'insurer_invoice_date' => $insurerInvoiceDate,
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
        return isAllianceTravelAutomationEnabled();
    }

    public function getStepsLockingStatus($policyIssuance): array
    {
        $response = [
            'policyIssuance' => $policyIssuance,
            'isEditPolicyDetailsDisabled' => true,
            'isPolicyDocumentUploadDisabled' => true,
            'isEditBookingDetailsDisabled' => true,
            'message' => 'All steps are locked',
        ];

        if ($policyIssuance->status === PolicyIssuanceEnum::FAILED_STATUS) {
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
        }

        return $response;
    }

    private function uploadAndAttachToQuoteDocuments($quote, $documentUrl, $documentCode, $originalName = null)
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $documentType = DocumentType::where(['quote_type_id' => self::TYPE_ID, 'code' => $documentCode, 'is_active' => true])->first();

        $fileContents = Http::get($documentUrl);
        [$mimeType , $docName] = $this->getMimeTypeAndFileName($documentUrl);

        //upload file to azure
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
        $httpHeaders = Http::head($documentUrl);

        $mimeType = $httpHeaders->header('Content-Type');
        $contentDisposition = $httpHeaders->header('Content-Disposition');

        $docName = basename(parse_url($documentUrl, PHP_URL_PATH));
        if ($contentDisposition && preg_match('/filename\*?=(?:UTF-\d\'\')?["\']?([^"\';\r\n]+)/', $contentDisposition, $matches)) {
            $docName = urldecode($matches[1]);
        }

        return [$mimeType, $docName];

    }

    private function getTravelDocumentMapping($docName): ?array
    {
        return match ($docName) {
            'Policy Tax Invoice' => ['key' => $docName, 'code' => QuoteDocumentsEnum::TRAVEL_TAX_INVOICE],
            'Certificate of Insurance' => ['key' => $docName, 'code' => QuoteDocumentsEnum::TRAVEL_POLICY_SCHEDULE],
            default => null,
        };
    }

    private function storePolicyIssuanceLog($quote, $payload, $response, $step)
    {
        $log = PolicyIssuanceLog::create([
            'policy_issuance_id' => $this->policyIssuance->id,
            'model_type' => $quote->getMorphClass(),
            'model_id' => $quote->id,
            'step' => $step,
            'payload' => json_encode($payload),
            'response' => json_encode($response),
        ]);

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Policy Issuance ID : '.$this->policyIssuance?->id.' Log ID : '.$log->id);
    }

}
