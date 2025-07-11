<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Car;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\SendPolicyTypeEnum;
use App\Interfaces\PolicyIssuanceInterface;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\DocumentType;
use App\Models\PolicyIssuanceLog;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\SageApiService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GIGInsuranceService implements PolicyIssuanceInterface
{
    private readonly string $className;
    private readonly string $baseUrl;
    private readonly string $authUrl;
    private readonly string $clientId;
    private readonly string $clientSecret;
    private readonly string $accessToken;
    
    public const INSURER_CODE = InsuranceProvidersEnum::AXA;
    public const POLICY_ISSUANCE_API_ACCESS_TOKEN_KEY = InsuranceProvidersEnum::AXA.'_POLICY_ISSUANCE_API_ACCESS_TOKEN';
    public const TYPE = quoteTypeCode::Car;
    public const TYPE_ID = QuoteTypeId::Car;

    public const UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const ISSUE_POLICY = 'IssuePolicy';
    public const GET_AND_UPLOAD_POLICY_DOCUMENTS = 'GetAndUploadPolicyDocuments';
    public const BOOK_POLICY = 'BookPolicy';

    public const PAYMENT_MODE = 'CT068';
    public const PAYMENT_MODE_VALUE = 'Broker Credit';
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

    public function __construct()
    {
        $this->className = __CLASS__;
        $this->baseUrl = config('constants.GIG_API_BASE_URL').'/apis/gulf-motor-v3-vs/motor';
        $this->authUrl = config('constants.GIG_API_AUTH_BASE_URL').'/oauth/token';
        $this->clientId = config('constants.GIG_API_AUTH_CLIENT_ID');
        $this->clientSecret = config('constants.GIG_API_AUTH_CLIENT_SECRET');
        $this->accessToken = Cache::store('redis')->get(self::POLICY_ISSUANCE_API_ACCESS_TOKEN_KEY) ?? $this->getAccessToken();
    }

    private function getAPISteps(): array
    {
        return [
            self::UPLOAD_DOCUMENTS,
            self::ISSUE_POLICY,
            self::GET_AND_UPLOAD_POLICY_DOCUMENTS,
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
            $response['message'] = $executeStepSequence['message'];

        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Exception : '.$e->getMessage());

            return $response;
        }

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - PID : '.$process->id.' ended');

        return $response;
    }

    private function executeStepSequence($quote, $process, $nextStepToBeExecuted): void
    {
        if ($nextStepToBeExecuted === self::UPLOAD_DOCUMENTS) {
            $this->executeUploadDocumentsStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === self::ISSUE_POLICY) {
            $this->executeIssuePolicyStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === self::GET_AND_UPLOAD_POLICY_DOCUMENTS) {
            $this->executeGetAndUploadPolicyDocumentsStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === self::BOOK_POLICY) {
            $this->executeBookPolicyStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }
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

    private function executeUploadDocumentsStep($quote, $process): void 
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::UPLOAD_DOCUMENTS);
        $uploadDocumentsResponse = $this->UploadDocuments($quote);

        if (! $uploadDocumentsResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document upload failed', extra: ['response' => $uploadDocumentsResponse]);
            // TODO:: this need to be handled properly and update the lead status to failed
            // throw new Exception($uploadDocumentsResponse['error']);
        }
        
        $process->update(['completed_step' => $uploadDocumentsResponse['completed_step']]);
        $process = $process->refresh();
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - Process ID : '.$process->id.' - Completed Step Updated to : '.$uploadDocumentsResponse['completed_step']);
    }

    public function UploadDocuments($quote): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started - Policy Issuance ID : '.$this->policyIssuance->id.' - Step : '.self::UPLOAD_DOCUMENTS);
        $response = ['status' => false, 'completed_step' => self::UPLOAD_DOCUMENTS, 'error' => null, 'message' => null];

        // Validation checks for document upload
        $documentUploadValidationCheck = app(PolicyIssuanceService::class)->documentUploadPreChecks(self::TYPE_ID, $quote, self::INSURER_CODE, [
            QuoteDocumentsEnum::CAR_REGISTRATION_CARD,
            QuoteDocumentsEnum::CAR_EMIRATE_ID_CARD,
            QuoteDocumentsEnum::CAR_DRIVING_LICENSE,
        ]);

        if (! $documentUploadValidationCheck['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document upload validation check failed', extra: ['response' => $documentUploadValidationCheck]);
            // TODO:: this need to be handled properly and update the lead status to failed
            // throw new Exception($documentUploadValidationCheck['message']);
        }

        $documentsToUpload = $this->documentsToUpload();
        $quoteDocumentCodes = $this->documentsToUpload()->keys()->toArray();
        $quoteDocuments = $quote->documents()->whereIn('document_type_code', $quoteDocumentCodes)->get();

        $endPoint = '/v1/insurance-documents';

        foreach ($documentsToUpload as $docTypeCode => $documentToUpload) {
            $quoteDocument = $quoteDocuments->where('document_type_code', $docTypeCode)->first();
            $documentFile = Storage::disk('azureIM')->get($quoteDocument->doc_url);
            $docFileBase64 = base64_encode($documentFile);
            $payload = [
                'referenceType' => 'quotation',
                'referenceValue' => $quote->quotation_number,

                'documentType' => [
                    'code' => $documentToUpload['insurer_doc_code'],
                    'value' => $documentToUpload['insurer_doc_name'],
                ],
                'documentContent' => $docFileBase64,
                'documentName' => $quoteDocument->document_name, // doc_name
                'mimeType' => $quoteDocument->document_mime_type, // doc_mime_type
            ];

            $uploadDocResponse = $this->httpCall($endPoint, $payload, [], self::REQUEST_POST);

            if ($uploadDocResponse['status']) {
                info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - '.$documentToUpload['insurerDocName'].' uploaded to insurer portal');
                $documentToUpload['uploaded'] = true;
                $documentsToUpload->put($docTypeCode, $documentToUpload);
            } else {
                info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Message : '.$uploadDocResponse['message'].' , Error : '.$uploadDocResponse['error']);
            }
        }

        $isAllDocumentsUploaded = $documentsToUpload->where('uploaded', false)->count() === 0;
        if (! $isAllDocumentsUploaded) {
            $failedToUploadDocNames = $documentsToUpload->where('uploaded', false)->pluck('insurerDocName')->toArray();
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - failed to upload documents to insurer : ', $failedToUploadDocNames);
            $response['message'] = 'Failed to upload '.implode(',', $failedToUploadDocNames).' documents to insurer';

            return $response;
        }
        $uploadedDocNames = $documentsToUpload->where('uploaded', true)->pluck('insurerDocName')->toArray();
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.'  ended', ['Document Uploaded' => $uploadedDocNames]);

        $response['status'] = true;
        $response['message'] = implode(',', $uploadedDocNames).' documents uploaded to insurer';

        return $response;
    }

    private function documentsToUpload()
    {
        return collect([
            QuoteDocumentsEnum::CAR_REGISTRATION_CARD => [
                'code' => QuoteDocumentsEnum::CAR_REGISTRATION_CARD,
                'insurerDocCode' => 'DT01',
                'insurerDocName' => 'Car Registration Document',
                'uploaded' => false,
            ],
            QuoteDocumentsEnum::CAR_EMIRATE_ID_CARD => [
                'code' => QuoteDocumentsEnum::CAR_EMIRATE_ID_CARD,
                'insurerDocCode' => 'DT02',
                'insurerDocName' => 'National Id',
                'uploaded' => false,
            ],
            QuoteDocumentsEnum::CAR_DRIVING_LICENSE => [
                'code' => QuoteDocumentsEnum::CAR_DRIVING_LICENSE,
                'insurerDocCode' => 'DT03',
                'insurerDocName' => 'Driving License',
                'uploaded' => false,
            ],
        ]);
    }

    private function executeIssuePolicyStep($quote, $process): void
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::ISSUE_POLICY);
        $policyIssuanceResponse = $this->issuePolicyAndFillPolicyDetails($quote, $process);

        if (! $policyIssuanceResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy issuance failed', extra: ['response' => $policyIssuanceResponse]);
            // TODO:: this need to be handled properly and update the lead status to failed
            // throw new Exception($policyIssuanceResponse['error']);
        }
        
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - Process ID : '.$process->id.' - Completed Step Updated to : '.$policyIssuanceResponse['completed_step']);
    }

    public function issuePolicyAndFillPolicyDetails($quote, $process): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started - Policy Issuance ID : '.$this->policyIssuance->id.' - Step : '.self::ISSUE_POLICY);
        $response = ['status' => false, 'completed_step' => self::ISSUE_POLICY, 'error' => null, 'message' => null];
        $endPoint = '/v3/policies';
        $payload = [
            'quoteId' => $quote->quotation_number,
            'isActive' => 'true',
            'payment' => [
                'paymentMode' => [
                    'code' => self::PAYMENT_MODE,
                    'value' => self::PAYMENT_MODE_VALUE,
                ],
                'paymentAmount' => [
                    'amount' => $quote->total_amount, // TODO:: this should be take from payment table
                    'currencyCode' => self::CURRENCY_CODE
                ],
                'paymentReferenceNumber' => '', // TODO:: Need to be confirm
                'paymentStatus' => '' // TODO:: Need to be confirm
            ]
        ];

        $issuePolicy = $this->httpCall($endPoint, $payload, [], self::REQUEST_POST);
        $this->storePolicyIssuanceLog($quote, $payload, $issuePolicy, $this->baseUrl.$endPoint, $response['completed_step'], $issuePolicy['status'] ? PolicyIssuanceEnum::FAILED_STATUS : PolicyIssuanceEnum::SUCCESS_STATUS);

        if (! $issuePolicy['status']) {
            return $issuePolicy;
        }

        $process->data = [ $response['completed_step'] => $issuePolicy];
        $process->completed_step = $response['completed_step'];
        $process->save();
        $process = $process->refresh();

        $issuePolicyResult = $issuePolicy?->PolicyInfo;
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Updating Policy Details in Quote');

        $quote->update([
            'insurer_invoice_date' => $issuePolicyResult?->CommissionAmount,
            'insurer_tax_invoice_no' => $issuePolicyResult?->CommissionAmount,
            'insurer_commission_tax_invoice_no' => $issuePolicyResult?->CommissionAmount,
            'commission_vat_applicable' => $issuePolicyResult?->CommissionAmount,
            'policy_number' => $issuePolicyResult?->PolicyNumber,
        ]);

        return $issuePolicy;
    }

    private function executeGetAndUploadPolicyDocumentsStep($quote, $process): void
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::GET_AND_UPLOAD_POLICY_DOCUMENTS);

        $policyIssuanceResponse = $this->getAndUploadPolicyDocuments($quote, $process);

        if (! $policyIssuanceResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy issuance failed', extra: ['response' => $policyIssuanceResponse]);
            // TODO:: this need to be handled properly and update the lead status to failed
            // throw new Exception($policyIssuanceResponse['error']);
        }

        $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
    }

    public function getAndUploadPolicyDocuments($quote, $process): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started - Policy Issuance ID : '.$this->policyIssuance->id.' - Step : '.self::GET_AND_UPLOAD_POLICY_DOCUMENTS);

        $response = ['status' => false, 'completed_step' => self::GET_AND_UPLOAD_POLICY_DOCUMENTS, 'error' => null, 'message' => null];
        $endPoint = '/v1/insurance-documents/document';

        $getPolicyIssuanceResponse = $process->data ? json_decode($process->data)?->self::ISSUE_POLICY : null;
        if (! $getPolicyIssuanceResponse) {
            $response['error'] = 'Policy issuance response not found in process data';
            return $response;
        }

        $uploadedDocumentsToIMCRM = collect();
        $policyDocuments = $getPolicyIssuanceResponse?->data->documents; 
        foreach ($policyDocuments as $policyDocument) {
            $quoteDocument = null;
            $docName = $policyDocument->name;
            $docMapping = $this->getPolicyIssuanceQuoteDocumentMapping($docName, $quote);
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document Name : '.$docName);
            $header = [
                'opCo' => self::OP_CO,
                'documentType' => $policyDocument->docId,
                'referenceType' => 'policy',
                'referenceValue' => $quote->policy_number,
                'Accept' => 'application/json',
            ];

            $document = $this->httpCall($endPoint, [], $header, self::REQUEST_GET);
            if ($docMapping) {
                if ($document['status']) {
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Start Upload To Quote Docs : '.$docName);
                    $quoteDocument = $this->uploadAndAttachToQuoteDocuments($quote, $document['data']?->document, $docMapping['code'], $docName);
                } else {
                    info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Insurer Document not found for : '.$docName, ['status' => $document['status'], 'error' => $document['error']]);
                }

                $uploadedDocumentsToIMCRM->push([
                    'name' => $docName,
                    'uploaded' => $quoteDocument?->id ? true : false,
                    'message' => $document['message'],
                ]);
            }
        }

        $allDocumentsUploaded = $uploadedDocumentsToIMCRM->where('uploaded', false)->count() === 0;
        if (! $allDocumentsUploaded) {
            $docsUploadToIMCRMFailed = $uploadedDocumentsToIMCRM->where('uploaded', false)->pluck('name')->toArray();
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - failed to fetch all documents from insurer : ', $docsUploadToIMCRMFailed);
            
            $process->update(['completed_step' => self::GET_AND_UPLOAD_POLICY_DOCUMENTS]);
            $process = $process->refresh();

            $error = 'Policy Issuance is pending as '.implode(',', $docsUploadToIMCRMFailed).' documents are not uploaded';
            $response['error'] = $error;
            $response['message'] = $error;

            return $response;
        } else {
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - fetched all documents from insurer and Uploaded to IMCRM ');
            // TODO:: Don't understand why this is needed
            $process->update(['completed_step' => PolicyIssuanceEnum::GIG_CAR_UPLOAD_POLICY_CERTIFICATE_OF_INSURANCE]);
            $process = $process->refresh();

            $response['status'] = true;
            $response['message'] = 'Fetched all documents from insurer and Uploaded to IMCRM';
        }

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Process completed step updated to : '.$process->completed_step);

        return $response;
    }

    private function getPolicyIssuanceQuoteDocumentMapping($docName, $quote): ?array
    {

        $documentCodeMapping = [
            self::POLICY_DOC_TAX_INVOICE => QuoteDocumentsEnum::CAR_TAX_INVOICE,
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

    private function uploadAndAttachToQuoteDocuments($quote, $documentContent, $documentCode, $originalName = null): void
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $documentType = DocumentType::where(['quote_type_id' => self::TYPE_ID, 'code' => $documentCode, 'is_active' => true])->first();

        if (! $documentType) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document type not found for code: '.$documentCode);

            return;
        }

        // Decode base64 content if needed
        $fileContents = base64_decode($documentContent);
        if ($fileContents === false) {
            $fileContents = $documentContent; // Not base64 encoded
        }

        $docName = $originalName ?? ('health_document_'.uniqid().'.pdf');
        $mimeType = 'application/pdf';

        // Upload file to Azure
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
    }

    private function executeBookPolicyStep($quote, $process): void
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::BOOK_POLICY);
        $policyIssuanceResponse = $this->bookPolicy($quote);

        if (! $policyIssuanceResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy issuance failed', extra: ['response' => $policyIssuanceResponse]);
            // TODO:: this need to be handled properly and update the lead status to failed
            // throw new Exception($policyIssuanceResponse['error']);
        }

        $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
    }

    public function bookPolicy($quote): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $response = ['status' => false, 'completed_step' => self::BOOK_POLICY, 'error' => null, 'message' => null];

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
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Sage Process Created : '.$createSageProcessResponse['message']);

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        $response['status'] = true;
        $response['message'] = 'Booking process in started! It will take some time to Complete. Come Back in a while to check the status!';

        return $response;
    }

    private function storePolicyIssuanceLog($quote, $payload, $response, $endPoint, $step, $status = 'success'): void
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Policy Issuance ID : '.$this->policyIssuance->id.' started');
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

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Policy Issuance ID : '.$this->policyIssuance?->id.' Log ID : '.$log->id);
    }

    private function getAccessToken()
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

        return $response;
    }

    private function httpCall($endPoint, $payload, $requestHeader = [], $method = self::REQUEST_GET)
    {
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Endpoint : '.$endPoint.' , Method : '.$method);
        $response = [
            'status' => false, 'error' => null, 'message' => null, 'data' => null, 'completed_step' => null,
        ];
        $commentHeader = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'opCo' => 'GULF',
            'sourceApplication' => 'OLS',
        ];
        $header = array_merge($commentHeader, $requestHeader);

        try {
            $httpResponse = match ($method) {
                self::REQUEST_PATCH => Http::retry(
                    $this->maxRetries, 
                    $this->retryDelay
                )->timeout(30)->withHeaders($header)->patch($endPoint, $payload),
                self::REQUEST_POST => Http::retry(
                    $this->maxRetries, 
                    $this->retryDelay
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
                $response['error'] = $httpResponse->object()->error;
                $response['message'] = $httpResponse->object()->error_description;
            }

            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Status : '.$response['status'].' , Message : '.$response['message'].' , Error : '.$response['error']);

            return $response;
        } catch (Exception $e) {
            logger()->error('automation:'.$this->className.' fn:'.__FUNCTION__.' - Endpoint :  '.$endPoint.' , Error : '.$e->getMessage());

            $response['error'] = $e->getMessage();
            $response['message'] = $e->getMessage();

            return $response;
        }
    }
}
