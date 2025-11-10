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
use App\Interfaces\PolicyIssuanceInterface;
use App\Models\Payment;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class AwniInsuranceService implements PolicyIssuanceInterface
{
    use GenericQueriesAllLobs;
    
    private $className = 'awniInsuranceService';
    private readonly string $baseUrl;
    private mixed $authParam;

    public const TYPE = quoteTypeCode::Cyber;
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
    public const BOOK_POLICY = 'BookPolicy';
    public const POLICY_ISSUANCE_RESPONSE = 'PolicyResponse';
    public const RETRIEVE_RESPONSE = 'RetrieveResponse';


    public function __construct()
    {
        $this->baseUrl = config('constants.AWNI_API_BASE_URL');
        $this->className = 'awniInsuranceService';
        $this->apiTimeout = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::AWNI_CYBER_AUTOMATION_API_TIMEOUT);
        // $this->headers = [
        //     'Partner-Id' => config('constants.AWNI_API_PARTNER_ID'),
        //     'Api-Key' => config('constants.AWNI_API_SECRET_KEY'),
        //     'Content-Type' => 'application/json',
        //     'Accept' => 'application/json',
        //     'TP-Payment-Key' => 'TP_PAYMENT',
        // ];
    }

    /**
     * Api Steps for Awni Insurance Service
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
     * 
     *
     * @return boolean
     */
    public function isPolicyIssuanceAutomationEnabled()
    {
        return app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_AWNI_CYBER_POLICY_ISSUANCE);
    }

    /**
     * 
     *
     * @return boolean
     */
    public function isPolicyIssuanceAutomationRetryEnabledForTimeout(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_AWNI_CAR_POLICY_ISSUANCE);
    }

    /**
     * Create Policy Issuance Schedule
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
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - AXA Car Automation is disabled');
        }
    }

    /**
     * Execute Steps
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

            $lastCompletedStep = $process->completed_step;
            $nextStepToBeExecuted = $lastCompletedStep ? $this->getNextStep($lastCompletedStep) : $this->getAPISteps()[0];
            $executeStepSequence = $this->executeStepSequence($quote, $process, $nextStepToBeExecuted);

            $response['status'] = $executeStepSequence['status'];
            $response['message'] = $executeStepSequence['message'];
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Exception : ' . $e->getMessage());

            return $response;
        }

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - PID : ' . $process->id . ' ended');

        return $response;
    }

    private function executeStepSequence($quote, $process, $nextStepToBeExecuted)
    {
        if ($nextStepToBeExecuted === self::UPLOAD_DOCUMENTS) {
            $uploadDocumentsResponse = $this->executeUploadDocumentsStep($quote, $process);
            if (isset($uploadDocumentsResponse['status']) && ! $uploadDocumentsResponse['status']) {
                return $uploadDocumentsResponse;
            }

            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === self::ISSUE_POLICY) {
            $issuePolicyResponse = $this->executeIssuePolicyStep($quote, $process);
            if (isset($issuePolicyResponse['status']) && ! $issuePolicyResponse['status']) {
                return $issuePolicyResponse;
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

    public function getNextStep($completedStep = null): ?string
    {
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

    private function executeIssuePolicyStep($quote, $process)
    {
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Step Executing : ' . self::ISSUE_POLICY);
        $policyIssuanceResponse = $this->issuePolicy($quote, $process);

        if (! $policyIssuanceResponse['status']) {
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' - Policy issuance failed', extra: ['response' => $policyIssuanceResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'Policy Creation');

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
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'Retrieve Document');

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
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'Send And Book Policy');

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

        $endPoint = '/generatePolicy';

        // $payment = $quote->payments()->mainLeadPayment()->first();
        // $splitPayment = $payment?->paymentSplits()->where('payment_method', PaymentMethodsEnum::CreditCard)->first();

        // $payload = [
        //     'PolicyRequest' => [
        //         'QuotationNo' => $quote?->carQuotePlanDetail?->insurer_quote_no,
        //         'PremiumPayable' => $payment->total_amount,
        //         'IsPaymentProcessed' => 'Success',
        //         'PartnerTrnReferenceNumber' => $quote->uuid,
        //         'PaymtMode' => 7,
        //         'PaymtTransactionDate' => $payment?->authorized_at ? Carbon::parse($payment?->authorized_at)->format('Y-m-d H:i:s') : '',
        //         'PaymtTransactionNumber' => $splitPayment?->payment_receipt_id,
        //         'Amount' => $payment?->price_vat_applicable,
        //         'AuthCode' => $splitPayment?->payment_auth_code,
        //         'Documents' => [
        //             'DocsInResponse' => false,
        //             'DocsDetails' => [
        //                 'PolicySchedule' => false,
        //                 'HirePurchaseLetter' => false,
        //                 'ProposalForm' => false,
        //                 'LetterToBank' => false,
        //                 'MotorArabicCertificate' => false,
        //                 'Receipt' => false,
        //                 'BreakDownRecovery' => false,
        //                 'UPRInvoice' => false,
        //             ],
        //         ],
        //         'PolicyConfirmationSMS' => false,
        //         'PolicyConfirmationEmail' => false,
        //     ],
        // ];
        $payload = [
            'CustName' => $quote->customer_name,
            'CustMobile' => $quote->customer_mobile,
            'CustEmail' => $quote->customer_email,
            'CustEID' => $quote->customer_eid,
            'CustDOB' => $quote->customer_dob,
            'CustAddress' => $quote->customer_address,
            'CustCountryCode' => $quote->customer_country_code,
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

        $issuePolicyResult = $issuePolicy['data']?->PolicyResponse;
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ', updating quote and payment information from Liva createPolicyRequest response');

        $quote->update([
            'policy_number' => $issuePolicyResult?->PolicyNumber,
            'policy_issuance_date' => $issuePolicyResult?->PolicyCreationDate,
            'policy_start_date' => $issuePolicyResult?->PolicyEffectiveDate,
            'policy_expiry_date' => $issuePolicyResult?->PolicyExpiryDate,
            'price_vat_applicable' => $issuePolicyResult?->PremiumWithoutVAT,
            'vat' => $issuePolicyResult?->VatAmount,
            'price_with_vat' => $issuePolicyResult?->PaidAmount,
        ]);

        Payment::where('code', $quote->code)->update([
            'commission_vat_applicable' => $issuePolicyResult?->Commission,
            'commission' => $issuePolicyResult?->Commissionincldvat,
            'commission_vat' => $issuePolicyResult?->VatonCommission,
            'commmission_percentage' => $issuePolicyResult?->CommissionPercentage,
            'insurer_commmission_invoice_number' => $issuePolicyResult?->InsurerCommissionTaxInvoice ?? null,
            'insurer_tax_number' => $issuePolicyResult?->InsurerPremiumTaxInvoice ?? null,
            'insurer_invoice_date' => $issuePolicyResult?->InsurerInvoicedate ?? null,
        ]);

        $response['status'] = true;
        $response['message'] = 'Policy issued successfully';
        $response['completed_step'] = self::ISSUE_POLICY;
        $response['data'] = $issuePolicy['data']; // verify this

        return $response;
    }

    public function uploadDocuments($quote)
    {
        $endPoint = 'motor/documents/upload/v2';
        $response = ['status' => false, 'completed_step' => self::UPLOAD_DOCUMENTS, 'error' => null, 'message' => null];
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' started', extra: [
            'endPoint' => $endPoint,
        ]);

        $documents = $quote->documents;
        $requiredDocuments = array_filter($documents->toArray(), function ($document) {
            return in_array($document['document_type_code'], [DocumentTypeCode::EMIRATES_ID]);
        });

        if (empty($requiredDocuments)) {
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' - Document upload validation check failed');

            $response['message'] =
                $response['error'] = 'Required Documents not uploaded';
            $response['status'] = false;

            return $response;
        }

        $attachments = [];

        foreach ($requiredDocuments as $document) {
            try {
                // Get the file path (assuming documents are stored in storage)
                $filePath = config('constants.AZURE_IM_STORAGE_URL') . config('constants.AZURE_IM_STORAGE_CONTAINER') . '/' . $document['doc_url']; // Adjust path as needed

                // Read file content and convert to base64
                $fileContent = file_get_contents($filePath);
                $base64Content = base64_encode($fileContent);

                // Get file extension
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);

                // Map document type based on your business logic
                $documentType = $this->getDocTypeCodeForCyber($document['document_type_code'] ?? 'other');

                $attachments[] = [
                    'DocumentType' => $documentType,
                    'Content' => $base64Content,
                    'Extension' => $extension,
                ];

                LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Document processed: ' . $document['document_type_text']);
            } catch (\Exception $ex) {
                LoggerService::error('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Error processing document', exception: $ex);

                continue;
            }
        }

        if (empty($attachments)) {
            return ['status' => false, 'message' => 'No valid documents could be processed'];
        }

        $payload['UploadDocumentsRequest'] = [
            'TransactionType' => $quote->source == LeadSourceEnum::RENEWAL_UPLOAD ? '6' : '5',
            'TransactionNumber' => $quote?->carQuotePlanDetail?->insurer_quote_no,
            'Attachments' => $attachments,
        ];

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Payload created with ' . count($attachments) . ' attachments');

        $response = $this->httpCall($endPoint, $payload, self::UPLOAD_DOCUMENTS_RESPONSE);
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Response', extra: ['response' => $response]);
        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $response, $this->baseUrl . $endPoint, self::UPLOAD_DOCUMENTS, $response['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

        if (! $response['status']) {
            $response['message'] = $response['error'];
            $response['error'] = $response['error'];

            return $response;
        }

        $responseStatus = [];
        $allUploadsSuccessful = true;

        foreach ($response['data']?->UploadDocumentsResponse->Attachments as $value) {
            $uploadStatus = $value->UploadStatus ?? false;
            $responseStatus[] = [
                'DocumentType' => $value->DocumentType ?? 'Unknown',
                'UploadStatus' => $uploadStatus,
            ];

            if (! $uploadStatus) {
                $allUploadsSuccessful = false;
            }

            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Document upload status', extra: [
                'DocumentType' => $value->DocumentType,
                'UploadStatus' => $uploadStatus,
            ]);
        }

        if ($allUploadsSuccessful) {
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' All documents uploaded successfully', extra: [
                'details' => $responseStatus,
            ]);
            $response['status'] = true;
            $response['completed_step'] = self::UPLOAD_DOCUMENTS;
        } else {
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Some documents failed to upload', extra: [
                'details' => $responseStatus,
            ]);
            $response['status'] = false;
        }

        return $response;
    }

    public function uploadPolicyDocumentsToIMCRM($quote, $process): array
    {
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Quote : ' . $quote->code . ' started - Policy Issuance ID : ' . $process->id . ' - Step : ' . self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);

        $response = ['status' => false, 'completed_step' => self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM, 'error' => null, 'message' => null];
        $endPoint = 'motor/transactions/retrieve/v2';

        $uploadedDocumentsToIMCRM = collect();

        $payload = [
            'RetrieveRequest' => [
                'RetrieveType' => '6',
                'TransactionNumber' => $quote?->policy_number,
                'PartnerTrnReferenceNumber' => $quote->uuid,
                'Documents' => [
                    'DocsInResponse' => true,
                    'DocsDetails' => [
                        'DebitNote' => false,
                        'CreditNote' => false,
                        'PolicySchedule' => false,
                        'MotorArabicCertificate' => false,
                        'HirePurchaseLetter' => false,
                        'ProposalForm' => false,
                        'LetterToBank' => false,
                        'Receipt' => false,
                    ],
                ],
                'ProposalForm' => false,
            ],
        ];

        foreach ($this->getDocTypeCodeForIMCRM() as $keyAWNI => $imcrm) {
            $payload['RetrieveRequest']['Documents']['DocsDetails'][$keyAWNI] = true;

            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' document retreive work start for : ' . $imcrm['IMNAME'], extra: [
                'time' => now()->format('d-m-Y H:i:s'),
                'payload' => json_encode($payload),
            ]);

            $retrieveRequest = $this->httpCall($endPoint, $payload, 'RetrieveResponse');

            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' Response', extra: ['response' => $retrieveRequest]);

            if ($retrieveRequest['status']) {
                $retrieveResponse = $retrieveRequest['data'];

                $documentContent = $retrieveResponse?->RetrieveResponse?->Policies[0]?->PolicyResponse?->Documents?->PolicyReportsPdf[0];

                $quoteDocument = $this->uploadAndAttachToQuoteDocuments($quote, $documentContent, $imcrm['IMKEY'], $imcrm['IMNAME'] . '.pdf');
            }

            app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $retrieveRequest, $this->baseUrl . $endPoint, self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM, $retrieveRequest['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);
            $payload['RetrieveRequest']['Documents']['DocsDetails'][$keyAWNI] = false;
            LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' document retreive work end', extra: [
                'time' => now()->format('d-m-Y H:i:s'),
                'status' => $retrieveRequest['status'],
            ]);

            $uploadedDocumentsToIMCRM->push([
                'name' => $imcrm['IMNAME'],
                'uploaded' => $quoteDocument?->id ?? false,
                'status' => $retrieveRequest['status'],
                'message' => $retrieveRequest['message'] ?? 'Document Retrieve Failed',
            ]);
        }

        $allDocumentsUploaded = $uploadedDocumentsToIMCRM->where('status', false)->count() === 0;

        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' - allDocumentsUploaded', extra: [
            'allDocumentsUploaded' => $allDocumentsUploaded,
        ]);

        if (! $allDocumentsUploaded || empty($uploadedDocumentsToIMCRM)) {
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
     * Map document types to AWNI document type codes
     */
    public function getDocTypeCodeForCyber($documentType): string
    {
        return match ($documentType) {
            'CEID' => '16', // Emirates ID (Front side & Back side)
            default => null
        };
    }

    private function httpCall($endPoint, $payload, $keyAPI)
    {
        LoggerService::info('automation:' . $this->className . ' fn:' . __FUNCTION__ . ' calling API: ' . $keyAPI);
        $response = ['status' => false, 'error' => null, 'message' => null, 'data' => null, 'completed_step' => null];
        $url = $this->baseUrl . $endPoint;
        $timeOut = $this->apiTimeout;
        // dd($this->headers);

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
                    isset($responseObject?->$keyAPI?->errors) ||
                    (isset($responseObject?->$keyAPI?->Status) && $responseObject?->$keyAPI?->Status == false)
                ) {
                    $response['error'] = $responseObject?->$keyAPI?->Status ?? $keyAPI . ' API Failed';
                    $response['status'] = false;
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
        if (isset($responseObject?->$keyAPI?->errors)) {
            return json_encode($responseObject->$keyAPI->errors);
        }

        if (isset($responseObject?->message)) {
            return $responseObject->message;
        }

        // Return entire response object as fallback
        return $responseObject ?? $keyAPI . ' API Failed';
    }
}