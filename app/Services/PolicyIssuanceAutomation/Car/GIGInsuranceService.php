<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Car;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Interfaces\PolicyIssuanceInterface;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\DocumentType;
use App\Models\PolicyIssuanceLog;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GIGInsuranceService implements PolicyIssuanceInterface
{
    private readonly string $className;
    private readonly string $baseUrl;
    
    public const INSURER_CODE = InsuranceProvidersEnum::AXA;
    public const TYPE = quoteTypeCode::Car;
    public const TYPE_ID = QuoteTypeId::Car;

    public const UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const ISSUE_POLICY = 'IssuePolicy';
    public const GET_POLICY_DOCUMENTS = 'GetPolicyDocument';
    public const BOOK_POLICY = 'BookPolicy';

    public const PAYMENT_MODE = 'CT068';
    public const PAYMENT_MODE_VALUE = 'Broker Credit';
    public const CURRENCY_CODE = 'AED';
    
    public $policyIssuance = null;
    public $currentInsurerApiStatus = null;

    public function __construct()
    {
        $this->className = __CLASS__;
        $this->baseUrl = config('constants.GIG_API_BASE_URL', '').'/apis/gulf-motor-v3-vs/motor';
        // $this->authParam = [
        //     'Authorization' => config('constants.GIG_AUTHORIZATION_TOKEN'),
        //     'Ocp-Apim-Subscription-Key' => config('constants.GIG_SUBSCRIPTION_KEY'),
        // ];
    }

    private function getAPISteps(): array
    {
        return [
            self::UPLOAD_DOCUMENTS,
            self::ISSUE_POLICY,
            self::GET_POLICY_DOCUMENTS,
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
        if ($nextStepToBeExecuted === PolicyIssuanceEnum::GIG_CAR_AUTO_CAPTURE) {
            $this->executeAutoCaptureStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === PolicyIssuanceEnum::GIG_CAR_UPLOAD_DOCUMENTS) {
            $this->executeUploadDocumentsStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === PolicyIssuanceEnum::GIG_CAR_ISSUE_POLICY) {
            $this->executeIssuePolicyStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === PolicyIssuanceEnum::GIG_CAR_GET_POLICY_DOCUMENTS) {
            $this->executeGetPolicyDocumentsStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === PolicyIssuanceEnum::GIG_CAR_BOOK_POLICY) {
            $this->executeBookPolicyStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }
    }

    public function getNextStep($completedStep = null): ?string
    {
        $allSteps = PolicyIssuanceEnum::getPolicyIssuanceSteps(self::INSURER_CODE, self::TYPE);

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
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.PolicyIssuanceEnum::GIG_CAR_UPLOAD_DOCUMENTS);
        $uploadDocumentsResponse = $this->UploadDocuments($quote);

        if (! $uploadDocumentsResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document upload failed', extra: ['response' => $uploadDocumentsResponse]);
            throw new Exception($uploadDocumentsResponse['error']);
        }
        $process->update(['completed_step' => $uploadDocumentsResponse['completed_step']]);
    }

    public function UploadDocuments($quote): array
    {
        $maxRetries = 5;
        $retryDelay = 10; // seconds
        $retryCount = 0;
        $failedPayloads = [];
        $successfulPayloads = [];
        $endPoint = '/v1/insurance-documents';

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');
        $response = ['status' => false, 'completed_step' => PolicyIssuanceEnum::GIG_CAR_UPLOAD_DOCUMENTS, 'error' => null, 'message' => null];

        $quoteDocuments = $quote->documents()->whereIn('document_type_code', [DocumentTypeCode::HPD, DocumentTypeCode::LPD, DocumentTypeCode::HOMPD])->get();

        // $policyIssuanceLog = PolicyIssuanceLog::where('model_id', $quote->id)->where([
        //     'step' => self::UPLOAD_DOCUMENTS,
        //     'status' => PolicyIssuanceEnum::FAILED_STATUS,
        // ])->get();

        // TODO:: those documents which are successfully uploaded should not be processed again and which are failed should be processed again and update same process log
        // $failedPayloads = $policyIssuanceLog->pluck('payload')->toArray();
        // $finalPayloads = array_filter($payloads, function ($payload) use ($failedPayloads) {
        //     foreach ($failedPayloads as $failedPayload) {
        //         $failedPayload = json_decode($failedPayload, true);
        //         if ($failedPayload['DocumentInfo']['DocumentType'] === $payload['DocumentInfo']['DocumentType'] && $failedPayload['DocumentInfo']['DocumentName'] === $payload['DocumentInfo']['DocumentName']) {
        //             return false;
        //         }
        //     }
        //     return true;
        // });

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Number of documents to process: '.count($quoteDocuments));

        foreach ($quoteDocuments as $index => $quoteDocument) {
            $payload = [
                'documentType' => [
                    'code' => $quoteDocument->document_type_code,
                    'value' => $quoteDocument->document_type_code,
                ],
                'referenceType' => 'quotation',
                'documentContent' => $quoteDocument->document_content,
                'documentName' => $quoteDocument->document_name,
                'mimeType' => $quoteDocument->document_mime_type,
                'referenceValue' => $quote->code,
            ];

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Processing document '.($index + 1).' of '.count($quoteDocuments), [ 
                'Document Name' => $quoteDocument->document_name,
                'Document Type' => $quoteDocument->document_type_code,
            ]);

            $retryCount = 0;
            do {
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Waiting for '.$retryDelay.' seconds for document generation');
                sleep($retryDelay);

                $documents = $this->gigHttpCall($endPoint, $payload);
                $documentsResponse = $documents->object();

                if ($this->hasGigError($documents)) {
                    $errorMessage = $this->extractGigErrorMessage($documents);

                    if (str_contains($errorMessage, 'Document is still generating')) {
                        $retryCount++;
                        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Documents still generating, retry '.$retryCount.' of '.$maxRetries);
                    } else {
                        break;
                    }
                } else {
                    break;
                }
            } while ($retryCount < $maxRetries);
            $isPayloadSuccessful = ! $this->hasGigError($documents);
            $this->storePolicyIssuanceLog(
                $quote, $payload, $documentsResponse, $this->baseUrl.$endPoint, $response['completed_step'], $this->hasGigError($documents) ? PolicyIssuanceEnum::FAILED_STATUS : PolicyIssuanceEnum::SUCCESS_STATUS
            );

            if ($isPayloadSuccessful) {

                $successfulPayloads[] = $index + 1;
            } else {
                $errorMessage = $this->extractGigErrorMessage($documents);
                $failedPayloads[] = ['payload_index' => $index + 1, 'error' => $errorMessage];

                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document upload failed, stopping further processing');
                break;
            }
        }
        
        if (! empty($failedPayloads)) {
            $errorMessages = array_map(function ($failedPayload) {
                return 'Document '.$failedPayload['payload_index'].': '.$failedPayload['error'];
            }, $failedPayloads);

            $response['error'] = 'Document upload failed. '.implode('; ', $errorMessages);
            $response['message'] = 'Failed documents: '.implode(', ', array_column($failedPayloads, 'payload_index')).'. Successful documents: '.implode(', ', $successfulPayloads);

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Some documents failed to upload: '.$response['error']);
        } else {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - All documents uploaded successfully');

            $response['status'] = true;
            $response['message'] = 'All documents uploaded successfully';
        }

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        return $response;
    }

    private function executeIssuePolicyStep($quote, $process): void
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.PolicyIssuanceEnum::GIG_CAR_ISSUE_POLICY);

        // $this->currentInsurerApiStatus = PolicyIssuanceEnum::POLICY_DETAIL_API_FAILED_STATUS_ID;
        $policyIssuanceResponse = $this->issuePolicyAndFillPolicyDetails($quote);

        if (! $policyIssuanceResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy issuance failed', extra: ['response' => $policyIssuanceResponse]);
            throw new Exception($policyIssuanceResponse['error']);
        }

        $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
    }

    public function issuePolicyAndFillPolicyDetails($quote): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $response = ['status' => false, 'completed_step' => PolicyIssuanceEnum::GIG_CAR_ISSUE_POLICY, 'error' => null, 'message' => null];
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
                    'amount' => '',
                    'currencyCode' => self::CURRENCY_CODE
                ],
                'paymentReferenceNumber' => '', // TODO:: Need to be confirm
                'paymentStatus' => '' // TODO:: Need to be confirm
            ]
        ];

        $issuePolicy = $this->gigHttpCall($endPoint, $payload);
        $issuePolicyResponse = $issuePolicy->object();
        $this->storePolicyIssuanceLog($quote, $payload, $issuePolicyResponse, $this->baseUrl.$endPoint, $response['completed_step'], $this->hasGigError($issuePolicy) ? PolicyIssuanceEnum::FAILED_STATUS : PolicyIssuanceEnum::SUCCESS_STATUS);

        if ($this->hasGigError($issuePolicy)) {
            $errorMessage = $this->extractGigErrorMessage($issuePolicy);
            $response['error'] = $errorMessage ?? 'Policy issuance failed';

            return $response;
        }

        $issuePolicyResult = $issuePolicyResponse?->PolicyInfo;

        $insurerInvoiceDate = $issuePolicyResult?->CommissionAmount; // TODO:: need to ask with Shereen
        $insurerTaxInvoiceNo = $issuePolicyResult?->CommissionAmount; // TODO:: need to ask with Shereen
        $insurerCommissionTaxInvoiceNo = $issuePolicyResult?->CommissionAmount; // TODO:: need to ask with Shereen
        $commissionVatApplicable = $issuePolicyResult?->CommissionAmount; // TODO:: need to ask with Shereen

        // TODO:: this need to be verified with database columns
        $quote->update([
            'insurer_invoice_date' => $insurerInvoiceDate,
            'insurer_tax_invoice_no' => $insurerTaxInvoiceNo,
            'insurer_commission_tax_invoice_no' => $insurerCommissionTaxInvoiceNo,
            'commission_vat_applicable' => $commissionVatApplicable,
        ]);

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy issued successfully and Quote updated');

        $response['status'] = true;
        $response['message'] = 'Policy issued successfully and Quote updated';

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        return $response;
    }

    private function executeGetPolicyDocumentsStep($quote, $process): void
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.PolicyIssuanceEnum::GIG_CAR_GET_POLICY_DOCUMENTS);

        $policyIssuanceResponse = $this->getPolicyDocuments($quote);

        if (! $policyIssuanceResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy issuance failed', extra: ['response' => $policyIssuanceResponse]);
            throw new Exception($policyIssuanceResponse['error']);
        }

        $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
    }

    public function getPolicyDocuments($quote): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $response = ['status' => false, 'completed_step' => PolicyIssuanceEnum::GIG_CAR_GET_POLICY_DOCUMENTS, 'error' => null, 'message' => null];
        $endPoint = '/v1/insurance-documents/document';

        $getPolicyDocuments = $this->gigHttpCall($endPoint, []);
        $getPolicyDocumentsResponse = $getPolicyDocuments->object();

        // TODO:: Need to upload these documents to the quote

        if ($this->hasGigError($getPolicyDocuments)) {
            $errorMessage = $this->extractGigErrorMessage($getPolicyDocuments);
            $response['error'] = $errorMessage ?? 'Policy documents failed';

            return $response;
        }

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy documents fetched successfully');

        $response['status'] = true;
        $response['message'] = 'Policy documents fetched successfully';

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

        return $response;
    }

    private function executeBookPolicyStep($quote, $process): void
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.PolicyIssuanceEnum::GIG_CAR_BOOK_POLICY);
        
    }

    public function bookPolicy($quote): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');
        
        
    }

    // private function executeGenerateDocumentsStep($quote, $process, $getQuoteDetails): void
    // {
    //     LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::GENERATE_POLICY_DOCUMENTS);

    //     // $this->currentInsurerApiStatus = PolicyIssuanceEnum::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID;
    //     $generateDocumentsResponse = $this->generatePolicyDocuments($quote, $process, $getQuoteDetails);

    //     if (! $generateDocumentsResponse['status']) {
    //         LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document generation failed', extra: ['response' => $generateDocumentsResponse]);
    //         throw new Exception($generateDocumentsResponse['error']);
    //     }

    //     $process->update(['completed_step' => $generateDocumentsResponse['completed_step']]);
    // }

    // public function generatePolicyDocuments($quote, $process, $getQuoteDetails): array
    // {
    //     LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

    //     $response = ['status' => false, 'completed_step' => self::GENERATE_POLICY_DOCUMENTS, 'error' => null, 'message' => null];

    //     $endPoint = '/GeneratePolicyDocument';
    //     $payload = app(ADNICPayloadsMappings::class)->createGeneratePolicyDocumentPayload($this, $process, $getQuoteDetails);
    //     $generateDocuments = $this->adnicHttpCall($endPoint, $payload);

    //     $generateDocumentsResponse = $generateDocuments->object();
    //     $this->storePolicyIssuanceLog($quote, $payload, $generateDocumentsResponse, $this->baseUrl.$endPoint, $response['completed_step'], $generateDocuments->failed() ? PolicyIssuanceEnum::FAILED_STATUS : PolicyIssuanceEnum::SUCCESS_STATUS);

    //     if ($generateDocuments->failed()) {
    //         $response['error'] = $generateDocumentsResponse?->ErrorInfo ?? 'Document generation failed';

    //         return $response;
    //     }

    //     LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Documents generated successfully');

    //     $response['status'] = true;
    //     $response['message'] = 'Documents generated successfully';

    //     // TODO:: need to check with Shereen, if this is correct or not
    //     $quote->update(['quote_status_id' => QuoteStatusEnum::PolicyIssued, 'policy_issuance_status_id' => PolicyIssuanceStatusEnum::PolicyIssued, 'quote_status_date' => now()]);

    //     LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

    //     return $response;
    // }

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

    private function storePolicyIssuanceLog($quote, $payload, $response, $endPoint, $step, $status = 'success'): void
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

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Policy Issuance ID : '.$this->policyIssuance?->id.' Log ID : '.$log->id);
    }

    private function gigHttpCall($endPoint, $payload)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            // 'Ocp-Apim-Subscription-Key' => $this->authParam['Ocp-Apim-Subscription-Key'],
            // 'Authorization' => 'Bearer '.$this->authParam['Authorization'],
        ];

        $url = $this->baseUrl.$endPoint;
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' URL : '.$url);

        return Http::timeout(30)
            ->withHeaders($headers)
            ->post($url, $payload);
    }

    private function hasGigError($response): bool
    {
        if ($response->failed()) {
            return true;
        }

        $responseObject = $response->object();

        if (isset($responseObject->DocumentInfo->ErrorInfo)) {
            if (is_array($responseObject->DocumentInfo->ErrorInfo) && ! empty($responseObject->DocumentInfo->ErrorInfo)) {
                return true;
            }
            if (is_string($responseObject->DocumentInfo->ErrorInfo) && ! empty($responseObject->DocumentInfo->ErrorInfo)) {
                return true;
            }
        }

        return false;
    }

    private function extractGigErrorMessage($response): string
    {
        if ($response->failed()) {
            return $response->body() ?? 'HTTP request failed';
        }

        $responseObject = $response->object();

        if (isset($responseObject->DocumentInfo->ErrorInfo)) {
            if (is_array($responseObject->DocumentInfo->ErrorInfo)) {
                $errorMessages = [];
                foreach ($responseObject->DocumentInfo->ErrorInfo as $error) {
                    if (isset($error->ErrorCode) && isset($error->ErrorMsg)) {
                        $errorMessages[] = "Error {$error->ErrorCode}: {$error->ErrorMsg}";
                    } elseif (is_string($error)) {
                        $errorMessages[] = $error;
                    }
                }

                return implode('; ', $errorMessages);
            }

            if (is_string($responseObject->DocumentInfo->ErrorInfo)) {
                return $responseObject->DocumentInfo->ErrorInfo;
            }
        }

        return 'Unknown error occurred';
    }
}
