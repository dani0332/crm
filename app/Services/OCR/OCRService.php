<?php

namespace App\Services\OCR;

use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Events\OcrNotifications;
use App\Models\DocumentType;
use App\Services\QuoteDocumentService;
use App\Services\CentralService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

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
                ->beforeSending(fn () => info(self::class."::sendRequest - Calling OCR API via {$method} request to {$endpoint}", $data))
                ->when(
                    $method === 'GET',
                    fn (PendingRequest $http) => $http->get($endpoint, $data),
                    fn (PendingRequest $http) => $http->post($endpoint, $data)
                );

            return $this->handleResponse($response, $endpoint);
        } catch (Exception $e) {
            info(self::class." - Exception occurred during API call: {$e->getMessage()}");

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
        $response = $this->sendRequest('/process-document', [
            'ref_id' => $quote->code,
            'uuid' => $quote->uuid,
            'quote_type_id' => $quoteType->id(),
            'doc_url' => $docUrl,
            'doc_type' => $docType->value,

            // for now image would be false on the basis of Hamas Request
            'image' => false,
        ]);

        if ($response['ok']) {
            return $response['object'];
        }

        return null;
    }

    public function process(
        QuoteTypes $quoteType,
        Model $quote,
        DocumentType $documentType,
        string $documentPath,
        string $fileMimeType,
        int $userId,
    ): ?bool {
        $docType = OCRDocumentTypeEnum::getDocumentType($documentType);

        if (! $docType?->isEnabled($quoteType)) {
            info(self::class."::process - OCR is not enabled for this document type {$documentType->code}");
            return null;
        }

        // Send start notification for TAX_INVOICE, TAX_INVOICE_RAISED_BY_BUYER, and CERTIFICATE_OF_ISSUANCE document types
        if (in_array($docType, [
            OCRDocumentTypeEnum::TAX_INVOICE,
            OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER,
            OCRDocumentTypeEnum::CERTIFICATE_OF_ISSUANCE
        ])) {
            event(new OcrNotifications($quote, 'start', 'OCR processing started', null, $docType?->value, $userId));
        }

        $url = $this->quoteDocumentService->getDocumentUrl($documentPath);

        try {
            $data = $this->getData($quoteType, $quote, $url, $docType, $fileMimeType);
            if ($data) {
                LoggerService::info(self::class."::process - Data received from getData", extra:['data' => $data]);
                $dataFilledResponse = $this->fill(
                    $quote,
                    $docType,
                    $data
                );
                (new CentralService)->updateQuoteInformation($quoteType->value, $quote->id);

                // Send end notification for TAX_INVOICE, TAX_INVOICE_RAISED_BY_BUYER, and CERTIFICATE_OF_ISSUANCE document types when processing completes successfully
                if (in_array($docType, [
                    OCRDocumentTypeEnum::TAX_INVOICE,
                    OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER,
                    OCRDocumentTypeEnum::CERTIFICATE_OF_ISSUANCE
                ]) && $dataFilledResponse) {
                    event(new OcrNotifications($quote, 'end', 'OCR processing completed successfully', null, $docType?->value, $userId));
                }

                return $dataFilledResponse;
            } else {
                // Send fail notification for supported document types
                if (in_array($docType, [
                    OCRDocumentTypeEnum::TAX_INVOICE,
                    OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER,
                    OCRDocumentTypeEnum::CERTIFICATE_OF_ISSUANCE
                ])) {
                    event(new OcrNotifications($quote, 'fail', 'OCR processing failed', null, $docType?->value, $userId));
                }
                return false;
            }
        } catch (\Exception $e) {
            // Send fail notification with error for supported document types
            if (in_array($docType, [
                OCRDocumentTypeEnum::TAX_INVOICE,
                OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER,
                OCRDocumentTypeEnum::CERTIFICATE_OF_ISSUANCE
            ])) {
                event(new OcrNotifications($quote, 'fail', 'OCR processing failed', $e->getMessage(), $docType?->value, $userId));
            }
            throw $e;
        }
    }
}
