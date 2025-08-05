<?php

namespace App\Services\OCR;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Events\OcrNotifications;
use App\Jobs\OCR\PopulateDocumentData;
use App\Models\DocumentType;
use App\Models\OcrLog;
use App\Models\SendUpdateLog;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class OCRService
{
    use Ocrable, OcrFillable;

    public const IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/jpg'];

    public function __construct(protected QuoteDocumentService $quoteDocumentService) {}

    private function sendRequest(string $endpoint, array $data = [], string $method = 'POST')
    {
        try {
            $response = Http::baseUrl(config('constants.OCR_API_ENDPOINT'))
                ->withHeader('Referer', trim(config('constants.APP_URL'), '/'))
                ->withHeader('x-api-key', config('constants.OCR_API_KEY'))
                ->timeout(config('constants.OCR_API_TIMEOUT'))
                ->beforeSending(fn () => LoggerService::info(self::class."::sendRequest - Calling OCR API via {$method} request to {$endpoint}", $data))
                ->when(
                    $method === 'GET',
                    fn (PendingRequest $http) => $http->get($endpoint, $data),
                    fn (PendingRequest $http) => $http->post($endpoint, $data)
                );

            return $this->handleResponse($response, $endpoint);
        } catch (Exception $e) {
            LoggerService::error(self::class." - Exception occurred during API call: {$e->getMessage()}");

            return ['ok' => false, 'object' => null, 'message' => $e->getMessage()];
        }
    }

    private function isMimeTypeImage(string $fileMimeType)
    {
        return in_array($fileMimeType, self::IMAGE_MIME_TYPES);
    }

    private function getData(
        QuoteTypes $quoteType,
        Model $quote,
        string $docUrl,
        OCRDocumentTypeEnum $docType,
        string $fileMimeType
    ) {
        $providerCode = null;

        if ($quote->payments && $quote->payments->isNotEmpty()) {
            $latestPayment = $quote->payments->first();
            if ($latestPayment && $latestPayment->insuranceProvider) {
                $providerCode = $latestPayment->insuranceProvider->code;
            }
        }

        LoggerService::info('Provider Code - Quote UUID: '.$quote->uuid, extra: [
            'provider_code' => $providerCode,
        ]);

        $requestData = [
            'ref_id' => $quote->code,
            'uuid' => $quote->uuid,
            'quote_type_id' => $quoteType->id(),
            'doc_url' => $docUrl,
            'doc_type' => $docType->value,
            'provider_code' => $providerCode,
            'image' => false,
        ];

        LoggerService::info('OCR API Request - Quote UUID: '.$quote->uuid, extra: [
            'request_data' => $requestData,
            'endpoint' => '/process-document',
        ]);

        $response = $this->sendRequest('/process-document', $requestData);

        if ($response['ok']) {
            LoggerService::info('OCR API Response Success - Quote UUID: '.$quote->uuid, extra: [
                'response_data' => $response['object'],
            ]);
            return $response['object'];
        }

        LoggerService::error('OCR API Response Failed - Quote UUID: '.$quote->uuid, extra: [
            'response_message' => $response['message'],
            'response_object' => $response['object'],
            'request_data' => $requestData,
        ]);

        return null;
    }

    public function isOCRServiceAvailable(): bool
    {
        try {
            $response = Http::baseUrl(config('constants.OCR_API_ENDPOINT'))
                ->withHeader('Referer', trim(config('constants.APP_URL'), '/'))
                ->withHeader('x-api-key', config('constants.OCR_API_KEY'))
                ->timeout(3)
                ->get('/health');

            return $response->successful() && $response->status() === Response::HTTP_OK;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            LoggerService::error('OCR Service Connection Failed', extra: [
                'error_message' => $e->getMessage(),
                'error_type' => 'ConnectionException',
            ]);
            return false;
        } catch (Exception $e) {
            LoggerService::error('OCR Service Health Check Failed', extra: [
                'error_message' => $e->getMessage(),
                'error_type' => get_class($e),
            ]);
            return false;
        }
    }

    public function process(
        QuoteTypes $quoteType,
        Model $quote,
        DocumentType $documentType,
        string $documentPath,
        string $fileMimeType,
        int $userId,
        bool $isEcom,
    ): ?bool {
        // Record start time for OCR processing
        $startTime = microtime(true);
        $documentCategory = $documentType->category;

        LoggerService::info('Starting OCR processing - Quote UUID: '.$quote->uuid, extra: [
            'quote_type' => $quoteType->value,
            'quote_code' => $quote->code,
            'document_type' => $documentType->code,
            'file_mime_type' => $fileMimeType,
            'user_id' => $userId,
            'is_ecom' => $isEcom,
            'start_time' => date('Y-m-d H:i:s', (int) $startTime),
            'document_category' => $documentCategory,
        ]);

        // Check if OCR service is available
        if (!$this->isOCRServiceAvailable()) {
            LoggerService::error('OCR Service Unavailable - Quote UUID: '.$quote->uuid, extra: [
                'quote_type' => $quoteType->value,
                'document_type' => $documentType->code,
            ]);
            
            // Log OCR activity for service unavailable
                    $this->logOcrActivity(
            $quote,
            $documentType,
            'failed',
            null,
            null,
            'process',
            null,
            'OCR service unavailable',
            $userId
        );
            
            return false;
        }

        $docType = OCRDocumentTypeEnum::getDocumentType($documentType);

        if (! $docType?->isEnabled($quoteType)) {
            LoggerService::info(self::class."::process - OCR is not enabled for this document type {$documentType->code} - Quote UUID: ".$quote->uuid);

            // Log OCR activity for disabled document type
                    $this->logOcrActivity(
            $quote,
            $documentType,
            'skipped',
            null,
            null,
            'process',
            null,
            'OCR not enabled for this document type',
            $userId
        );

            return null;
        }

        // Send start notification for TAX_INVOICE, TAX_INVOICE_RAISED_BY_BUYER, and CERTIFICATE_OF_ISSUANCE document types (skip for ecom)
        if (! $isEcom && $this->requiresOcrNotifications($docType)) {
            event(new OcrNotifications($quote, 'start', 'OCR processing started', null, $docType?->value, $userId));
        }

        // Log OCR activity for processing start
        $this->logOcrActivity(
            $quote,
            $documentType,
            'processing',
            null,
            null,
            'process',
            null,
            null,
            $userId
        );

        $url = $this->quoteDocumentService->getDocumentUrl($documentPath);

        try {
            // Record start time for OCR API call
            $apiCallStartTime = microtime(true);

            LoggerService::info('Starting OCR API call - Quote UUID: '.$quote->uuid, extra: [
                'quote_type' => $quoteType->value,
                'quote_code' => $quote->code,
                'document_type' => $documentType->code,
                'doc_type_enum' => $docType->value,
                'api_url' => $url,
                'document_category' => $documentCategory,
            ]);

            $data = $this->getData($quoteType, $quote, $url, $docType, $fileMimeType);

            // Calculate API call execution time
            $apiCallEndTime = microtime(true);
            $apiCallExecutionTime = round(($apiCallEndTime - $apiCallStartTime) * 1000, 2);

            LoggerService::info('OCR API call completed - Quote UID '.$quote->uuid, extra: [
                'quote_id' => $quote->id,
                'quote_type' => $quoteType->value,
                'quote_code' => $quote->code,
                'document_type' => $documentType->code,
                'api_execution_time_ms' => $apiCallExecutionTime,
                'api_execution_time_seconds' => round($apiCallExecutionTime / 1000, 2),
                'data_received' => ! is_null($data),
                'data_size' => is_array($data) ? count($data) : (is_string($data) ? strlen($data) : 0),
                'document_category' => $documentCategory,
            ]);

            if ($data) {
                LoggerService::info(self::class.'::process - Data received from getData - Quote UUID: '.$quote->uuid, extra: [
                    'data' => $data,
                ]);
                $dataFilledResponse = $this->fill(
                    $quote,
                    $docType,
                    $data,
                    $documentCategory
                );

                $isQuoteStatusTransectionApproved = $quote->quote_status_id == QuoteStatusEnum::TransactionApproved;
                if ($isQuoteStatusTransectionApproved) {
                    (new CentralService)->updateQuoteInformation($quoteType->value, $quote->id);
                } elseif (! $isEcom) {
                    event(new OcrNotifications($quote, 'end', 'Lead is not Transaction Approved.', null, $docType?->value, $userId));
                }

                // Send end notification for TAX_INVOICE, TAX_INVOICE_RAISED_BY_BUYER, and CERTIFICATE_OF_ISSUANCE document types when processing completes successfully (skip for ecom)
                if (! $isEcom && $this->requiresOcrNotifications($docType) && $dataFilledResponse) {
                    event(new OcrNotifications($quote, 'end', 'OCR processing completed successfully', null, $docType?->value, $userId));
                }

                // Calculate execution time and log success
                $endTime = microtime(true);
                $executionTime = round(($endTime - $startTime) * 1000, 2);
                $dataProcessingTime = round($executionTime - $apiCallExecutionTime, 2);

                LoggerService::info('OCR processing completed successfully - Quote UUID: '.$quote->uuid, extra: [
                    'quote_type' => $quoteType->value,
                    'quote_code' => $quote->code,
                    'document_type' => $documentType->code,
                    'total_execution_time_ms' => $executionTime,
                    'total_execution_time_seconds' => round($executionTime / 1000, 2),
                    'api_call_time_ms' => $apiCallExecutionTime,
                    'data_processing_time_ms' => $dataProcessingTime,
                    'end_time' => date('Y-m-d H:i:s', (int) $endTime),
                    'data_filled_response' => $dataFilledResponse,
                    'user_id' => $userId,
                    'is_ecom' => $isEcom,
                    'document_category' => $documentCategory,
                ]);

                // Log OCR activity for success
                $providerId = null;
                if ($quote->payments && $quote->payments->isNotEmpty()) {
                    $latestPayment = $quote->payments->first();
                    if ($latestPayment && $latestPayment->insuranceProvider) {
                        $providerId = $latestPayment->insuranceProvider->id;
                    }
                }

                $this->logOcrActivity(
                    $quote,
                    $documentType,
                    'success',
                    [
                        'ref_id' => $quote->code,
                        'quote_type_id' => $quoteType->id(),
                        'doc_url' => $url,
                        'doc_type' => $docType->value,
                        'provider_id' => $providerId,
                        'image' => $this->isMimeTypeImage($fileMimeType),
                    ],
                    is_array($data) ? $data : (is_object($data) ? (array) $data : null),
                    'process',
                    $executionTime,
                    null,
                    $userId
                );

                return $dataFilledResponse;
            } else {
                // Calculate execution time and log failure
                $endTime = microtime(true);
                $executionTime = round(($endTime - $startTime) * 1000, 2);

                LoggerService::warning('OCR processing failed - no data received - Quote UUID: '.$quote->uuid, extra: [
                    'quote_type' => $quoteType->value,
                    'quote_code' => $quote->code,
                    'document_type' => $documentType->code,
                    'total_execution_time_ms' => $executionTime,
                    'total_execution_time_seconds' => round($executionTime / 1000, 2),
                    'api_call_time_ms' => $apiCallExecutionTime,
                    'end_time' => date('Y-m-d H:i:s', (int) $endTime),
                    'user_id' => $userId,
                    'document_category' => $documentCategory,
                ]);

                // Log OCR activity for failure
                $providerId = null;
                if ($quote->payments && $quote->payments->isNotEmpty()) {
                    $latestPayment = $quote->payments->first();
                    if ($latestPayment && $latestPayment->insuranceProvider) {
                        $providerId = $latestPayment->insuranceProvider->id;
                    }
                }

                $this->logOcrActivity(
                    $quote,
                    $documentType,
                    'failed',
                    [
                        'ref_id' => $quote->code, // Use quote code for failed cases
                        'quote_type_id' => $quoteType->id(),
                        'doc_url' => $url,
                        'doc_type' => $docType->value,
                        'provider_id' => $providerId,
                        'image' => $this->isMimeTypeImage($fileMimeType),
                    ],
                    null,
                    'process',
                    $executionTime,
                    'OCR processing failed - no data received',
                    $userId
                );

                return false;
            }
        } catch (\Exception $e) {
            // Calculate execution time even in case of exception
            $endTime = microtime(true);
            $executionTime = isset($startTime) ? round(($endTime - $startTime) * 1000, 2) : 0;
            $apiCallExecutionTime = isset($apiCallStartTime) ? round(($endTime - $apiCallStartTime) * 1000, 2) : 0;

            LoggerService::error('OCR processing failed with exception - Quote UUID: '.$quote->uuid, extra: [
                'quote_type' => $quoteType->value,
                'quote_code' => $quote->code,
                'document_type' => $documentType->code,
                'total_execution_time_ms' => $executionTime,
                'total_execution_time_seconds' => round($executionTime / 1000, 2),
                'api_call_time_ms' => $apiCallExecutionTime,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'user_id' => $userId,
                'is_ecom' => $isEcom,
                'document_category' => $documentCategory,
            ]);

            // Log OCR activity for exception
            $providerId = null;
            if ($quote->payments && $quote->payments->isNotEmpty()) {
                $latestPayment = $quote->payments->first();
                if ($latestPayment && $latestPayment->insuranceProvider) {
                    $providerId = $latestPayment->insuranceProvider->id;
                }
            }

            $this->logOcrActivity(
                $quote,
                $documentType,
                'failed',
                [
                    'ref_id' => $quote->code,
                    'quote_type_id' => $quoteType->id(),
                    'doc_url' => $url,
                    'doc_type' => $docType->value,
                    'provider_id' => $providerId,
                    'image' => $this->isMimeTypeImage($fileMimeType),
                ],
                null,
                'process',
                $executionTime,
                'OCR processing failed with exception: ' . $e->getMessage(),
                $userId
            );

            throw $e;
        }
    }

    public function dispatchJobIfEligible(
        DocumentType $documentType,
        $quote,
        string $filePathAzure,
        string $fileMimeType,
        ?string $quoteTypeParam = null
    ): void {
        LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::OCR);

        if ($quote instanceof SendUpdateLog) {
            LoggerService::info('OCR Dispatch - Skipping for SendUpdateLog - Quote UUID: '.$quote->uuid, [
                'document_type' => $documentType->code,
            ]);

            return;
        }

        $quoteType = $this->determineQuoteType($quoteTypeParam);
        if (! $quoteType) {
            LoggerService::info('OCR Dispatch - Unable to determine quote type - Quote UUID: '.$quote->uuid, [
                'document_type' => $documentType->code,
                'quote_type_param' => $quoteTypeParam,
            ]);

            return;
        }

        $userId = Auth::id();

        // if userId is null, it means the request is from ecom
        $isEcom = is_null($userId);

        if ($quote && $filePathAzure) {
            LoggerService::info('OCR Dispatch - Dispatching PopulateDocumentData job - Quote UUID: '.$quote->uuid, [
                'quote_type' => $quoteType->value,
                'document_type' => $documentType->code,
                'file_path' => $filePathAzure,
                'user_id' => $userId,
                'is_ecom' => $isEcom,
            ]);

            PopulateDocumentData::dispatch(
                $quoteType,
                $quote,
                $documentType,
                $filePathAzure,
                $fileMimeType,
                $userId ?? 0,
                $isEcom,
            );
        } else {
            LoggerService::warning('OCR Dispatch - Missing required parameters - Quote UUID: '.$quote->uuid, [
                'quote_id' => $quote->id,
                'quote_exists' => ! is_null($quote),
                'file_path_exists' => ! empty($filePathAzure),
                'document_type' => $documentType->code,
            ]);
        }
    }

    private function determineQuoteType(?string $quoteTypeParam = null): ?QuoteTypes
    {
        $candidates = array_filter([
            $quoteTypeParam,
            request('quoteType'),    // API route parameter: /api/quotes/{quoteType}/documents
            request('quote_type'),   // Web route context
        ]);

        foreach ($candidates as $candidate) {
            $quoteType = QuoteTypes::tryFrom(ucfirst($candidate));
            if ($quoteType) {
                return $quoteType;
            }
        }

        return null;
    }

    /**
     * Check if the document type requires OCR notifications
     */
    public function requiresOcrNotifications(OCRDocumentTypeEnum $docType): bool
    {
        return in_array($docType, [
            OCRDocumentTypeEnum::TAX_INVOICE,
            OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER,
            OCRDocumentTypeEnum::CERTIFICATE_OF_ISSUANCE,
            OCRDocumentTypeEnum::ID_CARD,
            OCRDocumentTypeEnum::REGISTRATION_CERTIFICATE,
            OCRDocumentTypeEnum::DRIVING_LICENSE,
        ]);
    }

    /**
     * Log OCR activity to the database
     */
    private function logOcrActivity(
        Model $quote,
        DocumentType $documentType,
        string $status,
        ?array $requestData = null,
        ?array $responseData = null,
        string $executionMethod = 'process',
        ?float $executionTimeMs = null,
        ?string $errorMessage = null,
        int $userId = 0
    ): void {
        try {
            $providerId = null;
            if ($quote->payments && $quote->payments->isNotEmpty()) {
                $latestPayment = $quote->payments->first();
                if ($latestPayment && $latestPayment->insuranceProvider) {
                    $providerId = $latestPayment->insuranceProvider->id;
                }
            }

            $uploadedThrough = $userId ? 'imcrm' : 'other than imcrm';

            // Ensure document type has required fields
            $documentTypeCode = $documentType->code ?? 'UNKNOWN';
            $documentTypeName = $documentType->text ?? 'Unknown Document Type';

            OcrLog::create([
                'ocr_loggable_type' => get_class($quote),
                'ocr_loggable_id' => $quote->id,
                'document_type_code' => $documentTypeCode,
                'document_type_name' => $documentTypeName,
                'status' => $status,
                'request_data' => $requestData,
                'response_data' => $responseData,
    
                'execution_time_ms' => $executionTimeMs,
                'error_message' => $errorMessage,
                'provider_id' => $providerId,
                'user_id' => $userId,
                'uploaded_through' => $uploadedThrough,
            ]);

            Log::info('OCR activity logged - Quote UUID: '.$quote->uuid, [
                'quote_id' => $quote->id,
                'document_type' => $documentType->code,
                'status' => $status,
                'user_id' => $userId,
                'uploaded_through' => $uploadedThrough,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log OCR activity - Quote UUID: '.$quote->uuid, [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
