<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\DocumentTypeCode;
use App\Enums\NgiEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Facades\Ngi;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

class NgiApiService
{
    public function __construct(
        private NgiRequestBuilder $requestBuilder,
        private NgiResponseHandler $responseHandler,
        private NgiDocumentHandler $documentHandler,
        private NgiQuoteUpdaterService $quoteUpdater,
        private NgiValidationService $validationService,
    ) {}

    /**
     * Create policy from quote API call
     *
     * @param mixed $quote
     * @param mixed $process
     * @return array
     */
    public function createPolicyFromQuote($quote, $process, $customer = null, $deviceQuote = null, $latestInsured = null): array
    {
        LoggerService::info('Initiating CreatePolicyFromQuote API call', extra: [
            'process_id' => $process->id,
            'step' => NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
            'policy_start_date' => $quote->policy_start_date,
        ]);

        $response = $this->responseHandler->buildStepResponse(NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE);
        $endPoint = '/api/Policy/CreatePolicyFromQuoteBW';

        $customer = $customer??$quote?->customer;
        $deviceQuote = $deviceQuote??$quote?->deviceQuote;
        $latestInsured = $latestInsured??$quote?->latestInsured;

        $payment = $quote->payments()->mainLeadPayment()->first();
        $splitPayment = $payment?->paymentSplits()->where('payment_method', PaymentMethodsEnum::CreditCard)->first();

        $payload = $this->requestBuilder->buildCreatePolicyFromQuotePayload($quote, $customer, $deviceQuote, $splitPayment ?? $payment, $latestInsured);
        $headers = $this->requestBuilder->buildCreatePolicyHeaders();

        $httpResponse = Ngi::post($endPoint, $payload, $headers);
        $createPolicyResponse = $this->responseHandler->parseHttpResponse($httpResponse, NgiEnum::RESPONSE_CREATE_POLICY);

        app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
            $quote,
            $payload,
            $createPolicyResponse,
            Ngi::getBaseUrl() . $endPoint,
            NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE,
            $createPolicyResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS,
            $process
        );

        if (! $createPolicyResponse['status']) {
            LoggerService::error('CreatePolicyFromQuote API call failed', extra: [
                'endpoint' => $endPoint,
                'error' => $createPolicyResponse['error'] ?? NgiEnum::UNKNOWN_ERROR,
                'message' => $createPolicyResponse['message'] ?? null,
            ]);

            $response['error'] = $createPolicyResponse['error'];
            $response['message'] = $createPolicyResponse['message'];
            $response['status'] = false;

            return $response;
        }

        $createPolicyResult = $createPolicyResponse['data'];
        LoggerService::info('CreatePolicyFromQuote API call successful, updating quote', extra: [
            'policy_number' => $createPolicyResult?->policy_no,
            'policy_start_date' => $createPolicyResult?->policy_start_dt,
            'policy_end_date' => $createPolicyResult?->policy_end_dt,
        ]);

        $this->quoteUpdater->updateQuoteFromCreatePolicyResponse($quote, $createPolicyResult);

        $response['status'] = true;
        $response['message'] = 'Policy created successfully';
        $response['completed_step'] = NgiEnum::STEP_CREATE_POLICY_FROM_QUOTE;
        $response['data'] = $createPolicyResult;

        return $response;
    }

    /**
     * Get policy documents API call
     *
     * @param mixed $quote
     * @param mixed $process
     * @return array
     */
    public function getPolicyDocuments($quote, $process, $customer = null, $deviceQuote = null, $latestInsured = null): array
    {
        LoggerService::info('Initiating GetPolicyDocuments API call', extra: [
            'process_id' => $process->id,
            'step' => NgiEnum::STEP_GET_POLICY_DOCUMENTS,
            'policy_number' => $quote->policy_number,
        ]);

        $response = $this->responseHandler->buildStepResponse(NgiEnum::STEP_GET_POLICY_DOCUMENTS);

        // Validate policy number exists
        $validationResult = $this->validationService->validatePolicyNumberExists($quote);
        if (! $validationResult['status']) {
            return $validationResult;
        }

        $endPoint = '/api/Policy/GetPolicyDocuments';
        $queryParams = ['policyNumber' => $quote->policy_number];

        $httpResponse = Ngi::get($endPoint, $queryParams);
        $policyDocumentsResponse = $this->responseHandler->parseHttpResponse($httpResponse, NgiEnum::RESPONSE_GET_POLICY_DOCUMENTS);

        app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
            $quote,
            $queryParams,
            $policyDocumentsResponse,
            Ngi::getBaseUrl() . $endPoint . '?' . http_build_query($queryParams),
            NgiEnum::STEP_GET_POLICY_DOCUMENTS,
            $policyDocumentsResponse['status'] ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS,
            $process
        );

        if (! $policyDocumentsResponse['status']) {
            LoggerService::error('GetPolicyDocuments API call failed', extra: [
                'endpoint' => $endPoint,
                'policy_number' => $quote->policy_number,
                'error' => $policyDocumentsResponse['error'] ?? NgiEnum::UNKNOWN_ERROR,
                'message' => $policyDocumentsResponse['message'] ?? null,
            ]);

            $response['error'] = $policyDocumentsResponse['error'];
            $response['message'] = $policyDocumentsResponse['message'];
            $response['status'] = false;

            return $response;
        }

        $policyDocumentsResult = $policyDocumentsResponse['data'];
        LoggerService::info('GetPolicyDocuments API call successful, updating quote and payment', extra: [
            'policy_number' => $policyDocumentsResult?->policy_no,
            'has_policy_certificate_url' => ! empty($policyDocumentsResult?->policy_certificate_url),
            'has_premium_inv_url' => ! empty($policyDocumentsResult?->premium_inv_doc_url),
            'has_commission_inv_url' => ! empty($policyDocumentsResult?->commision_inv_doc_url),
        ]);

        $this->quoteUpdater->updateQuoteFromPolicyDocumentsResponse($quote, $policyDocumentsResult);
        $this->quoteUpdater->updatePaymentFromPolicyDocumentsResponse($quote->code, $policyDocumentsResult);

        $response['status'] = true;
        $response['message'] = 'Policy documents retrieved successfully';
        $response['completed_step'] = NgiEnum::STEP_GET_POLICY_DOCUMENTS;
        $response['data'] = $policyDocumentsResult;

        return $response;
    }

    /**
     * Upload policy documents to IMCRM
     *
     * @param mixed $quote
     * @param mixed $process
     * @return array
     */
    public function uploadPolicyDocumentsToIMCRM($quote, $process, $customer = null, $deviceQuote = null, $latestInsured = null): array
    {
        LoggerService::info('Starting policy documents download and upload to IMCRM', extra: [
            'process_id' => $process->id,
            'step' => NgiEnum::STEP_UPLOAD_POLICY_DOCS,
        ]);

        $response = $this->responseHandler->buildStepResponse(NgiEnum::STEP_UPLOAD_POLICY_DOCS);

        // Get document URLs from quote (stored during GetPolicyDocuments step)
        $quote->refresh();
        $documentUrls = [
            DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_SCHEDULE => $quote->insurer_policy_doc_id,
            DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE => $quote->insurer_tax_invoice_doc_id,
            DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE_RAISED_BY_BUYER => $quote->insurer_debit_note_doc_id,
        ];

        // Validate document URLs exist
        $validationResult = $this->validationService->validateDownloadDocuments($quote, $documentUrls);
        if (! $validationResult['status']) {
            return $validationResult;
        }

        $uploadedDocumentsToIMCRM = collect();

        foreach ($documentUrls as $docCode => $documentUrl) {
            LoggerService::info('Downloading document from NGI', extra: [
                'document_code' => $docCode,
                'document_url' => $documentUrl,
            ]);

            // Download document from URL
            $documentContentResponse = $this->documentHandler->fetchDocumentFromUrl($documentUrl);

            if (! $documentContentResponse['status']) {
                $uploadedDocumentsToIMCRM->push([
                    'name' => $docCode,
                    'uploaded' => false,
                    'status' => false,
                    'message' => $documentContentResponse['message'] ?? 'Document Download Failed',
                ]);
                continue;
            }

            // Determine file name based on document type
            $fileName = $this->getDocumentFileName($docCode, $quote->policy_number);

            // Encode content to base64 for upload
            $base64Content = base64_encode($documentContentResponse['content']);

            LoggerService::info('Uploading document to IMCRM', extra: [
                'document_code' => $docCode,
                'file_name' => $fileName,
            ]);

            // Upload to IMCRM
            $quoteDocument = $this->documentHandler->uploadAndAttachToQuoteDocuments(
                $quote,
                $base64Content,
                $docCode,
                $fileName
            );

            app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
                $quote,
                ['document_url' => $documentUrl, 'document_code' => $docCode],
                ['uploaded' => (bool) $quoteDocument?->id],
                $documentUrl,
                NgiEnum::STEP_UPLOAD_POLICY_DOCS,
                $quoteDocument?->id ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS,
                $process
            );

            $uploadedDocumentsToIMCRM->push([
                'name' => $docCode,
                'uploaded' => $quoteDocument?->id ?? false,
                'status' => (bool) $quoteDocument?->id,
                'message' => $quoteDocument?->id ? 'Document Uploaded Successfully' : 'Document Upload Failed',
            ]);
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

            $error = 'Policy Issuance is pending as ' . implode(', ', $docsUploadToIMCRMFailed) . ' documents are not uploaded';
            $response['error'] = $error;
            $response['message'] = $error;
            $response['status'] = false;

            return $response;
        }

        LoggerService::info('All documents fetched and uploaded successfully', extra: [
            'uploaded_documents' => $uploadedDocumentsToIMCRM->pluck('name')->toArray(),
        ]);

        $response['status'] = true;
        $response['message'] = 'Fetched all documents from insurer and uploaded to IMCRM';
        $response['completed_step'] = NgiEnum::STEP_UPLOAD_POLICY_DOCS;

        return $response;
    }

    /**
     * Get document file name based on document type
     *
     * @param string $docCode
     * @param string|null $policyNumber
     * @return string
     */
    private function getDocumentFileName(string $docCode, ?string $policyNumber): string
    {
        $prefix = $policyNumber ?? 'policy';

        return match ($docCode) {
            DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_SCHEDULE => $prefix . '_policy_schedule.pdf',
            DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE => $prefix . '_tax_invoice.pdf',
            DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE_RAISED_BY_BUYER => $prefix . '_commission_invoice.pdf',
            default => $prefix . '_document.pdf',
        };
    }
}
