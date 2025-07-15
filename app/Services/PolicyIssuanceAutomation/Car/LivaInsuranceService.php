<?php

namespace App\Services\PolicyIssuanceAutomation\Car;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Interfaces\PolicyIssuanceInterface;
use App\Models\Payment;
use App\Models\PolicyIssuanceLog;
use App\Repositories\PersonalQuoteRepository;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class LivaInsuranceService implements PolicyIssuanceInterface
{
    private $className = 'livaInsuranceService';
    private readonly string $baseUrl;

    public $policyIssuance = null;

    public const UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const ISSUE_POLICY = 'IssuePolicy';
    public const GET_AND_UPLOAD_POLICY_DOCUMENTS = 'GetAndUploadPolicyDocuments';
    public const BOOK_POLICY = 'BookPolicy';

    const POLICY_AUTOMATION_STATUS_YES_ID = 1;
    const POLICY_AUTOMATION_STATUS_NO_ID = 2;
    
    const UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID = 1;
    const UPLOAD_POLICY_DOCUMENTS_API_FAILED = 'Document Upload API Failed';
    const UPLOAD_POLICY_DOCUMENTS_API_ACTION_MESSAGE = 'Document Upload via API';

    const POLICY_ISSUANCE_API_FAILED_STATUS_ID = 2;
    const POLICY_ISSUANCE_API_FAILED = 'Policy Issuance API Failed';
    const POLICY_ISSUANCE_API_ACTION_MESSAGE = 'Policy Issuance via API';

    const UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID = 3;
    const UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED = 'Upload Policy Documents to IMCRM API Failed';
    const UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_ACTION_MESSAGE = 'Upload Policy Documents to IMCRM via API';

    const BOOK_POLICY_API_FAILED_STATUS_ID = 4;
    const BOOK_POLICY_API_FAILED = 'Book Policy API Failed';
    const BOOK_POLICY_API_ACTION_MESSAGE = 'Book Policy via API';

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
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_LIVA_CAR_POLICY_ISSUANCE);
    }

    public function isPolicyIssuanceAutomationRetryEnabledForTimeout(): bool
    {
        return (bool) app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::ENABLE_RETRY_TIMEOUT_LIVA_CAR_POLICY_ISSUANCE);
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
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - LIVA Car Automation is disabled');
                $response['error'] = 'LIVA Car Automation is disabled';
                $response['message'] = 'LIVA Car Automation is disabled';

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

        /* if ($nextStepToBeExecuted === self::ISSUE_POLICY) {
            $this->executeIssuePolicyStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        } */

        /* if ($nextStepToBeExecuted === self::GET_AND_UPLOAD_POLICY_DOCUMENTS) {
            $this->executeGetAndUploadPolicyDocumentsStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        }

        if ($nextStepToBeExecuted === self::BOOK_POLICY) {
            $this->executeBookPolicyStep($quote, $process);
            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        } */
    }

    private function executeIssuePolicyStep($quote, $process)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::ISSUE_POLICY);
        $policyIssuanceResponse = $this->issuePolicy($quote, $process);

        if (! $policyIssuanceResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy issuance failed', extra: ['response' => $policyIssuanceResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::POLICY_ISSUANCE_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID);
        
            return $policyIssuanceResponse;
        }

        $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
        $process = $process->refresh();

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - Process ID : '.$process->id.' - Completed Step Updated to : '.$policyIssuanceResponse['completed_step']);
    }

    public function issuePolicy($quote): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started - Policy Issuance ID : '.$this->policyIssuance->id.' - Step : '.self::ISSUE_POLICY);
        $response = ['status' => false, 'completed_step' => self::ISSUE_POLICY, 'error' => null, 'message' => null];

        $endPoint = 'policy/create/v2';
        
        $payload = [
            'PolicyRequest' => [
                'QuotationNo' => /* $quote?->carQuotePlanDetail?->insurer_quote_no ??  */7872837,
                'PremiumPayable' => 840,
                'PartnerTrnReferenceNumber' => /* $quote->code ??  */'123456',
                'Documents' => [
                    'DocsInResponse' => true,
                    'DocsDetails' => [
                        'PolicySchedule' => true,
                        'HirePurchaseLetter' => false,
                        'ProposalForm' => false,
                        'LetterToBank' => false,
                        'MotorArabicCertificate' => true,
                        'Receipt' => true,
                    ],
                ],
                'PolicyConfirmationSMS' => false,
                'PolicyConfirmationEmail' => false,
            ],
        ];

        $issuePolicy = $this->httpCall($endPoint, $payload, 'PolicyResponse');
        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $issuePolicy, $this->baseUrl.$endPoint, self::ISSUE_POLICY, $issuePolicy['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

        if (! $issuePolicy['status']) {
            $response['error'] = $issuePolicy['error'];
            $response['message'] = $issuePolicy['message'];
            $response['status'] = false;

            return $response;
        }

        $issuePolicyResult = $issuePolicy['data']?->PolicyResponse;
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.', updating quote and payment information from Liva createPolicyRequest response');
        
        $quote->update([
            'policy_number' => $issuePolicyResult?->PolicyNumber,
            'policy_issuance_date' => $issuePolicyResult?->PolicyCreationDate,
            'policy_start_date' => $issuePolicyResult?->PolicyEffectiveDate,
            'policy_expiry_date' => $issuePolicyResult?->PolicyExpiryDate,
            'price_vat_applicable' => $issuePolicyResult?->PremiumWithoutVAT,
            'vat' => $issuePolicyResult?->VatAmount,
            'vat' => $issuePolicyResult?->Commission,
        ]);

        Payment::where('code', $quote->code)->update([
            'commission_vat_applicable' => $issuePolicyResult?->Commission,
            'commission' => $issuePolicyResult?->Commissionincldvat,
        ]);

        $response['status'] = true;
        $response['message'] = 'Policy issued successfully';
        $response['completed_step'] = self::ISSUE_POLICY;
        $response['data'] = $issuePolicy['data']; // verify this

        return $response;
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
        $uploadDocumentsResponse = $this->uploadDocuments($quote);

        if (! $uploadDocumentsResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document upload failed', extra: ['response' => $uploadDocumentsResponse]);
            
            $this->currentInsurerApiStatus = self::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID;
            // TODO:: this function need to be updated
            // app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, $this->currentInsurerApiStatus, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);
        
            return $uploadDocumentsResponse;
        }

        $process->update(['completed_step' => $uploadDocumentsResponse['completed_step']]);
        $process = $process->refresh();
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - Policy Issuance ID : '.$process->id.' - Completed Step Updated to : '.$uploadDocumentsResponse['completed_step']);
    }

    public function uploadDocuments($quote)
    {
        $endPoint = 'documents/upload/v2';
        $response = ['status' => false, 'completed_step' => self::UPLOAD_DOCUMENTS, 'error' => null, 'message' => null];
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' started', extra: [
            'endPoint' => $endPoint,
        ]);

        $documents = $quote->documents;
        $requiredDocuments = array_filter($documents->toArray(), function ($document) {
            return in_array($document['document_type_code'], [DocumentTypeCode::DRIVING_LICENSE, DocumentTypeCode::EMIRATES_ID, DocumentTypeCode::REGISTRATION_CARD_MULKIYA]);
        });

        if (empty($requiredDocuments)) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Document upload validation check failed');
            
            $response['message'] = 
            $response['error'] = 'Required Documents not uploaded';
            $response['status'] = false;

            return $response;
        }

        $attachments = [];

        foreach ($requiredDocuments as $document) {
            try {
                // Get the file path (assuming documents are stored in storage)
                $filePath = config('constants.AZURE_IM_STORAGE_URL').config('constants.AZURE_IM_STORAGE_CONTAINER').'/'.$document['doc_url']; // Adjust path as needed

                // Read file content and convert to base64
                $fileContent = file_get_contents($filePath);
                $base64Content = base64_encode($fileContent);

                // Get file extension
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);

                // Map document type based on your business logic
                $documentType = $this->getDocumentType($document['document_type_code'] ?? 'other');

                $attachments[] = [
                    'DocumentType' => $documentType,
                    'Content' => $base64Content,
                    'Extension' => $extension,
                ];

                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Document processed: '.$document['document_type_text']);
            } catch (\Exception $ex) {
                LoggerService::error('automation:'.$this->className.' fn:'.__FUNCTION__.' Error processing document', exception: $ex);

                continue;
            }
        }

        if (empty($attachments)) {
            return ['status' => false, 'message' => 'No valid documents could be processed'];
        }

        $payload['UploadDocumentsRequest'] = [
            'TransactionType' => '5',
            'TransactionNumber' => $quote?->carQuotePlanDetail?->insurer_quote_no ?? 7872837, // TODO: Use actual quote number
            'Attachments' => $attachments,
        ];

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Payload created with '.count($attachments).' attachments');

        $response = $this->httpCall($endPoint, $payload, 'UploadDocumentsResponse');
        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $response, $this->baseUrl.$endPoint, self::UPLOAD_DOCUMENTS, $response['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

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

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Document upload status', extra: [
                'DocumentType' => $value->DocumentType,
                'UploadStatus' => $uploadStatus,
            ]);
        }

        if ($allUploadsSuccessful) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' All documents uploaded successfully', extra: [
                'details' => $responseStatus,
            ]);
            $status = true;
        } else {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Some documents failed to upload', extra: [
                'details' => $responseStatus,
            ]);
            $status = false;
        }

        return ['status' => $status];
    }

    private function httpCall($endPoint, $payload, $keyAPI)
    {
        $response = ['status' => false, 'error' => null, 'message' => null, 'data' => null, 'completed_step' => null,];
        $url = $this->baseUrl.$endPoint;

        try {
            $httpResponse = Http::timeout(20)->withHeaders($this->headers)->post($url, $payload);
            // TODO: statusCode: 404, message: Resource not found. if url wrong.

            if ($httpResponse->successful()) {
                if ($httpResponse->object()?->$keyAPI?->Status == false) {
                    $response['error'] = $httpResponse->object()?->$keyAPI?->Status;
                    $response['status'] = false;
                    $response['message'] = json_encode($httpResponse->object()?->$keyAPI?->errors);
                } else {
                    $response['status'] = true;
                    $response['data'] = $httpResponse->object();
                    $response['message'] = 'API call successfully executed.';
                }
            } else {
                $response['error'] = $httpResponse->object()?->$keyAPI?->Status;
                $response['status'] = false;
                $response['message'] = json_encode($httpResponse->object()?->$keyAPI?->errors);
            }
        } catch (Exception $ex) {
            LoggerService::error('automation:'.$this->className.' fn:'.__FUNCTION__, [
                'endPoint' => $endPoint,
            ], $ex);

            $response['error'] = $ex->getMessage();
            $response['message'] = $ex->getMessage();

            return ['status' => false, 'error' => $ex->getMessage(), 'message' => 'API call failed'];
        }

        return $response;
    }

    /**
     * Map document types to LIVA document type codes
     */
    public function getDocumentType($documentType): string
    {
        return match ($documentType) {
            'CEID' => '16', // Emirates ID (Front side & Back side)
            'DL' => '4', // Driving License (Front side & Back side)
            'CAR_MULKIY' => '5', // Registration card (Mulkiya)
            default => null
        };
    }
}
