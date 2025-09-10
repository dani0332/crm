<?php

namespace App\Services\OCR;

use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Events\OcrNotifications;
use App\Jobs\OCR\PopulateDocumentData;
use App\Models\DocumentType;
use App\Models\SendUpdateLog;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OCRService
{
    use Ocrable, OcrFillable;

    public const IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/jpg'];

    public function __construct(
        protected QuoteDocumentService $quoteDocumentService,
        protected OcrLogService $ocrLogService
    ) {}

    private function sendRequest(string $endpoint, array $data = [], string $method = 'POST')
    {
        try {
            $response = Http::baseUrl(config('constants.OCR_API_ENDPOINT'))
                ->withHeader('Referer', trim(config('constants.APP_URL'), '/'))
                ->withHeader('x-api-key', config('constants.OCR_API_KEY'))
                ->timeout(config('constants.OCR_API_TIMEOUT'))
                ->beforeSending(fn () => LoggerService::info(self::class."::sendRequest - Calling OCR API via {$method} request to {$endpoint}"))
                ->when(
                    $method === 'GET',
                    fn (PendingRequest $http) => $http->get($endpoint, $data),
                    fn (PendingRequest $http) => $http->post($endpoint, $data)
                );

            return $this->handleResponse($response, $endpoint);
        } catch (ConnectionException $e) {
            if ($this->isTimeoutException($e)) {
                LoggerService::info(self::class.' - API request timed out', ['endpoint' => $endpoint, 'message' => $e->getMessage()]);
            } else {
                LoggerService::info(self::class.' - Connection exception occurred during API call', ['endpoint' => $endpoint, 'message' => $e->getMessage()]);
            }

            return ['ok' => false, 'object' => null, 'message' => $e->getMessage()];
        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred during API call', exception: $e);

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
        OCRDocumentTypeEnum $docType
    ) {
        $providerCode = null;

        if ($quote->payments && $quote->payments->isNotEmpty()) {
            $latestPayment = $quote->payments->first();
            if ($latestPayment && $latestPayment->insuranceProvider) {
                $providerCode = $latestPayment->insuranceProvider->code;
            }
        }

        LoggerService::info('Provider Code - Quote UUID: '.$quote->uuid);

        $requestData = [
            'ref_id' => $quote->code,
            'uuid' => $quote->uuid,
            'quote_type_id' => $quoteType->id(),
            'doc_url' => $docUrl,
            'doc_type' => $docType->value,
            'provider_code' => $providerCode,
            'image' => false,
        ];

        LoggerService::info('OCR API Request - Quote UUID: '.$quote->uuid);

        $response = $this->sendRequest('/process-document', $requestData);

        if ($response['ok']) {
            LoggerService::info('OCR API Response Success - Quote UUID: '.$quote->uuid);

            return $response['object'];
        }

        LoggerService::warning('OCR API Response Failed - Quote UUID: '.$quote->uuid, [
            'document_type' => $docType?->value,
            'response_message' => $response['message'] ?? 'Unknown error',
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
        } catch (ConnectionException $e) {
            if ($this->isTimeoutException($e)) {
                LoggerService::info('OCR Service health check timed out', ['message' => $e->getMessage()]);
            } else {
                LoggerService::info('OCR Service Connection Failed: ', ['message' => $e->getMessage()]);
            }

            return false;
        } catch (Exception $e) {
            LoggerService::error('OCR Service Health Check Failed - ', exception: $e);

            return false;
        }
    }

    private function getProviderId(Model $quote): ?int
    {
        if (! $quote->payments || $quote->payments->isEmpty()) {
            return null;
        }

        $latestPayment = $quote->payments->first();

        return $latestPayment && $latestPayment->insuranceProvider
            ? $latestPayment->insuranceProvider->id
            : null;
    }

    private function prepareRequestMetadata(Model $quote, QuoteTypes $quoteType, string $url, OCRDocumentTypeEnum $docType, ?int $providerId, string $fileMimeType): array
    {
        return [
            'ref_id' => $quote->code,
            'quote_type_id' => $quoteType->id(),
            'doc_url' => $url,
            'doc_type' => $docType->value,
            'provider_id' => $providerId,
            'image' => $this->isMimeTypeImage($fileMimeType),
        ];
    }

    private function handleServiceAvailability(Model $quote, DocumentType $documentType, int $userId): bool
    {
        if (! $this->isOCRServiceAvailable()) {
            LoggerService::error('OCR Service Unavailable - Quote UUID: '.$quote->uuid);

            $this->ocrLogService->logActivity(
                $quote,
                $documentType,
                'failed',
                null,
                null,
                null,
                'OCR service unavailable',
                $userId
            );

            return false;
        }

        return true;
    }

    private function handleDocumentTypeValidation(Model $quote, QuoteTypes $quoteType, DocumentType $documentType, OCRDocumentTypeEnum $docType, int $userId): bool
    {
        if (! $docType?->isEnabled($quoteType)) {
            LoggerService::info(self::class."::process - OCR is not enabled for this document type {$documentType->code} - Quote UUID: ".$quote->uuid);

            $this->ocrLogService->logActivity(
                $quote,
                $documentType,
                'skipped',
                null,
                null,
                null,
                'OCR not enabled for this document type',
                $userId
            );

            return false;
        }

        return true;
    }

    private function processOcrData(
        Model $quote,
        QuoteTypes $quoteType,
        DocumentType $documentType,
        OCRDocumentTypeEnum $docType,
        string $url,
        string $fileMimeType,
        int $userId,
        bool $isEcom,
        string $documentCategory,
        float $startTime,
        float $apiCallStartTime,
        object $data
    ): ?bool {
        $apiCallEndTime = microtime(true);
        $apiCallExecutionTime = round(($apiCallEndTime - $apiCallStartTime) * 1000, 2);

        LoggerService::info('OCR API call completed - Quote UUID: '.$quote->uuid);
        LoggerService::info(self::class.'::process - Data received from getData - Quote UUID: '.$quote->uuid.' - apiCallEndTime: '.$apiCallEndTime.' - apiCallExecutionTime: '.$apiCallExecutionTime);

        $dataFilledResponse = $this->fill($quote, $docType, $data, $documentCategory);

        $isQuoteStatusTransectionApproved = $quote->quote_status_id == QuoteStatusEnum::TransactionApproved;
        if ($isQuoteStatusTransectionApproved) {
            (new CentralService)->updateQuoteInformation($quoteType->value, $quote->id);
        } elseif (! $isEcom) {
            event(new OcrNotifications($quote, 'end', 'Lead is not Transaction Approved.', null, $docType?->value, $userId));
        }

        // Send end notification for successful processing (skip for ecom)
        if (! $isEcom && $this->requiresOcrNotifications($docType) && $dataFilledResponse) {
            event(new OcrNotifications($quote, 'end', 'OCR processing completed successfully', null, $docType?->value, $userId));
        }

        // Calculate execution time and log success
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        LoggerService::info('OCR processing completed successfully - Quote UUID: '.$quote->uuid);

        $providerId = $this->getProviderId($quote);
        $processedData = is_array($data) ? $data : ((is_object($data)) ? (array) $data : null);

        $this->ocrLogService->logActivity(
            $quote,
            $documentType,
            'success',
            $this->prepareRequestMetadata($quote, $quoteType, $url, $docType, $providerId, $fileMimeType),
            $processedData,
            $executionTime,
            null,
            $userId
        );

        return $dataFilledResponse;
    }

    private function handleProcessingFailure(
        Model $quote,
        QuoteTypes $quoteType,
        DocumentType $documentType,
        OCRDocumentTypeEnum $docType,
        string $url,
        string $fileMimeType,
        int $userId,
        float $startTime
    ): bool {
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        LoggerService::warning('OCR processing failed - no data received - Quote UUID: '.$quote->uuid, [
            'document_type' => $docType?->value,
            'quote_type' => $quoteType?->value,
            'execution_time_ms' => $executionTime,
        ]);

        $providerId = $this->getProviderId($quote);

        $this->ocrLogService->logActivity(
            $quote,
            $documentType,
            'failed',
            $this->prepareRequestMetadata($quote, $quoteType, $url, $docType, $providerId, $fileMimeType),
            null,
            $executionTime,
            'OCR processing failed - no data received',
            $userId
        );

        return false;
    }

    private function handleProcessingException(
        \Exception $e,
        Model $quote,
        QuoteTypes $quoteType,
        DocumentType $documentType,
        OCRDocumentTypeEnum $docType,
        string $url,
        string $fileMimeType,
        int $userId,
        float $startTime
    ): void {
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        LoggerService::error('OCR processing failed with exception - Quote UUID: '.$quote->uuid, [
            'document_type' => $docType?->value,
            'quote_type' => $quoteType?->value,
            'execution_time_ms' => $executionTime,
        ], exception: $e);

        $providerId = $this->getProviderId($quote);

        $this->ocrLogService->logActivity(
            $quote,
            $documentType,
            'failed',
            $this->prepareRequestMetadata($quote, $quoteType, $url, $docType, $providerId, $fileMimeType),
            null,
            $executionTime,
            'OCR processing failed with exception: '.$e->getMessage(),
            $userId
        );

        throw $e;
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
        $result = null;

        LoggerService::info('Starting OCR processing - Quote UUID: '.$quote->uuid);

        // Check if OCR service is available
        // TODO: Uncomment this when Customer OCR service is available & OCR Health Check is implemented by OCR team
        // $serviceCheck = $this->handleServiceAvailability($quote, $documentType, $userId);
        // if (! $serviceCheck) {
        //     return false;
        // }

        $docType = OCRDocumentTypeEnum::getDocumentType($documentType);

        // Validate document type
        $docTypeCheck = $this->handleDocumentTypeValidation($quote, $quoteType, $documentType, $docType, $userId);
        if (! $docTypeCheck) {
            return null;
        }

        // Send start notification (skip for ecom)
        if (! $isEcom && $this->requiresOcrNotifications($docType)) {
            event(new OcrNotifications($quote, 'start', 'OCR processing started', null, $docType?->value, $userId));
        }

        $url = $this->quoteDocumentService->getDocumentUrl($documentPath);

        try {
            // Record start time for OCR API call
            $apiCallStartTime = microtime(true);
            LoggerService::info('Starting OCR API call - Quote UUID: '.$quote->uuid);

            $data = $this->getData($quoteType, $quote, $url, $docType);

            if ($data) {
                $result = $this->processOcrData(
                    $quote,
                    $quoteType,
                    $documentType,
                    $docType,
                    $url,
                    $fileMimeType,
                    $userId,
                    $isEcom,
                    $documentCategory,
                    $startTime,
                    $apiCallStartTime,
                    $data
                );
            } else {
                $result = $this->handleProcessingFailure(
                    $quote,
                    $quoteType,
                    $documentType,
                    $docType,
                    $url,
                    $fileMimeType,
                    $userId,
                    $startTime
                );
            }
        } catch (\Exception $e) {
            $this->handleProcessingException(
                $e,
                $quote,
                $quoteType,
                $documentType,
                $docType,
                $url,
                $fileMimeType,
                $userId,
                $startTime
            );
        }

        return $result;
    }

    public function dispatchJobIfEligible(
        DocumentType $documentType,
        $quote,
        string $filePathAzure,
        string $fileMimeType,
        ?string $quoteTypeParam = null
    ): void {

        // early return if Customer OCR Journey is not supported on prod
        $docType = OCRDocumentTypeEnum::getDocumentType($documentType);
        if (in_array($docType, [OCRDocumentTypeEnum::ID_CARD, OCRDocumentTypeEnum::REGISTRATION_CERTIFICATE, OCRDocumentTypeEnum::DRIVING_LICENSE])) {
            LoggerService::info(self::class.' - Customer OCR Journey is not supported for now');

            return;
        }

        if ($quote instanceof SendUpdateLog) {
            LoggerService::info('OCR Dispatch - Skipping for SendUpdateLog - Quote UUID: '.$quote->uuid);

            return;
        }

        $quoteType = $this->determineQuoteType($quoteTypeParam);
        if (! $quoteType) {
            LoggerService::info('OCR Dispatch - Unable to determine quote type - Quote UUID: '.$quote->uuid);

            return;
        }

        $userId = Auth::id();

        // if userId is null, it means the request is from ecom
        $isEcom = is_null($userId);

        if ($quote && $filePathAzure) {
            LoggerService::info('OCR Dispatch - Dispatching PopulateDocumentData job - Quote UUID: '.$quote->uuid);

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
            LoggerService::warning('OCR Dispatch - Missing required parameters - Quote UUID: '.$quote->uuid);
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

    private function isTimeoutException(ConnectionException $exception): bool
    {
        $message = $exception->getMessage();
        
        return str_contains($message, 'cURL error 28') || 
               str_contains($message, 'Operation timed out') ||
               str_contains($message, 'Connection timed out') ||
               str_contains($message, 'timeout');
    }
}
