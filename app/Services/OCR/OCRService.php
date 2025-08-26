<?php

namespace App\Services\OCR;

use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Events\OcrNotifications;
use App\Jobs\OCR\PopulateDocumentData;
use App\Models\BusinessQuote;
use App\Models\DocumentType;
use App\Models\SendUpdateLog;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OCRService
{
    use Ocrable, OcrFillable, OcrUtils, OcrValidator;

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
        $providerCode = $this->extractProviderCode($quote);

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
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            LoggerService::error('OCR Service Connection Failed: ', exception: $e);

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
        object $data,
        bool $isSendUpdateEligibleForOCR = false
    ): ?bool {
        $apiCallEndTime = microtime(true);
        $apiCallExecutionTime = round(($apiCallEndTime - $apiCallStartTime) * 1000, 2);

        LoggerService::info('OCR API call completed - Quote UUID: '.$quote->uuid);
        LoggerService::info(self::class.'::process - Data received from getData - Quote UUID: '.$quote->uuid.' - apiCallEndTime: '.$apiCallEndTime.' - apiCallExecutionTime: '.$apiCallExecutionTime, [
            'is_send_update_eligible_for_ocr' => $isSendUpdateEligibleForOCR,
        ]);

        $dataFilledResponse = $this->fill($quote, $docType, $data, $documentCategory, $isSendUpdateEligibleForOCR, $quoteType);

        $isQuoteStatusTransectionApproved = $quote->quote_status_id == QuoteStatusEnum::TransactionApproved;
        if ($isQuoteStatusTransectionApproved) {
            $quoteType = $this->checkIfQuoteTypeIsGroupMedical($quoteType);
            (new CentralService)->updateQuoteInformation($quoteType->value, $quote->id);
        } elseif (! $isEcom) {
            event(new OcrNotifications($quote, 'end', 'Lead is not Transaction Approved.', null, $docType?->value, $userId));
        }

        // Send end notification for successful processing (skip for ecom)
        if (! $isEcom && $this->requiresOcrNotifications($docType) && $dataFilledResponse) {
            event(new OcrNotifications($quote, 'end', 'OCR processing completed successfully', null, $docType?->value, $userId));
        }

        // Update Accuracy Matrix cache after successful OCR processing
        try {
            $this->updateAccuracyMatrix($quoteType, $quote, $docType, $data, $documentType);
        } catch (\Exception $e) {
            // Log error but don't interrupt OCR flow
            LoggerService::error('Accuracy Matrix update failed but OCR completed successfully - ', exception: $e);
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
        bool $isSendUpdateEligibleForOCR
    ): ?bool {
        // Record start time for OCR processing
        $startTime = microtime(true);
        $documentCategory = $documentType->category;
        $result = null;

        LoggerService::info('Starting OCR processing - Quote UUID: '.$quote->uuid);

        // Check if OCR service is available
        $serviceCheck = $this->handleServiceAvailability($quote, $documentType, $userId);
        if (! $serviceCheck) {
            return false;
        }

        // Skip OCR for non-eligible providers
        if (! $this->isProviderEligibleForOcr($quoteType, $quote)) {
            $providerCode = $this->extractProviderCode($quote);
            LoggerService::info('OCR processing skipped - Provider not eligible for OCR - Quote UUID: '.$quote->uuid, [
                'quote_type' => $quoteType->value,
                'provider_code' => $providerCode ?? 'null',
            ]);

            return false;
        }

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
                    $data,
                    $isSendUpdateEligibleForOCR
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
        ?string $quoteTypeParam = null,
        bool $isSendUpdateEligibleForOCR = false
    ): void {

        // Skip OCR only for SendUpdate logs that are NOT eligible (e.g., Car SendUpdate)
        if ($quote instanceof SendUpdateLog && ! $isSendUpdateEligibleForOCR) {
            LoggerService::info(self::class.'::populateDocumentData - Send Update Log found but not eligible for OCR, skipping document data population', [
                'quote_uuid' => $quote->uuid,
                'quote_type' => ucfirst(request('quote_type')),
                'document_type' => $documentType->code,
                'reason' => 'Send Update not eligible for OCR for this LOB',
                'eligible_lobs' => 'HOME, GROUP_MEDICAL only',
            ]);

            return;
        }

        $quoteType = $this->determineQuoteType($quoteTypeParam);

        // If quote type is not available from request and this is a Send Update Log, get it from the model
        if (! $quoteType && $quote instanceof SendUpdateLog) {
            $quoteType = $this->getCorrectQuoteTypeForOCR($quote);
            LoggerService::info('PopulateDocumentData - Quote type derived from Send Update Log', [
                'quote_type_id' => $quote->quote_type_id,
                'derived_quote_type' => $quoteType?->value,
                'quote_uuid' => $quote->uuid,
                'is_group_medical_override' => $this->isGroupMedicalBusiness($quote),
            ]);
        }

        // For regular Business quotes, check if they are Group Medical and adjust quote type accordingly
        if ($quoteType === QuoteTypes::BUSINESS && $this->isGroupMedicalBusiness($quote)) {
            $quoteType = QuoteTypes::GROUP_MEDICAL;
            LoggerService::info('PopulateDocumentData - Business quote type overridden to Group Medical', [
                'original_quote_type' => 'Business',
                'new_quote_type' => $quoteType->value,
                'quote_uuid' => $quote->uuid,
                'quote_model' => get_class($quote),
            ]);
        }

        // Final check if we still couldn't determine the quote type
        if (! $quoteType) {
            LoggerService::info('OCR Dispatch - Unable to determine quote type after all attempts - Quote UUID: '.$quote->uuid);

            return;
        }

        LoggerService::info('PopulateDocumentData - About to dispatch OCR job', [
            'parsed_quote_type' => $quoteType?->value,
            'quote_uuid' => $quote->uuid ?? 'N/A',
            'quote_code' => $quote->code ?? 'N/A',
            'is_send_update_eligible_for_ocr' => $isSendUpdateEligibleForOCR,
            'will_dispatch_ocr_job' => ! is_null($quoteType) && ! is_null($quote) && ! is_null($filePathAzure),
            'document_type' => $documentType->code,
            'user_id' => Auth::user()->id ?? 'Not authenticated',
        ]);

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
                $isSendUpdateEligibleForOCR
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

    private function getCorrectQuoteTypeForOCR($quote)
    {
        // For Group Medical business quotes, return GROUP_MEDICAL instead of BUSINESS
        if ($this->isGroupMedicalBusiness($quote)) {
            return QuoteTypes::GROUP_MEDICAL;
        }

        // For all other cases, use the normal mapping
        return QuoteTypes::getName($quote->quote_type_id);
    }

    public function isGroupMedicalBusiness($quote)
    {
        try {
            // Handle direct BusinessQuote instances
            if ($quote instanceof BusinessQuote) {
                return $quote->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL;
            }

            // Handle SendUpdateLog instances
            if ($quote instanceof SendUpdateLog && $quote->quote_type_id == QuoteTypes::getId(QuoteTypes::BUSINESS)) {
                $actualQuote = BusinessQuote::where('uuid', $quote->quote_uuid)->first();

                return $actualQuote && $actualQuote->business_type_of_insurance_id == BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL;
            }

            return false;
        } catch (\Exception $e) {
            LoggerService::error('Error checking Group Medical business type', [
                'error' => $e->getMessage(),
                'quote_type' => get_class($quote),
                'quote_id' => $quote->id ?? 'N/A',
                'quote_uuid' => $quote->uuid ?? 'N/A',
            ]);

            return false;
        }
    }
}
