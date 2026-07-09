<?php

namespace App\Services;

use App\DTO\EpBookingContext;
use App\Enums\EmbeddedProductEnum;
use App\Enums\EmbeddedTransactionEnum;
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
use App\Traits\ChecksAzureFileExistence;
use App\Traits\GenericQueriesAllLobs;
use Error;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class EpBookingService extends BaseService
{
    use ChecksAzureFileExistence, GenericQueriesAllLobs;

    protected string $logPrefix = '';
    protected array $logExtra = [];
    public int $providerId = 0;
    public mixed $quote = null;
    public ?EmbeddedTransaction $embeddedTransaction = null;
    public array $reqDocTypeCodes = [];
    public array $watermarkableDocTypeCodes = [];

    public const STEP_GET_POLICY_DOCUMENTS = 'GetPolicyDocuments';
    public const CALL_TYPE_EP_ECB = 'EpEcb';

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
        $this->watermarkableDocTypeCodes = $this->getWatermarkableDocTypeCodes($this->context->epShortCode);
    }

    public static function buildContext(int $etId, string $quoteId, int $quoteTypeId, string $quoteCode)
    {
        $ep = EmbeddedProduct::whereHas('prices.transactions', fn ($q) => $q->where('id', $etId))->first();

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

        LoggerService::info(self::class.' Starting processWatermarkDocuments', extra: [
            'documentTypeCodes' => $documentTypes->pluck('code')->toArray(),
            'documentItems' => $documents->select('is_watermarked', 'document_type_code')->toArray(),
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

                if (! $documentType) {
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

        LoggerService::info(self::class.' Completed processWatermarkDocuments: ', extra: [...$extraLog, 'watermarked_status' => $watermarkedStatus]);

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
                throw new Error('File is already being processed. Retrying later');
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
                'extraLog' => $extraLog,
            ];

        } catch (\Exception $e) {
            if ($this->isTransientFileExistenceFailure($e)) {
                LoggerService::warning(self::class." Transient file existence failure: {$e->getMessage()}", extra: [
                    'exception_class' => $e::class,
                    'previous_exception_class' => $e->getPrevious() ? $e->getPrevious()::class : null,
                    'previous_exception_message' => $e->getPrevious()?->getMessage(),
                ]);

                throw $e;
            }

            return [
                'success' => false,
                'data' => null,
                'error' => $e->getMessage(),
                'extraLog' => $extraLog,
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

        LoggerService::info($this->logPrefix.' Transaction status updated', extra: [
            ...$this->logExtra,
            'policy_status' => $this->embeddedTransaction?->policy_status,
            'missing_watermarable_doc_type_codes' => $missingWatermarableDocTypeCodes,
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
                    'policy_status' => $item->policy_status ?? '',
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

        if ($this->quote->quote_status_id != QuoteStatusEnum::POLICY_BOOKING_QUEUED) {
            $sageApiService->updateAndLogQuoteStatus($this->quote, $this->context?->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_QUEUED);
        }

        $request = new \stdClass;
        $request->quote_id = $this->quote?->id;
        $request->modelType = $quoteType;
        $request->model_type = $quoteType;
        $request->is_send_policy = false;
        $request->send_policy_type = SendPolicyTypeEnum::SAGE;
        $request->transaction_payment_status = null;

        $createSageProcessResponse = $sageApiService->postBookPolicyToSage($request, $this->quote);

        if (! $createSageProcessResponse['status']) {
            $sageApiService->updateAndLogQuoteStatus($this->quote, $this->context?->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_FAILED);
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

    protected function getWatermarkableDocTypeCodes($epShortCode): array
    {
        return QuoteDocumentsEnum::getWatermarkableDocTypeCodes($epShortCode);
    }

    protected function getRequiredDocTypeCodes(): array
    {
        return [
            QuoteDocumentsEnum::POLICY_SCHEDULE,
            QuoteDocumentsEnum::CAR_EP_TAX_INVOICE,
            QuoteDocumentsEnum::CAR_TAX_INVOICE_RAISE_BY_BUYER,
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
        if ($this->getDocTypeFromUrl($url) == '2') {
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
            return $this->checkAzureFileExistsWithRetry($path);
        } catch (Throwable $e) {
            LoggerService::error(self::class." Error checking file existence: {$path}. Error: ".$e->getMessage(), extra: [
                'exception_class' => $e::class,
                'previous_exception_class' => $e->getPrevious() ? $e->getPrevious()::class : null,
                'previous_exception_message' => $e->getPrevious()?->getMessage(),
            ]);

            throw new RuntimeException("Unable to check existence for: {$path}", previous: $e);
        }
    }

    /**
     * Upload policy document.
     *
     * $response = $this->uploadDocument($fileName, $fileContent, $dir);
     *
     * @return string The generated UUID.
     */
    protected function uploadDocument($docName, $fileContent, $dir): array
    {
        try {
            $fileNameAzure = uniqid()."_{$docName}";
            $docUrl = "{$dir}/{$fileNameAzure}";
            $isSuccess = Storage::disk('azureIMPrivate')->put($docUrl, $fileContent);

            if (! $isSuccess) {
                throw new Error("Process Failed, doc_name: {$docName}, doc_url: {$docUrl}");
            }

            return [
                'success' => $isSuccess,
                'statusCode' => 200,
                'statusMessage' => 'Success',
                'data' => ['doc_name' => $docName, 'doc_url' => $docUrl],
            ];
        } catch (Throwable $e) {

            return [
                'success' => false,
                'statusCode' => 402,
                'error' => 'UploadDocument: '.$e->getMessage(),
            ];
        }
    }

    public static function updateInsurerRequestResponseDocumentNumberForSageBooking(EmbeddedTransaction $quote, ?string $duplicateNumber): bool
    {
        $quoteUuid = Str::afterLast($quote->code, '-');
        $epShortCode = $quote->product?->embeddedProduct?->short_code ?? '';
        $updates = self::prepareSageBookingInvoiceNumberUpdates($quote, $quoteUuid, $duplicateNumber, $epShortCode);

        if (empty($updates)) {
            LoggerService::info(self::class.' - EP table invoice numbers missing, null, or already include Sage postfix', extra: [
                'quote_uuid' => $quoteUuid,
                'ep_code' => $quote->code,
            ]);

            return false;
        }

        return self::persistSageBookingInvoiceNumberUpdates($quote, $quoteUuid, $updates);
    }

    private static function prepareSageBookingInvoiceNumberUpdates(EmbeddedTransaction $quote, string $quoteUuid, ?string $duplicateNumber, string $epShortCode = ''): array
    {
        $updates = [];
        $isMdxOrRdx = in_array($epShortCode, EmbeddedProductEnum::getSukoonMedexCodes(), true);

        if ($quote->tax_invoice_no !== null && ($quote->tax_invoice_no === $duplicateNumber || ($isMdxOrRdx && Str::endsWith($quote->tax_invoice_no, $duplicateNumber)))) {
            $updated = self::withSageDocumentNumberPostfix($quote->tax_invoice_no);

            if ($updated !== $quote->tax_invoice_no) {
                $updates['tax_invoice_no'] = $updated;

                LoggerService::info(self::class.' - Prepared Tax Invoice number update in EP table for Sage booking', extra: [
                    'quote_uuid' => $quoteUuid,
                    'ep_code' => $quote->code,
                    'previous_tax_invoice_no' => $quote->tax_invoice_no,
                    'updated_tax_invoice_no' => $updated,
                ]);
            } else {
                LoggerService::info(self::class.' - Tax Invoice number already includes Sage postfix, no update needed', extra: [
                    'quote_uuid' => $quoteUuid,
                    'ep_code' => $quote->code,
                    'tax_invoice_no' => $quote->tax_invoice_no,
                ]);
            }
        }

        if ($quote->tax_invoice_buyer_no !== null && ($quote->tax_invoice_buyer_no === $duplicateNumber || ($isMdxOrRdx && Str::endsWith($quote->tax_invoice_buyer_no, $duplicateNumber)))) {
            $updated = self::withSageDocumentNumberPostfix($quote->tax_invoice_buyer_no);

            if ($updated !== $quote->tax_invoice_buyer_no) {
                $updates['tax_invoice_buyer_no'] = $updated;

                LoggerService::info(self::class.' - Prepared Commission Invoice number update in EP table for Sage booking', extra: [
                    'quote_uuid' => $quoteUuid,
                    'ep_code' => $quote->code,
                    'previous_tax_invoice_buyer_no' => $quote->tax_invoice_buyer_no,
                    'updated_tax_invoice_buyer_no' => $updated,
                ]);
            } else {
                LoggerService::info(self::class.' - Commission Invoice number already includes Sage postfix, no update needed', extra: [
                    'quote_uuid' => $quoteUuid,
                    'ep_code' => $quote->code,
                    'tax_invoice_buyer_no' => $quote->tax_invoice_buyer_no,
                ]);
            }
        }

        return $updates;
    }

    private static function persistSageBookingInvoiceNumberUpdates(EmbeddedTransaction $quote, string $quoteUuid, array $updates): bool
    {
        $isPersisted = false;

        try {
            $quoteUpdated = $quote->update([
                ...$updates,
                'sage_invoice_no_update_at' => now(),
            ]);

            if ($quoteUpdated) {
                $isPersisted = true;

                LoggerService::info(self::class.' - Updated EP table invoice numbers for Sage booking', extra: [
                    'quote_uuid' => $quoteUuid,
                    'ep_code' => $quote->code,
                    'updated_fields' => array_keys($updates),
                ]);
            } else {
                LoggerService::warning(self::class.' - Failed to save updated EP table invoice numbers for Sage booking', extra: [
                    'quote_uuid' => $quoteUuid,
                    'ep_code' => $quote->code,
                ]);
            }
        } catch (Throwable $exception) {
            LoggerService::warning(self::class.' - Exception while updating EP table invoice numbers for Sage booking', extra: [
                'quote_uuid' => $quoteUuid,
                'ep_code' => $quote->code,
                'error' => $exception->getMessage(),
            ], exception: $exception);
        }

        return $isPersisted;
    }

    protected static function withSageDocumentNumberPostfix(mixed $documentNumber): mixed
    {
        if (! is_string($documentNumber) || $documentNumber === '') {
            return $documentNumber;
        }

        // If the document number already ends with /digits, return it unchanged
        if (preg_match('/^(.*)\/(\d+)$/', $documentNumber)) {
            return $documentNumber;
        }

        return $documentNumber.'/1';
    }
}
