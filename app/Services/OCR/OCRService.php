<?php

namespace App\Services\OCR;

use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Models\DocumentType;
use App\Services\QuoteDocumentService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OCRService
{
    use Ocrable, OcrFillable;

    public function __construct(protected QuoteDocumentService $quoteDocumentService) {}

    private function sendRequest(string $endpoint, array $data = [], string $method = 'POST')
    {
        try {
            $response = Http::baseUrl(config('constants.OCR_API_ENDPOINT'))
                ->withHeader('Referer', trim(config('constants.APP_URL'), '/'))
                ->withHeader('x-api-key', config('constants.OCR_API_KEY'))
                ->timeout(60)
                ->beforeSending(fn () => info(self::class."::sendRequest - Calling OCR API via {$method} request to {$endpoint}"))
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

    private function getData(string $docUrl, OCRDocumentTypeEnum $docType)
    {
        $response = $this->sendRequest('/process-document', [
            'doc_url' => $docUrl,
            'doc_type' => $docType,
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
        string $documentPath): ?bool
    {
        $docType = OCRDocumentTypeEnum::getDocumentType($documentType);

        if (! $docType?->isEnabled($quoteType)) {
            info(self::class."::process - OCR is not enabled for this document type {$documentType->code} for quote {$quote?->uuid}");

            return null;
        }

        $url = $this->quoteDocumentService->getDocumentUrl($documentPath);

        $data = Cache::remember('data', now()->addHour(1), fn () => $this->getData($url, $docType));

        if ($data) {
            return $this->fill(
                $quoteType,
                $quote,
                $documentType,
                $data
            );
        }

        return false;
    }
}
