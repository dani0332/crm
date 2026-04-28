<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\AdnicEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Facades\AdnicHttpFacade;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

class AdnicApiService
{
    public function __construct(
        private AdnicRequestBuilder $requestBuilder,
        private AdnicResponseHandler $responseHandler,
        private AdnicDocumentHandler $documentHandler,
        private AdnicQuoteUpdaterService $quoteUpdater,
        private AdnicValidationService $validationService,
    ) {}

    /**
     * Issue policy API call
     *
     * @param  mixed  $quote
     * @param  mixed  $process
     */
    public function issuePolicy($quote, $process, $healthInsurerRequestResponse): array
    {
        LoggerService::info('Initiating policy issuance API call', extra: [
            'process_id' => $process->id,
            'step' => AdnicEnum::STEP_ISSUE_POLICY,
            'policy_start_date' => $quote->policy_start_date,
        ]);

        $response = $this->responseHandler->buildStepResponse(AdnicEnum::STEP_ISSUE_POLICY);
        $endPoint = '/GeneratePolicy';

        $payment = $quote->payments()->mainLeadPayment()->first();
        $splitPayment = $payment?->paymentSplits()->where('payment_method', PaymentMethodsEnum::CreditCard)->first();

        $payload = $this->requestBuilder->buildIssuePolicyPayload($quote, $process, $healthInsurerRequestResponse, $splitPayment);

        $httpResponse = AdnicHttpFacade::post($endPoint, $payload);
        $issuePolicyResponse = $this->responseHandler->parseHttpResponse($httpResponse, AdnicEnum::RESPONSE_POLICY);

        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $issuePolicyResponse, AdnicHttpFacade::getBaseUrl().$endPoint, AdnicEnum::STEP_ISSUE_POLICY, $issuePolicyResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);

        if (! $issuePolicyResponse['status']) {
            LoggerService::error('API call failed', extra: [
                'endpoint' => $endPoint,
                'error' => $issuePolicyResponse['error'] ?? AdnicEnum::UNKNOWN_ERROR,
                'message' => $issuePolicyResponse['message'] ?? null,
            ]);

            $response['error'] = $issuePolicyResponse['error'];
            $response['message'] = $issuePolicyResponse['message'];
            $response['status'] = false;

            return $response;
        }

        $issuePolicyResult = $issuePolicyResponse['data'];
        LoggerService::info('API call successful, updating quote and payment', extra: [
            'policy_number' => $issuePolicyResult?->PolicyInfo?->PolicyNo,
            'policy_start_date' => $issuePolicyResult?->PolicyInfo?->PolicyStartDate,
            'policy_end_date' => $issuePolicyResult?->PolicyInfo?->PolicyEndDate,
        ]);

        $this->quoteUpdater->updateQuoteFromIssuePolicyResponse($quote, $issuePolicyResult);
        $this->quoteUpdater->updatePaymentFromIssuePolicyResponse($quote->code, $issuePolicyResult);

        $response['status'] = true;
        $response['message'] = 'Policy issued successfully';
        $response['completed_step'] = AdnicEnum::STEP_ISSUE_POLICY;
        $response['data'] = $issuePolicyResult;

        return $response;
    }

    /**
     * Upload documents API call
     *
     * @param  mixed  $quote
     * @param  mixed  $policyIssuance
     */
    public function uploadDocuments($quote, $process, $healthInsurerRequestResponse): array
    {
        $healthInsurerRequest = json_decode($healthInsurerRequestResponse->request);
        $healthInsurerResponse = json_decode($healthInsurerRequestResponse->response);

        $insuredInfoDetails = [];
        if ($healthInsurerRequest && isset($healthInsurerRequest->InsuredInfo)) {
            $insuredInfoDetails = $healthInsurerRequest->InsuredInfo;
        }

        // Validate required documents and insurer quote number added before hitting api
        $documentsToUpload = $this->documentHandler->getQuoteDocumentTypeCodessToUpload();
        $quoteDocumentTypeCodes = $documentsToUpload->keys()->toArray();
        $quoteDocuments = $this->documentHandler->getDocumentByType($quote, $quoteDocumentTypeCodes);

        $documentValidation = $this->validationService->validateUploadDocuments($quote, $quoteDocuments, $insuredInfoDetails);
        if (! $documentValidation['status']) {
            return $this->responseHandler->buildStepResponse(
                AdnicEnum::STEP_UPLOAD_DOCUMENTS,
                false,
                $documentValidation['message'] ?? 'Document validation failed',
                $documentValidation['error'] ?? null
            );
        }

        return $this->uploadDocumentsAfterValidation($quote, $process, $healthInsurerResponse, $insuredInfoDetails, $quoteDocuments);
    }

    /**
     * @param  mixed  $quote
     * @param  mixed  $process
     * @param  mixed  $healthInsurerResponse
     * @param  mixed  $quoteDocuments
     */
    private function uploadDocumentsAfterValidation($quote, $process, $healthInsurerResponse, array $insuredInfoDetails, $quoteDocuments): array
    {
        $endPoint = '/UploadDocument';
        $response = $this->responseHandler->buildStepResponse(AdnicEnum::STEP_UPLOAD_DOCUMENTS);

        LoggerService::info('Starting document upload process', extra: [
            'endpoint' => $endPoint,
            'insurer_quote_number' => $quote->insurer_quote_number,
        ]);

        $allInsurerUploadsSucceeded = true;

        foreach ($insuredInfoDetails as $memberIndex => $insuredMember) {
            $memberSeqNo = $insuredMember?->MemberSeqNo ?? $memberIndex;
            $memberDocumentUploads[$memberSeqNo] = [];

            foreach ($quoteDocuments as $quoteDocument) {
                $insurerDocumentCode = $this->documentHandler->getInsurerDocCodeForHealth($quoteDocument->document_type_code);
                $documentContentResponse = $this->documentHandler->fetchDocumentContent($quoteDocument->doc_url);
                if (! $documentContentResponse['status']) {
                    $response['message'] = $response['error'] = $documentContentResponse['message'];

                    return $response;
                }

                $base64Content = base64_encode($documentContentResponse['content']);

                LoggerService::info('Preparing upload payload', extra: [
                    'quote_uuid' => $quote->uuid,
                    'document_type' => $quoteDocument->document_type_code,
                    'insurer_document_code' => $insurerDocumentCode,
                    'document_name' => $quoteDocument->original_name ?? $quoteDocument->doc_name,
                    'document_string_length' => strlen($base64Content),
                    'document_size_kb' => round(strlen($base64Content) / 1024, 2),
                ]);

                $payload = $this->requestBuilder->buildUploadDocumentsPayload($base64Content, $healthInsurerResponse, $insuredMember, $insurerDocumentCode, $quoteDocument);

                $httpResponse = AdnicHttpFacade::post($endPoint, $payload);
                $uploadDocResponse = $this->responseHandler->parseHttpResponse($httpResponse, AdnicEnum::RESPONSE_UPLOAD_DOCUMENTS);
                $uploadDocResponse['MemberSeqNo'] = $memberSeqNo;

                app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $uploadDocResponse, AdnicHttpFacade::getBaseUrl().$endPoint, AdnicEnum::STEP_UPLOAD_DOCUMENTS, $uploadDocResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);

                if (! $uploadDocResponse['status']) {
                    LoggerService::info('Document upload failed', extra: [
                        'document_type' => $quoteDocument->document_type_code,
                        'insurer_document_code' => $insurerDocumentCode,
                        'document_name' => $quoteDocument->original_name ?? $quoteDocument->doc_name,
                        'message' => $uploadDocResponse['message'] ?? 'Document upload failed',
                    ]);

                    $allInsurerUploadsSucceeded = false;
                    break 2;
                }
            }
        }

        if (! $allInsurerUploadsSucceeded) {
            $response['message'] = 'Some documents failed to upload';
            $response['error'] = 'Some documents failed to upload';
            $response['status'] = false;

            return $response;
        }

        LoggerService::info('Documents uploaded successfully');

        $response['status'] = true;
        $response['message'] = 'Documents uploaded successfully';
        $response['completed_step'] = AdnicEnum::STEP_UPLOAD_DOCUMENTS;

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
            'step' => AdnicEnum::STEP_UPLOAD_POLICY_DOCS,
            'endpoint' => '/GeneratePolicyDocument',
        ]);

        $response = $this->responseHandler->buildStepResponse(AdnicEnum::STEP_UPLOAD_POLICY_DOCS);

        $generatePolicyResponse = $process->policyIssuanceLogs()->where([
            'step' => AdnicEnum::STEP_ISSUE_POLICY,
            'status' => PolicyIssuanceEnum::SUCCESS_STATUS,
        ])->latest()->first();

        if (! $generatePolicyResponse) {
            $response['error'] = 'Policy generation response not found in process data';

            return $response;
        }

        return $this->uploadPolicyDocumentsToIMCRMFromIssueLog($quote, $process, $generatePolicyResponse, $response);
    }

    /**
     * @param  mixed  $quote
     * @param  mixed  $process
     * @param  mixed  $issuePolicyLog
     * @param  array<string, mixed>  $response
     */
    private function uploadPolicyDocumentsToIMCRMFromIssueLog($quote, $process, $issuePolicyLog, array $response): array
    {
        $endPoint = '/GeneratePolicyDocument';

        $uploadedDocumentsToIMCRM = collect();

        // validation added before hitting api to awnic for downloading document

        $decodedIssuePolicy = $issuePolicyLog?->response ? json_decode($issuePolicyLog->response) : null;
        $policyIssueResponse = $decodedIssuePolicy?->data;
        $policyDocuments = data_get($policyIssueResponse, 'PolicyDocumentInfo');

        if ($policyDocuments === null) {
            LoggerService::error('PolicyDocumentInfo missing from policy issue response', extra: [
                'process_id' => $process->id,
                'quote_uuid' => data_get($quote, 'uuid'),
            ]);
            $response['error'] = 'Policy document list not found in policy issue response';
            $response['message'] = $response['error'];
            $response['status'] = false;

            return $response;
        }

        foreach ($policyDocuments as $policyDocumentKey => $policyDocumentId) {
            $payload = $this->requestBuilder->buildDownloadDocumentPayload($policyIssueResponse, $policyDocumentId);

            $httpResponse = AdnicHttpFacade::post($endPoint, $payload);
            $downloadReponse = $this->responseHandler->parseHttpResponse($httpResponse, AdnicEnum::RESPONSE_DOWNLOAD_DOCUMENT);

            app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $downloadReponse, AdnicHttpFacade::getBaseUrl().$endPoint, AdnicEnum::STEP_UPLOAD_POLICY_DOCS, $downloadReponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);

            if (isset($downloadReponse['status'])) {
                $docCode = $this->documentHandler->getQuoteDocumentMappingForInsurerDocuments($policyDocumentKey);

                $documentContent = $downloadReponse['data']?->PolicyDocumentInfo?->DocumentContent ?? null;
                $documentName = $downloadReponse['data']?->PolicyDocumentInfo?->DocumentName ?? null;

                if ($downloadReponse['status'] && $documentContent && $documentName) {
                    $quoteDocument = $this->documentHandler->uploadAndAttachToQuoteDocuments($quote, $documentContent, $docCode, $documentName);
                } else {
                    $quoteDocument = null;
                }

                $uploadedDocumentsToIMCRM->push([
                    'name' => $policyDocumentKey,
                    'uploaded' => $quoteDocument?->id ?? false,
                    'status' => $downloadReponse['status'],
                    'message' => $downloadReponse['message'] ?? 'Document Retrieve Failed',
                ]);
            }
        }

        /*
         * ADNIC issue-policy returns PolicyDocumentInfo with exactly three document slots (policy document,
         * commission note, tax invoice — see AdnicEnum::INSURER_DOCUMENT_KEY_*). The literal 3 is intentional:
         * we only treat the step as complete when all three downloads succeed. If ADNIC changes the number of
         * documents, this check fails so the integration mismatch is visible in logs and monitoring. Do not
         * replace with count($policyDocuments) unless the insurer contract and IMCRM mappings are updated together.
         */
        $allDocsDownload = $uploadedDocumentsToIMCRM->where('status', true)->count() === 3;

        LoggerService::info('Document processing completed', extra: [
            'all_successful' => $allDocsDownload,
            'total_documents' => $uploadedDocumentsToIMCRM->count(),
            'successful_uploads' => $uploadedDocumentsToIMCRM->where('status', true)->count(),
            'failed_uploads' => $uploadedDocumentsToIMCRM->where('status', false)->count(),
        ]);

        if (! $allDocsDownload || $uploadedDocumentsToIMCRM->isEmpty()) {
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
        $response['completed_step'] = AdnicEnum::STEP_UPLOAD_POLICY_DOCS;

        return $response;
    }

}
