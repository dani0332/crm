<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance;

use App\Enums\DeviceFailureTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\NgiEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Models\PolicyIssuance;
use App\Services\DeviceFailureEmailService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiException;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiApiService;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiDocumentHandler;
use App\Services\PolicyIssuanceAutomation\Device\SmartPhone\NationalGeneralInsurance\NgiValidationService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Job to retrieve policy documents from NGI provider with FRD-compliant retry logic.
 *
 * FRD Requirements:
 * - Initial delay: 3 minutes after policy creation (handled by dispatch delay)
 * - Retry: up to 3 times with 5-minute gaps
 */
class NgiGetPolicyDocumentsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of attempts: 1 initial + 3 retries = 4 total (per FRD)
     */
    public int $tries = 4;

    /**
     * Timeout for each attempt (seconds) - API call + document downloads
     */
    public int $timeout = 120;

    /**
     * Backoff between retries: 5 minutes = 300 seconds (per FRD)
     */
    public int $backoff = 300;

    /**
     * Unique lock duration (slightly longer than timeout to prevent overlap)
     */
    public int $uniqueFor = 180;

    private int $processId;

    public function __construct(int $processId)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::POLICY_ISSUANCE_DOWNLOAD_UPLOAD_DOCUMENTS_JOB);
        $this->processId = $processId;
        $this->onQueue('policy-issuance-automation');
    }

    /**
     * Unique identifier for preventing duplicate jobs
     */
    public function uniqueId(): string
    {
        return 'ngi-get-policy-docs-' . $this->processId;
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        $process = PolicyIssuance::with('model')->find($this->processId);

        if (!$process) {
            LoggerService::error('NgiGetPolicyDocumentsJob: Process not found', [
                'process_id' => $this->processId,
            ]);
            throw new NgiException(
                'NgiGetPolicyDocumentsJob: Process not found. process_id -> ' . $this->processId,
                NgiException::PROCESS_NOT_FOUND,
                ['process_id' => $this->processId]
            );
        }

        $quote = $process->model;

        if (!$quote) {
            LoggerService::error('NgiGetPolicyDocumentsJob: Quote not found', [
                'process_id' => $this->processId,
            ]);
            throw new NgiException(
                'NgiGetPolicyDocumentsJob: Quote not found. process_id -> ' . $this->processId,
                NgiException::QUOTE_NOT_FOUND,
                ['process_id' => $this->processId]
            );
        }

        LoggerService::info('NgiGetPolicyDocumentsJob: Starting document retrieval', [
            'process_id' => $this->processId,
            'quote_code' => $quote->code,
            'policy_number' => $quote->policy_number,
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
        ]);

        // Validate policy number exists
        $validationService = app(NgiValidationService::class);
        $validationResult = $validationService->validatePolicyNumberExists($quote);
        if (!$validationResult['status']) {
            LoggerService::error('NgiGetPolicyDocumentsJob: Policy number validation failed', [
                'process_id' => $this->processId,
                'error' => $validationResult['error'] ?? 'No policy number',
            ]);
            throw new NgiException(
                'NgiGetPolicyDocumentsJob: Policy number validation failed ' . ($validationResult['error'] ?? 'Policy number not found'),
                NgiException::POLICY_NUMBER_VALIDATION_FAILED,
                ['process_id' => $this->processId, 'error' => $validationResult['error'] ?? 'Policy number not found']
            );
        }

        // Step 1: Call GetPolicyDocuments API
        $apiService = app(NgiApiService::class);
        $getPolicyDocsResponse = $apiService->getPolicyDocuments($quote, $process);

        if (!$getPolicyDocsResponse['status']) {
            $errorMessage = $getPolicyDocsResponse['error'] ?? 'GetPolicyDocuments API failed';
            LoggerService::warning('NgiGetPolicyDocumentsJob: API call failed', [
                'process_id' => $this->processId,
                'quote_code' => $quote->code,
                'attempt' => $this->attempts(),
                'error' => $errorMessage,
            ]);
            throw new NgiException(
                'NgiGetPolicyDocumentsJob: API call failed ' . $errorMessage,
                NgiException::API_CALL_FAILED,
                ['process_id' => $this->processId, 'error' => $errorMessage]
            );
        }

        LoggerService::info('NgiGetPolicyDocumentsJob: API call successful, downloading documents', [
            'process_id' => $this->processId,
            'quote_code' => $quote->code,
        ]);

        // Step 2: Download documents from provider URLs and store in DB
        $downloadResult = $this->downloadAndStoreDocuments($quote, $process);

        if (!$downloadResult['status']) {
            $errorMessage = $downloadResult['error'] ?? 'Document download failed';
            LoggerService::warning('NgiGetPolicyDocumentsJob: Document download failed', [
                'process_id' => $this->processId,
                'quote_code' => $quote->code,
                'attempt' => $this->attempts(),
                'error' => $errorMessage,
            ]);
            throw new NgiException(
                'NgiGetPolicyDocumentsJob: Document download failed ' . $errorMessage,
                NgiException::DOCUMENT_DOWNLOAD_FAILED,
                ['process_id' => $this->processId, 'error' => $errorMessage]
            );
        }

        // Success - Update process step and set status back to PENDING for PolicyIssuanceJob to continue
        LoggerService::info('NgiGetPolicyDocumentsJob: All documents downloaded successfully', [
            'process_id' => $this->processId,
            'quote_code' => $quote->code,
            'documents_count' => $downloadResult['documents_count'] ?? 0,
        ]);

        $process->update([
            'completed_step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            'status' => PolicyIssuanceEnum::PENDING_STATUS,  // Set back to PENDING so PolicyIssuanceJob continues
            'message' => json_encode([
                'message' => 'Documents retrieved successfully, ready for upload step',
                'documents_downloaded' => $downloadResult['documents_count'] ?? 0,
            ]),
        ]);

        LoggerService::info('NgiGetPolicyDocumentsJob: Process updated, ready for next steps', [
            'process_id' => $this->processId,
            'completed_step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
            'new_status' => PolicyIssuanceEnum::PENDING_STATUS,
        ]);
    }

    /**
     * Download documents from provider URLs and store in database
     */
    private function downloadAndStoreDocuments($quote, $process): array
    {
        $documentHandler = app(NgiDocumentHandler::class);

        // Refresh quote to get latest document URLs from GetPolicyDocuments response
        $quote->refresh();

        // Document URLs stored by NgiQuoteUpdaterService during GetPolicyDocuments API call
        $documentUrls = [
            DocumentTypeCode::DEVICE_SMARTPHONE_POLICY_SCHEDULE => $quote->insurer_policy_doc_id,
            DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE => $quote->insurer_tax_invoice_doc_id,
            DocumentTypeCode::DEVICE_SMARTPHONE_TAX_INVOICE_RAISED_BY_BUYER => $quote->insurer_debit_note_doc_id,
        ];

        // Validate document URLs exist
        $validationService = app(NgiValidationService::class);
        $validationResult = $validationService->validateDownloadDocuments($documentUrls);
        if (!$validationResult['status']) {
            return $validationResult;
        }

        $downloadedDocuments = collect();
        $failedDocuments = [];

        foreach ($documentUrls as $docCode => $documentUrl) {
            if (empty($documentUrl)) {
                $failedDocuments[] = $docCode;
                LoggerService::warning('NgiGetPolicyDocumentsJob: Empty document URL', [
                    'document_code' => $docCode,
                ]);
                continue;
            }

            LoggerService::info('NgiGetPolicyDocumentsJob: Downloading document', [
                'document_code' => $docCode,
                'document_url' => $documentUrl,
            ]);

            // Download document content from provider URL
            $documentContentResponse = $documentHandler->fetchDocumentFromUrl($documentUrl);

            if (!$documentContentResponse['status']) {
                $failedDocuments[] = $docCode;
                LoggerService::warning('NgiGetPolicyDocumentsJob: Document download failed', [
                    'document_code' => $docCode,
                    'error' => $documentContentResponse['message'] ?? 'Download failed',
                ]);
                continue;
            }

            // Generate file name
            $fileName = $this->getDocumentFileName($docCode, $quote->policy_number);

            // Detect MIME type from raw content
            $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($documentContentResponse['content']);

            // Encode content to base64 and format as Data URL for storage
            $base64Content = 'data:' . $mimeType . ';base64,' . base64_encode($documentContentResponse['content']);

            LoggerService::info('NgiGetPolicyDocumentsJob: Storing document in DB', [
                'document_code' => $docCode,
                'file_name' => $fileName,
            ]);

            // Store document in database (similar to upload step but just storing)
            $quoteDocument = $documentHandler->uploadAndAttachToQuoteDocuments(
                $quote,
                $base64Content,
                $docCode,
                $fileName
            );

            // Log the download action
            app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
                $quote,
                ['document_url' => $documentUrl, 'document_code' => $docCode],
                ['stored' => (bool) $quoteDocument?->id, 'document_id' => $quoteDocument?->id],
                $documentUrl,
                NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
                $quoteDocument?->id ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS,
                $process
            );

            if ($quoteDocument?->id) {
                $downloadedDocuments->push([
                    'code' => $docCode,
                    'document_id' => $quoteDocument->id,
                    'file_name' => $fileName,
                ]);
            } else {
                $failedDocuments[] = $docCode;
            }
        }

        // Check if all 3 required documents were downloaded
        $allDocsDownloaded = $downloadedDocuments->count() === 3;

        if (!$allDocsDownloaded) {
            return [
                'status' => false,
                'error' => 'Failed to download documents: ' . implode(', ', $failedDocuments),
                'documents_count' => $downloadedDocuments->count(),
                'failed_documents' => $failedDocuments,
            ];
        }

        return [
            'status' => true,
            'documents_count' => $downloadedDocuments->count(),
            'documents' => $downloadedDocuments->toArray(),
        ];
    }

    /**
     * Get document file name based on document type
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

    /**
     * Handle job failure after all retries exhausted
     */
    public function failed(Throwable $exception): void
    {
        $process = PolicyIssuance::with('model')->find($this->processId);

        LoggerService::error('NgiGetPolicyDocumentsJob: Job failed permanently', [
            'process_id' => $this->processId,
            'quote_code' => $process?->model?->code ?? 'unknown',
            'total_attempts' => $this->attempts(),
            'max_tries' => $this->tries,
            'exception' => $exception->getMessage(),
        ]);

        if ($process) {
            // Mark process as failed
            $process->update([
                'status' => PolicyIssuanceEnum::FAILED_STATUS,
                'message' => json_encode([
                    'error' => 'GetPolicyDocuments failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
                    'step' => NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM,
                    'final_attempt' => $this->attempts(),
                ]),
            ]);

            // Update quote API issuance status
            if ($process->model) {
                app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus(
                    $process->model,
                    QuoteTypes::DEVICE->value,
                    PolicyIssuanceEnum::PIA_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM_API_FAILED_STATUS_ID,
                    PolicyIssuanceEnum::PIA_POLICY_AUTOMATION_STATUS_NO_ID,
                    NgiEnum::STEP_GET_AND_UPLOAD_POLICY_DOCUMENTS_TO_IMCRM
                );

                // Trigger failure email - uses DeviceFailureEmailService directly (no local wrapper)
                app(DeviceFailureEmailService::class)->sendFailureEmail(
                        $process->model->id,
                    DeviceFailureTypeEnum::GET_AND_UPLOAD_DOCUMENTS
                );
            }
        }
    }
}
