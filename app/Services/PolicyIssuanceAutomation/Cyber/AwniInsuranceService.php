<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Http\Requests\BookPolicyRequest;
use App\Http\Requests\SendBookPolicyRequest;
use App\Interfaces\PolicyIssuanceInterface;
use App\Models\CyberPlan;
use App\Models\CyberQuote;
use App\Models\Payment;
use App\Services\ApplicationStorageService;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\QuoteDocumentService;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AwniInsuranceService implements PolicyIssuanceInterface
{
    use GenericQueriesAllLobs;
    
    private $className = 'awniInsuranceService';
    private readonly string $baseUrl;
    private mixed $authParam;

    public const TYPE = quoteTypeCode::CYBER;
    public const TYPE_ID = QuoteTypeId::Cyber;

    public mixed $vat = null;
    public $policyIssuance = null;

    public $currentInsurerApiStatus = null;
    public $headers = [];
    private $appEnv;
    private $apiTimeout;

    public const UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const ISSUE_POLICY = 'IssuePolicy';
    public const UPLOAD_POLICY_DOCUMENTS_TO_IMCRM = 'UploadPolicyDocumentsToIMCRM';
    public const DOWNLOAD_DOCUMENT_RESPONSE = 'DownloadDocumentResponse';
    public const BOOK_POLICY = 'BookPolicy';
    public const POLICY_ISSUANCE_RESPONSE = 'PolicyResponse';
    public const UPLOAD_DOCUMENTS_RESPONSE = 'UploadDocumentsResponse';
    public const RETRIEVE_RESPONSE = 'RetrieveResponse';


    public function __construct()
    {
        $this->baseUrl = config('constants.AWNI_API_BASE_URL');
        $this->className = 'awniInsuranceService';
        $this->apiTimeout = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::AWNI_CYBER_AUTOMATION_API_TIMEOUT);
        $this->headers = [
            'Partner-Id' => config('constants.AWNI_API_PARTNER_ID'),
            'Api-Key' => config('constants.AWNI_API_SECRET_KEY'),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Api Steps for Awni Insurance Service to execute the policy issuance automation
     * for AWNI issue policy is the first step while other insurer automation steps are different
     *
     * @return array
     */
    private function getAPISteps(): array
    {
        return [
            self::ISSUE_POLICY,
            self::UPLOAD_DOCUMENTS,
            self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            self::BOOK_POLICY,
        ];
    }

    /**
     * Check if the policy issuance automation is enabled in the application storage
     * 
     * @return boolean
     */
    public function isPolicyIssuanceAutomationEnabled()
    {
        return app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_AWNI_CYBER_POLICY_ISSUANCE);
    }

    /**
     * Policy Issuance Automation Retry Enabled for Timeout
     * Check if the policy issuance automation retry is enabled for timeout in the application storage
     *
     * @return boolean
     */
    public function isPolicyIssuanceAutomationRetryEnabledForTimeout(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_AWNI_CYBER_POLICY_ISSUANCE);
    }

    /**
     * Schedule the policy issuance automation for AWNI Cyber Insurance
     *
     * @param Model $quote
     * @param Insurer $insurer
     * @return void
     */
    public function createPolicyIssuanceSchedule($quote, $insurer)
    {
        LoggerService::startQuoteLogging($quote);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        if ($this->isPolicyIssuanceAutomationEnabled()) {
            $this->policyIssuance = (new PolicyIssuanceService)->schedulePolicyIssuance($quote, $insurer, self::TYPE, $this->className);
        } else {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - AWNI Cyber Automation is disabled');
        }
    }

    /**
     * Execute Steps for Policy Issuance Automation for AWNI Cyber Insurance
     *
     * @param Process $process
     * @return array
     */
    public function executeSteps($process)
    {
        $response = ['status' => false, 'error' => null, 'message' => null];

        $this->policyIssuance = $process;
        $quote = $process->model;

        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::POLICY_AUTOMATION);
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - PID : ' . $process->id . ' - Plan ID : ' . $quote->plan_id . ' started');

        try {
            if (! $this->isPolicyIssuanceAutomationEnabled()) {
                LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - AWNI Cyber Automation is disabled');
                $response['error'] = 'AWNI Cyber Automation is disabled';
                $response['message'] = 'AWNI Cyber Automation is disabled';

                return $response;
            }
            // dd($quote->payments, $quote->cyberPlanDetail);
            $customer = $quote->customer;
            if (!$quote->payments || !$quote->cyberPlanDetail || !$customer->emirates_id_number) {
                LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Payments or cyber plan detail or emirates id number not found');
                $response['error'] = 'Payments or cyber plan detail or emirates id number not found';
                $response['message'] = 'Payments or cyber plan detail or emirates id number not found';

                return $response;
            }

            $lastCompletedStep = $process->completed_step;
            $nextStepToBeExecuted = $lastCompletedStep ? $this->getNextStep($lastCompletedStep) : $this->getAPISteps()[0];
            $executeStepSequence = $this->executeStepSequence($quote, $process, $nextStepToBeExecuted);

            $response['status'] = $executeStepSequence['status'];
            $response['message'] = $executeStepSequence['message'];
            $response['error'] = $executeStepSequence['error'];
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Exception : ' . $e->getMessage());

            return $response;
        }

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - PID : ' . $process->id . ' ended');

        return $response;
    }

    /**
     * Execute Step Sequence for Policy Issuance Automation for AWNI Cyber Insurance
     *
     * @param Model $quote
     * @param Model $process
     * @param string $nextStepToBeExecuted
     * @return array
     */
    private function executeStepSequence($quote, $process, $nextStepToBeExecuted)
    {
        if ($nextStepToBeExecuted === self::ISSUE_POLICY) {
            $issuePolicyResponse = $this->executeIssuePolicyStep($quote, $process);
            if (isset($issuePolicyResponse['status']) && ! $issuePolicyResponse['status']) {
                return $issuePolicyResponse;
            }

            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === self::UPLOAD_DOCUMENTS) {
            $uploadDocumentsResponse = $this->executeUploadDocumentsStep($quote, $process);
            if (isset($uploadDocumentsResponse['status']) && ! $uploadDocumentsResponse['status']) {
                return $uploadDocumentsResponse;
            }

            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }        

        if ($nextStepToBeExecuted === self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM) {
            $uploadPolicyDocumentsToIMCRMResponse = $this->executeUploadPolicyDocumentsStep($quote, $process);
            if (isset($uploadPolicyDocumentsToIMCRMResponse['status']) && ! $uploadPolicyDocumentsToIMCRMResponse['status']) {
                return $uploadPolicyDocumentsToIMCRMResponse;
            }

            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === self::BOOK_POLICY) {
            $bookPolicyResponse = $this->executeBookPolicyStep($quote, $process);
            if (isset($bookPolicyResponse['status']) && ! $bookPolicyResponse['status']) {
                return $bookPolicyResponse;
            }

            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        // Return success response when all steps are completed
        return [
            'status' => true,
            'message' => 'All policy issuance steps completed successfully',
            'completed_step' => $nextStepToBeExecuted,
        ];
    }

    /**
     * Get Next Step for Policy Issuance Automation for AWNI Cyber Insurance
     *
     * @param string $completedStep
     * @return string|null
     */
    public function getNextStep($completedStep = null): ?string
    {
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . '- Completed Step : ' . $completedStep);
        $allSteps = $this->getAPISteps();

        if (! $completedStep) {
            return $allSteps[0];
        }

        $completedStepIndex = array_search($completedStep, $allSteps);
        if ($completedStepIndex === false || $completedStepIndex === count($allSteps) - 1) {
            return null;
        }

        return $allSteps[$completedStepIndex + 1];
    }

    /**
     * Execute Upload Documents Step for Policy Issuance Automation for AWNI Cyber Insurance
     *
     * @param Model $quote
     * @param Model $process
     * @return array
     */
    private function executeUploadDocumentsStep($quote, $process)
    {
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Step Executing : ' . self::UPLOAD_DOCUMENTS);
        $uploadDocumentsResponse = $this->uploadDocuments($quote);

        if (! $uploadDocumentsResponse['status']) {
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Document upload failed', extra: ['response' => $uploadDocumentsResponse]);

            $this->currentInsurerApiStatus = PolicyIssuanceEnum::PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID;
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CYBER->value, PolicyIssuanceEnum::PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'Document Upload');

            return $uploadDocumentsResponse;
        }

        $process->update(['completed_step' => $uploadDocumentsResponse['completed_step']]);
        $process = $process->refresh();
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $process->model->code . ' - Policy Issuance ID : ' . $process->id . ' - Completed Step Updated to : ' . $uploadDocumentsResponse['completed_step']);

        return $uploadDocumentsResponse;
    }

    /**
     * Execute the issue policy step for the policy issuance automation for AWNI Cyber Insurance
     *
     * @param Model $quote
     * @param Model $process
     * @return array
     */
    private function executeIssuePolicyStep($quote, $process)
    {
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Step Executing : ' . self::ISSUE_POLICY);
        $policyIssuanceResponse = $this->issuePolicy($quote, $process);
        // dd($policyIssuanceResponse);
        if (! $policyIssuanceResponse['status']) {
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Policy issuance failed', extra: ['response' => $policyIssuanceResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CYBER->value, PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'Policy Creation');

            return $policyIssuanceResponse;
        }

        $quote->update([
            'quote_status_id' => QuoteStatusEnum::PolicyIssued,
            'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued,
            'quote_status_date' => now(),
        ]);

        $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
        $process = $process->refresh();

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $process->model->code . ' - Process ID : ' . $process->id . ' - Completed Step Updated to : ' . $policyIssuanceResponse['completed_step']);

        return $policyIssuanceResponse;
    }

    private function executeUploadPolicyDocumentsStep($quote, $process)
    {
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Step Executing : ' . self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);
        $uploadPolicyDocumentsToIMCRMResponse = $this->uploadPolicyDocumentsToIMCRM($quote, $process);

        if (! $uploadPolicyDocumentsToIMCRMResponse['status']) {
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Policy issuance failed', extra: ['response' => $uploadPolicyDocumentsToIMCRMResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CYBER->value, PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'Retrieve Document');

            return $uploadPolicyDocumentsToIMCRMResponse;
        }

        $process->update(['completed_step' => $uploadPolicyDocumentsToIMCRMResponse['completed_step']]);
        $process = $process->refresh();

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $process->model->code . ' - Process ID : ' . $process->id . ' - Completed Step Updated to : ' . $uploadPolicyDocumentsToIMCRMResponse['completed_step']);

        return $uploadPolicyDocumentsToIMCRMResponse;
    }

    private function executeBookPolicyStep($quote, $process)
    {
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Step Executing : ' . self::BOOK_POLICY);
        $triggerBookPolicyResponse = $this->bookPolicy($quote);

        if (! $triggerBookPolicyResponse['status']) {
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Policy issuance failed', extra: ['response' => $triggerBookPolicyResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CYBER->value, PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'Send And Book Policy');

            return $triggerBookPolicyResponse;
        }

        $process->update(['completed_step' => $triggerBookPolicyResponse['completed_step']]);
        $process = $process->refresh();

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $process->model->code . ' - Process ID : ' . $process->id . ' - Completed Step Updated to : ' . $triggerBookPolicyResponse['completed_step']);

        return $triggerBookPolicyResponse;
    }

    public function issuePolicy($quote, $process): array
    {
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' started - Policy Issuance ID : ' . $process->id . ' - Step : ' . self::ISSUE_POLICY);
        $response = ['status' => false, 'completed_step' => self::ISSUE_POLICY, 'error' => null, 'message' => null];

        $endPoint = '/cyber/generatePolicy';
        
        $customer = $quote->customer;
        $nationality = $quote->nationality;
        $planDetail = $quote->cyberPlanDetail;

        $payment = $quote->payments()->mainLeadPayment()->first();
        $splitPayment = $payment?->paymentSplits()->where('payment_method', PaymentMethodsEnum::CreditCard)->first();

        // CustCode is hardcoded, i have tried different values but it is not working
        $payload = [
            'CustName' => trim(($quote->first_name ?? '') . ' ' . ($quote->last_name ?? '')),
            'CustMobile' => $quote->mobile_no,
            'CustEmail' => $quote->email,
            'CustEID' => str_replace('-', '', $customer->emirates_id_number ?? "784200012345671"),
            'CustDOB' => $customer->dob ? strtoupper(Carbon::parse($customer->dob)->format('d-M-Y')) : null,
            'CustAddress' => $quote->company_address ?? "abc address",
            'CustCountryCode' => $nationality?->awni_country_code ?? null,
            'LimitOfLiability' => $planDetail->coverage ?? null,
            'PlanName' => $planDetail->planName ?? null,
            // 'PolStartDate' => $quote->policy_start_date ? strtoupper(Carbon::parse($quote->policy_start_date)->format('d-M-Y')) : strtoupper(\Carbon\Carbon::parse($payment->collection_date)->format('d-M-Y')),
            'PolStartDate' => strtoupper(Carbon::now()->format('d-M-Y')),
            'CustCode' => 150214,
            'BrokerCode' => 150214,
            'PaymentRefNo' => $splitPayment?->reference,
            'PartnerRefNo' => $quote->code,
        ];

        $issuePolicy = $this->httpCall($endPoint, $payload, self::POLICY_ISSUANCE_RESPONSE);
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Response', extra: ['response' => $issuePolicy]);
        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $issuePolicy, $this->baseUrl . $endPoint, self::ISSUE_POLICY, $issuePolicy['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

        if (! $issuePolicy['status']) {
            $response['error'] = $issuePolicy['error'];
            $response['message'] = $issuePolicy['message'];
            $response['status'] = false;

            return $response;
        }

        $issuePolicyResult = $issuePolicy['data'];
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ', updating quote and payment information from AWNI createPolicyRequest response');

        $quote->update([
            'policy_number' => $issuePolicyResult?->policyInfo?->policyNo,
            'policy_issuance_date' => $issuePolicyResult?->policyInfo?->policyIssuedDate,
            'policy_start_date' => $issuePolicyResult?->policyInfo?->policyStartDate,
            'policy_expiry_date' => $issuePolicyResult?->policyInfo?->policyEndDate,
            'price_vat_applicable' => $issuePolicyResult?->policyInfo?->premiumAmount,
            'vat' => $issuePolicyResult?->policyInfo?->prmVatAmt,
            'price_with_vat' => $issuePolicyResult?->policyInfo?->prmPayableAmt,
            'insurer_quote_number' => $issuePolicyResult?->QuoteRefNo ?? null,
        ]);

        $quote->cyberQuote->update([
            'awni_drcr_doc_id' => $issuePolicyResult?->policyInfo?->drcrDocId,
            'awni_tax_invoice_doc_id' => $issuePolicyResult?->policyInfo?->taxInvoiceDocId,
            'awni_policy_doc_id' => $issuePolicyResult?->policyInfo?->policyDocId,
        ]);

        Payment::where('code', $quote->code)->update([
            'commission_vat_applicable' => $issuePolicyResult?->policyInfo?->commissionPayableAmt,
            'commission' => $issuePolicyResult?->policyInfo?->commissionAmt,
            'commission_vat' => $issuePolicyResult?->policyInfo?->commissionVatAmt,
            'commmission_percentage' => $issuePolicyResult?->policyInfo?->CommissionPercentage ?? 0, // TODO: need to verify commission percentage is not coming in response
            'insurer_tax_number' => $issuePolicyResult?->policyInfo?->invoiceNo ?? null,
            'insurer_invoice_date' => $issuePolicyResult?->policyInfo?->policyIssuedDate ?? null,
            'insurer_commmission_invoice_number' => $issuePolicyResult?->policyInfo?->creditNoteNo ?? null,

        $response['status'] = true;
        $response['message'] = 'Policy issued successfully';
        $response['completed_step'] = self::ISSUE_POLICY;
        $response['data'] = $issuePolicyResult;

        return $response;
    }

    public function uploadDocuments($quote)
    {
        $endPoint = '/cyber/uploadDocument';
        $response = ['status' => false, 'completed_step' => self::UPLOAD_DOCUMENTS, 'error' => null, 'message' => null];
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' started', extra: [
            'endPoint' => $endPoint,
        ]);

        $documents = $quote->documents;
        $requiredDocuments = array_filter($documents->toArray(), function ($document) {
            return in_array($document['document_type_code'], [DocumentTypeCode::CYBER_EMIRATES_ID]);
        });

        if (empty($requiredDocuments)) {
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' - Document upload validation check failed');

            $response['message'] =
                $response['error'] = 'Required Documents not uploaded';
            $response['status'] = false;

            return $response;
        }

        $attachments = [];
        $requiredDocuments = collect($requiredDocuments)->where('document_type_code', DocumentTypeCode::CYBER_EMIRATES_ID)->first();

        if($requiredDocuments === null) {
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' - Required Documents not found');

            $response['message'] =
                $response['error'] = 'Required Documents not found';
            $response['status'] = false;

            return $response;
        }

        $documentName = $requiredDocuments['doc_name'];
        $documentType = $this->getDocTypeCodeForCyber($requiredDocuments['document_type_code']);
        $filePath = config('constants.AZURE_IM_STORAGE_URL') . config('constants.AZURE_IM_STORAGE_CONTAINER') . '/' . $requiredDocuments['doc_url'];
        $fileContent = file_get_contents($filePath);
        // Ensure the file exists and is a valid document before encoding
        if ($fileContent === false || empty($fileContent)) {
            LoggerService::error('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' - Invalid or empty document content at ' . $filePath);
            $response['message'] =
                $response['error'] = 'Invalid or empty document content';
            $response['status'] = false;
            return $response;
        }

        // Optionally, perform a MIME type check to ensure valid PDF/JPEG/etc
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $fileContent);
        finfo_close($finfo);

        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($mimeType, $allowedMimeTypes)) {
            LoggerService::error('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' - Unsupported document type: ' . $mimeType);
            $response['message'] =
                $response['error'] = 'Unsupported document type: ' . $mimeType;
            $response['status'] = false;
            return $response;
        }

        // API expects only the Base64 encoded content itself, not data URI format
        $base64Content = base64_encode($fileContent);
        // TODO: Document upload is a problem need to confirm from 

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Payload created with Document Type: ' . $documentType . ' and Document Name: ' . $documentName);

        $payload = [
            "QuoteRefNo" => $quote->insurer_quote_number,
            "DocCategory" => $documentType,
            "DocName" => 'Emirates_Id.png',
            "DocContent" => $base64Content,
        ];

        $response = $this->httpCall($endPoint, $payload, self::UPLOAD_DOCUMENTS_RESPONSE);
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Response', extra: ['response' => $response]);
        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $response, $this->baseUrl . $endPoint, self::UPLOAD_DOCUMENTS, $response['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

        if (! $response['status']) {
            $response['message'] = $response['error'];
            $response['error'] = $response['error'];

            return $response;
        }

        $response['status'] = true;
        $response['message'] = 'Documents uploaded successfully';
        $response['completed_step'] = self::UPLOAD_DOCUMENTS;
        $response['data'] = $response;

        return $response;
    }

    public function uploadPolicyDocumentsToIMCRM($quote, $process): array
    {
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' started - Policy Issuance ID : ' . $process->id . ' - Step : ' . self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);

        $response = ['status' => false, 'completed_step' => self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM, 'error' => null, 'message' => null];
        $endPoint = '/cyber/downloadDocument';

        $uploadedDocumentsToIMCRM = collect();

        $cyberQuote = $quote->cyberQuote;
        foreach ($this->getDocTypeCodeForIMCRM($cyberQuote) as $i => $docId) {
            $payload = [
                "docId" => $docId,
            ];

            $downloadRequest = $this->httpCall($endPoint, $payload, self::DOWNLOAD_DOCUMENT_RESPONSE);

            app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $downloadRequest, $this->baseUrl . $endPoint, self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM, $downloadRequest['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);
            // dd($downloadRequest);
            if(isset($downloadRequest['status'])) {
                $docCode = $i;

                $documentContent = $downloadRequest['data'];
                // TODO: need to map document according to IMCRM cyber document types
                $quoteDocument = $this->uploadAndAttachToQuoteDocuments($quote, $documentContent->documentContent, $docCode, $documentContent->documentName);

                $uploadedDocumentsToIMCRM->push([
                    'name' => $docId,
                    'uploaded' => $quoteDocument?->id ?? false,
                    'status' => $downloadRequest['status'],
                    'message' => $downloadRequest['message'] ?? 'Document Retrieve Failed',
                ]);
            }
        };

        $allDocsDownload = $uploadedDocumentsToIMCRM->where('status', true)->count() === 3;

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' - allDocumentsUploaded', extra: [
            'allDocsDownload' => $allDocsDownload,
            'docCount' => $uploadedDocumentsToIMCRM->count()
        ]);

        if (! $allDocsDownload || empty($uploadedDocumentsToIMCRM)) {
            $docsUploadToIMCRMFailed = $uploadedDocumentsToIMCRM->where('status', false)->pluck('name')->toArray();
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - failed to fetch all documents from insurer : ', $docsUploadToIMCRMFailed);

            $error = 'Policy Issuance is pending as ' . implode(',', $docsUploadToIMCRMFailed) . ' documents are not uploaded';
            $response['error'] = $error;
            $response['message'] = $error;
            $response['status'] = false;

            return $response;
        }

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - fetched all documents from insurer and Uploaded to IMCRM ');
        $response['status'] = true;
        $response['message'] = 'Fetched all documents from insurer and Uploaded to IMCRM';
        $response['completed_step'] = self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM;

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Process completed step updated to : ' . $response['completed_step']);

        return $response;
    }

    /**
     * This function use upload document at IMCRM
     *
     * @param [type] $quote
     * @param [type] $documentContent
     * @param [type] $documentCode
     * @param [type] $originalName
     * @return void
     */
    private function uploadAndAttachToQuoteDocuments($quote, $documentContent, $documentCode, $originalName = null)
    {
        $quoteType = QuoteTypes::CYBER->value;
        // Ensure is_base_64 flag is set in data for proper handling
        $data['is_base_64'] = 1;
        $data['quote_uuid'] = $quote->uuid;
        $data['quote_type'] = $quoteType;
        $data['file_name'] = $originalName;
        $data['document_type_code'] = $documentCode;

        $quoteDocumentService = new QuoteDocumentService;
        $document = $quoteDocumentService->uploadQuoteDocument($documentContent, $data, $quote);
        return $document;
    }

    public function bookPolicy($quote): array
    {
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' started');
        $response = ['status' => false, 'completed_step' => self::BOOK_POLICY, 'error' => null, 'message' => null];

        $updateBookingDetailsResponse = $this->updateBookingDetails($quote);
        if (! $updateBookingDetailsResponse['status']) {
            $response['error'] = $updateBookingDetailsResponse['error'];
            $response['message'] = $updateBookingDetailsResponse['message'];

            return $response;
        }

        $quote->refresh();
        $preCheckResult = $this->validateBookPolicy($quote);
        if (! $preCheckResult['status']) {
            $response['error'] = $preCheckResult['error'];
            $response['message'] = $preCheckResult['message'];

            return $response;
        }

        $request = new \stdClass;
        $request->quote_id = $quote->id;
        $request->modelType = self::TYPE;
        $request->model_type = self::TYPE;
        $request->is_send_policy = false;
        $request->send_policy_type = SendPolicyTypeEnum::SAGE;
        $request->transaction_payment_status = null;

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Book Policy execution initiated, creating Sage process');
        $createSageProcessResponse = (new SageApiService)->postBookPolicyToSage($request, $quote);
        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, [], $createSageProcessResponse, '', self::BOOK_POLICY, $createSageProcessResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

        if (! $createSageProcessResponse['status']) {
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Book Policy execution failed, Error: ' . $createSageProcessResponse['message']);
            $response['error'] = $createSageProcessResponse['message'];

            return $response;
        }

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' Sage Process Created : ' . $createSageProcessResponse['message']);

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' ended');

        $response['status'] = true;
        $response['message'] = 'Booking process in started! It will take some time to Complete. Come Back in a while to check the status!';

        return $response;
    }

    private function updateBookingDetails($quote): array
    {
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Update booking details process started');
        $response = ['status' => true, 'error' => null, 'message' => null];

        $payment = $quote->payments()->mainLeadPayment()->first();
        $bookPolicyPayload = $this->bookPolicyPayload($quote, QuoteTypes::CYBER->value, $quote->payments, $quote->quoteDocuments);

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Booking Details before exeuting validation', extra: [
            'invoice_date' => $payment->insurer_invoice_date,
            'insurer_tax_invoice_number' => $payment->insurer_tax_number,
            'insurer_commmission_invoice_number' => $payment->insurer_commmission_invoice_number,
            'discount' => $payment->discount_value,
            'transaction_payment_status' => $bookPolicyPayload['transactionPaymentStatus'],
            'broker_invoice_number' => $bookPolicyPayload['brokerInvoiceNo'],
            'commission_vat_not_applicable' => $payment->commission_vat_not_applicable,
            'commission_vat_applicable' => $payment->commission_vat_applicable,
            'total_commission' => $payment->commission,
            'invoice_description' => $bookPolicyPayload['invoiceDescription'],
            'vat_on_commission' => $payment->commission_vat,
            'commission_percentage' => $payment->commmission_percentage,
            'payment_code' => $payment->code,
            'model_type' => self::TYPE,
            'quote_id' => $quote->id,
        ]);

        try {
            $updateBookingRequest = [
                'invoice_date' => $payment->insurer_invoice_date,
                'insurer_tax_invoice_number' => $payment->insurer_tax_number,
                'insurer_commmission_invoice_number' => $payment->insurer_commmission_invoice_number,
                'discount' => $payment->discount_value,
                'transaction_payment_status' => $bookPolicyPayload['transactionPaymentStatus'],
                'broker_invoice_number' => $bookPolicyPayload['brokerInvoiceNo'],
                'commission_vat_not_applicable' => $payment->commission_vat_not_applicable,
                'commission_vat_applicable' => $payment->commission_vat_applicable,
                'total_commission' => $payment->commission,
                'invoice_description' => $bookPolicyPayload['invoiceDescription'],
                'vat_on_commission' => $payment->commission_vat,
                'commission_percentage' => $payment->commmission_percentage,
                'payment_code' => $payment->code,
                'model_type' => self::TYPE,
                'quote_id' => $quote->id,
                'through_automation' => true,
            ];

            request()->merge($updateBookingRequest);

            $bookPolicyRequest = new BookPolicyRequest();
            $validator = Validator::make($updateBookingRequest, $bookPolicyRequest->rules());
            $bookPolicyRequest->withValidator($validator);

            if ($validator->fails()) {
                $response['status'] = false;
                $response['error'] = $validator->errors()->first() ?? 'BookPolicyRequest validation failed';
                $response['message'] = $validator->errors()->first();

                LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - BookPolicyRequest validation failed: ' . $response['message']);

                return $response;
            }

            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Updating booking details');
            $updateBookingDetailsResponse = app(CentralService::class)->updateBookingDetails($updateBookingRequest, $bookPolicyRequest);

            if (! $updateBookingDetailsResponse['status']) {
                LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Failed to update booking details');
                $response['status'] = false;
                $response['error'] = $updateBookingDetailsResponse['message'];
                $response['message'] = $updateBookingDetailsResponse['message'];
            }

            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Update booking details process completed');
        } catch (Exception $e) {
            $response['status'] = false;
            $response['error'] = 'Booking update error: ' . $e->getMessage();
            $response['message'] = 'An error occurred while updating booking details: ' . $e->getMessage();

            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Booking update process failed, Exception: ' . $e->getMessage());
        }

        return $response;
    }

    private function validateBookPolicy($quote): array
    {
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Validating book policy prerequisites process started');
        $response = ['status' => true, 'error' => null, 'message' => null];

        try {
            $requestData = [
                'quote_id' => $quote->id,
                'model_type' => self::TYPE,
                'send_policy_type' => SendPolicyTypeEnum::SAGE,
                'is_send_policy' => false,
                'transaction_payment_status' => null,
                'through_automation' => true,
            ];
            request()->merge($requestData);

            $sendBookPolicyRequest = new SendBookPolicyRequest();
            $validator = Validator::make($requestData, $sendBookPolicyRequest->rules());
            $sendBookPolicyRequest->withValidator($validator);

            if ($validator->fails()) {
                $response['status'] = false;
                $response['error'] = $validator->errors()->first() ?? 'SendBookPolicyRequest validation failed';
                $response['message'] = $validator->errors()->first();

                LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - SendBookPolicyRequest validation failed: ' . $response['message']);

                return $response;
            }

            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - All prerequisites validated successfully, Validate prerequisites process completed');
            $response['message'] = 'All book policy prerequisites validated successfully';
        } catch (Exception $e) {
            $response['status'] = false;
            $response['error'] = 'Validation error: ' . $e->getMessage();
            $response['message'] = 'An error occurred during validation: ' . $e->getMessage();

            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Validate prerequisites process failed, Exception: ' . $e->getMessage());
        }

        return $response;
    }

    public function getStepsLockingStatus($quote, $throughAutomation = false): array
    {
        LoggerService::info('class: ' . $this->className . ' fn: ' . __FUNCTION__ . ' Quote : ' . $quote->code);
        $policyIssuance = $quote->policyIssuance;

        $response = [
            'policyIssuance' => $policyIssuance,
            'isEditPolicyDetailsDisabled' => true,
            'isEditBookingDetailsDisabled' => true,
            'message' => 'All steps are locked',
            'insurer_api_status' => $quote->insurer_api_status,
        ];

        if ($throughAutomation) {
            $response['isEditPolicyDetailsDisabled'] = false;
            $response['isEditBookingDetailsDisabled'] = false;
            $response['message'] = 'All steps are editable';

            return $response;
        }

        if (
            $policyIssuance?->status === PolicyIssuanceEnum::FAILED_STATUS ||
            ($policyIssuance?->completed_step && $policyIssuance?->status == '')
        ) {
            if (! $policyIssuance?->completed_step || $policyIssuance?->completed_step === self::UPLOAD_DOCUMENTS) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'All Steps are editable';

                return $response;
            }
            if ($policyIssuance?->completed_step === self::ISSUE_POLICY) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'Upload Documents and Update Booking Details are editable';

                return $response;
            }
            if ($policyIssuance?->completed_step === self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'Booking Details is editable';

                return $response;
            }

            return $response;
        } elseif (! $policyIssuance) {
            $response['isEditPolicyDetailsDisabled'] = false;
            $response['isEditBookingDetailsDisabled'] = false;
            $response['message'] = 'All Steps are editable';
        }

        if (
            $policyIssuance?->status === PolicyIssuanceEnum::PROCESSING_STATUS &&
            $policyIssuance?->completed_step === self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM
        ) {
            $response['isEditBookingDetailsDisabled'] = false;
            $response['message'] = 'All Steps are editable';

            return $response;
        }

        return $response;
    }


    /**
     * Map document types to AWNI document type codes
     */
    public function getDocTypeCodeForCyber($documentType): string | null
    {
        return match ($documentType) {
            DocumentTypeCode::CYBER_EMIRATES_ID => '4', // Emirates ID (Front side & Back side)
            default => null
        };
    }

    /**
     * Map document types to IMCRM document type codes
     */
    public function getDocTypeCodeForIMCRM(CyberQuote $cyberQuote): array
    {
        return [
            DocumentTypeCode::CYBER_TAX_INVOICE => $cyberQuote->awni_tax_invoice_doc_id,
            DocumentTypeCode::CYBER_TAX_INVOICE_RAISED_BY_BUYER => $cyberQuote->awni_drcr_doc_id,
            DocumentTypeCode::CYBER_POLICY_SCHEDULE => $cyberQuote->awni_policy_doc_id,
        ];
    }

    private function httpCall($endPoint, $payload, $keyAPI)
    {
        if($keyAPI == self::ISSUE_POLICY) {
            $this->headers['TP-Payment-Key'] = 'TP_PAYMENT';
            $this->headers['Accept'] = 'application/json';
        } else {
            $this->headers['Accept'] = '*/*';
        }
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' calling API: ' . $keyAPI, extra: [
            'headers' => $this->headers,
            'url' => $this->baseUrl . $endPoint,
            'payload' => $payload,
            'keyAPI' => $keyAPI,
        ]);
        $response = ['status' => false, 'error' => null, 'message' => null, 'data' => null, 'completed_step' => null];
        $url = $this->baseUrl . $endPoint;
        $timeOut = $this->apiTimeout;

        try {
            $httpResponse = Http::timeout($timeOut)->withHeaders($this->headers)->post($url, $payload);

            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Response API: ' . $keyAPI, extra: [
                'response' => json_encode($httpResponse),
                'response_body' => $httpResponse->body(),
                'response_object' => $httpResponse->object(),
                'response_status' => $httpResponse->status(),
            ]);

            $responseObject = $httpResponse->object();
            if (in_array($httpResponse->status(), [JsonResponse::HTTP_OK, JsonResponse::HTTP_CREATED])) {
                if (
                    $responseObject == null ||
                    isset($responseObject?->errorList) ||
                    (isset($responseObject?->isSuccess) && $responseObject?->isSuccess == 'N')
                ) {
                    $response['error'] = $responseObject?->errorList ?? $keyAPI . ' API Failed';
                    $response['status'] = false;
                    // if response object is null, set data to null so we can identify if the API call failed and response object is null its means api not sending any response or response is null while http call is successful
                    $response['data'] = $responseObject == null ? null : '';
                    $response['message'] = $this->extractErrorMessage($responseObject, $keyAPI);
                } else {
                    $response['status'] = true;
                    $response['data'] = $responseObject;
                    $response['message'] = 'API call successfully executed.';
                }
            } elseif (isset($responseObject?->statusCode) && $responseObject?->statusCode == 404) {
                $response['error'] = '404 Not Found';
                $response['status'] = false;
                $response['message'] = '404 Not Found';
            } else {
                $response['error'] = $keyAPI . ' API Failed';
                $response['status'] = false;
                $response['message'] = 'There is an Exception on AWNI API call.';
            }
        } catch (Exception $ex) {
            LoggerService::error('automation:' . $this->className . ' fn:' . __FUNCTION__, [
                'endPoint' => $endPoint,
                'lineNumber' => $ex->getLine(),
                'trace' => $ex->getTraceAsString(),
            ], $ex);

            $response['error'] = $ex->getMessage();
            $response['message'] = $ex->getMessage();
            $response['status'] = false;
        }

        return $response;
    }

    /**
     * Extract error message from API response object
     *
     * @param  \stdClass|null  $responseObject  The API response object
     * @param  string  $keyAPI  The API key to access nested error details
     * @return string|mixed The extracted error message
     */
    private function extractErrorMessage($responseObject, string $keyAPI)
    {
        if (isset($responseObject?->errorList)) {
            return json_encode($responseObject->errorList);
        }

        if (isset($responseObject?->message)) {
            return $responseObject->message;
        }

        // Return entire response object as fallback
        return $responseObject ?? $keyAPI . ' API Failed';
    }
}