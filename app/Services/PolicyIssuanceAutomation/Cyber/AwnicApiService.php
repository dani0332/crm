<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\AwnicEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Facades\Awnic;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

class AwnicApiService
{
    public function __construct(
        private AwnicRequestBuilder $requestBuilder,
        private AwnicResponseHandler $responseHandler,
        private AwnicDocumentHandler $documentHandler,
        private AwnicQuoteUpdaterService $quoteUpdater,
        private AwnicValidationService $validationService,
    ) {}

    /**
     * Issue policy API call
     *
     * @param  mixed  $quote
     * @param  mixed  $process
     */
    public function issuePolicy($quote, $process): array
    {
        LoggerService::info('Initiating policy issuance API call', extra: [
            'process_id' => $process->id,
            'step' => AwnicEnum::STEP_ISSUE_POLICY,
            'policy_start_date' => $quote->policy_start_date,
        ]);

        $response = $this->responseHandler->buildStepResponse(AwnicEnum::STEP_ISSUE_POLICY);
        $endPoint = '/cyber/generatePolicy';

        $customer = $quote->customer;
        $nationality = $quote->nationality;
        $emirateOfRegistration = $quote->cyberQuote->emirateOfRegistration;
        $planDetail = $quote->cyberPlanDetail;

        $payment = $quote->payments()->mainLeadPayment()->first();
        $splitPayment = $payment?->paymentSplits()->where('payment_method', PaymentMethodsEnum::CreditCard)->first();

        $payload = $this->requestBuilder->buildIssuePolicyPayload($quote, $customer, $nationality, $planDetail, $splitPayment, $emirateOfRegistration);
        $headers = $this->requestBuilder->buildIssuePolicyHeaders();

        $httpResponse = Awnic::post($endPoint, $payload, $headers);
        $issuePolicyResponse = $this->responseHandler->parseHttpResponse($httpResponse, AwnicEnum::RESPONSE_POLICY);

        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $issuePolicyResponse, Awnic::getBaseUrl().$endPoint, AwnicEnum::STEP_ISSUE_POLICY, $issuePolicyResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);

        // this email is used to test the policy issuance automation failure scenario
        if (! $issuePolicyResponse['status'] || $quote->email == PolicyIssuanceEnum::FAKE_EMAIL_IMCRM_POLICY_ISSUANCE) {
            LoggerService::error('API call failed', extra: [
                'endpoint' => $endPoint,
                'error' => $issuePolicyResponse['error'] ?? AwnicEnum::UNKNOWN_ERROR,
                'message' => $issuePolicyResponse['message'] ?? null,
            ]);

            $response['error'] = $issuePolicyResponse['error'];
            $response['message'] = $issuePolicyResponse['message'];
            $response['status'] = false;

            return $response;
        }

        $issuePolicyResult = $issuePolicyResponse['data'];
        LoggerService::info('API call successful, updating quote and payment', extra: [
            'policy_number' => $issuePolicyResult?->policyInfo?->policyNo,
            'policy_start_date' => $issuePolicyResult?->policyInfo?->policyStartDate,
            'policy_end_date' => $issuePolicyResult?->policyInfo?->policyEndDate,
        ]);

        $this->quoteUpdater->updateQuoteFromIssuePolicyResponse($quote, $issuePolicyResult);
        $this->quoteUpdater->updatePaymentFromIssuePolicyResponse($quote->code, $issuePolicyResult);

        $response['status'] = true;
        $response['message'] = 'Policy issued successfully';
        $response['completed_step'] = AwnicEnum::STEP_ISSUE_POLICY;
        $response['data'] = $issuePolicyResult;

        return $response;
    }

    /**
     * Upload documents API call
     *
     * @param  mixed  $quote
     * @param  mixed  $policyIssuance
     */
    public function uploadDocuments($quote, $process): array
    {
        // Validate required documents and insurer quote number added before hitting api
        $requiredDocuments = $this->documentHandler->getDocumentByType($quote, DocumentTypeCode::CYB_EID);
        $validationResult = $this->validationService->validateUploadDocuments($quote, $requiredDocuments);
        $response = $this->responseHandler->buildStepResponse(AwnicEnum::STEP_UPLOAD_DOCUMENTS);
        $shouldExecute = $validationResult['status'];

        if (! $shouldExecute) {
            $response = $validationResult;
        } else {
            $documents = is_array($requiredDocuments) ? $requiredDocuments : [$requiredDocuments];
            $uploadOutcome = $this->uploadRequiredDocuments($quote, $process, $documents);

            $response['status'] = $uploadOutcome['status'];
            $response['message'] = $uploadOutcome['message'];
            $response['error'] = $uploadOutcome['error'];
            $response['completed_step'] = $uploadOutcome['completed_step'];
        }

        return $response;
    }

    private function uploadRequiredDocuments($quote, $process, array $documents): array
    {
        $endPoint = '/cyber/uploadDocument';
        $response = [
            'status' => false,
            'message' => null,
            'error' => null,
            'completed_step' => null,
        ];
        $allDocsUploaded = true;

        LoggerService::info('Starting document upload process', extra: [
            'endpoint' => $endPoint,
            'insurer_quote_number' => $quote->insurer_quote_number,
        ]);

        foreach ($documents as $requiredDocument) {
            $documentType = $this->documentHandler->getDocTypeCodeForCyber($requiredDocument['document_type_code']);
            $documentContentResponse = $this->documentHandler->fetchDocumentContent($requiredDocument['doc_url']);
            if (! $documentContentResponse['status']) {
                $response['message'] = $response['error'] = $documentContentResponse['message'];
                $allDocsUploaded = false;
                break;
            }

            $base64Content = base64_encode($documentContentResponse['content']);

            LoggerService::info('Preparing upload payload', extra: [
                'document_type' => $documentType,
                'document_name' => $requiredDocument['doc_name'] ?? 'Emirates_Id.png',
                'document_size_kb' => round(strlen($base64Content) / 1024, 2),
            ]);

            $payload = $this->requestBuilder->buildUploadDocumentsPayload($quote, $base64Content, $documentType, $requiredDocument['doc_name'] ?? 'Emirates_Id.png');

            $httpResponse = Awnic::post($endPoint, $payload);
            $uploadResponse = $this->responseHandler->parseHttpResponse($httpResponse, AwnicEnum::RESPONSE_UPLOAD_DOCUMENTS);

            app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $uploadResponse, Awnic::getBaseUrl().$endPoint, AwnicEnum::STEP_UPLOAD_DOCUMENTS, $uploadResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);

            if (! $uploadResponse['status']) {
                $response['message'] = $response['error'] = 'Some documents failed to upload';
                $allDocsUploaded = false;
                break;
            }
        }

        // this email is used to test the document upload automation failure scenario from insurer
        if ($quote->email == PolicyIssuanceEnum::FAKE_EMAIL_IMCRM_DOC_UPLOAD || ! $allDocsUploaded) {
            $response['error'] = $response['error'] ?? 'Some documents failed to upload';
            $response['message'] = $response['error'];
            $response['status'] = false;
        } else {
            LoggerService::info('Documents uploaded successfully');

            $response['status'] = true;
            $response['message'] = 'Documents uploaded successfully';
            $response['completed_step'] = AwnicEnum::STEP_UPLOAD_DOCUMENTS;
        }

        return $response;
    }

    /**
     * Upload policy documents to IMCRM
     *
     * @param  mixed  $quote
     * @param  mixed  $process
     */
    public function uploadPolicyDocumentsToIMCRM($quote, $process): array
    {
        LoggerService::info('Starting policy documents download and upload to IMCRM', extra: [
            'process_id' => $process->id,
            'step' => AwnicEnum::STEP_UPLOAD_POLICY_DOCS,
            'endpoint' => '/cyber/downloadDocument',
        ]);

        $response = $this->responseHandler->buildStepResponse(AwnicEnum::STEP_UPLOAD_POLICY_DOCS);
        $endPoint = '/cyber/downloadDocument';

        $uploadedDocumentsToIMCRM = collect();
        $quote->load('cyberQuote');
        $docTypeCodeForIMCRM = $this->documentHandler->getDocTypeCodeForIMCRM($quote);

        // validation added before hitting api to awnic for downloading document
        $validationResult = $this->validationService->validateDownloadDocuments($docTypeCodeForIMCRM);
        if (! $validationResult['status']) {
            return $validationResult;
        }

        foreach ($docTypeCodeForIMCRM as $imCrmDocKey => $docId) {
            $payload = $this->requestBuilder->buildDownloadDocumentPayload($docId);

            $httpResponse = Awnic::post($endPoint, $payload);
            $downloadRequest = $this->responseHandler->parseHttpResponse($httpResponse, AwnicEnum::RESPONSE_DOWNLOAD_DOCUMENT);

            app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $downloadRequest, Awnic::getBaseUrl().$endPoint, AwnicEnum::STEP_UPLOAD_POLICY_DOCS, $downloadRequest['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);

            if (isset($downloadRequest['status'])) {
                $docCode = $imCrmDocKey;

                $documentContent = $downloadRequest['data'];
                if ($downloadRequest['status'] && $documentContent && isset($documentContent->documentContent, $documentContent->documentName)) {
                    $quoteDocument = $this->documentHandler->uploadAndAttachToQuoteDocuments($quote, $documentContent->documentContent, $docCode, $documentContent->documentName);
                } else {
                    $quoteDocument = null;
                }

                $uploadedDocumentsToIMCRM->push([
                    'name' => $docId,
                    'uploaded' => $quoteDocument?->id ?? false,
                    'status' => $downloadRequest['status'],
                    'message' => $downloadRequest['message'] ?? 'Document Retrieve Failed',
                ]);
            }
        }

        $allDocsDownload = $uploadedDocumentsToIMCRM->where('status', true)->count() === 3;

        LoggerService::info('Document processing completed', extra: [
            'all_successful' => $allDocsDownload,
            'total_documents' => $uploadedDocumentsToIMCRM->count(),
            'successful_uploads' => $uploadedDocumentsToIMCRM->where('status', true)->count(),
            'failed_uploads' => $uploadedDocumentsToIMCRM->where('status', false)->count(),
        ]);

        // this email is used to test the document download automation failure scenario from insurer to IMCRM
        if (! $allDocsDownload || $uploadedDocumentsToIMCRM->isEmpty() || $quote->email == PolicyIssuanceEnum::FAKE_EMAIL_IMCRM_DOC_DOWNLOAD) {
            $docsUploadToIMCRMFailed = $uploadedDocumentsToIMCRM->where('status', false)->pluck('name')->toArray();
            LoggerService::error('Failed to fetch/upload all documents', extra: [
                'failed_documents' => $docsUploadToIMCRMFailed,
                'upload_summary' => $uploadedDocumentsToIMCRM->toArray(),
            ]);

            $error = 'Policy Issuance is pending as '.implode(',', $docsUploadToIMCRMFailed).' documents are not uploaded';
            $response['error'] = $error;
            $response['message'] = $error;
            $response['status'] = false;

            return $response;
        }

        LoggerService::info('All documents fetched and uploaded successfully', extra: [
            'uploaded_documents' => $uploadedDocumentsToIMCRM->pluck('name')->toArray(),
        ]);

        $response['status'] = true;
        $response['message'] = 'Fetched all documents from insurer and Uploaded to IMCRM';
        $response['completed_step'] = AwnicEnum::STEP_UPLOAD_POLICY_DOCS;

        return $response;
    }
}
