<?php

namespace App\Services\PolicyIssuanceAutomation\Car;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCode;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Facades\Ken;
use App\Interfaces\PolicyIssuanceInterface;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\CarQuoteRequestDetail;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Services\AMLService;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\SageApiService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class LivaInsuranceService implements PolicyIssuanceInterface
{
    private $className = 'livaInsuranceService';
    private readonly string $baseUrl;

    public const INSURER_CODE = InsuranceProvidersEnum::RSA;
    public const TYPE = quoteTypeCode::Car;
    public const TYPE_ID = QuoteTypeId::Car;

    public $policyIssuance = null;

    public const UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const ISSUE_POLICY = 'IssuePolicy';
    public const UPLOAD_POLICY_DOCUMENTS_TO_IMCRM = 'UploadPolicyDocumentsToIMCRM';
    public const BOOK_POLICY = 'BookPolicy';
    public const UPLOAD_DOCUMENTS_RESPONSE = 'UploadDocumentsResponse';
    public const POLICY_ISSUANCE_RESPONSE = 'PolicyResponse';
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
    const GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID = 4;
    const GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED = 'Get and Upload Policy Documents to IMCRM API Failed';
    const OCR_PROCESSING_API_FAILED_STATUS_ID = 5;
    const OCR_PROCESSING_API_FAILED = 'OCR Processing API Failed';

    public $currentInsurerApiStatus = null;
    public $headers = [];

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
            self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
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

    public function executeSteps($process)
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

        /* if ($nextStepToBeExecuted === self::BOOK_POLICY) {
            $bookPolicyResponse = $this->executeBookPolicyStep($quote, $process);
            if (isset($bookPolicyResponse['status']) && ! $bookPolicyResponse['status']) {
                return $bookPolicyResponse;
            }

            $nextStepToBeExecuted = $this->getNextStep($process->completed_step);
        } */

        // Return success response when all steps are completed
        return [
            'status' => true,
            'message' => 'All policy issuance steps completed successfully',
        ];
    }

    private function executeBookPolicyStep($quote, $process)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::BOOK_POLICY);
        $triggerBookPolicyResponse = $this->bookPolicy($quote);

        if (! $triggerBookPolicyResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy issuance failed', extra: ['response' => $triggerBookPolicyResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::BOOK_POLICY_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID, 'Send And Book Policy');

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
        $response['completed_step'] = self::BOOK_POLICY;
        $response['message'] = 'Booking process in started! It will take some time to Complete. Come Back in a while to check the status!';

        return $response;
    }

    private function executeUploadPolicyDocumentsStep($quote, $process)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);
        $uploadPolicyDocumentsToIMCRMResponse = $this->uploadPolicyDocumentsToIMCRM($quote, $process);

        if (! $uploadPolicyDocumentsToIMCRMResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy issuance failed', extra: ['response' => $uploadPolicyDocumentsToIMCRMResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID, 'Retrieve Document');

            return $uploadPolicyDocumentsToIMCRMResponse;
        }

        $process->update(['completed_step' => $uploadPolicyDocumentsToIMCRMResponse['completed_step']]);
        $process = $process->refresh();

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - Process ID : '.$process->id.' - Completed Step Updated to : '.$uploadPolicyDocumentsToIMCRMResponse['completed_step']);

        return $uploadPolicyDocumentsToIMCRMResponse;
    }

    public function uploadPolicyDocumentsToIMCRM($quote, $process): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started - Policy Issuance ID : '.$process->id.' - Step : '.self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);

        $response = ['status' => false, 'completed_step' => self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM, 'error' => null, 'message' => null];
        $endPoint = 'transactions/retrieve/v1';

        $uploadedDocumentsToIMCRM = collect();

        $payload = [
            'RetrieveRequest' => [
                'RetrieveType' => $quote->source == LeadSourceEnum::RENEWAL_UPLOAD ? '6' : '5',
                'TransactionNumber' => $quote?->carQuotePlanDetail?->insurer_quote_no,
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

        foreach ($this->getDocTypeCodeForIMCRM() as $keyLIVA => $imcrm) {
            $payload['RetrieveRequest']['Documents']['DocsDetails'][$keyLIVA] = true;

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' document retreive work start for : '.$imcrm['IMNAME'], extra: [
                'time' => now()->format('d-m-Y H:i:s'),
                'payload' => json_encode($payload),
            ]);

            $retrieveRequest = $this->httpCall($endPoint, $payload, 'RetrieveResponse');
            $payload['RetrieveRequest']['Documents']['DocsDetails'][$keyLIVA] = false;

            app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $retrieveRequest, $this->baseUrl.$endPoint, self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM, $retrieveRequest['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);

            if (! $retrieveRequest['status']) {
                $response['error'] = $retrieveRequest['error'];
                $response['message'] = $retrieveRequest['message'];
                $response['status'] = false;

                continue;
            }

            $retrieveResponse = $retrieveRequest['data'];

            $documentContent = $retrieveResponse?->RetrieveResponse?->Policies[0]?->PolicyResponse?->Documents?->PolicyReportsPdf[0];

            $quoteDocument = $this->uploadAndAttachToQuoteDocuments($quote, $documentContent, $imcrm['IMKEY'], $imcrm['IMNAME'].'.pdf');

            $uploadedDocumentsToIMCRM->push([
                'name' => $imcrm['IMNAME'],
                'uploaded' => $quoteDocument?->id ? true : false,
                'message' => $retrieveRequest['message'],
            ]);
        }

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' document retreive work end', extra: [
            'time' => now()->format('d-m-Y H:i:s'),
        ]);

        $allDocumentsUploaded = $uploadedDocumentsToIMCRM->where('uploaded', false)->count() === 0;
        if (! $allDocumentsUploaded) {
            $docsUploadToIMCRMFailed = $uploadedDocumentsToIMCRM->where('uploaded', false)->pluck('name')->toArray();
            info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - failed to fetch all documents from insurer : ', $docsUploadToIMCRMFailed);

            $error = 'Policy Issuance is pending as '.implode(',', $docsUploadToIMCRMFailed).' documents are not uploaded';
            $response['error'] = $error;
            $response['message'] = $error;
            $response['status'] = false;

            return $response;
        }

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - fetched all documents from insurer and Uploaded to IMCRM ');
        $response['status'] = true;
        $response['message'] = 'Fetched all documents from insurer and Uploaded to IMCRM';
        $response['completed_step'] = self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM;

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Process completed step updated to : '.$response['completed_step']);

        return $response;
    }

    private function uploadAndAttachToQuoteDocuments($quote, $documentContent, $documentCode, $originalName = null)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');

        $documentType = DocumentType::where(['quote_type_id' => self::TYPE_ID, 'code' => $documentCode, 'is_active' => true])->first();

        if (! $documentType) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Document type not found for code: '.$documentCode);

            return;
        }

        $fileContents = base64_decode(base64_decode($documentContent));
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

        /* if ($newDocument->exists) {
            WatermarkDocumentsJob::dispatch(
                $newDocument->id,
                $quote->uuid,
                $documentType->id
            );
        } */

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Uploaded Document Name : '.$docName);

        return $newDocument;
    }

    private function executeIssuePolicyStep($quote, $process)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::ISSUE_POLICY);
        $policyIssuanceResponse = $this->issuePolicy($quote, $process);

        if (! $policyIssuanceResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy issuance failed', extra: ['response' => $policyIssuanceResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::POLICY_ISSUANCE_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID, 'Policy Creation');

            return $policyIssuanceResponse;
        }

        $process->update(['completed_step' => $policyIssuanceResponse['completed_step']]);
        $process = $process->refresh();

        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - Process ID : '.$process->id.' - Completed Step Updated to : '.$policyIssuanceResponse['completed_step']);

        return $policyIssuanceResponse;
    }

    public function issuePolicy($quote, $process): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started - Policy Issuance ID : '.$process->id.' - Step : '.self::ISSUE_POLICY);
        $response = ['status' => false, 'completed_step' => self::ISSUE_POLICY, 'error' => null, 'message' => null];

        $endPoint = 'policy/create/v2';

        $payment = $quote->payments()->mainLeadPayment()->first();

        $payload = [
            'PolicyRequest' => [
                'QuotationNo' => $quote?->carQuotePlanDetail?->insurer_quote_no,
                'PremiumPayable' => $payment->total_amount,
                'PartnerTrnReferenceNumber' => $quote->uuid,
                'Documents' => [
                    'DocsInResponse' => false,
                    'DocsDetails' => [
                        'PolicySchedule' => false,
                        'HirePurchaseLetter' => false,
                        'ProposalForm' => false,
                        'LetterToBank' => false,
                        'MotorArabicCertificate' => false,
                        'Receipt' => false,
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
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, self::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID, self::POLICY_AUTOMATION_STATUS_NO_ID, 'Document Upload');

            return $uploadDocumentsResponse;
        }

        $process->update(['completed_step' => $uploadDocumentsResponse['completed_step']]);
        $process = $process->refresh();
        info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - Policy Issuance ID : '.$process->id.' - Completed Step Updated to : '.$uploadDocumentsResponse['completed_step']);

        return $uploadDocumentsResponse;
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
                $documentType = $this->getDocTypeCodeForLIVA($document['document_type_code'] ?? 'other');

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
            'TransactionType' => $quote->source == LeadSourceEnum::RENEWAL_UPLOAD ? '6' : '5',
            'TransactionNumber' => $quote?->carQuotePlanDetail?->insurer_quote_no,
            'Attachments' => $attachments,
        ];

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Payload created with '.count($attachments).' attachments');

        $response = $this->httpCall($endPoint, $payload, self::UPLOAD_DOCUMENTS_RESPONSE);
        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $response, $this->baseUrl.$endPoint, self::UPLOAD_DOCUMENTS, $response['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

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

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Document upload status', extra: [
                'DocumentType' => $value->DocumentType,
                'UploadStatus' => $uploadStatus,
            ]);
        }

        if ($allUploadsSuccessful) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' All documents uploaded successfully', extra: [
                'details' => $responseStatus,
            ]);
            $response['status'] = true;
            $response['completed_step'] = self::UPLOAD_DOCUMENTS;
        } else {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Some documents failed to upload', extra: [
                'details' => $responseStatus,
            ]);
            $response['status'] = false;
        }

        return $response;
    }

    private function httpCall($endPoint, $payload, $keyAPI)
    {
        $response = ['status' => false, 'error' => null, 'message' => null, 'data' => null, 'completed_step' => null];
        $url = $this->baseUrl.$endPoint;

        try {
            $httpResponse = Http::timeout(20)->withHeaders($this->headers)->post($url, $payload);
            // TODO: statusCode: 404, message: Resource not found. if url wrong.

            if ($responseObject = $httpResponse->object()) {
                if (
                    isset($responseObject->$keyAPI?->errors) ||
                    (isset($responseObject->$keyAPI?->Status) && $responseObject->$keyAPI?->Status == false)
                ) {
                    $response['error'] = $responseObject->$keyAPI?->Status ?? $keyAPI.' API Failed';
                    $response['status'] = false;
                    $response['message'] = json_encode($responseObject->$keyAPI?->errors);
                } else {
                    $response['status'] = true;
                    $response['data'] = $responseObject;
                    $response['message'] = 'API call successfully executed.';
                }
            }
        } catch (Exception $ex) {
            LoggerService::error('automation:'.$this->className.' fn:'.__FUNCTION__, [
                'endPoint' => $endPoint,
            ], $ex);

            $response['error'] = $ex->getMessage();
            $response['message'] = $ex->getMessage();
            $response['status'] = false;
        }

        return $response;
    }

    /**
     * Map document types to LIVA document type codes
     */
    public function getDocTypeCodeForLIVA($documentType): string
    {
        return match ($documentType) {
            'CEID' => '16', // Emirates ID (Front side & Back side)
            'DL' => '4', // Driving License (Front side & Back side)
            'CAR_MULKIY' => '5', // Registration card (Mulkiya)
            default => null
        };
    }

    private function getDocTypeCodeForIMCRM(): array
    {
        return [
            'DebitNote' => [
                'IMKEY' => DocumentTypeCode::TI,
                'IMNAME' => 'Tax Invoice',
            ],
            'CreditNote' => [
                'IMKEY' => DocumentTypeCode::CTIRBB,
                'IMNAME' => 'Tax Invoice Raised By Buyer',
            ],
            'PolicySchedule' => [
                'IMKEY' => DocumentTypeCode::POLICY_SCHEDULE,
                'IMNAME' => 'Policy Schedule',
            ],
            'MotorArabicCertificate' => [
                'IMKEY' => DocumentTypeCode::POLICY_CERTIFICATE,
                'IMNAME' => 'Policy Certificate',
            ],
        ];
    }

    public function getInsurerAPIStatuses()
    {
        return [
            self::UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID => self::UPLOAD_POLICY_DOCUMENTS_API_FAILED,
            self::POLICY_ISSUANCE_API_FAILED_STATUS_ID => self::POLICY_ISSUANCE_API_FAILED,
            self::GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID => self::GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED,
            self::OCR_PROCESSING_API_FAILED_STATUS_ID => self::OCR_PROCESSING_API_FAILED,
            self::BOOK_POLICY_API_FAILED_STATUS_ID => self::BOOK_POLICY_API_FAILED,
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

    public function getQuoteDetailsFromInsurer($quoteTypeId, $quoteDetails)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quoteDetails->code.' started');

        try {
            $response = Ken::request("/get-quote-from-insurer?quoteTypeId=$quoteTypeId&quoteUID=$quoteDetails->uuid", 'get');
            $responseData = $response['data'];

            // Extract driver name parts for first and last name
            $driverName = $responseData['DriverDetails'][0]['AdditionalDriverDetails']['DriverName'] ?? '';
            $nameParts = explode(' ', $driverName, 2);
            $driverFirstName = $nameParts[0] ?? '';
            $driverLastName = $nameParts[1] ?? '';

            $getQuoteResponseMapping = [
                'rta_transaction_type' => (string) ($responseData['VehicleDetails']['RtaTransactionType'] ?? ''),
                'plate_code' => $responseData['VehicleDetails']['RegnNoText'] ?? '', // optional
                'plate_number' => $responseData['VehicleDetails']['RegnNoNumber'] ?? '', // optional
                'traffic_code_number' => $responseData['VehicleDetails']['TcfNo'] ?? '',
                'chassis_number' => $responseData['VehicleDetails']['ChassisNo'] ?? '',
                'engine_number' => $responseData['VehicleDetails']['EngineNo'] ?? '',
                'rta_plate_category' => (string) ($responseData['VehicleDetails']['PlateCategory'] ?? ''),
                'vehicle_color' => (string) ($responseData['VehicleDetails']['ColorCode'] ?? ''),
                'plate_color' => '', // Not available in response
                'bank_loan' => ! empty($responseData['VehicleDetails']['CarFinanceCode']) ? '1' : '0',
                'bank_name' => $responseData['VehicleDetails']['CarFinanceCode'] ?? '', // optional
                'first_registration_date' => $responseData['VehicleDetails']['DateOfRegn'] ?? '',
                'policy_effective_date' => $responseData['PolicyEffectiveDate'] ?? '',
                'policy_expiry_date' => $responseData['PolicyExpiryDate'] ?? '', // optional
                'certificate_start_date' => $responseData['VehicleDetails']['CertificateStartDate'] ?? '',
                'certificate_end_date' => $responseData['VehicleDetails']['CertificateEndDate'] ?? '', // optional
                // 'annual_mileage_estimate' => '', // Not available in response
                'is_insured_and_driver_same' => ($responseData['DriverDetails'][0]['AdditionalDriverDetails']['MainDriverInd'] ?? '') === 'Y' ? '1' : '0',
                'driver_first_name' => $driverFirstName, // optional
                'driver_last_name' => $driverLastName, // optional
                'driver_dob' => $responseData['DriverDetails'][0]['AdditionalDriverDetails']['DriverDOB'] ?? '', // optional
                'driver_gender' => $responseData['DriverDetails'][0]['AdditionalDriverDetails']['DriverGender'] === 'M' ? 'male' : 'female',
                'driver_license_number' => $responseData['DriverDetails'][0]['AdditionalDriverDetails']['LicenseNo'] ?? '',
                'driver_license_issue_place' => $responseData['DriverDetails'][0]['AdditionalDriverDetails']['FirstDrvLicCountry'] ?? '', // optional
                'driver_uae_driving_experience' => $responseData['DriverDetails'][0]['AdditionalDriverDetails']['LocalLicense'] ?? 0, // optional
                'home_country_license_issuance' => $responseData['DriverDetails'][0]['AdditionalDriverDetails']['FirstDrvLicCountry'] ?? '', // optional
                'home_country_driving_experience' => $responseData['DriverDetails'][0]['AdditionalDriverDetails']['OtherLicense'] ?? 0, // optional
            ];

            if (! isset($response['errors'])) {
                $carQuoteRequestDetails = CarQuoteRequestDetail::where('car_quote_request_id', $quoteDetails->id)->first();
                if ($carQuoteRequestDetails) {
                    $carQuoteRequestDetails->update($getQuoteResponseMapping);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Quote details retrieved and updated successfully',
                    'data' => $getQuoteResponseMapping ?? null,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'] ?? 'Failed to retrieve quote details from insurer portal',
                ]);
            }
        } catch (\Exception $e) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving quote details from insurer portal',
            ]);
        }
    }

    /* public function updateQuoteRequest($quote)
    {
        LoggerService::startQuoteLogging($quote->code, LoggerFeatureEnum::POLICY_AUTOMATION);

        $livaMapping = app(LivaInsurancePayloadMapping::class);

        $nationalityId = $livaMapping->nationalityList($quote->latestInsured->nationality->text);
        if (! $nationalityId) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Nationality not found on LIVA', extra: [
                'nationality' => $quote->latestInsured->nationality->text,
            ]);
        }

        $homeCountryLicenseIssuance = $livaMapping->nationalityList($quote?->carQuoteRequestDetail?->home_country_license_issuance);
        if (! $homeCountryLicenseIssuance) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Home Country License Issuance nationality not found on LIVA', extra: [
                'nationality' => $quote?->carQuoteRequestDetail?->home_country_license_issuance,
            ]);
        }

        $vehicleMakeId = $livaMapping->vehicleMakeList(strtoupper($quote?->carMake?->text));
        if (! $vehicleMakeId) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Vehicle Make not found on LIVA', extra: [
                'vehicleMake' => strtoupper($quote?->carMake?->text),
            ]);
        }

        $vehicleModelList = $this->getVehicleModelId($vehicleMakeId);

        if (empty($vehicleModelList) || ! isset($vehicleModelList[strtoupper($quote?->carModel?->text)])) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Vehicle Model not found on LIVA', extra: [
                'vehicleModel' => $quote?->carModel?->text,
                'vehicleMake' => $quote?->carMake?->text,
            ]);
        }

        $vehicleModelId = $vehicleModelList[strtoupper($quote?->carModel?->text)];

        $vehicleVariants = $this->getVehicleVariants($quote->latestInsured->dob, $quote->mobile_no, $vehicleMakeId, $vehicleModelId, $quote->year_of_manufacture, $quote->code);

        $closestVariant = $this->matchClosestVehicleVariant($vehicleVariants, [
            'cc' => $quote?->carModelDetail?->cubic_capacity,
            'noOfDoors' => $quote?->carModelDetail?->no_of_doors,
            'noOfCyl' => $quote?->cylinder,
            'bodyType' => $quote?->vehicleType->text,
            'trim' => $quote?->carQuoteRequestDetail?->insurer_trim,
        ]);

        $endPoint = 'quote/update/v2';
        $payload = [
            'QuotationRequest' => [
                'CustomerDetails' => [
                    'Gender' => 'M',
                    'DOB' => $quote->latestInsured->dob.' 00:00:00',
                    'Nationality' => $nationalityId,
                    'MobileNo' => $quote->mobile_no,
                    'EmailId' => $quote->email,
                    'FirstName' => $quote->latestInsured->first_name,
                    'LastName' => $quote->latestInsured->last_name,
                    'NationalId' => $quote->latestInsured->id_number,
                    'CustomerCategory' => 1,
                ],
                'VehicleDetails' => [
                    'CC' => $quote?->carModelDetail?->cubic_capacity,
                    'PlaceOfRegn' => '1', // TODO: need to ask ecom side
                    'NcbYears' => 99,
                    'DateOfRegn' => $quote?->carQuoteRequestDetail?->first_registration_date ? ($quote?->carQuoteRequestDetail?->first_registration_date.' 00:00:00') : '',
                    'YearOfManf' => $quote?->year_of_manufacture,
                    'InsuredValue' => $quote?->car_value,
                    'VehicleDescCode' => $closestVariant->VehicleDescCode,
                    'VehicleDesc' => $quote?->carQuoteRequestDetail?->insurer_trim,
                    'Seats' => $quote?->seat_capacity,
                    'UseCode' => '2',
                    'BodyType' => $closestVariant->BodyTypeCode,
                    'MakeCode' => $vehicleMakeId,
                    'ModelCode' => $vehicleModelId,
                    'NoOfCyl' => $quote?->cylinder,
                    'EstimatedAnnualMileage' => $quote?->carQuoteRequestDetail?->annual_mileage_estimate,
                    'VehicleSpecification' => $quote?->is_gcc_standard,
                    'vehHP' => $closestVariant->HP,
                    'NoOfDoors' => $quote?->carModelDetail?->no_of_doors,
                    'DrivenWheel' => $closestVariant->DrivenWheel,
                    'modelSpecification' => $closestVariant->ModelSpecification,
                    'ColorCode' => $quote?->carQuoteRequestDetail?->vehicle_color,
                    'RegistrationType' => '1', // TODO: rta transaction based
                    'RtaTransactionType' => (string) $quote?->carQuoteRequestDetail?->rta_transaction_type,
                    'RegnNoText' => $quote->carQuoteRequestDetail?->plate_code,
                    'RegnNoNumber' => $quote->carQuoteRequestDetail?->plate_number,
                    'ChassisNo' => $quote->carQuoteRequestDetail?->chassis_number,
                    'EngineNo' => $quote->carQuoteRequestDetail?->engine_number,
                    'TcfNo' => $quote->carQuoteRequestDetail?->traffic_code_number,
                ],
                'TransactionDetails' => [
                    'PolicyTypeCode' => '1', // TODO: need to ask api team
                    'EffectiveDate' => '2025-01-06 21:09:00', // TODO: need to ask api team
                    'SchemeCode' => '13',
                    'TariffCode' => '17', // TODO: need to ask api team
                    'PartnerTrnReferenceNumber' => $quote->uuid ?? 'Q1aw2bvcvT', // TODO: need to ask
                ],
                'OptionalCovers' => [
                    [
                        'CoverIncluded' => true,
                        'CoverMappingCode' => '2-1-0', // TODO: need to ask
                    ],
                ],
                'DriverDetails' => [
                    [
                        'DriverName' => $quote?->latestInsured->first_name.' '.$quote?->latestInsured->last_name,
                        'MainDriverInd' => $quote?->carQuoteRequestDetail?->is_insured_and_driver_same ? 'Y' : 'N',
                        'DriverDOB' => $quote?->carQuoteRequestDetail?->driver_dob.' 00:00:00',
                        'DriverGender' => str_starts_with(strtoupper($quote?->carQuoteRequestDetail?->driver_gender ?? ''), 'M') ? 'M' : 'F',
                        'FirstDrvLicCountry' => $homeCountryLicenseIssuance,
                        'LocalLicense' => $quote?->carQuoteRequestDetail?->driver_uae_driving_experience,
                        'OtherLicense' => $quote?->carQuoteRequestDetail?->home_country_driving_experience,
                        'LicenseNo' => $quote?->carQuoteRequestDetail?->driver_license_number,
                    ],
                ],
                'QuotationNo' => $quote?->carQuotePlanDetail?->insurer_quote_no,
                'PolicyId' => $quote?->carQuotePlanDetail?->insurer_quote_no,
                'EndtId' => '0',
                'ProposalForm' => false,
                'UserComments' => 'Update Quote Request',
            ],
        ];

        return $this->livaHttpCall($endPoint, $payload, 'QuotationResponse');
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

        return $this->livaHttpCall($endPoint, $payload, 'RetrieveResponse');
    } */

    public function registrationType($rtaTransactionType)
    {
        return match ($rtaTransactionType) {
            '10' => 'NVR',
            '20' => 'CO',
            '30' => 'CO',
            '40' => 'VR',
            '50' => 'VR',
        };
    }
}
