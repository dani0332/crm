<?php

namespace App\Services;

use App\DTO\EpBookingContext;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Jobs\EpSendDocumentJob;
use App\Models\DocumentType;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedTransaction;
use App\Models\QuoteDocument;
use App\Repositories\EmbeddedTransactionRepository;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Error;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EpBookingService extends BaseService
{
    use GenericQueriesAllLobs;

    protected string $className = 'EpBookingService:';
    protected string $logPrefix = '';
    protected array $logExtra = [];

    public int $providerId = 0;

    public mixed $quote = null;
    public ?EmbeddedTransaction $embeddedTransaction = null;
    public array $reqDocTypeCodes = [];
    public array $watermarkableDocTypeCodes = [];

    /**
     * Create a new class instance.
     */
    protected function __construct(
        public string $epServiceName,
        public EpBookingContext $context
    ) {
        $this->logPrefix = "{$epServiceName}Service:";

        $quoteType = QuoteTypes::getName($this->context->quoteTypeId)->value;
        $this->quote = $this->getQuoteObject($quoteType, $this->context->quoteId);

        // Load embedded transaction
        $this->embeddedTransaction = EmbeddedTransaction::find($this->context->etId);

        $this->reqDocTypeCodes = $this->getRequiredDocTypeCodes();
        $this->watermarkableDocTypeCodes = $this->getWatermarkableDocTypeCodes();
    }

    public static function buildContext(int $etId, string $quoteId, int $quoteTypeId, string $quoteCode)
    {
        $ep = EmbeddedProduct::whereHas('prices.transactions', fn($q) => $q->where('id', $etId))->first();

        $epBookingContext = new EpBookingContext(
            etId: (int) $etId,
            quoteId: (string) $quoteId,
            quoteTypeId: (int) $quoteTypeId,
            quoteCode: (string) $quoteCode,
            epShortCode: (string) $ep->short_code ?? '',
            insuranceProviderId: (int) $ep->insurance_provider_id ?? 0
        );

        return $epBookingContext;
    }

    public function processWatermarkDocuments(Collection $documents, array $watermarkableDocTypeCodes): array
    {
        $documentTypes = DocumentType::whereIn('code', $watermarkableDocTypeCodes)
            ->where(['quote_type_id' => $this->context->quoteTypeId, 'is_active' => 1])->get();

        LoggerService::info("{$this->className} Starting processWatermarkDocuments", extra: [
            'documentTypeCodes' => $documentTypes->pluck('code')->toArray(),
            'documentItems' => $documents->select('is_watermarked', 'document_type_code')->toArray()
        ]);

        $watermarkedDocuments = [];
        $watermarkedStatus = ['created' => [], 'skipped' => []];
        $extraLog = $this->context->logExtra;

        if ((count($documents) > 0)) {
            foreach ($documents as $documentItem) {

                // skip iteration when document_type_code is not from initial document types
                if (! in_array($documentItem->document_type_code, $watermarkableDocTypeCodes)) {
                    $watermarkedStatus['skipped'][] = "{$documentItem->document_type_code} is not watermarkable";
                    continue;
                }

                // skip iteration when document is already watermarked
                if ($documentItem->is_watermarked) {
                    $watermarkedStatus['skipped'][] = "{$documentItem->document_type_code} is already watermarked";
                    continue;
                }

                $documentType = $documentTypes->firstWhere('code', $documentItem->document_type_code);

                if (!$documentType) {
                    $watermarkedStatus['skipped'][] = "{$documentItem->document_type_code} document_type is not found";
                    continue;
                }

                $savedWatermarkedDocument = $this->watermarkDocument($documentItem, $documentType);

                if ($savedWatermarkedDocument['success'] == true) {
                    $watermarkedDocuments[] = $savedWatermarkedDocument['data'];
                    $message = $savedWatermarkedDocument['message'] ?? 'watermarked';
                    $watermarkedStatus['created'][] = "{$documentItem->document_type_code} {$message}";
                    $extraLog = [...$extraLog, ...$savedWatermarkedDocument['extraLog']];
                    continue;
                }

                $errorMessage = $savedWatermarkedDocument['error'] ?? 'Unknown error';
                $watermarkedStatus['skipped'][] = "{$documentItem->document_type_code} {$errorMessage}";
                $extraLog = [...$extraLog, ...$savedWatermarkedDocument['extraLog']];
            }
        }

        LoggerService::info("{$this->className} Completed processWatermarkDocuments: ", extra: [...$extraLog, 'watermarked_status' => $watermarkedStatus]);

        return $watermarkedDocuments;
    }

    public function watermarkDocument(QuoteDocument $quoteDocument, DocumentType $documentType): array
    {
        $lockKey = "watermark_{$quoteDocument?->id}_{$this->quote?->uuid}_{$documentType?->id}";

        $extraLog = [
            'uuid' => $this->quote?->uuid,
            'lock_key' => $lockKey,
            'document_id' => $quoteDocument?->id,
            'document_type_code' => $quoteDocument?->document_type_code,
        ];

        try {
            // Check if the file is already being processed
            if ($this->isFileBeingProcessed($lockKey)) {
                throw new Error("File is already being processed. Retrying later");
            }

            // Check if the source file exists
            if (empty($quoteDocument->doc_url) || ! $this->fileExists($quoteDocument->doc_url)) {
                throw new Error("Source file does not exist: {$quoteDocument->doc_url}");
            }

            // Perform watermarking based on file type
            $watermarkService = app()->make(QuoteDocumentService::class);
            $fileMimeType = $quoteDocument->doc_mime_type;
            $docName = str_replace('original_', '', $quoteDocument->doc_name);

            $extension = strtolower(pathinfo($quoteDocument->doc_name, PATHINFO_EXTENSION));
            $watermarkData = [];
            if ($fileMimeType == 'application/pdf' || $fileMimeType == '.pdf' || $extension == 'pdf') {
                $watermarkData = $watermarkService->watermarkPdf($quoteDocument->doc_url, $docName, $this->quote->uuid, $documentType);
            } else {
                throw new Error("Unsupported file type: fileMimeType: {$fileMimeType}, extension: {$extension}");
            }

            // Update the document with watermark data
            if (isset($watermarkData['watermarked_doc_name']) && isset($watermarkData['watermarked_doc_url'])) {
                $quoteDocument->update([
                    'watermarked_doc_name' => $watermarkData['watermarked_doc_name'],
                    'watermarked_doc_url' => $watermarkData['watermarked_doc_url'],
                ]);
            }

            return [
                'success' => true,
                'data' => $quoteDocument,
                'message' => 'Watermarked document successfully',
                'extraLog' => $extraLog
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage(),
                'extraLog' => $extraLog
            ];
        }
    }

    public function finalizeTransactionStatus(): void
    {
        $policyPrice = floatval($this->embeddedTransaction?->policy_price ?? 0);

        $watermarkedDocumentDocTypeCodes = $this->embeddedTransaction?->documents()
            ->whereIn('document_type_code', $this->watermarkableDocTypeCodes)->get()
            ->where('is_watermarked', true)
            ->pluck('document_type_code')
            ->toArray();

        $missingWatermarableDocTypeCodes = array_diff($this->watermarkableDocTypeCodes, $watermarkedDocumentDocTypeCodes);

        $isPolicyBooked = $this->embeddedTransaction?->policy_status == EmbeddedTransactionEnum::STATUS_BOOKED;
        if (empty($missingWatermarableDocTypeCodes) && $isPolicyBooked && $policyPrice > 0) {
            $this->embeddedTransaction->update(['policy_status' => EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE]);
            dispatch(new EpSendDocumentJob($this->context));
        }

        LoggerService::info($this->logPrefix . ' Transaction status updated', extra: [
            ...$this->logExtra,
            'policy_status' => $this->embeddedTransaction?->policy_status,
            'missing_watermarable_doc_type_codes' => $missingWatermarableDocTypeCodes
        ]);
    }

    public function handleJobSuccess()
    {
        $response = [];

        $this->embeddedTransaction->refresh();
        $quoteStatusId = $this->quote?->quote_status_id;
        $epPolicyStatus = $this->embeddedTransaction?->policy_status;

        LoggerService::info("{$this->logPrefix} Begin handleJobSuccess: QuoteStatusId: {$quoteStatusId}, EpPolicyStatus: {$epPolicyStatus}");

        $epTransactionRepo = app(EmbeddedTransactionRepository::class);
        $underProcessEpTransactions = $epTransactionRepo->getUnderProcessEpTransactions($this->context->quoteTypeId, $this->context->quoteId);
        if ($underProcessEpTransactions->isEmpty()) {

            $response = match ($quoteStatusId) {
                QuoteStatusEnum::PolicyIssued => $this->callSageBookingProcess(),
                QuoteStatusEnum::PolicyBooked => $this->scheduleSageBookingForEp(),
                default => ['status' => true, 'message' => 'Sage booking is not called'],
            };

        } else {
            $underProcessEpDetails = $underProcessEpTransactions->map(function ($item) { 
                return [
                    'et_id' => $item->id,
                    'short_code' => $item->product->embeddedProduct->short_code ?? '', 
                    'payment_status_id' => $item->payment_status_id ?? '', 
                    'policy_status' => $item->policy_status ?? ''
                ];
            });
            LoggerService::info("{$this->logPrefix} Under process EP transactions found: ", extra: ['underProcessEpDetails' => $underProcessEpDetails]);
        }

        LoggerService::info("{$this->logPrefix} Finish handleJobSuccess: QuoteStatusId: {$quoteStatusId}, EpPolicyStatus: {$epPolicyStatus}", extra: ['response' => $response]);

        return $response;
    }

    private function callSageBookingProcess()
    {
        $quoteType = QuoteTypes::getName($this->context->quoteTypeId)->value;

        $sageApiService = (new SageApiService);
        $sageApiService->updateAndLogQuoteStatus($this->quote, $this->context?->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_QUEUED, null);

        $request = new \stdClass;
        $request->quote_id = $this->quote?->id;
        $request->modelType = $quoteType;
        $request->model_type = $quoteType;
        $request->is_send_policy = false;
        $request->send_policy_type = SendPolicyTypeEnum::SAGE;
        $request->transaction_payment_status = null;

        $createSageProcessResponse = $sageApiService->postBookPolicyToSage($request, $this->quote);

        if (! $createSageProcessResponse['status']) {
            $sageApiService->updateAndLogQuoteStatus($this->quote, $this->context?->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_FAILED, null);
        }

        return $createSageProcessResponse;
    }

    private function scheduleSageBookingForEp()
    {
        $quoteType = QuoteTypes::getName($this->context->quoteTypeId)->value;

        $request = [
            'epTransactionId' => $this->embeddedTransaction->id, // embedded_transaction_id
            'insuranceProviderId' => $this->context->insuranceProviderId, // embedded_product's provider_id
            'modelType' => $quoteType, // lead quote_type (Car)
            'quoteId' => $this->quote?->id, // lead quote_id
        ];

        $scheduledBookingResponse = (new SageApiEmbeddedProductService)->scheduleBookingOfEmbeddedProduct($request);

        return $scheduledBookingResponse;
    }

    protected function getWatermarkableDocTypeCodes(): array
    {
        return [
            QuoteDocumentsEnum::POLICY_SCHEDULE,
            QuoteDocumentsEnum::CAR_TAX_INVOICE
        ];
    }

    protected function getRequiredDocTypeCodes(): array
    {
        return [
            QuoteDocumentsEnum::POLICY_SCHEDULE,
            QuoteDocumentsEnum::CAR_TAX_INVOICE,
            QuoteDocumentsEnum::CAR_TAX_INVOICE_RAISE_BY_BUYER
        ];
    }

    protected function getMissingDocumentDocTypes(array $checkableDocTypeCodes, $isWatermarked = false): array
    {
        $this->embeddedTransaction->load('documents');
        $savedDocumentDocTypes = $this->embeddedTransaction->documents()
            ->whereIn('document_type_code', $checkableDocTypeCodes)->get()
            ->when($isWatermarked, fn ($q) => $q->where('is_watermarked', true))
            ->pluck('document_type_code')
            ->toArray();

        return array_diff($checkableDocTypeCodes, $savedDocumentDocTypes);
    }

    /**
     * Check if the file is already being processed
     */
    private function isFileBeingProcessed($lockKey)
    {
        // Use cache to track processing status
        $cacheKey = "processing_{$lockKey}";
        $lockAcquired = cache()->add($cacheKey, true, now()->addMinutes(3));

        return ! $lockAcquired;
    }

    protected function makeFileNameFromUrl(string $url): string
    {
        $extractedFileName = str_replace('get', '', strtolower($this->extractFilename($url)));
        if($this->getDocTypeFromUrl($url) == '2') {
            $extractedFileName .= '_raise_by_buyer';
        }

        return $extractedFileName;
    }

    /**
     * Extract filename from HTTP response headers or URL
     */
    protected function extractFilename(string $url): string
    {
        return basename(parse_url($url, PHP_URL_PATH)) ?? 'document';
    }

    private function getDocTypeFromUrl(string $url): ?string
    {
        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $params);
        return $params['DOCTYPE'] ?? null;
    }

    /**
     * Check if a file exists
     */
    private function fileExists(string $path): bool
    {
        try {
            // For local storage
            if (Storage::disk('azureIM')->exists($path)) {
                return true;
            }

            // For remote URLs
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                $headers = get_headers($path);

                return $headers && strpos($headers[0], '200') !== false;
            }

            return false;
        } catch (\Exception $e) {
            LoggerService::error("{$this->className} Error checking file existence: {$path}. Error: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Upload policy document.
     *
     * $response = $this->uploadDocument($fileName, $fileContent, $dir);
     * @return string The generated UUID.
     */
    protected function uploadDocument($docName, $fileContent, $dir): array
    {
        try {
            $fileNameAzure = uniqid() . "_{$docName}";
            $docUrl = "{$dir}/{$fileNameAzure}";
            $isSuccess = Storage::disk('azureIM')->put($docUrl, $fileContent);

            if (! $isSuccess) {
                throw new Error("Process Failed, doc_name: {$docName}, doc_url: {$docUrl}");
            }

            return [
                'success' => $isSuccess,
                'statusCode' => 200,
                'statusMessage' => 'Success',
                'data' => ['doc_name' => $docName, 'doc_url' => $docUrl]
            ];
        } catch (Throwable $e) {

            return [
                'success' => false,
                'statusCode' => 402,
                'error' => "UploadDocument: " . $e->getMessage(),
            ];
        }
    }
}
