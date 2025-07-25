<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Car;

use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Facades\Ken;
use App\Http\Requests\SendBookPolicyRequest;
use App\Interfaces\PolicyIssuanceInterface;
use App\Jobs\OCR\PopulateDocumentData;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\DocumentType;
use App\Models\InsurerRequestResponse;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\SageApiService;
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
    private readonly string $className;
    private readonly string $baseUrl;
    private readonly string $authUrl;
    private readonly string $clientId;
    private readonly string $clientSecret;
    private string $accessToken;
    
    public const INSURER_CODE = InsuranceProvidersEnum::AXA;
    public const POLICY_ISSUANCE_API_ACCESS_TOKEN_KEY = InsuranceProvidersEnum::AXA.'_POLICY_ISSUANCE_API_ACCESS_TOKEN';
    public const TYPE = quoteTypeCode::Car;
    public const TYPE_ID = QuoteTypeId::Car;

    public const UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const ISSUE_POLICY = 'IssuePolicy';
    public const UPLOAD_POLICY_DOCUMENTS_TO_IMCRM = 'UploadPolicyDocumentsToIMCRM';
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

    private const POLICY_DOC_TAX_INVOICE = 'Tax invoice';
    private const POLICY_DOC_COMMISSION_STATEMENT = 'Commission statement';
    private const POLICY_DOC_POLICY_SCHEDULE = 'Motor Insurance Policy Schedule';
    private const POLICY_DOC_CERTIFICATE_OF_INSURANCE = 'Certificate of Insurance';

    private const RTA_UPLOAD_STATUS_DONE = '1';
    private const RTA_UPLOAD_STATUS_PENDING = '0';

    const POLICY_AUTOMATION_STATUS_YES_ID = 1;
    const POLICY_AUTOMATION_STATUS_NO_ID = 2;

    const UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID = 2;
    const UPLOAD_POLICY_DOCUMENTS_API_FAILED = 'Document Upload API Failed';
    const UPLOAD_POLICY_DOCUMENTS_API_ACTION_MESSAGE = 'Document Upload via API';

    const POLICY_ISSUANCE_API_FAILED_STATUS_ID = 3;
    const POLICY_ISSUANCE_API_FAILED = 'Policy Issuance API Failed';
    const POLICY_ISSUANCE_API_ACTION_MESSAGE = 'Policy Issuance via API';

    const UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID = 4;
    const UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED = 'Upload Policy Documents to IMCRM API Failed';
    const UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_ACTION_MESSAGE = 'Upload Policy Documents to IMCRM via API';

    const BOOK_POLICY_API_FAILED_STATUS_ID = 5;
    const BOOK_POLICY_API_FAILED = 'Book Policy API Failed';
    const BOOK_POLICY_API_ACTION_MESSAGE = 'Book Policy via API';

    public function __construct()
    {
        $this->className = __CLASS__;
        $this->baseUrl = config('constants.GIG_API_BASE_URL').'/apis/gulf-motor-v3-vs/motor';
        $this->authUrl = config('constants.GIG_API_AUTH_BASE_URL').'/oauth/token';
        $this->clientId = config('constants.GIG_API_AUTH_CLIENT_ID');
        $this->clientSecret = config('constants.GIG_API_AUTH_CLIENT_SECRET');
        // $this->accessToken = Cache::store('redis')->get(self::POLICY_ISSUANCE_API_ACCESS_TOKEN_KEY) ?? $this->getAccessToken();
        // $this->accessToken = $this->getAccessToken();
        $this->accessToken = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6Ijg1ZDBKQ2M4TUJua2xxVlZzSUgyRyJ9.eyJodHRwczovL2d1bGYtaW5zdXJhbmNlLXBwL2VwYXIiOiI2MDM0IiwiaXNzIjoiaHR0cHM6Ly9ndWxmLWluc3VyYW5jZS1wcC5ldS5hdXRoMC5jb20vIiwic3ViIjoiakJzSTRYN2FaWjl2UjBIak5tS3lZdTM4am03Vkl1WHRAY2xpZW50cyIsImF1ZCI6ImludGVncmF0aW9uLXBsYXRmb3JtIiwiaWF0IjoxNzUyODM2NTQ4LCJleHAiOjE3NTI4NDAxNDgsInNjb3BlIjoicmVhZDpleHRlcm5hbCIsImd0eSI6ImNsaWVudC1jcmVkZW50aWFscyIsImF6cCI6ImpCc0k0WDdhWlo5dlIwSGpObUt5WXUzOGptN1ZJdVh0In0.r4sN6BT1qq8vUC-R3J0IYuXh7nubZjp--VYb0QKukuQr_HIGhf1dPFY1iGnpcq3iq5THyazrYABDTXha1wErPtHr16FjmSiE0DvoV_8wPu0V-C59pEP4hOh-3fIDuOUs-0mUQy0_9rzh8PgZare_rqbO_T5L4QoaTgj435-h7uH-BItpsLUXAxrNboSTb6Vppq-JiYLDEHjxs_NHBpz2C8ws3SFswD5qH83-VVhLb6uUJkH_I0u1J0wvkFomH1UUlBAab2tFWJ7r34fhUQ2JqzRLbqkFuw5A9QEvDrWJKK1JeBT3lyusR_dhC7VOetQCWfl0cNHw5_Rt-XTttWsGLw';
    }

    private function getAPISteps(): array
    {
        return [
            self::UPLOAD_DOCUMENTS,
            self::ISSUE_POLICY,
            self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
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

    public function createPolicyIssuanceSchedule($quote, $insurer)
    {
        LoggerService::startQuoteLogging($quote);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        if ($this->isPolicyIssuanceAutomationEnabled()) {
            $this->policyIssuance = (new PolicyIssuanceService)->schedulePolicyIssuance($quote, $insurer, self::TYPE, $this->className);
        } else {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - AXA Car Automation is disabled');
        }

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        return $this->policyIssuance;
    }

    public function executeSteps($process): array
    {
        $response = ['status' => false, 'error' => null, 'message' => null];

        $this->policyIssuance = $process;
        $quote = $process->model;

        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::POLICY_AUTOMATION);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' - Plan ID : '.$quote->plan_id.' started');

        try {
            if (! $this->isPolicyIssuanceAutomationEnabled()) {
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - GIG Car Automation is disabled');
                $response['error'] = 'GIG Car Automation is disabled';
                $response['message'] = 'GIG Car Automation is disabled';

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
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Exception : '.$e->getMessage());

            return $response;
        }

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' ended');

        return $response;
    }

    private function executeStepSequence($quote, $process, $nextStepToBeExecuted)
    {
        if ($nextStepToBeExecuted === self::UPLOAD_DOCUMENTS) {
            $uploadDocumentsResponse = $this->executeUploadDocumentsStep($quote, $process);
            if(! $uploadDocumentsResponse['status']) {
                return $uploadDocumentsResponse;
            }

            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === self::ISSUE_POLICY) {
            $issuePolicyResponse = $this->executeIssuePolicyStep($quote, $process);
            if(! $issuePolicyResponse['status']) {
                return $issuePolicyResponse;
            }

            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM) {
            $uploadPolicyDocumentsToIMCRMResponse = $this->executeUploadPolicyDocumentsStep($quote, $process);
            if(! $uploadPolicyDocumentsToIMCRMResponse['status']) {
                return $uploadPolicyDocumentsToIMCRMResponse;
            }

            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === self::BOOK_POLICY) {
            $bookPolicyResponse = $this->executeBookPolicyStep($quote, $process);
            if(! $bookPolicyResponse['status']) {
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
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::UPLOAD_DOCUMENTS);
        $uploadDocumentsResponse = $this->UploadDocuments($quote);

        if (! $uploadDocumentsResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - '.$uploadDocumentsResponse['message'] ?? 'Document upload failed', extra: ['response' => $uploadDocumentsResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID);
        
            return $uploadDocumentsResponse;
        }

        $process->update(['completed_step' => $uploadDocumentsResponse['completed_step']]);
        $process = $process->refresh();
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - PID : '.$process->id.' - Completed Step Updated to : '.$uploadDocumentsResponse['completed_step']);
    
        return $uploadDocumentsResponse;
    }

    public function UploadDocuments($quote): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started - PID : '.$this->policyIssuance->id.' - Step : '.self::UPLOAD_DOCUMENTS);
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
                info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - No documents found for document type: '.$docTypeCode);
                continue;
            }

            $documentTypeUploadedCount = 0;
            $documentTypeFailedCount = 0;

            foreach ($documentsForThisType as $quoteDocument) {
                $documentFile = Storage::disk('azureIM')->get($quoteDocument->doc_url);
                $docFileBase64 = base64_encode($documentFile);
                $payload = [
                    'referenceType' => 'quotation',
                    'referenceValue' => $quote->insurer_quote_number,
                    'documentType' => [
                        'code' => $documentToUpload['insurerDocCode'],
                        'value' => $documentToUpload['insurerDocName'],
                    ],
                    'documentContent' => $docFileBase64,
                    'documentName' => $quoteDocument->doc_name,
                    'mimeType' => $quoteDocument->doc_mime_type,
                ];

                $uploadDocResponse = $this->httpCall($endPoint, $payload, [], self::REQUEST_POST);
                app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $uploadDocResponse, $this->baseUrl.$endPoint, self::UPLOAD_DOCUMENTS, $uploadDocResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

                if ($uploadDocResponse['status']) {
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - '.$documentToUpload['insurerDocName'].' ('.$quoteDocument->doc_name.') uploaded to insurer portal');
                    $documentTypeUploadedCount++;
                } else {
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Failed to upload '.$quoteDocument->doc_name.' - Message : '.$uploadDocResponse['message'].' , Error : '.$uploadDocResponse['error']);
                    $documentTypeFailedCount++;
                }
            }

            // Mark document type as uploaded only if ALL documents for this type were uploaded successfully
            if ($documentTypeUploadedCount > 0 && $documentTypeFailedCount === 0) {
                $documentToUpload['uploaded'] = true;
                $documentToUpload['uploaded_count'] = $documentTypeUploadedCount;
                info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - All '.$documentTypeUploadedCount.' documents for '.$documentToUpload['insurerDocName'].' uploaded successfully');
            } else {
                $documentToUpload['uploaded'] = false;
                $documentToUpload['uploaded_count'] = $documentTypeUploadedCount;
                $documentToUpload['failed_count'] = $documentTypeFailedCount;
                info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document type '.$documentToUpload['insurerDocName'].' partially failed - Uploaded: '.$documentTypeUploadedCount.', Failed: '.$documentTypeFailedCount);
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
                    $errorDetails[] = $docType['insurerDocName'] . " (uploaded {$uploadedCount}/{$totalCount})";
                } else {
                    $errorDetails[] = $docType['insurerDocName'] . " (all {$totalCount} failed)";
                }
            }
            
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - failed to upload all required documents to insurer : ', $errorDetails);
            $response['message'] = 'Failed to upload all documents for: '.implode(', ', $errorDetails);
            $response['error'] = 'Failed to upload all required documents to insurer';

            return $response;
        }
        
        $successDetails = [];
        $totalUploadedCount = 0;
        foreach ($documentsToUpload->where('uploaded', true) as $docType) {
            $uploadedCount = $docType['uploaded_count'] ?? 1;
            $totalUploadedCount += $uploadedCount;
            $successDetails[] = $docType['insurerDocName'] . " ({$uploadedCount} document" . ($uploadedCount > 1 ? 's' : '') . ")";
        }
        
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.'  ended', [
            'Total Documents Uploaded' => $totalUploadedCount,
            'Document Types' => $successDetails
        ]);

        $response['status'] = true;
        $response['message'] = 'Successfully uploaded ' . $totalUploadedCount . ' documents: ' . implode(', ', $successDetails);

        return $response;
    }

    private function documentsToUpload()
    {
        return collect([
            QuoteDocumentsEnum::CAR_REGISTRATION_CARD => [
                'code' => QuoteDocumentsEnum::CAR_REGISTRATION_CARD,
                'insurerDocCode' => 'DT01',
                'insurerDocName' => 'Car Registration',
                'uploaded' => false,
            ],
            QuoteDocumentsEnum::CAR_EMIRATE_ID => [
                'code' => QuoteDocumentsEnum::CAR_EMIRATE_ID,
                'insurerDocCode' => 'DT02',
                'insurerDocName' => 'National Id',
                'uploaded' => false,
            ],
            QuoteDocumentsEnum::DRIVING_LICENSE => [
                'code' => QuoteDocumentsEnum::DRIVING_LICENSE,
                'insurerDocCode' => 'DT03',
                'insurerDocName' => 'Driving License',
                'uploaded' => false,
            ],
        ]);
    }

    private function executeIssuePolicyStep($quote, $process): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::ISSUE_POLICY);
        $policyIssuanceResponse = $this->issuePolicy($quote, $process);

        if (! $policyIssuanceResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - '.$policyIssuanceResponse['message'] ?? 'Policy issuance failed', extra: ['response' => $policyIssuanceResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::POLICY_ISSUANCE_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID);
        
            return $policyIssuanceResponse;
        }

        $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
        $process = $process->refresh();

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - Process ID : '.$process->id.' - Completed Step Updated to : '.$policyIssuanceResponse['completed_step']);
        
        return $policyIssuanceResponse;
    }

    public function issuePolicy($quote): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started - PID : '.$this->policyIssuance->id.' - Step : '.self::ISSUE_POLICY);
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
                    'currencyCode' => self::CURRENCY_CODE
                ],
            ]
        ];

        $issuePolicy = $this->httpCall($endPoint, $payload, [], self::REQUEST_POST);
        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $issuePolicy, $this->baseUrl.$endPoint, self::ISSUE_POLICY, $issuePolicy['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

        if (! $issuePolicy['status']) {
            $response['error'] = $issuePolicy['error'];
            $response['message'] = $issuePolicy['message'];
            $response['status'] = false;

            return $response;
        }

        $response['status'] = true;
        $response['message'] = 'Policy issued successfully';
        $response['completed_step'] = self::ISSUE_POLICY;
        $response['data'] = $issuePolicy;

        return $response;
    }

    private function executeUploadPolicyDocumentsStep($quote, $process)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);
        $uploadPolicyDocumentsToIMCRMResponse = $this->uploadPolicyDocumentsToIMCRM($quote, $process);

        if (! $uploadPolicyDocumentsToIMCRMResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - '.$uploadPolicyDocumentsToIMCRMResponse['message'] ?? 'Upload policy documents to IMCRM failed', extra: ['response' => $uploadPolicyDocumentsToIMCRMResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID);

            return $uploadPolicyDocumentsToIMCRMResponse;
        }

        $process->update(['completed_step' => $uploadPolicyDocumentsToIMCRMResponse['completed_step']]);
        $process = $process->refresh();

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - Process ID : '.$process->id.' - Completed Step Updated to : '.$uploadPolicyDocumentsToIMCRMResponse['completed_step']);

        return $uploadPolicyDocumentsToIMCRMResponse;
    }

    public function uploadPolicyDocumentsToIMCRM($quote, $process): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started - Policy Issuance ID : '.$this->policyIssuance->id.' - Step : '.self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);
        $response = ['status' => false, 'completed_step' => self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM, 'error' => null, 'message' => null];

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

        foreach ($policyDocuments as $policyDocument) {
            $quoteDocument = null;
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
                $docName = $document['data']?->document?->name;
                $docMapping = $this->getPolicyIssuanceQuoteDocumentMapping($policyDocument->name, $quote);
                info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document Name : '.$docName);

                $quoteDocument = $this->uploadAndAttachToQuoteDocuments($quote, $document['data']?->document?->content, $docMapping['code'], $docName);
            } else {
                info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Insurer Document not found for : '.$policyDocument->name, ['status' => $document['status'], 'error' => $document['error']]);
            }

            app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, [], $document, $this->baseUrl.$endPoint, self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM, $document['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);
            $uploadedDocumentsToIMCRM->push([
                'name' => $docName,
                'uploaded' => $quoteDocument?->id ? true : false,
                'message' => $document['message'],
                'document' => $quoteDocument,
            ]);

            if ($docName === self::POLICY_DOC_CERTIFICATE_OF_INSURANCE && $quoteDocument?->id) {
                // $quote->update(['rta_upload_status' => self::RTA_UPLOAD_STATUS_DONE]);
            } else {
                // $quote->update(['rta_upload_status' => self::RTA_UPLOAD_STATUS_PENDING]);
            }
        }

        $allDocumentsUploaded = $uploadedDocumentsToIMCRM->where('uploaded', false)->count() === 0;
        if (! $allDocumentsUploaded) {
            $docsUploadToIMCRMFailed = $uploadedDocumentsToIMCRM->where('uploaded', false)->pluck('name')->toArray();
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - failed to fetch all documents from insurer : ', $docsUploadToIMCRMFailed);
            
            $error = 'Upload policy documents to IMCRM failed';
            $response['error'] = $error;
            $response['message'] = $error;

            return $response;
        } 

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - fetched all documents from insurer and Uploaded to IMCRM ');

        // Collect successfully uploaded documents for batch OCR processing
        // $uploadedDocuments = $uploadedDocumentsToIMCRM->where('uploaded', true)
        //     ->filter(function ($item) {
        //         return isset($item['document']) && $item['document'];
        //     })
        //     ->pluck('document');

        // if ($uploadedDocuments->isNotEmpty()) {
        //     $this->dispatchPopulateDocumentDataBatch($quote, $uploadedDocuments, $process);
            
        //     $response['status'] = true;
        //     $response['message'] = 'Documents uploaded to IMCRM successfully. OCR processing batch dispatched.';
        //     $response['completed_step'] = self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM;
        // } else {
        //     // No documents were successfully uploaded - this should be treated as an error
        //     $response['status'] = false;
        //     $response['error'] = 'No documents were successfully uploaded to IMCRM';
        //     $response['message'] = 'Failed to upload any documents to IMCRM';
            
        //     return $response;
        // }

        $response['status'] = true;
        $response['message'] = 'Documents uploaded to IMCRM successfully. OCR processing batch dispatched.';
        $response['completed_step'] = self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM;

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Process completed step updated to : '.$response['completed_step']);

        return $response;
    }

    /**
     * Dispatch PopulateDocumentData jobs as a batch for all uploaded documents
     */
    private function dispatchPopulateDocumentDataBatch($quote, $uploadedDocuments, $process): void
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Starting batch OCR processing for '.count($uploadedDocuments).' documents');

        $jobs = [];
        
        foreach ($uploadedDocuments as $document) {
            if (! $document) {
                continue;
            }

            $documentType = DocumentType::where([
                'quote_type_id' => self::TYPE_ID, 
                'code' => $document->document_type_code, 
                'is_active' => true
            ])->first();

            if ($documentType) {
                $jobs[] = new PopulateDocumentData(
                    QuoteTypes::CAR,
                    $quote,
                    $documentType,
                    $document->doc_url,
                    $document->doc_mime_type,
                    1 // Default user ID for automated processes, i think it's happy customer or something
                );
            }
        }

        if (empty($jobs)) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - No OCR jobs to dispatch');
            return;
        }

        try {
            Bus::batch($jobs)
                ->then(function (Batch $batch) use ($quote, $process) {
                    LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - All OCR jobs completed successfully');
                    
                    // Update the process status to reflect completion of document processing
                    $process->update([
                        'completed_step' => self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
                        'updated_at' => now()
                    ]);
                    
                    LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Process step updated after successful OCR batch completion');
                })
                ->catch(function (Batch $batch, Throwable $e) use ($quote) {
                    LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - OCR batch processing failed: '.$e->getMessage());
                })
                ->finally(function (Batch $batch) use ($quote) {
                    LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - OCR batch processing completed (success or failure)');
                })
                ->allowFailures()
                ->onQueue('shared')
                ->dispatch();

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - OCR batch dispatched with '.count($jobs).' jobs');
        } catch (Exception $e) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Failed to dispatch OCR batch: '.$e->getMessage());
        }
    }

    private function getPolicyIssuanceQuoteDocumentMapping($docName, $quote): ?array
    {
        // TODO:: need to update this mapping
        $documentCodeMapping = [
            self::POLICY_DOC_TAX_INVOICE => QuoteDocumentsEnum::CAR_TAX_INVOICE, // TI
            self::POLICY_DOC_COMMISSION_STATEMENT => QuoteDocumentsEnum::CAR_TAX_INVOICE,
            self::POLICY_DOC_POLICY_SCHEDULE => QuoteDocumentsEnum::POLICY_SCHEDULE,
        ];

        $docCode = $documentCodeMapping[$docName] ?? null;
        if ($docCode) {
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document Mapping found for : '.$docName, ['key' => $docName, 'code' => $docCode]);
            return ['key' => $docName, 'code' => $docCode];
        }

        if (str_contains($docName, self::POLICY_DOC_CERTIFICATE_OF_INSURANCE)) {
            $docCode = QuoteDocumentsEnum::CAR_POLICY_CERTIFICATE; 
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Document Name : '.$docName.' - ', ['key' => $docName, 'code' => $docCode]);

            return ['key' => $docName, 'code' => $docCode];
        }

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document Mapping not found for : '.$docName);

        return null;
    }

    private function uploadAndAttachToQuoteDocuments($quote, $documentContent, $documentCode, $originalName = null)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $documentType = DocumentType::where(['quote_type_id' => self::TYPE_ID, 'code' => $documentCode, 'is_active' => true])->first();

        if (! $documentType) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document type not found for code: '.$documentCode);

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

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Uploaded Document Name : '.$docName);

        return $newDocument;
    }

    private function executeBookPolicyStep($quote, $process)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::BOOK_POLICY);
        $triggerBookPolicyResponse = $this->bookPolicy($quote);

        if (! $triggerBookPolicyResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - '.$triggerBookPolicyResponse['message'] ?? 'Book policy failed', extra: ['response' => $triggerBookPolicyResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::BOOK_POLICY_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID);
            
            return $triggerBookPolicyResponse;
        }

        $process->update(['completed_step' => $triggerBookPolicyResponse['completed_step']]);
        $process = $process->refresh();

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - Process ID : '.$process->id.' - Completed Step Updated to : '.$triggerBookPolicyResponse['completed_step']);

        return $triggerBookPolicyResponse;
    }

    public function bookPolicy($quote): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $response = ['status' => false, 'completed_step' => self::BOOK_POLICY, 'error' => null, 'message' => null];
        // $payment = $quote->payments()->mainLeadPayment()->first();
        // $insurer = getInsuranceProvider($payment, QuoteTypes::CAR->value);

        // $getQuoteCallFromInsurer = InsurerRequestResponse::where([
        //     'quote_uuid' => '98M8PNSL',//$quote->uuid,
        //     'call_type' => 'quoteInfo',
        //     'provider_id' => $insurer->id,
        //     'status' => 'passed',
        // ])->latest()->first();

        // if (! $getQuoteCallFromInsurer) {
        //     $response['error'] = 'Get Quote call from insurer not found';
        //     $response['message'] = 'Get Quote call from insurer not found';

        //     return $response;
        // }

        // $getQuoteResponse = json_decode($getQuoteCallFromInsurer->response);
        // $this->updateBookingAndPolicyDetails($quote, $getQuoteResponse);

        // Pre-checks before executing postBookPolicyToSage using SendBookPolicyRequest validation
        $preCheckResult = $this->validateBookPolicyUsingRequest($quote);
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

        $createSageProcessResponse = (new SageApiService)->postBookPolicyToSage($request, $quote);
        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, [], $createSageProcessResponse, '', self::BOOK_POLICY, $createSageProcessResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

        if (! $createSageProcessResponse['status']) {
            $response['error'] = $createSageProcessResponse['message'];

            return $response;
        }
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Sage Process Created : '.$createSageProcessResponse['message']);

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Booking process in started! It will take some time to Complete. Come Back in a while to check the status!';

        return $response;
    }

    /**
     * Validate book policy prerequisites using SendBookPolicyRequest validation 
     * and additional document/status checks
     */
    private function validateBookPolicyUsingRequest($quote): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Validating book policy prerequisites');

        $response = ['status' => true, 'error' => null, 'message' => null];

        try {
            // Prepare data for SendBookPolicyRequest validation
            $requestData = [
                'quote_id' => $quote->id,
                'model_type' => self::TYPE,
                'send_policy_type' => SendPolicyTypeEnum::SAGE,
                'is_send_policy' => false,
                'transaction_payment_status' => null,
            ];

            // Create validator using SendBookPolicyRequest rules
            $sendBookPolicyRequest = new SendBookPolicyRequest();
            $validator = Validator::make($requestData, $sendBookPolicyRequest->rules());
            
            // Apply the custom validation logic from SendBookPolicyRequest
            $sendBookPolicyRequest->withValidator($validator);

            if ($validator->fails()) {
                $response['status'] = false;
                $response['error'] = 'SendBookPolicyRequest validation failed';
                $response['message'] = $validator->errors()->first();
                
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - SendBookPolicyRequest validation failed: '.$response['message']);
                return $response;
            }


            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - All prerequisites validated successfully');
            $response['message'] = 'All book policy prerequisites validated successfully';
            
        } catch (Exception $e) {
            $response['status'] = false;
            $response['error'] = 'Validation error: ' . $e->getMessage();
            $response['message'] = 'An error occurred during validation: ' . $e->getMessage();
            
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Validation exception: '.$e->getMessage());
        }

        return $response;
    }

    private function getAccessToken()
    {
        // if ($this->accessToken) {
        //     return $this->accessToken;
        // }

        $payload = [
            'client_id' => $this->clientId, 'client_secret' => $this->clientSecret,  'grant_type' => 'client_credentials',  'audience' => 'integration-platform',
        ];

        $header = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        $response = $this->httpCall($this->authUrl, $payload, $header, self::REQUEST_AUTH);

        if ($response['status']) {
            $data = $response['data'];

            // $accessToken = $data->access_token;
            // $expiresIn = (int) $data->expires_in;

            // Cache::store('redis')->put(self::POLICY_ISSUANCE_API_ACCESS_TOKEN_KEY, $accessToken, $expiresIn);
            // $this->accessToken = Cache::store('redis')->get(self::POLICY_ISSUANCE_API_ACCESS_TOKEN_KEY);
            $this->accessToken = $data->access_token;

            return $this->accessToken;
        }

        return $response;
    }

    private function httpCall($endPoint, $payload, $requestHeader = [], $method = self::REQUEST_GET)
    {
        $response = ['status' => false, 'error' => null, 'message' => null, 'data' => null, 'completed_step' => null,];

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
                )->timeout(30)->withHeaders($header)->post($endPoint, $payload),
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

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Endpoint : '.$endPoint.' Status : '.($response['status'] ? 'success' : 'failed').' , Message : '.$response['message'].' , Error : '.$response['error']);

            return $response;
        } catch (Exception $e) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Endpoint :  '.$endPoint.' , Error : '.$e->getMessage());

            $response['error'] = $e->getMessage();
            $response['message'] = $e->getMessage();

            return $response;
        }
    }

    private function updateBookingAndPolicyDetails($quote, $getQuoteResponse)
    {
        return true;
        // $quote->update([
        //     'policy_number' => $getQuoteResponse->policyNumber,
        //     'policy_start_date' => $getQuoteResponse->policyStartDate,
        //     'policy_end_date' => $getQuoteResponse->policyEndDate,
        //     'policy_status' => $getQuoteResponse->policyStatus,
        // ]);

        // $quote->payments()->mainLeadPayment()->update([
        //     'policy_number' => $getQuoteResponse->policyNumber,
        //     'policy_start_date' => $getQuoteResponse->policyStartDate,
        //     'policy_end_date' => $getQuoteResponse->policyEndDate,
        //     'policy_status' => $getQuoteResponse->policyStatus,
        // ]);
    }

    public function getQuoteDetailsFromInsurer($quoteTypeId, $quoteDetails)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quoteDetails->code.' started');
        
        return response()->json([
            'success' => true,
            'message' => 'Quote details retrieved successfully from insurer portal',
            'data' => [
                'rtaTransactionType' => 'RTT03',
                'plateCode' => 'A',
                'plateNumber' => '123456',
                'trafficCodeNumber' => 'TFC123456',
                'chassisNumber' => 'CHASSIS123456789',
                'engineNumber' => 'ENGINE123456',
                'rtaPlateCategory' => 'RPC01',
                'vehicleColor' => '38',
                'plateColor' => '38',
                'bankLoan' => false,
                'bankName' => 'FI0079',
                'firstRegistrationDate' => '2023-01-01',
                'policyEffectiveDate' => '2024-01-01',
                'policyExpiryDate' => '2024-12-31',
                'certificateStartDate' => '2024-01-01',
                'certificateEndDate' => '2024-12-31',
                'annualMileageEstimate' => '20000',
                'isInsuredAndDriverSame' => 1,
                'driverFirstName' => 'John',
                'driverLastName' => 'Doe',
                'driverDob' => '1990-01-01',
                'driverGender' => 'male',
                'driverLicenseNumber' => 'LIC123456789',
                'licenseIssuePlace' => 'sharjah', 
                'licenseIssueDate' => '2018-01-01',
                'licenseExpiryDate' => '2028-01-01',
                'uaeDrivingExperience' => '5',
                'homeCountryLicenseIssuance' => 'Bosnian',
                'homeCountryDrivingExperience' => '8'
            ]
        ]);
        
        /* Commented out for testing
        try {
            $payload = ['quoteTypeId' => $quoteTypeId, 'quoteUID' => $quoteDetails->uuid];
            $response = Ken::request('/get-quote-from-insurer', 'get', $payload);
            $responseData = $response['data'];

            $getQuoteResponseMapping = [
                'rtaTransactionType' => $responseData->authorityTransactionDetails->code,
                'plateCode' => $this->extractPlateCode($responseData->plateNumber), // optional
                'plateNumber' => $this->extractPlateNumber($responseData->plateNumber), // optional
                'trafficCodeNumber' => $responseData->motorInformation->trafficFileNumber,
                'chassisNumber' => $responseData->motorInformation->chassisNumber,
                'engineNumber' => $responseData->motorInformation->engineNumber,
                'rtaPlateCategory' => '',
                'vehicleColor' => $responseData->motorInformation->vehicleColor->code,
                'plateColor' => $responseData->motorInformation->plateColor->code,
                'bankLoan' => $responseData->motorInformation->isVehicleMortgaged,
                'bankName' => ($responseData->motorInformation->isVehicleMortgaged) ? $responseData->motorInformation->bankName : '', // optional
                'firstRegistrationDate' => $responseData->policySchedule->creationDate,
                'policyEffectiveDate' => $responseData->policySchedule->effectiveDate,
                'policyExpiryDate' => $responseData->policySchedule->expirationDate, // optional
                'certificateStartDate' => $responseData->certificateInceptionDate,
                'certificateEndDate' => $responseData->certificateEndDate, // optional
                'annualMileageEstimate' => '', // optional
                'isInsuredAndDriverSame' => $responseData->policyHolder->isPolicyHolderDriver,
                'driverFirstName' => ($responseData->policyHolder->isPolicyHolderDriver) ? $responseData->policyHolder->person->givenName : $responseData->driver->firstName, // optional
                'driverLastName' => ($responseData->policyHolder->isPolicyHolderDriver) ? $responseData->policyHolder->person->surName : $responseData->driver->lastName, // optional
                'driverDob' => ($responseData->policyHolder->isPolicyHolderDriver) ? $responseData->policyHolder->person->birthDate : $responseData->driver->dateOfBirth, // optional
                'driverGender' => ($responseData->policyHolder->isPolicyHolderDriver) ? $responseData->policyHolder->person->gender->value : $responseData->driver->gender,
                'driverLicenseNumber' => '',
                'licenseIssuePlace' => ($responseData->policyHolder->isPolicyHolderDriver) ? $responseData->policyHolder->person->firstDrivingLicenseIssueCountry->code : $responseData->driver->nationality->value, // optional
                'licenseIssueDate' => '', // optional
                'licenseExpiryDate' => '',
                'uaeDrivingExperience' => '', // optional
                'homeCountryLicenseIssuance' => '', // optional
                'homeCountryDrivingExperience' => '', // optional
            ];

            // return response()->json([
            //     'success' => true,
            //     'message' => 'Quote details retrieved successfully from insurer portal',
            //     'data' => $getQuoteResponseMapping ?? null
            // ]);

            if (isset($response['status']) && $response['status'] === true) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quote details retrieved successfully from insurer portal',
                    'data' => $getQuoteResponseMapping ?? null
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? 'Failed to retrieve quote details from insurer portal'
                ]);
            }
        } catch (\Exception $e) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving quote details from insurer portal'
            ]);
        }
        */ // End of commented out section for testing
    }

    public function getInsurerAPIStatuses()
    {
        return [
            self::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID => self::UPLOAD_POLICY_DOCUMENTS_API_FAILED,
            self::POLICY_ISSUANCE_API_FAILED_STATUS_ID => self::POLICY_ISSUANCE_API_FAILED,
            self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID => self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED,
            self::BOOK_POLICY_API_FAILED_STATUS_ID => self::BOOK_POLICY_API_FAILED,
        ];
    }

    public function getFailedIssuanceAPIStatuses()
    {
        return [
            self::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
            self::POLICY_ISSUANCE_API_FAILED_STATUS_ID,
            self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID,
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
