<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Health\Adnic;

use App\Enums\AdnicEnum;
use App\Enums\DocumentTypeCode;
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
    public function issuePolicy($quote, $process): array
    {
        LoggerService::info('Initiating policy issuance API call', extra: [
            'process_id' => $process->id,
            'step' => AdnicEnum::STEP_ISSUE_POLICY,
            'policy_start_date' => $quote->policy_start_date,
        ]);

        $response = $this->responseHandler->buildStepResponse(AdnicEnum::STEP_ISSUE_POLICY);
        $endPoint = '/cyber/generatePolicy';

        $customer = $quote->customer;
        $nationality = $quote->nationality;
        $emirateOfRegistration = $quote->cyberQuote->emirateOfRegistration;
        $planDetail = $quote->cyberPlanDetail;

        $payment = $quote->payments()->mainLeadPayment()->first();
        $splitPayment = $payment?->paymentSplits()->where('payment_method', PaymentMethodsEnum::CreditCard)->first();

        $payload = $this->requestBuilder->buildIssuePolicyPayload($quote, $customer, $nationality, $planDetail, $splitPayment, $emirateOfRegistration);
        $headers = $this->requestBuilder->buildIssuePolicyHeaders();

        $httpResponse = AdnicHttpFacade::post($endPoint, $payload, $headers);
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
            'policy_number' => $issuePolicyResult?->policyInfo?->policyNo,
            'policy_start_date' => $issuePolicyResult?->policyInfo?->policyStartDate,
            'policy_end_date' => $issuePolicyResult?->policyInfo?->policyEndDate,
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
        $validationResult = $this->validationService->validateUploadDocuments($quote, $quoteDocuments, $insuredInfoDetails);
        if (! $validationResult['status']) {
            return $validationResult;
        }

        $endPoint = '/UploadDocument';
        $response = $this->responseHandler->buildStepResponse(AdnicEnum::STEP_UPLOAD_DOCUMENTS);

        LoggerService::info('Starting document upload process', extra: [
            'endpoint' => $endPoint,
            'insurer_quote_number' => $quote->insurer_quote_number,
        ]);

        $allDocsDownloaded = true;
        $documents = is_array($quoteDocuments) ? $quoteDocuments : [$quoteDocuments];

        foreach ($insuredInfoDetails as $memberIndex => $insuredMember) {
            $memberSeqNo = $insuredMember?->MemberSeqNo ?? $memberIndex;
            $memberDocumentUploads[$memberSeqNo] = [];

            foreach ($documents as $docTypeCode => $quoteDocument) {
                $quoteDocument = $quoteDocuments->firstWhere('document_type_code', $docTypeCode);
                $insurerDocumentCode = $this->documentHandler->getInsurerDocCodeForHealth($quoteDocument->document_type_code);
                $documentContentResponse = $this->documentHandler->fetchDocumentContent($quoteDocument->doc_url);
                if (! $documentContentResponse['status']) {
                    $response['message'] = $response['error'] = $documentContentResponse['message'];

                    return $response;
                }

                $base64Content = base64_encode($documentContentResponse['content']);

                LoggerService::info('Preparing upload payload', extra: [
                    'document_type' => $docTypeCode,
                    'insurer_document_code' => $insurerDocumentCode,
                    'document_name' => $quoteDocument->original_name ?? $quoteDocument->doc_name,
                    'document_size_kb' => round(strlen($base64Content) / 1024, 2),
                ]);

                $payload = $this->requestBuilder->buildUploadDocumentsPayload($base64Content, $healthInsurerResponse, $insuredMember, $insurerDocumentCode, $quoteDocument);

                $httpResponse = AdnicHttpFacade::post($endPoint, $payload);
                $uploadDocResponse = $this->responseHandler->parseHttpResponse($httpResponse, AdnicEnum::RESPONSE_UPLOAD_DOCUMENTS);
                $uploadDocResponse['MemberSeqNo'] = $memberSeqNo;

                app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $uploadDocResponse, AdnicHttpFacade::getBaseUrl().$endPoint, AdnicEnum::STEP_UPLOAD_DOCUMENTS, $uploadDocResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);

                if (! $uploadDocResponse['status']) {
                    $allDocsDownloaded = false;
                    break;
                }
            }
        }


        if (! $allDocsDownloaded) {
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
            'endpoint' => '/cyber/downloadDocument',
        ]);

        $response = $this->responseHandler->buildStepResponse(AdnicEnum::STEP_UPLOAD_POLICY_DOCS);
        $endPoint = '/cyber/downloadDocument';

        $uploadedDocumentsToIMCRM = collect();
        $docTypeCodeForIMCRM = $this->documentHandler->getDocTypeCodeForIMCRM($quote);

        // validation added before hitting api to awnic for downloading document
        $validationResult = $this->validationService->validateDownloadDocuments($quote, $docTypeCodeForIMCRM);
        if (! $validationResult['status']) {
            return $validationResult;
        }

        foreach ($docTypeCodeForIMCRM as $imCrmDocKey => $docId) {
            $payload = $this->requestBuilder->buildDownloadDocumentPayload($docId);

            $httpResponse = AdnicHttpFacade::post($endPoint, $payload);
            $downloadRequest = $this->responseHandler->parseHttpResponse($httpResponse, AdnicEnum::RESPONSE_DOWNLOAD_DOCUMENT);

            app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $downloadRequest, AdnicHttpFacade::getBaseUrl().$endPoint, AdnicEnum::STEP_UPLOAD_POLICY_DOCS, $downloadRequest['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);

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
