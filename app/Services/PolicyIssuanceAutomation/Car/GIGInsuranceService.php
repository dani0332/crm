<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Car;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Enums\EnvEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\LookupsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Facades\Ken;
use App\Http\Requests\BookPolicyRequest;
use App\Http\Requests\SendBookPolicyRequest;
use App\Interfaces\PolicyIssuanceInterface;
use App\Jobs\OCR\PopulateDocumentData;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\CarQuoteRequestDetail;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\AMLService;
use App\Services\ApplicationStorageService;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\ManualCommissionUpdateService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class GIGInsuranceService implements PolicyIssuanceInterface
{
    use GenericQueriesAllLobs;

    private readonly string $className;
    private readonly string $baseUrl;
    private readonly string $authUrl;
    private readonly string $clientId;
    private readonly string $clientSecret;
    private ?string $accessToken = null;

    public const INSURER_CODE = InsuranceProvidersEnum::AXA;
    public const POLICY_ISSUANCE_API_ACCESS_TOKEN_KEY = InsuranceProvidersEnum::AXA.'_POLICY_ISSUANCE_API_ACCESS_TOKEN';
    public const TYPE = quoteTypeCode::Car;
    public const TYPE_ID = QuoteTypeId::Car;
    public const UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const ISSUE_POLICY = 'IssuePolicy';
    public const GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM = 'GetAndUploadPolicyDocumentsToIMCRM';
    public const EXECUTE_OCR_PROCESSING = 'ExecuteOCRProcessing';
    public const BOOK_POLICY = 'BookPolicy';
    public const PAYMENT_MODE = 'CT068';
    public const PAYMENT_MODE_VALUE = 'Upfront Commission Partner Payment';
    public const CURRENCY_CODE = 'AED';
    public const OP_CO = 'UAE';

    public $policyIssuance = null;
    public $currentInsurerApiStatus = null;

    private const REQUEST_GET = 'GET';
    private const REQUEST_POST = 'POST';
    private const REQUEST_PATCH = 'PATCH';
    private const REQUEST_AUTH = 'AUTH';

    private $maxRetries = 5;
    private $retryDelay = 10000; // 10 seconds

    private const CAR_REGISTRATION_CARD_DOC_TYPE_CODE = 'DT01';
    private const DRIVING_LICENSE_DOC_TYPE_CODE = 'DT02';
    private const NATIONAL_ID_DOC_TYPE_CODE = 'DT03';
    private const POLICY_DOC_TAX_INVOICE = 'Tax invoice';
    private const POLICY_DOC_TAX_INVOICE_BY_BUYER = 'Tax invoice by buyer';
    private const POLICY_DOC_RECEIPT = 'Receipt with reference';
    private const POLICY_DOC_POLICY_SCHEDULE = 'Motor Insurance Policy Schedule';
    private const POLICY_DOC_RENEWAL_POLICY_SCHEDULE = 'Motor Renewal Policy Schedule';
    private const POLICY_DOC_CERTIFICATE_OF_INSURANCE = 'Certificate of Insurance';
    private const RTA_UPLOAD_STATUS_DONE = '1';
    private const RTA_UPLOAD_STATUS_PENDING = '0';
    const POLICY_AUTOMATION_STATUS_YES_ID = 1;
    const POLICY_AUTOMATION_STATUS_NO_ID = 2;
    const UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID = 2;
    const UPLOAD_POLICY_DOCUMENTS_API_FAILED = 'Insurer Document Upload Failed';
    const UPLOAD_POLICY_DOCUMENTS_API_ACTION_MESSAGE = 'Document Upload via API';
    const POLICY_ISSUANCE_API_FAILED_STATUS_ID = 3;
    const POLICY_ISSUANCE_API_FAILED = 'Policy Creation Failed';
    const POLICY_ISSUANCE_API_ACTION_MESSAGE = 'Policy Issuance via API';
    const GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID = 4;
    const GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED = 'Policy Document Retrieval Failed';
    const GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_ACTION_MESSAGE = 'Get and Upload Policy Documents to IMCRM via API';
    const OCR_PROCESSING_API_FAILED_STATUS_ID = 5;
    const OCR_PROCESSING_API_FAILED = 'OCR Processing API Failed';
    const OCR_PROCESSING_API_ACTION_MESSAGE = 'OCR Processing via API';
    const BOOK_POLICY_API_FAILED_STATUS_ID = 6;
    const BOOK_POLICY_API_FAILED = 'Send and Book Policy Failed';
    const BOOK_POLICY_API_ACTION_MESSAGE = 'Book Policy via API';

    public function __construct()
    {
        $this->className = class_basename(__CLASS__);
        $this->baseUrl = config('constants.GIG_API_BASE_URL').'/apis/gulf-motor-v3-vs/motor';
        $this->authUrl = config('constants.GIG_API_AUTH_BASE_URL').'/oauth/token';
        $this->clientId = config('constants.GIG_API_AUTH_CLIENT_ID');
        $this->clientSecret = config('constants.GIG_API_AUTH_CLIENT_SECRET');
    }

    private function getLogPrefix(string $functionName): string
    {
        return 'automation:class:'.$this->className.' - fn:'.$functionName;
    }

    private function getAPISteps(): array
    {
        return [
            self::UPLOAD_DOCUMENTS,
            self::ISSUE_POLICY,
            self::GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            self::EXECUTE_OCR_PROCESSING,
            self::BOOK_POLICY,
        ];
    }

    public function isPolicyIssuanceAutomationEnabled(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_GIG_CAR_POLICY_ISSUANCE);
    }

    public function isPolicyIssuanceAutomationRetryEnabledForTimeout(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_GIG_CAR_POLICY_ISSUANCE);
    }

    private function initializeAccessToken(): bool
    {
        $this->accessToken = Cache::store('redis')->get(self::POLICY_ISSUANCE_API_ACCESS_TOKEN_KEY);

        if (! $this->accessToken) {
            $this->accessToken = $this->getAccessToken();
        }

        return $this->accessToken !== null;
    }

    public function createPolicyIssuanceSchedule($quote, $insurer)
    {
        LoggerService::startQuoteLogging($quote);

        if ($this->isPolicyIssuanceAutomationEnabled()) {
            $this->policyIssuance = (new PolicyIssuanceService)->schedulePolicyIssuance($quote, $insurer, self::TYPE, $this->className);
        } else {
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - AXA Car Automation is disabled');
        }

        return $this->policyIssuance;
    }

    public function executeSteps($process): array
    {
        $response = ['status' => false, 'error' => null, 'message' => null];

        $this->policyIssuance = $process;
        $quote = $process->model;

        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::POLICY_AUTOMATION);
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - PID : '.$process->id.' - Plan ID : '.$quote->plan_id.' started');

        try {
            if (! $this->isPolicyIssuanceAutomationEnabled()) {
                LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - GIG Car Automation is disabled');
                $response['error'] = 'GIG Car Automation is disabled';
                $response['message'] = 'GIG Car Automation is disabled';

                return $response;
            }

            // Initialize access token when automation starts
            if (! $this->initializeAccessToken()) {
                LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Failed to initialize access token');
                $response['error'] = 'Failed to authenticate with insurer API';
                $response['message'] = 'Authentication failed - unable to obtain access token';

                return $response;
            }

            $lastCompletedStep = $process->completed_step;
            $nextStepToBeExecuted = $lastCompletedStep ? $this->getNextStep($lastCompletedStep) : self::UPLOAD_DOCUMENTS;
            $executeStepSequence = $this->executeStepSequence($quote, $process, $nextStepToBeExecuted);

            $response['status'] = $executeStepSequence['status'];
            $response['error'] = $executeStepSequence['error'] ?? null;
            $response['message'] = $executeStepSequence['message'] ?? null;

        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Exception : '.$e->getMessage());

            return $response;
        }

        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - PID : '.$process->id.' completed');

        return $response;
    }

    private function executeStepSequence($quote, $process, $nextStepToBeExecuted)
    {
        if ($nextStepToBeExecuted === self::UPLOAD_DOCUMENTS) {
            $uploadDocumentsResponse = $this->executeUploadDocumentsStep($quote, $process);
            if (! $uploadDocumentsResponse['status']) {
                return $uploadDocumentsResponse;
            }

            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === self::ISSUE_POLICY) {
            $issuePolicyResponse = $this->executeIssuePolicyStep($quote, $process);
            if (! $issuePolicyResponse['status']) {
                return $issuePolicyResponse;
            }

            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === self::GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM) {
            $getAndUploadPolicyDocumentsToIMCRMResponse = $this->executeGetAndUploadPolicyDocumentsToIMCRMStep($quote, $process);
            if (! $getAndUploadPolicyDocumentsToIMCRMResponse['status']) {
                return $getAndUploadPolicyDocumentsToIMCRMResponse;
            }

            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === self::EXECUTE_OCR_PROCESSING) {
            $executeOCRProcessingResponse = $this->executeOCRProcessingStep($quote, $process);
            if (! $executeOCRProcessingResponse['status']) {
                return $executeOCRProcessingResponse;
            }

            if (isset($executeOCRProcessingResponse['processing']) && $executeOCRProcessingResponse['processing']) {
                return $executeOCRProcessingResponse;
            }

            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === self::BOOK_POLICY) {
            $bookPolicyResponse = $this->executeBookPolicyStep($quote, $process);
            if (! $bookPolicyResponse['status']) {
                return $bookPolicyResponse;
            }

            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        return ['status' => true, 'message' => 'Step executed successfully', 'completed_step' => $nextStepToBeExecuted];
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
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - PID : '.$process->id.' - Initiating step : '.self::UPLOAD_DOCUMENTS);
        $uploadDocumentsResponse = $this->UploadDocuments($quote);

        if (! $uploadDocumentsResponse['status']) {
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - '.$uploadDocumentsResponse['message'] ?? 'Document upload failed', extra: ['response' => $uploadDocumentsResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID, 'Document Upload');

            return $uploadDocumentsResponse;
        }

        $process->update(['completed_step' => $uploadDocumentsResponse['completed_step']]);
        $process = $process->refresh();
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$process->model->code.' - Step completed : '.$uploadDocumentsResponse['completed_step']);

        return $uploadDocumentsResponse;
    }

    public function UploadDocuments($quote): array
    {
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Document upload process started - PID : '.$this->policyIssuance->id);
        $response = ['status' => false, 'completed_step' => self::UPLOAD_DOCUMENTS, 'error' => null, 'message' => null];
        $endPoint = $this->baseUrl.'/v1/insurance-documents';

        $documentUploadValidationCheck = app(PolicyIssuanceService::class)->documentUploadPreChecks(self::TYPE_ID, $quote, [
            QuoteDocumentsEnum::CAR_REGISTRATION_CARD,
            QuoteDocumentsEnum::CAR_EMIRATE_ID,
            QuoteDocumentsEnum::DRIVING_LICENSE,
        ]);

        if (! $documentUploadValidationCheck['status']) {
            $response['message'] =
            $response['error'] = 'Required Documents not uploaded';
            $response['status'] = false;

            return $response;
        }

        $documentsToUpload = $this->documentsToUpload();
        $quoteDocumentCodes = $this->documentsToUpload()->keys()->toArray();
        $quoteDocuments = $quote->documents()->whereIn('document_type_code', $quoteDocumentCodes)->get();

        foreach ($documentsToUpload as $docTypeCode => $documentToUpload) {
            // Get all documents for this document type (handles multiple documents like Emirates ID front/back)
            $documentsForThisType = $quoteDocuments->where('document_type_code', $docTypeCode);

            if ($documentsForThisType->isEmpty()) {
                LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - No documents found for document type: '.$docTypeCode);

                continue;
            }

            $documentTypeUploadedCount = 0;
            $documentTypeFailedCount = 0;

            foreach ($documentsForThisType as $quoteDocument) {
                $documentFile = Storage::disk('azureIM')->get($quoteDocument->doc_url);
                $docFileBase64 = base64_encode($documentFile);
                // Create payload with guaranteed field order for GIG API
                $payload = [];
                $payload['referenceType'] = 'quotation';
                $payload['referenceValue'] = $quote->insurer_quote_number;
                $payload['documentType'] = [
                    'code' => $documentToUpload['insurerDocCode'],
                    'value' => $documentToUpload['insurerDocName'],
                ];
                $payload['documentContent'] = $docFileBase64;
                $payload['documentName'] = uniqid().'_'.$quoteDocument->doc_name;
                $payload['mimeType'] = $quoteDocument->doc_mime_type;

                $headers = ['Content-Type' => 'application/json'];
                $uploadDocResponse = $this->httpCall($endPoint, $payload, $headers, self::REQUEST_POST);
                app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $uploadDocResponse, $this->baseUrl.$endPoint, self::UPLOAD_DOCUMENTS, $uploadDocResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

                if ($uploadDocResponse['status']) {
                    $documentTypeUploadedCount++;
                } else {
                    LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Failed to upload '.$quoteDocument->doc_name.' - Message : '.$uploadDocResponse['message'].' , Error : '.$uploadDocResponse['error']);
                    $documentTypeFailedCount++;
                }
            }

            // Mark document type as uploaded only if ALL documents for this type were uploaded successfully
            if ($documentTypeUploadedCount > 0 && $documentTypeFailedCount === 0) {
                $documentToUpload['uploaded'] = true;
                $documentToUpload['uploaded_count'] = $documentTypeUploadedCount;
            } else {
                $documentToUpload['uploaded'] = false;
                $documentToUpload['uploaded_count'] = $documentTypeUploadedCount;
                $documentToUpload['failed_count'] = $documentTypeFailedCount;
                LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Document type '.$documentToUpload['insurerDocName'].' partially failed - Uploaded: '.$documentTypeUploadedCount.', Failed: '.$documentTypeFailedCount);
            }

            $documentsToUpload->put($docTypeCode, $documentToUpload);
        }

        $isAllDocumentsUploaded = $documentsToUpload->where('uploaded', false)->count() === 0;
        if (! $isAllDocumentsUploaded) {
            $failedDocumentTypes = $documentsToUpload->where('uploaded', false);
            $errorDetails = [];
            foreach ($failedDocumentTypes as $docType) {
                $uploadedCount = $docType['uploaded_count'] ?? 0;
                $failedCount = $docType['failed_count'] ?? 0;
                $totalCount = $uploadedCount + $failedCount;

                if ($uploadedCount > 0) {
                    $errorDetails[] = $docType['insurerDocName']." (uploaded {$uploadedCount}/{$totalCount})";
                } else {
                    $errorDetails[] = $docType['insurerDocName']." (all {$totalCount} failed)";
                }
            }

            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - failed to upload all required documents to insurer : ', $errorDetails);
            $response['message'] = 'Failed to upload all documents for: '.implode(', ', $errorDetails);
            $response['error'] = 'Failed to upload all required documents to insurer';

            return $response;
        }

        $successDetails = [];
        $totalUploadedCount = 0;
        foreach ($documentsToUpload->where('uploaded', true) as $docType) {
            $uploadedCount = $docType['uploaded_count'] ?? 1;
            $totalUploadedCount += $uploadedCount;
            $successDetails[] = $docType['insurerDocName']." ({$uploadedCount} document".($uploadedCount > 1 ? 's' : '').')';
        }

        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Document upload process completed', [
            'Total Documents Uploaded' => $totalUploadedCount,
            'Document Types' => $successDetails,
        ]);

        $response['status'] = true;
        $response['message'] = 'Successfully uploaded '.$totalUploadedCount.' documents: '.implode(', ', $successDetails);

        return $response;
    }

    private function documentsToUpload()
    {
        return collect([
            QuoteDocumentsEnum::CAR_REGISTRATION_CARD => [
                'code' => QuoteDocumentsEnum::CAR_REGISTRATION_CARD,
                'insurerDocCode' => self::CAR_REGISTRATION_CARD_DOC_TYPE_CODE,
                'insurerDocName' => 'Car Registration Document',
                'uploaded' => false,
            ],
            QuoteDocumentsEnum::DRIVING_LICENSE => [
                'code' => QuoteDocumentsEnum::DRIVING_LICENSE,
                'insurerDocCode' => self::DRIVING_LICENSE_DOC_TYPE_CODE,
                'insurerDocName' => 'Driving License',
                'uploaded' => false,
            ],
            QuoteDocumentsEnum::CAR_EMIRATE_ID => [
                'code' => QuoteDocumentsEnum::CAR_EMIRATE_ID,
                'insurerDocCode' => self::NATIONAL_ID_DOC_TYPE_CODE,
                'insurerDocName' => 'National ID',
                'uploaded' => false,
            ],
        ]);
    }

    private function executeIssuePolicyStep($quote, $process): array
    {
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - PID : '.$process->id.' - Initiating step : '.self::ISSUE_POLICY);
        $policyIssuanceResponse = $this->issuePolicy($quote);

        if (! $policyIssuanceResponse['status']) {
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - '.$policyIssuanceResponse['message'] ?? 'Policy issuance failed', extra: ['response' => $policyIssuanceResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::POLICY_ISSUANCE_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID, 'Policy Creation');

            return $policyIssuanceResponse;
        }

        $quote->update([
            'quote_status_id' => QuoteStatusEnum::PolicyIssued,
            'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued,
            'policy_number' => $policyIssuanceResponse['data']['data']->policyId,
            'quote_status_date' => now(),
        ]);

        $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
        $process = $process->refresh();

        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$process->model->code.' - Step completed : '.$policyIssuanceResponse['completed_step']);

        return $policyIssuanceResponse;
    }

    public function issuePolicy($quote): array
    {
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Policy issuance process started - PID : '.$this->policyIssuance->id);
        $response = ['status' => false, 'completed_step' => self::ISSUE_POLICY, 'error' => null, 'message' => null];

        $payment = $quote->payments()->mainLeadPayment()->first();
        $endPoint = $this->baseUrl.'/v3/policies';

        $payload = [
            'quoteId' => $quote->insurer_quote_number,
            'isActive' => 'true',
            'payment' => [
                'paymentMode' => [
                    'code' => self::PAYMENT_MODE,
                    'value' => self::PAYMENT_MODE_VALUE,
                ],
                'paymentAmount' => [
                    'amount' => $payment->total_amount,
                    'currencyCode' => self::CURRENCY_CODE,
                ],
            ],
        ];

        $issuePolicy = $this->httpCall($endPoint, $payload, [], self::REQUEST_POST);
        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $issuePolicy, $this->baseUrl.$endPoint, self::ISSUE_POLICY, $issuePolicy['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

        if (! $issuePolicy['status']) {
            $response['error'] = $issuePolicy['error'];
            $response['message'] = $issuePolicy['message'];
            $response['status'] = false;

            return $response;
        }

        // Sync latest Car Quote Info to Quote
        app(CentralService::class)->syncLatestCarQuoteInfoToQuote($quote);
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Latest Car Quote Info synced to quote');

        $response['status'] = true;
        $response['message'] = 'Policy issued successfully';
        $response['completed_step'] = self::ISSUE_POLICY;
        $response['data'] = $issuePolicy;

        return $response;
    }

    private function executeGetAndUploadPolicyDocumentsToIMCRMStep($quote, $process)
    {
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - PID : '.$process->id.' - Initiating step : '.self::GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);
        $getAndUploadPolicyDocumentsToIMCRMResponse = $this->getAndUploadPolicyDocumentsToIMCRM($quote, $process);

        if (! $getAndUploadPolicyDocumentsToIMCRMResponse['status']) {
            LoggerService::info('$this->getLogPrefix(__FUNCTION__) Quote : '.$quote->code.' - '.$getAndUploadPolicyDocumentsToIMCRMResponse['message'] ?? 'Upload policy documents to IMCRM failed', extra: ['response' => $getAndUploadPolicyDocumentsToIMCRMResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID, 'Retrieve Document ');

            return $getAndUploadPolicyDocumentsToIMCRMResponse;
        }

        $process->update(['completed_step' => $getAndUploadPolicyDocumentsToIMCRMResponse['completed_step']]);
        $process = $process->refresh();
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$process->model->code.' - Step completed : '.$getAndUploadPolicyDocumentsToIMCRMResponse['completed_step']);

        return $getAndUploadPolicyDocumentsToIMCRMResponse;
    }

    public function getAndUploadPolicyDocumentsToIMCRM($quote, $process): array
    {
        LoggerService::startQuoteLogging($quote);
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Policy documents upload to IMCRM started');
        $response = ['status' => false, 'completed_step' => self::GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM, 'error' => null, 'message' => null];

        $endPoint = $this->baseUrl.'/v1/insurance-documents/document';
        $getDocumentDelay = 10;
        $getPolicyIssuanceResponse = $process->policyIssuanceLogs()->where([
            'step' => self::ISSUE_POLICY,
            'status' => PolicyIssuanceEnum::SUCCESS_STATUS,
        ])->latest()->first();

        if (! $getPolicyIssuanceResponse) {
            $response['error'] = 'Policy issuance response not found in process data';
            $response['message'] = 'Policy issuance response not found in process data';

            return $response;
        }

        $uploadedDocumentsToIMCRM = collect();
        $policyDocuments = json_decode($getPolicyIssuanceResponse?->response)?->data?->documents;
        $policyId = json_decode($getPolicyIssuanceResponse?->response)?->data?->policyId;
        $certificateOfInsuranceAvailable = false;
        $isProduction = (config('constants.APP_ENV') == EnvEnum::PRODUCTION);

        foreach ($policyDocuments as $policyDocument) {
            // Skip certificate of insurance document in non-production environments only
            // In production, we want to upload the actual certificate of insurance
            // Reminder:: Commission statement is same as Tax invoice raised by buyer
            if ((! $isProduction && str_contains($policyDocument->name, 'Certificate of Insurance')) || str_contains($policyDocument->name, 'Commission statement')) {
                continue;
            }

            $quoteDocument = null;
            $docName = $policyDocument->name ?? 'Unknown Document';
            $docMapping = null;

            $header = [
                'opCo' => self::OP_CO,
                'documentType' => $policyDocument->docId,
                'referenceType' => 'policy',
                'referenceValue' => $policyId,
                'Accept' => 'application/json',
            ];

            $document = $this->httpCall($endPoint, [], $header, self::REQUEST_GET);
            sleep($getDocumentDelay);

            if ($document['status']) {
                $docName = $document['data']?->document?->name ?? $policyDocument->name ?? 'Unknown Document';
                $docMapping = $this->getPolicyIssuanceQuoteDocumentMapping($policyDocument->name, $quote);

                if ($docMapping && isset($docMapping['code'])) {
                    $quoteDocument = $this->uploadAndAttachToQuoteDocuments($quote, $document['data']?->document?->content, $docMapping['code'], $docName);
                } else {
                    LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - No document mapping found for : '.$policyDocument->name);
                }
            } else {
                LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Insurer Document not found for : '.$policyDocument->name, ['status' => $document['status'], 'error' => $document['error']]);
            }

            app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, ['documentType' => $policyDocument->docId, 'referenceType' => 'policy', 'referenceValue' => $policyId], $document, $this->baseUrl.$endPoint, self::GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM, $document['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

            $uploadedDocumentsToIMCRM->push([
                'name' => $docName,
                'uploaded' => $quoteDocument?->id ? true : false,
                'message' => $document['message'] ?? 'No response message',
                'document' => $quoteDocument,
            ]);

            // Upload duplicate certificate only in non-production environments for testing purposes
            // In production, the actual certificate of insurance will be processed above
            // If Policy Schedule (CPS) uploaded, also upload the same content as CPC to simulate Certificate of Insurance without API call
            if (! $isProduction && $docMapping && isset($docMapping['code']) && $docMapping['code'] === DocumentTypeCode::CPS && ($quoteDocument?->id ?? false)) {
                $duplicateDocName = self::POLICY_DOC_CERTIFICATE_OF_INSURANCE;
                $cpcDocument = $this->uploadAndAttachToQuoteDocuments(
                    $quote,
                    $document['data']?->document?->content,
                    DocumentTypeCode::CPC,
                    $duplicateDocName
                );

                $uploadedDocumentsToIMCRM->push([
                    'name' => $duplicateDocName,
                    'uploaded' => $cpcDocument?->id ? true : false,
                    'message' => 'Uploaded as CPC duplicate of Policy Schedule (non-production only)',
                    'document' => $cpcDocument,
                ]);

                $docName = $duplicateDocName;
            }

            $certificateOfInsuranceAvailable = str_contains($docName, self::POLICY_DOC_CERTIFICATE_OF_INSURANCE) && $quoteDocument?->id;
        }

        $quote->update(['rta_upload_status' => $certificateOfInsuranceAvailable ? self::RTA_UPLOAD_STATUS_DONE : self::RTA_UPLOAD_STATUS_PENDING]);
        if (! $certificateOfInsuranceAvailable) {
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID);
        }

        $allDocumentsUploaded = $uploadedDocumentsToIMCRM->where('uploaded', false)->count() === 0;
        if (! $allDocumentsUploaded) {
            $docsUploadToIMCRMFailed = $uploadedDocumentsToIMCRM->where('uploaded', false)->pluck('name')->toArray();
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - failed to fetch all documents from insurer : ', $docsUploadToIMCRMFailed);

            $error = 'Upload policy documents to IMCRM failed';
            $response['error'] = $error;
            $response['message'] = $error;

            return $response;
        }

        // Collect successfully uploaded documents for counting
        $uploadedDocuments = $uploadedDocumentsToIMCRM->where('uploaded', true)
            ->filter(function ($item) {
                return isset($item['document']) && $item['document'];
            })
            ->pluck('document');

        $response['status'] = true;
        $response['message'] = 'Documents uploaded to IMCRM successfully. Ready for OCR processing.';
        $response['uploaded_documents_count'] = $uploadedDocuments->count();

        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Policy documents upload to IMCRM completed with '.$uploadedDocuments->count().' documents ready for OCR');

        return $response;
    }

    private function executeOCRProcessingStep($quote, $process): array
    {
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - PID : '.$process->id.' - Initiating step : '.self::EXECUTE_OCR_PROCESSING);
        $executeOCRProcessingResponse = $this->executeOCRProcessing($quote, $process);

        if (! $executeOCRProcessingResponse['status']) {
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - '.$executeOCRProcessingResponse['message'] ?? 'OCR processing failed', extra: ['response' => $executeOCRProcessingResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::OCR_PROCESSING_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID);

            return $executeOCRProcessingResponse;
        }

        if (isset($executeOCRProcessingResponse['processing']) && $executeOCRProcessingResponse['processing']) {
            $executeOCRProcessingResponse['message'] = 'OCR processing is in progres, Booking execution will continue after successful OCR processing';
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - OCR processing is in progress, will continue via batch callback');

            return $executeOCRProcessingResponse;
        }

        if (isset($executeOCRProcessingResponse['completed_step'])) {
            $process->update(['completed_step' => $executeOCRProcessingResponse['completed_step']]);
            $process = $process->refresh();
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$process->model->code.' - Step completed : '.$executeOCRProcessingResponse['completed_step']);
        }

        return $executeOCRProcessingResponse;
    }

    public function executeOCRProcessing($quote, $process): array
    {
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - OCR processing started');
        $response = ['status' => false, 'completed_step' => self::EXECUTE_OCR_PROCESSING, 'error' => null, 'message' => null];

        // Get the policy documents that were uploaded in the previous step
        // These are the document types that should have been uploaded to IMCRM
        $policyDocumentTypes = [
            DocumentTypeCode::TI, // Tax Invoice
            DocumentTypeCode::CTIRBB, // Tax Invoice Raised By Buyer
        ];

        // Get documents that were recently uploaded (after the upload step started)
        $uploadedDocuments = $quote->documents()
            ->whereIn('document_type_code', $policyDocumentTypes)
            ->where('created_at', '>=', $process->updated_at->subMinutes(30)) // Look for documents created within 30 minutes of step completion
            ->get();

        if ($uploadedDocuments->isEmpty()) {
            $response['error'] = 'No policy documents found for OCR processing';
            $response['message'] = 'No policy documents found for OCR processing. Documents may not have been uploaded successfully.';

            return $response;
        }

        $this->dispatchPopulateDocumentDataBatch($quote, $uploadedDocuments, $process);

        $response['status'] = 'true';
        $response['processing'] = 'true';
        $response['message'] = 'OCR processing batch dispatched successfully';
        $response['documents_count'] = $uploadedDocuments->count();

        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - OCR processing batch dispatched for '.$uploadedDocuments->count().' documents');

        return $response;
    }

    private function dispatchPopulateDocumentDataBatch($quote, $uploadedDocuments, $process): void
    {
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Starting batch OCR processing for '.count($uploadedDocuments).' documents');
        $happinessUser = User::where('email', PolicyIssuanceEnum::API_POLICY_ISSUANCE_AUTOMATION_USER_EMAIL)->first();

        $documentOCRJobs = [];
        foreach ($uploadedDocuments as $document) {
            if (! $document) {
                continue;
            }

            $documentType = DocumentType::where(['quote_type_id' => self::TYPE_ID, 'code' => $document->document_type_code, 'is_active' => true])->first();
            if ($documentType) {
                $documentOCRJobs[] = new PopulateDocumentData(QuoteTypes::CAR, $quote, $documentType, $document->doc_url, $document->doc_mime_type, $happinessUser->id);
            } else {
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document type not found against document code: '.$document->document_type_code);
            }
        }

        if (empty($documentOCRJobs)) {
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - No OCR jobs to dispatch');

            return;
        }

        try {
            Bus::batch($documentOCRJobs)
                ->then(function (Batch $batch) use ($quote, $process) {
                    LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - All OCR jobs completed successfully (perfect success)');

                    LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Last executed Step: '.$process->completed_step);

                    $process->update([
                        'completed_step' => self::EXECUTE_OCR_PROCESSING,
                        'status' => PolicyIssuanceEnum::BOOKING_PENDING_STATUS,
                        'updated_at' => now(),
                    ]);

                    $process = $process->refresh();
                    LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Triggering next automation step: Policy Booking');
                    // (new PolicyIssuanceService)->executePolicyIssuanceAutomationSteps();
                })
                ->catch(function (Batch $batch, Throwable $e) use ($quote, $process) {
                    LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - OCR batch processing failed completely: '.$e->getMessage());

                    // Handle complete OCR failure - update status to failed
                    app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus(
                        $quote,
                        QuoteTypes::CAR->value,
                        self::OCR_PROCESSING_API_FAILED_STATUS_ID,
                        self::POLICY_AUTOMATION_STATUS_NO_ID
                    );

                    $process->update(['status' => PolicyIssuanceEnum::FAILED_STATUS, 'message' => json_encode(['error' => 'OCR batch processing failed: '.$e->getMessage()])]);
                    $process = $process->refresh();
                })
                ->finally(function (Batch $batch) use ($quote) {
                    LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - OCR batch processing completed');

                    // Note: Not handling OCR failures here to avoid race condition with policy booking
                    // OCR failures will be handled by the automation retry mechanism
                    if ($batch->hasFailures()) {
                        $successfulJobs = $batch->totalJobs - $batch->failedJobs;
                        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Partial failure detected. Stats: Total: '.$batch->totalJobs.', Failed: '.$batch->failedJobs.', Successful: '.$successfulJobs);
                    }
                })
                ->allowFailures()
                ->onQueue('shared')
                ->dispatch();

            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - OCR batch dispatched with '.count($documentOCRJobs).' jobs');
        } catch (Exception $e) {
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Failed to dispatch OCR batch: '.$e->getMessage());
        }
    }

    private function getPolicyIssuanceQuoteDocumentMapping($docName, $quote): ?array
    {
        $documentCodeMapping = [
            self::POLICY_DOC_TAX_INVOICE => DocumentTypeCode::TI,
            self::POLICY_DOC_TAX_INVOICE_BY_BUYER => DocumentTypeCode::CTIRBB,
            self::POLICY_DOC_RECEIPT => DocumentTypeCode::CPD_RECEIPT,
            self::POLICY_DOC_POLICY_SCHEDULE => DocumentTypeCode::CPS,
            self::POLICY_DOC_CERTIFICATE_OF_INSURANCE => DocumentTypeCode::CPC,
            self::POLICY_DOC_RENEWAL_POLICY_SCHEDULE => DocumentTypeCode::CPS,
        ];

        $docCode = $documentCodeMapping[$docName] ?? null;
        if ($docCode) {
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Document Mapping found for : '.$docName, ['key' => $docName, 'code' => $docCode]);

            return ['key' => $docName, 'code' => $docCode];
        }

        if (str_contains($docName, self::POLICY_DOC_CERTIFICATE_OF_INSURANCE)) {
            $docCode = QuoteDocumentsEnum::CAR_POLICY_CERTIFICATE;
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Document Name : '.$docName.' - ', ['key' => $docName, 'code' => $docCode]);

            return ['key' => $docName, 'code' => $docCode];
        }

        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Document Mapping not found for : '.$docName);

        return null;
    }

    private function uploadAndAttachToQuoteDocuments($quote, $documentContent, $documentCode, $originalName = null)
    {
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' started');

        $documentType = DocumentType::where(['quote_type_id' => self::TYPE_ID, 'code' => $documentCode, 'is_active' => true])->first();

        if (! $documentType) {
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Document type not found for code: '.$documentCode);

            return null;
        }

        $fileContents = base64_decode($documentContent);
        if ($fileContents === false) {
            $fileContents = $documentContent;
        }

        $docName = $originalName ?? ('car_document_'.uniqid().'.pdf');
        $mimeType = 'application/pdf';

        $docName = preg_replace('/\s+/', '_', $docName);
        $fileNameAzure = uniqid().'_'.$quote->uuid.'_'.$docName;
        $filePathAzure = 'documents/'.ucwords(self::TYPE).'/'.$fileNameAzure;

        Storage::disk('azureIM')->put($filePathAzure, $fileContents);

        $newDocument = $quote->documents()->create([
            'doc_name' => $docName,
            'original_name' => $originalName ?? $docName,
            'doc_url' => $filePathAzure,
            'doc_mime_type' => $mimeType,
            'document_type_code' => $documentType->code,
            'document_type_text' => $documentType->text,
            'doc_uuid' => generateUUID(),
        ]);

        if ($newDocument->exists) {
            WatermarkDocumentsJob::dispatch(
                $newDocument->id,
                $quote->uuid,
                $documentType->id
            );
        }

        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' Uploaded Document Name : '.$docName);

        return $newDocument;
    }

    private function executeBookPolicyStep($quote, $process)
    {
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - PID : '.$process->id.' - Initiating step : '.self::BOOK_POLICY);
        $triggerBookPolicyResponse = $this->bookPolicy($quote);

        if (! $triggerBookPolicyResponse['status']) {
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - '.$triggerBookPolicyResponse['message'] ?? 'Book policy failed', extra: ['response' => $triggerBookPolicyResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::BOOK_POLICY_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID, 'Send And Book Policy');

            return $triggerBookPolicyResponse;
        }

        $process->update(['completed_step' => $triggerBookPolicyResponse['completed_step']]);
        $process = $process->refresh();

        info($this->getLogPrefix(__FUNCTION__).' Quote : '.$process->model->code.' - Process ID : '.$process->id.' - Completed Step Updated to : '.$triggerBookPolicyResponse['completed_step']);

        return $triggerBookPolicyResponse;
    }

    public function bookPolicy($quote): array
    {
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' started');
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

        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Book Policy execution initiated, creating Sage process');
        $createSageProcessResponse = (new SageApiService)->postBookPolicyToSage($request, $quote);
        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, [], $createSageProcessResponse, '', self::BOOK_POLICY, $createSageProcessResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

        if (! $createSageProcessResponse['status']) {
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Book Policy execution failed, Error: '.$createSageProcessResponse['message']);
            $response['error'] = $createSageProcessResponse['message'];

            return $response;
        }

        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' Sage Process Created : '.$createSageProcessResponse['message']);

        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Booking process in started! It will take some time to Complete. Come Back in a while to check the status!';

        return $response;
    }

    private function updateBookingDetails($quote): array
    {
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Update booking details process started');
        $response = ['status' => true, 'error' => null, 'message' => null];

        // TODO:: this should be move in the service class
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Updating commission details');
        $updateCommission = app(ManualCommissionUpdateService::class)->updateCommissionForLeads([$quote->code]);
        if (! $updateCommission['status']) {
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Failed to update commission details');
            $response['status'] = false;
            $response['error'] = $updateCommission['error'];
            $response['message'] = $updateCommission['message'];

            // return $response;
        }

        $payment = $quote->payments()->mainLeadPayment()->first();
        $bookPolicyPayload = $this->bookPolicyPayload($quote, QuoteTypes::CAR->value, $quote->payments, $quote->quoteDocuments);

        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Booking Details before exeuting validation', extra: [
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
            ];

            request()->merge($updateBookingRequest);

            $bookPolicyRequest = new BookPolicyRequest;
            $validator = Validator::make($updateBookingRequest, $bookPolicyRequest->rules());
            $bookPolicyRequest->withValidator($validator);

            if ($validator->fails()) {
                $response['status'] = false;
                $response['error'] = $validator->errors()->first() ?? 'BookPolicyRequest validation failed';
                $response['message'] = $validator->errors()->first();

                LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - BookPolicyRequest validation failed: '.$response['message']);

                return $response;
            }

            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Updating booking details');
            $updateBookingDetailsResponse = app(CentralService::class)->updateBookingDetails($updateBookingRequest, $bookPolicyRequest);

            if (! $updateBookingDetailsResponse['status']) {
                LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Failed to update booking details');
                $response['status'] = false;
                $response['error'] = $updateBookingDetailsResponse['message'];
                $response['message'] = $updateBookingDetailsResponse['message'];
            }

            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Update booking details process completed');

        } catch (Exception $e) {
            $response['status'] = false;
            $response['error'] = 'Booking update error: '.$e->getMessage();
            $response['message'] = 'An error occurred while updating booking details: '.$e->getMessage();

            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Booking update process failed, Exception: '.$e->getMessage());
        }

        return $response;
    }

    private function validateBookPolicy($quote): array
    {
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Validating book policy prerequisites process started');
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

            $sendBookPolicyRequest = new SendBookPolicyRequest;
            $validator = Validator::make($requestData, $sendBookPolicyRequest->rules());
            $sendBookPolicyRequest->withValidator($validator);

            if ($validator->fails()) {
                $response['status'] = false;
                $response['error'] = $validator->errors()->first() ?? 'SendBookPolicyRequest validation failed';
                $response['message'] = $validator->errors()->first();

                LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - SendBookPolicyRequest validation failed: '.$response['message']);

                return $response;
            }

            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - All prerequisites validated successfully, Validate prerequisites process completed');
            $response['message'] = 'All book policy prerequisites validated successfully';

        } catch (Exception $e) {
            $response['status'] = false;
            $response['error'] = 'Validation error: '.$e->getMessage();
            $response['message'] = 'An error occurred during validation: '.$e->getMessage();

            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quote->code.' - Validate prerequisites process failed, Exception: '.$e->getMessage());
        }

        return $response;
    }

    private function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $payload = [
            'client_id' => $this->clientId, 'client_secret' => $this->clientSecret,  'grant_type' => 'client_credentials',  'audience' => 'integration-platform',
        ];

        $header = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        $response = $this->httpCall($this->authUrl, $payload, $header, self::REQUEST_AUTH);

        if ($response['status']) {
            $data = $response['data'];

            $accessToken = $data->access_token;
            $expiresIn = (int) $data->expires_in;

            Cache::store('redis')->put(self::POLICY_ISSUANCE_API_ACCESS_TOKEN_KEY, $accessToken, $expiresIn);
            $this->accessToken = Cache::store('redis')->get(self::POLICY_ISSUANCE_API_ACCESS_TOKEN_KEY);

            return $this->accessToken;
        }

        LoggerService::info($this->getLogPrefix(__FUNCTION__).' - Failed to get access token: '.($response['message'] ?? 'Unknown error'));

        return null;
    }

    private function httpCall($endPoint, $payload, $requestHeader = [], $method = self::REQUEST_GET)
    {
        $response = ['status' => false, 'error' => null, 'message' => null, 'data' => null, 'completed_step' => null];

        if ($method == self::REQUEST_AUTH) {
            $header = $requestHeader;
        } else {
            $commentHeader = [
                'Authorization' => 'Bearer '.$this->accessToken,
                'opCo' => 'GULF',
                'sourceApplication' => 'OLS',
            ];
            $header = array_merge($commentHeader, $requestHeader);
        }

        try {
            $httpResponse = match ($method) {
                self::REQUEST_PATCH => Http::retry(
                    $this->maxRetries,
                    $this->retryDelay
                )->timeout(30)->withHeaders($header)->patch($endPoint, $payload),
                self::REQUEST_POST => Http::retry(
                    1,
                    10000
                )->timeout(30)->withHeaders($header)->asJson()->post($endPoint, $payload),
                self::REQUEST_AUTH => Http::retry(
                    $this->maxRetries,
                    $this->retryDelay
                )->timeout(30)->withHeaders($header)->asForm()->post($endPoint, $payload),
                default => Http::retry($this->maxRetries, $this->retryDelay)->timeout(30)->withHeaders($header)->get($endPoint, $payload),
            };

            if ($httpResponse->successful()) {
                $response['status'] = true;
                $response['data'] = $httpResponse->object();
                $response['message'] = 'API call successfully executed.';
            } else {
                $response['error'] = $httpResponse->object()->message;
                $response['message'] = $httpResponse->object()?->description ?? $httpResponse->object()->message;
            }

            LoggerService::info($this->getLogPrefix(__FUNCTION__).' Endpoint : '.$endPoint.' Status : '.($response['status'] ? 'success' : 'failed').' , Message : '.$response['message'].' , Error : '.$response['error']);

            return $response;
        } catch (Exception $e) {
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' - Endpoint :  '.$endPoint.' , Error : '.$e->getMessage());

            $response['error'] = $e->getMessage();
            $response['message'] = $e->getMessage();

            return $response;
        }
    }

    public function getQuoteDetailsFromInsurer($quoteTypeId, $quoteDetails)
    {
        LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quoteDetails->code.' started');
        $colors = collect(app(AMLService::class)->getAMLLookups($quoteDetails?->plan?->provider_id, [
            LookupsEnum::VEHICLE_COLOR,
        ])->toArray()['vehicle_color'] ?? [])->pluck('text', 'code')->toArray();

        $othersColorCode = collect($colors ?? [])->filter(function ($text, $code) {
            return stripos($text, 'other') !== false;
        })->keys()->first();

        $validateColorCode = function ($colorCode) use ($colors, $othersColorCode) {
            if ($colorCode && isset($colors[$colorCode])) {
                return $colorCode; // Color exists, return original
            }

            return $othersColorCode; // Fallback to Others option
        };

        try {
            $response = Ken::request('/get-quote-from-insurer?quoteTypeId='.$quoteTypeId.'&quoteUID='.$quoteDetails->uuid, 'get');

            if (isset($response['data'])) {
                $responseData = $response['data'];
                $vehicleDriverDetailsData = [
                    'is_insured_and_driver_same' => ($responseData['policyHolder']['isPolicyHolderDriver'] ? '1' : '0') ?? null,
                    'driver_first_name' => isset($responseData['policyHolder']['isPolicyHolderDriver'])
                        ? (
                            $responseData['policyHolder']['isPolicyHolderDriver']
                                ? ($responseData['policyHolder']['person']['givenName'] ?? null)
                                : ($responseData['driver']['firstName'] ?? null)
                        )
                        : null,
                    'driver_last_name' => isset($responseData['policyHolder']['isPolicyHolderDriver'])
                        ? (
                            $responseData['policyHolder']['isPolicyHolderDriver']
                                ? ($responseData['policyHolder']['person']['surName'] ?? null)
                                : ($responseData['driver']['lastName'] ?? null)
                        )
                        : null,
                    'driver_dob' => isset($responseData['policyHolder']['isPolicyHolderDriver'])
                        ? (
                            $responseData['policyHolder']['isPolicyHolderDriver']
                                ? ($responseData['policyHolder']['person']['birthDate'] ?? null)
                                : ($responseData['driver']['dateOfBirth'] ?? null)
                        )
                        : null,
                    'driver_gender' => isset($responseData['policyHolder']['isPolicyHolderDriver'])
                        ? (
                            ($getDriverGender = $responseData['policyHolder']['isPolicyHolderDriver']
                                ? ($responseData['policyHolder']['person']['gender']['value'] ?? null)
                                : ($responseData['driver']['gender'] ?? null)
                            ) !== null
                                ? strtolower($getDriverGender)
                                : null
                        )
                        : null,
                    'driver_license_number' => isset($responseData['policyHolder']['documents']) && is_array($responseData['policyHolder']['documents'])
                        ? (
                            collect($responseData['policyHolder']['documents'])
                                ->first(fn ($doc) => isset($doc['docType']['code']) && $doc['docType']['code'] === self::DRIVING_LICENSE_DOC_TYPE_CODE)['docId']
                                ?? null
                        )
                        : null,
                    'driver_license_expiry_date' => isset($responseData['policyHolder']['documents']) && is_array($responseData['policyHolder']['documents'])
                        ? (
                            collect($responseData['policyHolder']['documents'])
                                ->first(fn ($doc) => isset($doc['docType']['code']) && $doc['docType']['code'] === self::DRIVING_LICENSE_DOC_TYPE_CODE)['docExpiryDate']
                                ?? null
                        )
                        : null,
                    'driver_uae_driving_experience' => $responseData['policyHolder']['policyHolderDrivingExperience'] ?? '',
                    'traffic_code_number' => $responseData['motorInformation']['trafficFileNumber'] ?? null,
                    'rta_transaction_type' => $responseData['authorityTransactionDetails']['code'] ?? null,
                    'vehicle_plate_code' => isset($responseData['motorInformation']['plateNumber']) ? $this->extractPlateCode($responseData['motorInformation']['plateNumber']) : null,
                    'vehicle_plate_number' => isset($responseData['motorInformation']['plateNumber']) ? $this->extractPlateNumber($responseData['motorInformation']['plateNumber']) : null,
                    'vehicle_engine_number' => $responseData['motorInformation']['engineNumber'] ?? null,
                    'rta_plate_category' => $responseData['motorInformation']['rtaPlateCategory'] ?? '',
                    'vehicle_color' => $validateColorCode($responseData['motorInformation']['vehicleColor']['code'] ?? null),
                    'vehicle_plate_color' => $validateColorCode($responseData['motorInformation']['plateColor']['code'] ?? null),
                    'bank_loan' => $responseData['motorInformation']['isVehicleMortgaged'] ?? null,
                    'bank_name' => ! empty($responseData['motorInformation']['isVehicleMortgaged']) ? ($responseData['motorInformation']['bankName'] ?? '') : '',
                    'first_registration_date' => $responseData['policySchedule']['creationDate'] ?? null,
                    'annual_mileage_estimate' => $responseData['annualMileageEstimate'] ?? '',
                ];

                $quoteDetailsData = [
                    'policy_start_date' => $responseData['policySchedule']['effectiveDate'] ?? null,
                    'policy_expiry_date' => $responseData['policySchedule']['expirationDate'] ?? null,
                    'certificate_start_date' => $responseData['motorInformation']['certificateInceptionDate'] ?? '',
                    'certificate_end_date' => $responseData['motorInformation']['certificateEndDate'] ?? '',
                ];

                $getQuoteResponseMapping = [
                    'chassis_number' => $responseData['motorInformation']['chassisNumber'] ?? null,
                ];

                $carQuoteRequestDetails = CarQuoteRequestDetail::where('car_quote_request_id', $quoteDetails->id)->first();
                if ($carQuoteRequestDetails) {
                    LoggerService::info($this->getLogPrefix(__FUNCTION__).' Quote : '.$quoteDetails->code.' - Updating quote details');
                    $carQuoteRequestDetails->update($getQuoteResponseMapping);
                    $quoteDetails->update($quoteDetailsData);
                    $quoteDetails->vehicleDriverDetail()->updateOrCreate([], $vehicleDriverDetailsData); // TODO: need to discuss update or create.
                }

                $getQuoteResponseMapping['uwApprovalStatus'] = $responseData['uwApprovalStatus'] ?? null;

                $getQuoteResponseMapping = array_merge($getQuoteResponseMapping, $vehicleDriverDetailsData, $quoteDetailsData);

                return [
                    'success' => true,
                    'message' => 'Quote details retrieved and updated successfully',
                    'data' => $getQuoteResponseMapping ?? null,
                ];
            } else {
                $_returnResponse = ['success' => false, 'message' => $response['message'] ?? 'Failed to retrieve quote details from insurer portal'];

                if (isset($response['isPolicyExpired']) && $response['isPolicyExpired']) {
                    $_returnResponse['isPolicyExpired'] = $response['isPolicyExpired'];
                }

                return $_returnResponse;
            }
        } catch (\Exception $e) {
            LoggerService::info($this->getLogPrefix(__FUNCTION__).' - Error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'An error occurred while retrieving quote details from insurer portal',
            ];
        }
    }

    public function getStepsLockingStatus($quote): array
    {
        $policyIssuance = $quote->policyIssuance;
        $response = [
            'policyIssuance' => $policyIssuance,
            'isEditPolicyDetailsDisabled' => true,
            'isEditBookingDetailsDisabled' => true,
            'message' => 'All steps are locked',
            'insurer_api_status' => $quote->insurer_api_status,
        ];

        if ($policyIssuance?->status === PolicyIssuanceEnum::BOOKING_PROCESSING_STATUS) {
            $response['isEditPolicyDetailsDisabled'] = false;
            $response['isEditBookingDetailsDisabled'] = false;
            $response['message'] = 'All Steps are editable';

            return $response;
        }

        if ($policyIssuance?->status === PolicyIssuanceEnum::FAILED_STATUS) {
            if (! $policyIssuance->completed_step || $policyIssuance->completed_step === self::UPLOAD_DOCUMENTS) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'All Steps are editable';

                return $response;
            }
            if ($policyIssuance->completed_step === self::ISSUE_POLICY) {
                $response['isEditPolicyDetailsDisabled'] = false;
                $response['isEditBookingDetailsDisabled'] = false;
                $response['message'] = 'Upload Documents and Update Booking Details are editable';

                return $response;
            }
            if (in_array($policyIssuance->completed_step, [self::GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM, self::EXECUTE_OCR_PROCESSING])) {
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

        return $response;
    }

    public function getInsurerAPIStatuses()
    {
        return [
            self::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID => self::UPLOAD_POLICY_DOCUMENTS_API_FAILED,
            self::POLICY_ISSUANCE_API_FAILED_STATUS_ID => self::POLICY_ISSUANCE_API_FAILED,
            self::GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID => self::GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED,
            self::OCR_PROCESSING_API_FAILED_STATUS_ID => self::OCR_PROCESSING_API_FAILED,
            self::BOOK_POLICY_API_FAILED_STATUS_ID => self::BOOK_POLICY_API_FAILED,
            GenericRequestEnum::PREVIOUS_POLICY_EXPIRED_STATUS_ID => GenericRequestEnum::PREVIOUS_POLICY_EXPIRED, // 99 is the status id for previous policy expired
        ];
    }

    public function getFailedIssuanceAPIStatuses()
    {
        return [
            self::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
            self::POLICY_ISSUANCE_API_FAILED_STATUS_ID,
            self::GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID,
            self::OCR_PROCESSING_API_FAILED_STATUS_ID,
            self::BOOK_POLICY_API_FAILED_STATUS_ID,
        ];
    }

    private function extractPlateCode(string $plateNumber): string
    {
        preg_match('/([A-Za-z]+)/', $plateNumber, $matches);

        return $matches[1] ?? '';
    }

    private function extractPlateNumber(string $plateNumber): string
    {
        preg_match('/(\d+)/', $plateNumber, $matches);

        return $matches[1] ?? '';
    }
}
