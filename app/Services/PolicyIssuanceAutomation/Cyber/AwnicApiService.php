<?php

namespace App\Services\PolicyIssuanceAutomation\Cyber;

use App\Enums\DocumentTypeCode;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Facades\Awnic;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

class AwnicApiService
{
    private string $className = 'AwnicApiService';

    public const ISSUE_POLICY = 'IssuePolicy';
    public const UPLOAD_DOCUMENTS = 'UploadDocuments';
    public const UPLOAD_POLICY_DOCUMENTS_TO_IMCRM = 'UploadPolicyDocumentsToIMCRM';
    public const POLICY_ISSUANCE_RESPONSE = 'PolicyResponse';
    public const UPLOAD_DOCUMENTS_RESPONSE = 'UploadDocumentsResponse';
    public const DOWNLOAD_DOCUMENT_RESPONSE = 'DownloadDocumentResponse';

    public function __construct(
        private AwnicRequestBuilder $requestBuilder,
        private AwnicResponseHandler $responseHandler,
        private AwnicDocumentHandler $documentHandler,
    ) {}

    /**
     * Issue policy API call
     *
     * @param mixed $quote
     * @param mixed $process
     * @return array
     */
    public function issuePolicy($quote, $process): array
    {
        LoggerService::info('Initiating policy issuance API call', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'process_id' => $process->id,
            'step' => self::ISSUE_POLICY,
            'policy_start_date' => $quote->policy_start_date,
        ]);

        $response = $this->responseHandler->buildStepResponse(self::ISSUE_POLICY);
        $endPoint = '/cyber/generatePolicy';
        
        $customer = $quote->customer;
        $nationality = $quote->nationality;
        $planDetail = $quote->cyberPlanDetail;

        $payment = $quote->payments()->mainLeadPayment()->first();
        $splitPayment = $payment?->paymentSplits()->where('payment_method', PaymentMethodsEnum::CreditCard)->first();

        $payload = $this->requestBuilder->buildIssuePolicyPayload($quote, $customer, $nationality, $planDetail, $payment, $splitPayment);

        $issuePolicy = Awnic::post($endPoint, $payload, self::POLICY_ISSUANCE_RESPONSE);
        
        app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $issuePolicy, Awnic::getBaseUrl() . $endPoint, self::ISSUE_POLICY, $issuePolicy['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);

        if (! $issuePolicy['status']) {
            LoggerService::error('API call failed', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'endpoint' => $endPoint,
                'error' => $issuePolicy['error'] ?? 'Unknown error',
                'message' => $issuePolicy['message'] ?? null,
            ]);

            $response['error'] = $issuePolicy['error'];
            $response['message'] = $issuePolicy['message'];
            $response['status'] = false;

            return $response;
        }

        $issuePolicyResult = $issuePolicy['data'];
        LoggerService::info('API call successful, updating quote and payment', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'policy_number' => $issuePolicyResult?->policyInfo?->policyNo,
            'policy_start_date' => $issuePolicyResult?->policyInfo?->policyStartDate,
            'policy_end_date' => $issuePolicyResult?->policyInfo?->policyEndDate,
        ]);

        $this->responseHandler->updateQuoteFromIssuePolicyResponse($quote, $issuePolicyResult);
        $this->responseHandler->updatePaymentFromIssuePolicyResponse($quote->code, $issuePolicyResult);
        
        $response['status'] = true;
        $response['message'] = 'Policy issued successfully';
        $response['completed_step'] = self::ISSUE_POLICY;
        $response['data'] = $issuePolicyResult;

        return $response;
    }

    /**
     * Upload documents API call
     *
     * @param mixed $quote
     * @param mixed $policyIssuance
     * @return array
     */
    public function uploadDocuments($quote, $process): array
    {
        $endPoint = '/cyber/uploadDocument';
        $response = $this->responseHandler->buildStepResponse(self::UPLOAD_DOCUMENTS);
        
        LoggerService::info('Starting document upload process', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'endpoint' => $endPoint,
            'insurer_quote_number' => $quote->insurer_quote_number,
        ]);

        $requiredDocuments = $this->documentHandler->getDocumentByType($quote, DocumentTypeCode::CYBER_EMIRATES_ID);
        if (! $requiredDocuments || empty($requiredDocuments)) {
            $errorMessage = 'Required Documents not uploaded';
            LoggerService::warning('Missing required document', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'required_document_type' => DocumentTypeCode::CYBER_EMIRATES_ID,
                'available_documents' => collect($quote->documents ?? [])->pluck('document_type_code')->toArray(),
            ]);
            $response['message'] = $response['error'] = $errorMessage;

            return $response;
        }

        $allDocsDownloaded = true;
        $documentContentResponses = [];
        foreach ($requiredDocuments as $requiredDocument) {
            $documentType = $this->documentHandler->getDocTypeCodeForCyber($requiredDocument['document_type_code']);
            $documentContentResponse = $this->documentHandler->fetchDocumentContent($requiredDocument['doc_url']);
            if (! $documentContentResponse['status']) {
                $response['message'] = $response['error'] = $documentContentResponse['message'];

                return $response;
            }

            $base64Content = base64_encode($documentContentResponse['content']);

            LoggerService::info('Preparing upload payload', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'document_type' => $documentType,
                'document_name' => $requiredDocument['doc_name'] ?? 'Emirates_Id.png',
                'document_size_kb' => round(strlen($base64Content) / 1024, 2),
            ]);

            $payload = $this->requestBuilder->buildUploadDocumentsPayload($quote, $base64Content, $documentType);

            $uploadResponse = Awnic::post($endPoint, $payload, self::UPLOAD_DOCUMENTS_RESPONSE);
            $documentContentResponses[] = $uploadResponse;

            app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $uploadResponse, Awnic::getBaseUrl() . $endPoint, self::UPLOAD_DOCUMENTS, $uploadResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);

            if (! $uploadResponse['status']) {
                $allDocsDownloaded = false;
                break;
            }
        }

        if (! $allDocsDownloaded) {
            $response['message'] = 'Some documents failed to upload';
            $response['error'] = 'Some documents failed to upload';
            $response['status'] = false;

            return $response;
        }

        LoggerService::info('Documents uploaded successfully', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
        ]);

        $response['status'] = true;
        $response['message'] = 'Documents uploaded successfully';
        $response['completed_step'] = self::UPLOAD_DOCUMENTS;
        $response['data'] = $uploadResponse;

        return $response;
    }

    /**
     * Upload policy documents to IMCRM
     *
     * @param mixed $quote
     * @param mixed $process
     * @return array
     */
    public function uploadPolicyDocumentsToIMCRM($quote, $process): array
    {
        LoggerService::info('Starting policy documents download and upload to IMCRM', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'process_id' => $process->id,
            'step' => self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            'endpoint' => '/cyber/downloadDocument',
        ]);

        $response = $this->responseHandler->buildStepResponse(self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM);
        $endPoint = '/cyber/downloadDocument';

        $uploadedDocumentsToIMCRM = collect();

        $cyberQuote = $quote->cyberQuote;
        foreach ($this->documentHandler->getDocTypeCodeForIMCRM($cyberQuote) as $i => $docId) {
            $payload = $this->requestBuilder->buildDownloadDocumentPayload($docId);

            $downloadRequest = Awnic::post($endPoint, $payload, self::DOWNLOAD_DOCUMENT_RESPONSE);

            app(PolicyIssuanceService::class)->storePolicyIssuanceLog($quote, $payload, $downloadRequest, Awnic::getBaseUrl() . $endPoint, self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM, $downloadRequest['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS, $process);
            
            if(isset($downloadRequest['status'])) {
                $docCode = $i;

                $documentContent = $downloadRequest['data'];
                // TODO: need to map document according to IMCRM cyber document types
                if ($documentContent && isset($documentContent->documentContent, $documentContent->documentName)) {
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
        };

        $allDocsDownload = $uploadedDocumentsToIMCRM->where('status', true)->count() === 3;

        LoggerService::info('Document processing completed', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'all_successful' => $allDocsDownload,
            'total_documents' => $uploadedDocumentsToIMCRM->count(),
            'successful_uploads' => $uploadedDocumentsToIMCRM->where('status', true)->count(),
            'failed_uploads' => $uploadedDocumentsToIMCRM->where('status', false)->count(),
        ]);

        if (! $allDocsDownload || empty($uploadedDocumentsToIMCRM)) {
            $docsUploadToIMCRMFailed = $uploadedDocumentsToIMCRM->where('status', false)->pluck('name')->toArray();
            LoggerService::error('Failed to fetch/upload all documents', extra: [
                'class' => $this->className,
                'function' => __FUNCTION__,
                'failed_documents' => $docsUploadToIMCRMFailed,
                'upload_summary' => $uploadedDocumentsToIMCRM->toArray(),
            ]);

            $error = 'Policy Issuance is pending as ' . implode(',', $docsUploadToIMCRMFailed) . ' documents are not uploaded';
            $response['error'] = $error;
            $response['message'] = $error;
            $response['status'] = false;

            return $response;
        }

        LoggerService::info('All documents fetched and uploaded successfully', extra: [
            'class' => $this->className,
            'function' => __FUNCTION__,
            'uploaded_documents' => $uploadedDocumentsToIMCRM->pluck('name')->toArray(),
        ]);

        $response['status'] = true;
        $response['message'] = 'Fetched all documents from insurer and Uploaded to IMCRM';
        $response['completed_step'] = self::UPLOAD_POLICY_DOCUMENTS_TO_IMCRM;

        return $response;
    }
}

