<?php

namespace App\Services\PolicyIssuanceAutomation\Car;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CarRegistrationType;
use App\Enums\DocumentTypeCode;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\LookupsEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\PolicyIssuanceStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Facades\Ken;
use App\Http\Requests\BookPolicyRequest;
use App\Http\Requests\SendBookPolicyRequest;
use App\Interfaces\PolicyIssuanceInterface;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\CarQuoteRequestDetail;
use App\Models\DocumentType;
use App\Models\InsuranceProvider;
use App\Models\Payment;
use App\Models\UAELicenseHeldFor;
use App\Services\AMLService;
use App\Services\ApplicationStorageService;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LivaInsuranceService implements PolicyIssuanceInterface
{
    use GenericQueriesAllLobs;

    private $className = 'livaInsuranceService';
    private readonly string $baseUrl;

    public const INSURER_CODE = InsuranceProvidersEnum::RSA;
    public const TYPE = quoteTypeCode::Car;
    public const TYPE_ID = QuoteTypeId::Car;

    public $policyIssuance = null;

    public const POLICY_ISSUANCE_API_ACCESS_TOKEN_KEY = InsuranceProvidersEnum::RSA.'_POLICY_ISSUANCE_API_ACCESS_TOKEN';
    public const UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const ISSUE_POLICY = 'IssuePolicy';
    public const UPLOAD_POLICY_DOCUMENTS_TO_IMCRM = 'UploadPolicyDocumentsToIMCRM';
    public const BOOK_POLICY = 'BookPolicy';
    public const UPLOAD_DOCUMENTS_RESPONSE = 'UploadDocumentsResponse';
    public const POLICY_ISSUANCE_RESPONSE = 'PolicyResponse';
    public const RETRIEVE_RESPONSE = 'RetrieveResponse';

    public $currentInsurerApiStatus = null;
    public $headers = [];
    private $appEnv;
    private $apiTimeout;

    public function __construct()
    {
        $this->baseUrl = config('constants.LIVA_API_BASE_URL');
        $this->appEnv = config('constants.APP_ENV');
        $this->apiTimeout = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::LIVA_AUTOMATION_API_TIMEOUT);
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

        $this->getAccessToken();

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

    private function executeBookPolicyStep($quote, $process)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::BOOK_POLICY);
        $triggerBookPolicyResponse = $this->bookPolicy($quote);

        if (! $triggerBookPolicyResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy issuance failed', extra: ['response' => $triggerBookPolicyResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'Send And Book Policy');

            return $triggerBookPolicyResponse;
        }

        $process->update(['completed_step' => $triggerBookPolicyResponse['completed_step']]);
        $process = $process->refresh();

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - Process ID : '.$process->id.' - Completed Step Updated to : '.$triggerBookPolicyResponse['completed_step']);

        return $triggerBookPolicyResponse;
    }

    public function bookPolicy($quote): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started');
        $response = ['status' => false, 'completed_step' => self::BOOK_POLICY, 'error' => null, 'message' => null];

        try {
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

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Book Policy execution initiated, creating Sage process');
            $createSageProcessResponse = (new SageApiService)->postBookPolicyToSage($request, $quote);
            app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, [], $createSageProcessResponse, '', self::BOOK_POLICY, $createSageProcessResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $this->policyIssuance);

            if (! $createSageProcessResponse['status']) {
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Book Policy execution failed, Error: '.$createSageProcessResponse['message']);
                $response['error'] = $createSageProcessResponse['message'];

                return $response;
            }

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' Sage Process Created : '.$createSageProcessResponse['message']);

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' ended');

            $response['status'] = true;
            $response['message'] = 'Booking process in started! It will take some time to Complete. Come Back in a while to check the status!';

            return $response;

        } catch (Exception $e) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Exception occurred', extra: [
                'errorTraceMessage' => $e->getTraceAsString(),
                'errorCode' => $e->getCode(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
                'errorMessage' => $e->getMessage(),
            ]);
            $response['error'] = 'Book policy failed: '.$e->getMessage();
            $response['message'] = 'An error occurred during book policy: '.$e->getMessage();

            return $response;
        }
    }

    private function validateBookPolicy($quote): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Validating book policy prerequisites process started');
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

                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - SendBookPolicyRequest validation failed: '.$response['message']);

                return $response;
            }

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - All prerequisites validated successfully, Validate prerequisites process completed');
            $response['message'] = 'All book policy prerequisites validated successfully';

        } catch (Exception $e) {
            $response['status'] = false;
            $response['error'] = 'Validation error: '.$e->getMessage();
            $response['message'] = 'An error occurred during validation: '.$e->getMessage();

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Validate prerequisites process failed, Exception: '.$e->getMessage());
        }

        return $response;
    }

    private function updateBookingDetails($quote): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Update booking details process started');
        $response = ['status' => true, 'error' => null, 'message' => null];

        try {
            $payment = $quote->payments()->mainLeadPayment()->first();

            if (! $payment) {
                $response['status'] = false;
                $response['error'] = 'Payment not found for quote';
                $response['message'] = 'Main lead payment not found for quote: '.$quote->code;
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Payment not found');

                return $response;
            }

            $bookPolicyPayload = $this->bookPolicyPayload($quote, QuoteTypes::CAR->value, $quote->payments, $quote->quoteDocuments);

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Booking Details before exeuting validation', extra: [
                'invoice_date' => $payment->insurer_invoice_date,
                'insurer_tax_invoice_number' => $payment->insurer_tax_number,
                'insurer_commmission_invoice_number' => $payment->insurer_commmission_invoice_number,
                'discount' => $payment->discount_value,
                'transaction_payment_status' => $bookPolicyPayload['transactionPaymentStatus'] ?? null,
                'broker_invoice_number' => $bookPolicyPayload['brokerInvoiceNo'] ?? null,
                'commission_vat_not_applicable' => $payment->commission_vat_not_applicable,
                'commission_vat_applicable' => $payment->commission_vat_applicable,
                'total_commission' => $payment->commission,
                'invoice_description' => $bookPolicyPayload['invoiceDescription'] ?? null,
                'vat_on_commission' => $payment->commission_vat,
                'commission_percentage' => $payment->commmission_percentage,
                'payment_code' => $payment->code,
                'model_type' => self::TYPE,
                'quote_id' => $quote->id,
            ]);

            $updateBookingRequest = [
                'invoice_date' => $payment->insurer_invoice_date,
                'insurer_tax_invoice_number' => $payment->insurer_tax_number,
                'insurer_commmission_invoice_number' => $payment->insurer_commmission_invoice_number,
                'discount' => $payment->discount_value,
                'transaction_payment_status' => $bookPolicyPayload['transactionPaymentStatus'] ?? null,
                'broker_invoice_number' => $bookPolicyPayload['brokerInvoiceNo'] ?? null,
                'commission_vat_not_applicable' => $payment->commission_vat_not_applicable,
                'commission_vat_applicable' => $payment->commission_vat_applicable,
                'total_commission' => $payment->commission,
                'invoice_description' => $bookPolicyPayload['invoiceDescription'] ?? null,
                'vat_on_commission' => $payment->commission_vat,
                'commission_percentage' => $payment->commmission_percentage,
                'payment_code' => $payment->code,
                'model_type' => self::TYPE,
                'quote_id' => $quote->id,
                'through_automation' => true,
            ];

            request()->merge($updateBookingRequest);

            $bookPolicyRequest = new BookPolicyRequest;
            $validator = Validator::make($updateBookingRequest, $bookPolicyRequest->rules());
            $bookPolicyRequest->withValidator($validator);

            if ($validator->fails()) {
                $response['status'] = false;
                $response['error'] = $validator->errors()->first() ?? 'BookPolicyRequest validation failed';
                $response['message'] = $validator->errors()->first();

                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - BookPolicyRequest validation failed: '.$response['message']);

                return $response;
            }

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Updating booking details');
            $updateBookingDetailsResponse = app(CentralService::class)->updateBookingDetails($updateBookingRequest, $bookPolicyRequest);

            if (! $updateBookingDetailsResponse['status']) {
                LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Failed to update booking details');
                $response['status'] = false;
                $response['error'] = $updateBookingDetailsResponse['message'];
                $response['message'] = $updateBookingDetailsResponse['message'];
            }

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Update booking details process completed');

        } catch (Exception $e) {
            $response['status'] = false;
            $response['error'] = 'Booking update error: '.$e->getMessage();
            $response['message'] = 'An error occurred while updating booking details: '.$e->getMessage();

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Booking update process failed', extra: [
                'errorTraceMessage' => $e->getTraceAsString(),
                'errorCode' => $e->getCode(),
                'errorFile' => $e->getFile(),
                'errorLine' => $e->getLine(),
            ]);
        }

        return $response;
    }

    private function executeUploadPolicyDocumentsStep($quote, $process)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Step Executing : '.self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);
        $uploadPolicyDocumentsToIMCRMResponse = $this->uploadPolicyDocumentsToIMCRM($quote, $process);

        if (! $uploadPolicyDocumentsToIMCRMResponse['status']) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Policy issuance failed', extra: ['response' => $uploadPolicyDocumentsToIMCRMResponse]);
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'Retrieve Document');

            return $uploadPolicyDocumentsToIMCRMResponse;
        }

        $process->update(['completed_step' => $uploadPolicyDocumentsToIMCRMResponse['completed_step']]);
        $process = $process->refresh();

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - Process ID : '.$process->id.' - Completed Step Updated to : '.$uploadPolicyDocumentsToIMCRMResponse['completed_step']);

        return $uploadPolicyDocumentsToIMCRMResponse;
    }

    public function uploadPolicyDocumentsToIMCRM($quote, $process): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started - Policy Issuance ID : '.$process->id.' - Step : '.self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);

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

        foreach ($this->getDocTypeCodeForIMCRM() as $keyLIVA => $imcrm) {
            $payload['RetrieveRequest']['Documents']['DocsDetails'][$keyLIVA] = true;

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' document retreive work start for : '.$imcrm['IMNAME'], extra: [
                'time' => now()->format('d-m-Y H:i:s'),
                'payload' => json_encode($payload),
            ]);

            $retrieveRequest = $this->httpCall($endPoint, $payload, 'RetrieveResponse');

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Response', extra: ['response' => $retrieveRequest]);

            if ($retrieveRequest['status']) {
                $retrieveResponse = $retrieveRequest['data'];

                $documentContent = $retrieveResponse?->RetrieveResponse?->Policies[0]?->PolicyResponse?->Documents?->PolicyReportsPdf[0];

                $quoteDocument = $this->uploadAndAttachToQuoteDocuments($quote, $documentContent, $imcrm['IMKEY'], $imcrm['IMNAME'].'.pdf');
            }

            app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $retrieveRequest, $this->baseUrl.$endPoint, self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM, $retrieveRequest['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);
            $payload['RetrieveRequest']['Documents']['DocsDetails'][$keyLIVA] = false;
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' document retreive work end', extra: [
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

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - allDocumentsUploaded', extra: [
            'allDocumentsUploaded' => $allDocumentsUploaded,
        ]);

        if (! $allDocumentsUploaded || empty($uploadedDocumentsToIMCRM)) {
            $docsUploadToIMCRMFailed = $uploadedDocumentsToIMCRM->where('status', false)->pluck('name')->toArray();
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - failed to fetch all documents from insurer : ', $docsUploadToIMCRMFailed);

            $error = 'Policy Issuance is pending as '.implode(',', $docsUploadToIMCRMFailed).' documents are not uploaded';
            $response['error'] = $error;
            $response['message'] = $error;
            $response['status'] = false;

            return $response;
        }

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - fetched all documents from insurer and Uploaded to IMCRM ');
        $response['status'] = true;
        $response['message'] = 'Fetched all documents from insurer and Uploaded to IMCRM';
        $response['completed_step'] = self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM;

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' - Process completed step updated to : '.$response['completed_step']);

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

        if ($newDocument?->exists && $documentCode == DocumentTypeCode::POLICY_CERTIFICATE) {
            $quote->update(['rta_upload_status' => PolicyIssuanceEnum::PIA_RTA_UPLOAD_STATUS_DONE]);
        }

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

        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - Process ID : '.$process->id.' - Completed Step Updated to : '.$policyIssuanceResponse['completed_step']);

        return $policyIssuanceResponse;
    }

    public function issuePolicy($quote, $process): array
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quote->code.' started - Policy Issuance ID : '.$process->id.' - Step : '.self::ISSUE_POLICY);
        $response = ['status' => false, 'completed_step' => self::ISSUE_POLICY, 'error' => null, 'message' => null];

        $endPoint = 'motor/policy/create/v2';

        $payment = $quote->payments()->mainLeadPayment()->first();
        $splitPayment = $payment?->paymentSplits()->where('payment_method', PaymentMethodsEnum::CreditCard)->first();

        $payload = [
            'PolicyRequest' => [
                'QuotationNo' => $quote?->carQuotePlanDetail?->insurer_quote_no,
                'PremiumPayable' => $payment->total_amount,
                'IsPaymentProcessed' => 'Success',
                'PartnerTrnReferenceNumber' => $quote->uuid,
                'PaymtMode' => 7,
                'PaymtTransactionDate' => $payment?->authorized_at ? Carbon::parse($payment?->authorized_at)->format('Y-m-d H:i:s') : '',
                'PaymtTransactionNumber' => $splitPayment?->payment_receipt_id,
                'Amount' => $payment?->price_vat_applicable,
                'AuthCode' => $splitPayment?->payment_auth_code,
                'Documents' => [
                    'DocsInResponse' => false,
                    'DocsDetails' => [
                        'PolicySchedule' => false,
                        'HirePurchaseLetter' => false,
                        'ProposalForm' => false,
                        'LetterToBank' => false,
                        'MotorArabicCertificate' => false,
                        'Receipt' => false,
                        'BreakDownRecovery' => false,
                        'UPRInvoice' => false,
                    ],
                ],
                'PolicyConfirmationSMS' => false,
                'PolicyConfirmationEmail' => false,
            ],
        ];

        $issuePolicy = $this->httpCall($endPoint, $payload, self::POLICY_ISSUANCE_RESPONSE);
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Response', extra: ['response' => $issuePolicy]);
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

            $this->currentInsurerApiStatus = PolicyIssuanceEnum::PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID;
            app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, QuoteTypes::CAR->value, PolicyIssuanceEnum::PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID, PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID, 'Document Upload');

            return $uploadDocumentsResponse;
        }

        $process->update(['completed_step' => $uploadDocumentsResponse['completed_step']]);
        $process = $process->refresh();
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$process->model->code.' - Policy Issuance ID : '.$process->id.' - Completed Step Updated to : '.$uploadDocumentsResponse['completed_step']);

        return $uploadDocumentsResponse;
    }

    public function uploadDocuments($quote)
    {
        $endPoint = 'motor/documents/upload/v2';
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
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Response', extra: ['response' => $response]);
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
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' calling API: '.$keyAPI);
        $response = ['status' => false, 'error' => null, 'message' => null, 'data' => null, 'completed_step' => null];
        $url = $this->baseUrl.$endPoint;
        $timeOut = $this->apiTimeout;

        try {
            $httpResponse = Http::timeout($timeOut)->withHeaders($this->headers)->post($url, $payload);

            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Response API: '.$keyAPI, extra: [
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
                    $response['error'] = $responseObject?->$keyAPI?->Status ?? $keyAPI.' API Failed';
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
                $response['error'] = $keyAPI.' API Failed';
                $response['status'] = false;
                $response['message'] = 'There is an Exception on LIVA API call.';
            }
        } catch (Exception $ex) {
            LoggerService::error('automation:'.$this->className.' fn:'.__FUNCTION__, [
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
        return $responseObject ?? $keyAPI.' API Failed';
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

    public function getInsurerAPIStatusByStep($policyIssuance)
    {
        $lastCompletedStep = $policyIssuance->completed_step;
        $step = $this->getNextStep($lastCompletedStep);
        $insurerApiStatus = [
            self::UPLOAD_DOCUMENTS => PolicyIssuanceEnum::PIA_UPLOAD_POLICY_DOCUMENTS_API_FAILED_STATUS_ID,
            self::ISSUE_POLICY => PolicyIssuanceEnum::PIA_POLICY_ISSUANCE_API_FAILED_STATUS_ID,
            self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM => PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID,
            self::BOOK_POLICY => PolicyIssuanceEnum::PIA_BOOK_POLICY_API_FAILED_STATUS_ID,
        ];

        return $insurerApiStatus[$step] ?? null;
    }

    public function getQuoteDetailsFromInsurer($quoteTypeId, $quoteDetails)
    {
        LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Quote : '.$quoteDetails->code.' started');

        try {
            $response = Ken::request("/get-quote-from-insurer?quoteTypeId=$quoteTypeId&quoteUID=$quoteDetails->uuid", 'get');

            if (isset($response['data'])) {
                $responseData = $response['data'];

                $additionalDriverDetails = $responseData['DriverDetails'][0]['AdditionalDriverDetails'] ?? [];
                $isMultipleDriver = ! empty($additionalDriverDetails) && is_array(reset($additionalDriverDetails));

                if ($isMultipleDriver) {
                    $mainDriver = array_values(array_filter($additionalDriverDetails, function ($driver) {
                        return ($driver['MainDriverInd'] ?? '') === 'Y';
                    }))[0] ?? [];
                } else {
                    $mainDriver = $additionalDriverDetails;
                }

                $driverName = $mainDriver['DriverName'] ?? '';
                $nameParts = explode(' ', $driverName, 2);
                $driverFirstName = $nameParts[0] ?? '';
                $driverLastName = $nameParts[1] ?? '';

                $vehicleDriverDetailsData = [
                    'is_insured_and_driver_same' => $isMultipleDriver ? '0' : '1',
                    'rta_transaction_type' => (string) ($responseData['VehicleDetails']['RtaTransactionType'] ?? ''),
                    'vehicle_plate_code' => $responseData['VehicleDetails']['RegnNoText'] ?? '', // optional
                    'vehicle_plate_number' => $responseData['VehicleDetails']['RegnNoNumber'] ?? '', // optional
                    'traffic_code_number' => $responseData['VehicleDetails']['TcfNo'] ?? '',
                    'vehicle_engine_number' => $responseData['VehicleDetails']['EngineNo'] ?? '',
                    'rta_plate_category' => (string) ($responseData['VehicleDetails']['PlateCategory'] ?? ''),
                    'vehicle_color' => (string) ($responseData['VehicleDetails']['ColorCode'] ?? ''),
                    'vehicle_plate_color' => '', // Not available in response
                    'bank_loan' => ! empty($responseData['VehicleDetails']['CarFinanceCode']) ? '1' : '0',
                    'bank_name' => $responseData['VehicleDetails']['CarFinanceCode'] ?? '', // optional
                    'first_registration_date' => $responseData['VehicleDetails']['DateOfRegn'] ?? '',
                    'driver_first_name' => $driverFirstName, // optional
                    'driver_last_name' => $driverLastName, // optional
                    'driver_dob' => $mainDriver['DriverDOB'] ?? '', // optional
                    'driver_gender' => ($mainDriver['DriverGender'] ?? '') === 'M' ? 'male' : 'female',
                    'driver_license_number' => $mainDriver['LicenseNo'] ?? '',
                    'driver_license_issue_place' => (string) ($mainDriver['FirstDrvLicCountry'] ?? ''), // optional
                    'driver_uae_driving_experience' => (string) ($mainDriver['LocalLicense'] ?? 0), // optional
                    'driver_home_country_license_issuance' => (string) ($mainDriver['FirstDrvLicCountry'] ?? ''), // optional
                    'driver_home_country_driving_experience' => (string) ($mainDriver['OtherLicense'] ?? 0), // optional
                ];

                $quoteDetailsData = [
                    'policy_start_date' => $this->formatDateToYmd($responseData['PolicyEffectiveDate'] ?? ''),
                    'policy_expiry_date' => $this->formatDateToYmd($responseData['PolicyExpiryDate'] ?? ''), // optional
                    'certificate_start_date' => $this->formatDateToYmd($responseData['VehicleDetails']['CertificateStartDate'] ?? ''),
                    'certificate_end_date' => $this->formatDateToYmd($responseData['VehicleDetails']['CertificateEndDate'] ?? ''), // optional
                ];

                $getQuoteResponseMapping = [
                    'chassis_number' => $responseData['VehicleDetails']['ChassisNo'] ?? '',
                ];

                $carQuoteRequestDetails = CarQuoteRequestDetail::where('car_quote_request_id', $quoteDetails->id)->first();
                if ($carQuoteRequestDetails) {
                    LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Updating quote details');
                    $carQuoteRequestDetails->update($getQuoteResponseMapping);
                    $quoteDetails->update($quoteDetailsData);
                    $quoteDetails->vehicleDriverDetail()->updateOrCreate([], $vehicleDriverDetailsData);
                }

                $getQuoteResponseMapping = array_merge($getQuoteResponseMapping, $vehicleDriverDetailsData, $quoteDetailsData, ['QuoteStatus' => $responseData['QuoteStatus']]);

                return [
                    'success' => true,
                    'message' => 'Quote details retrieved and updated successfully',
                    'data' => $getQuoteResponseMapping ?? null,
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['message'] ?? 'Failed to retrieve quote details from insurer portal',
                    'isGetQuoteAPIFailed' => true,
                ];
            }
        } catch (\Exception $e) {
            LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' - Failed', extra: [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while retrieving quote details from insurer portal',
                'isGetQuoteAPIFailed' => true,
            ];
        }
    }

    public function registrationType($rtaTransactionType)
    {
        return match ($rtaTransactionType) {
            '10' => '3',
            '20' => '2',
            '30' => '2',
            '40' => '1',
            '50' => '1',
        };
    }

    public function getAccessToken(): ?string
    {
        $cachedToken = Cache::store('redis')->get(self::POLICY_ISSUANCE_API_ACCESS_TOKEN_KEY);
        if ($cachedToken) {
            $this->headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic '.config('constants.LIVA_BASIC_AUTH'),
                'PartnerId' => config('constants.LIVA_PARENT_ID'),
                'location' => config('constants.LIVA_LOCATION'),
                'Authentication' => 'Bearer '.$cachedToken,
                'SubscriptionKey' => config('constants.LIVA_SUBSCRIPTION_KEY'),
            ];

            return $cachedToken;
        }

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $payload = [
            'client_id' => config('constants.LIVA_CLIENT_ID'),
            'client_secret' => config('constants.LIVA_CLIENT_SECRET'),
            'grant_type' => 'client_credentials',
            'scope' => config('constants.LIVA_SCOPE'),
        ];

        try {
            $timeOut = $this->apiTimeout;

            $response = Http::timeout($timeOut)
                ->withHeaders($headers)
                ->asForm() // This ensures proper form encoding
                ->post(config('constants.LIVA_API_BASE_URL').'/auth-token', $payload);

            if ($response->successful()) {
                $responseData = $response->json();

                $accessToken = $responseData['access_token'] ?? null;
                $expiresIn = (int) ($responseData['expires_in'] ?? 3599);

                if ($accessToken) {
                    Cache::store('redis')->put(self::POLICY_ISSUANCE_API_ACCESS_TOKEN_KEY, $accessToken, $expiresIn);

                    $this->headers = [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Basic '.config('constants.LIVA_BASIC_AUTH'),
                        'PartnerId' => config('constants.LIVA_PARENT_ID'),
                        'location' => config('constants.LIVA_LOCATION'),
                        'Authentication' => 'Bearer '.$accessToken,
                        'SubscriptionKey' => config('constants.LIVA_SUBSCRIPTION_KEY'),
                    ];

                    LoggerService::info('automation:'.$this->className.' fn:'.__FUNCTION__.' Token retrieved successfully', extra: [
                        'expires_in' => $expiresIn,
                        'expires_in_minutes' => round($expiresIn / 60, 2),
                    ]);

                    return $accessToken;
                }
            }

            LoggerService::error('automation:'.$this->className.' fn:'.__FUNCTION__.' Token request failed', extra: [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (Exception $e) {
            LoggerService::error('automation:'.$this->className.' fn:'.__FUNCTION__.' Token request exception', exception: $e);

            return null;
        }
    }

    public function getStepsLockingStatus($quote, $throughAutomation = false): array
    {
        LoggerService::info('class: '.$this->className.' fn: '.__FUNCTION__.' Quote : '.$quote->code);
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

        if ($quote?->registration_type !== CarRegistrationType::PERSONAL) {
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

    public function getLIVALookups($leadSource)
    {
        $insuranceProviderId = InsuranceProvider::where('code', InsuranceProvidersEnum::RSA)->first()->id;
        $additionalLookups = app(AMLService::class)->getAMLLookups($insuranceProviderId, [
            LookupsEnum::RTA_TRANSACTION_TYPE,
            LookupsEnum::RTA_PLATE_CATEGORY,
            LookupsEnum::VEHICLE_COLOR,
            LookupsEnum::BANK_NAME,
            LookupsEnum::ANNUAL_MILEAGE_ESTIMATE,
        ])->toArray();
        $additionalLookups['driving_experience'] = UAELicenseHeldFor::select('id', 'rsa_driving_experience', 'text')->get()->toArray();

        if ($leadSource == LeadSourceEnum::RENEWAL_UPLOAD) {
            $rtaTransactionType = array_filter($additionalLookups['rta_transaction_type'], function ($item) {
                return in_array($item['code'], app(LivaInsurancePayloadMapping::class)->renewalRtaTransactionType());
            });

            $additionalLookups['rta_transaction_type'] = array_values($rtaTransactionType);
        }

        return $additionalLookups;
    }

    public function dateFormat($date, $endDate = false)
    {
        if (empty($date)) {
            return null;
        }

        $carbonDate = Carbon::parse($date);

        return $endDate
            ? $carbonDate->endOfDay()->format('Y-m-d H:i:s')
            : $carbonDate->startOfDay()->format('Y-m-d H:i:s');
    }

    public function livaPortalTimeoutResponse($policyIssuance): bool
    {
        if (str_contains($policyIssuance->message, 'PolicyIssuanceJob has been attempted too many times')) {
            LoggerService::info($this->className.' fn:'.__FUNCTION__.' - PolicyIssuanceJob was failed due to timeout', extra: [
                'reason' => $policyIssuance->message,
            ]);

            return false;
        } else {
            $policyIssuanceLogs = $policyIssuance->policyIssuanceLogs;
            if (
                $policyIssuanceLogs &&
                $policyIssuanceLogs->last()->status === PolicyIssuanceEnum::FAILED_STATUS
            ) {
                $failedNullLogFound = $policyIssuanceLogs->filter(function ($log) {
                    return $this->hasNullStatusResponse($log->response);
                });

                $failedLogsCount = $policyIssuanceLogs->where('status', PolicyIssuanceEnum::FAILED_STATUS)->count();

                LoggerService::info($this->className.' fn:'.__FUNCTION__.' - Failed logs count : '.$failedLogsCount);

                if ($failedNullLogFound->isNotEmpty() && $failedLogsCount < 2) {
                    LoggerService::info($this->className.' fn:'.__FUNCTION__.' - PolicyIssuanceJob was failed due to timeout', extra: [
                        'error' => $failedNullLogFound->first()?->response,
                    ]);

                    return true;
                }
            }
        }

        return false;
    }

    public function hasNullStatusResponse(string $response): bool
    {
        $nullStatusPatterns = [
            '"status": false, "message": null, "completed_step": null',
            '"status": false, "message": "null", "completed_step": null',
        ];

        foreach ($nullStatusPatterns as $pattern) {
            if (str_contains($response, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if policy issuance timed out and update status accordingly
     *
     * @param  \App\Models\PolicyIssuance  $policyIssuance
     */
    public function handleTimeoutStatusUpdate($policyIssuance): void
    {
        LoggerService::info($this->className.' fn:'.__FUNCTION__.' - Updating Policy Issuance ID : '.$policyIssuance->id, extra: [
            'status' => $policyIssuance->status,
            'completed_step' => $policyIssuance->completed_step,
        ]);

        try {
            $isTimeout = $this->livaPortalTimeoutResponse($policyIssuance);

            if ($isTimeout) {
                $policyIssuance->update([
                    'status' => PolicyIssuanceEnum::TIMEOUT_STATUS,
                ]);

                LoggerService::info($this->className.' fn:'.__FUNCTION__.' - Updated Policy Issuance ID : '.$policyIssuance->id.' to TIMEOUT_STATUS');
            }
        } catch (\Exception $ex) {
            LoggerService::info($this->className.' fn:'.__FUNCTION__.' - Error Updating Policy Issuance ID : '.$policyIssuance->id, extra: [
                'errorMessage' => $ex->getMessage(),
            ]);
        }
    }

    /**
     * Format LIVA date to Y-m-d format
     */
    private function formatDateToYmd(string $date): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (Exception $ex) {
            LoggerService::info($this->className.' fn:'.__FUNCTION__.' - Failed to parse date: '.$date, extra: [
                'error' => $ex->getMessage(),
                'line' => $ex->getLine(),
            ]);

            return '';
        }
    }
}
