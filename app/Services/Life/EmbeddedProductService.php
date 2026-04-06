<?php

namespace App\Services\Life;

use App\Enums\EmbeddedProductEnum;
use App\Enums\EpCategoryEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\RolesEnum;
use App\Jobs\WatermarkDocumentsJob;
use App\Models\CustomerAddress;
use App\Models\DocumentType;
use App\Models\EmbeddedProduct;
use App\Models\EmbeddedTransaction;
use App\Models\QuoteDocument;
use App\Models\QuoteType;
use App\Repositories\EmbeddedProductRepository;
use App\Services\BaseService;
use App\Services\Logger\LoggerService;
use App\Strategies\EmbeddedProducts\EmbeddedProduct as EmbeddedProductStrategy;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmbeddedProductService extends BaseService
{
    use GenericQueriesAllLobs;

    public function __construct(
        private EmbeddedProductRepository $embeddedProductRepository
    ) {
        parent::__construct();
    }

    public function byQuoteTypeId($quoteTypeId, $quoteRequestId)
    {
        $ep = EmbeddedProduct::whereHas('placements', function ($query) use ($quoteTypeId) {
            $query->where('quote_type_id', $quoteTypeId);
        })
            ->with([
                'prices' => function ($query) {
                    $query->where('is_active', 1);
                },
                'prices.transactions' => function ($query) use ($quoteRequestId) {
                    $query->where('quote_request_id', $quoteRequestId);
                },
                'prices.transactions.payments',
                'prices.transactions.travelAnnualPayments',
            ])
            ->whereHas('prices.transactions', function ($query) use ($quoteRequestId) {
                $query->where('quote_request_id', $quoteRequestId);
            })
            ->get();
        $modelType = QuoteType::where('id', '=', $quoteTypeId)->value('code');
        $ep->each(function ($item) use ($modelType, $quoteTypeId, $quoteRequestId) {
            $item->send_document_button = false;
            $item->download_document_button = false;
            $optionsIds = $item->prices->pluck('id');
            $item->sync_document_button = false;

            $transaction = EmbeddedTransaction::with('documents', 'product.embeddedProduct')->where([
                ['quote_type_id', '=', $quoteTypeId],
                ['quote_request_id',  '=', $quoteRequestId],
                ['is_selected',  '=', true],
            ])->whereIn('product_id', $optionsIds)->get();
            $quoteObject = $this->getQuoteObject($modelType, $quoteRequestId);

            $isAlfredProtect = EmbeddedProductStrategy::checkAlfredProtect($item->short_code);
            if ($isAlfredProtect) {
                $isDocPresent = count($transaction) > 0 ? $transaction[0]->documents()->count() > 0 : false;
                $item->download_document_button = $isDocPresent && $this->canSendAndDownloadDocuments($item->product_category, $quoteObject->quote_status_id, $transaction);

                if (auth()->user()->hasRole(RolesEnum::Engineering)) {
                    $documentCount = $isDocPresent ? $transaction[0]->documents()->count() : 0;
                    $item->sync_document_button = $documentCount < 5;
                }

            }

            $item->send_document_button = $this->canSendAndDownloadDocuments($item->product_category, $quoteObject->quote_status_id, $transaction);
            $item->can_cancel_payment = $this->canCancelPayment($transaction->first(), $quoteTypeId);
        });

        return $ep;
    }

    private function canSendAndDownloadDocuments($productCategory, $quoteStatusId, $transaction)
    {
        if (
            ! $transaction->isEmpty() &&
            in_array($transaction->first()->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED]) &&
            (
                $productCategory == EpCategoryEnum::STAND_ALONE ||
                ($productCategory == EpCategoryEnum::BOLT_ON && in_array($quoteStatusId, $this->canSendDocumentEnums()))
            )
        ) {
            return true;
        }

        return false;
    }

    private function canCancelPayment($transaction, $quoteTypeId)
    {
        if ($transaction && $transaction->payments->first()) {
            $payment = $transaction->payments->first();
            if ($payment->getAttributes()['payment_status_id'] == PaymentStatusEnum::CAPTURED) {

                if ($transaction->product->embeddedProduct->short_code == EmbeddedProductEnum::COURIER) {
                    return CustomerAddress::where('quote_uuid', $transaction->quoteRequest->uuid)->where('quote_type_id', $quoteTypeId)->count() == 0;
                }

                $paymentDate = Carbon::parse($payment->getAttributes()['captured_at']);

                return $paymentDate->diffInDays(Carbon::now()) <= 3;
            }
        }

        return false;
    }

    public function canSendDocumentEnums(): array
    {
        return [
            QuoteStatusEnum::PolicySentToCustomer,
            QuoteStatusEnum::PolicyBooked,
            QuoteStatusEnum::CancellationPending,
            QuoteStatusEnum::PolicyCancelled,
            QuoteStatusEnum::PolicyCancelledReissued,
        ];
    }

    public function deSelectEPTransactions($quoteId)
    {
        LoggerService::info("De-selecting embedded product transactions for quote id: {$quoteId}");
        EmbeddedTransaction::where('quote_request_id', $quoteId)
            ->where('is_selected', 1)
            ->update(['is_selected' => 0]);
    }

    /**
     * Replaces an embedded-product quote document (manual override), including storage upload and optional watermark dispatch.
     */
    public function updateEpDocument(array $data): bool
    {
        $ctx = $this->resolveUpdateEpDocumentContext($data);
        if ($ctx === null) {
            return false;
        }

        $embeddedTransaction = $ctx['embeddedTransaction'];
        $oldDocument = $ctx['oldDocument'];
        $documentType = $ctx['documentType'];
        $quoteObject = $ctx['quoteObject'];
        $storedDocName = $ctx['storedDocName'];

        $uploadedFile = $data['file'];
        $originalName = $uploadedFile->getClientOriginalName();
        $uniqueBlobName = $this->embeddedProductRepository->uniqueBlobNameFromOriginalName($originalName);
        $azureObjectName = $quoteObject->uuid.'_'.$uniqueBlobName;
        $docUuid = uniqid();

        $filePathAzure = $uploadedFile->storeAs(
            EmbeddedProductRepository::DOCUMENTS_STORAGE_PREFIX.$documentType->folder_path,
            $azureObjectName,
            'azureIMPrivate'
        );

        if ($filePathAzure === false) {
            throw new Exception(EmbeddedProductRepository::ERROR_UPLOADING_DOCUMENT);
        }

        DB::transaction(function () use (
            $embeddedTransaction,
            $oldDocument,
            $storedDocName,
            $originalName,
            $filePathAzure,
            $documentType,
            $docUuid,
            $data,
            $quoteObject
        ): void {
            $embeddedTransaction->save();
            $oldDocument->delete();

            $this->persistManualOverrideEpDocument(
                $embeddedTransaction,
                $storedDocName,
                $originalName,
                $filePathAzure,
                $documentType,
                $docUuid,
                $data['remarks'],
                $quoteObject
            );
        });

        return true;
    }

    /**
     * Resolves models and derived names for EP document replacement, or null when prerequisites fail.
     *
     * @return array{
     *     embeddedTransaction: EmbeddedTransaction,
     *     oldDocument: QuoteDocument,
     *     documentType: DocumentType,
     *     quoteObject: object,
     *     storedDocName: string,
     * }|null
     */
    private function resolveUpdateEpDocumentContext(array $data): ?array
    {
        $ep = EmbeddedProduct::query()->with('prices')->where('id', $data['epId'])->first();
        $transaction = $ep !== null
            ? $this->embeddedProductRepository->fetchTransaction($data['modelType'], $data['quoteId'], $ep, false)
            : null;

        $embeddedTransaction = ($transaction !== null && $transaction->isNotEmpty())
            ? $transaction->first()
            : null;

        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($data['modelType']));

        $oldDocument = $embeddedTransaction !== null
            ? $embeddedTransaction->documents()
                ->withTrashed()
                ->with([
                    'documentType' => function ($query) use ($quoteTypeId): void {
                        $query->where('quote_type_id', $quoteTypeId);
                    },
                ])
                ->find($data['documentId'])
            : null;

        $documentType = $oldDocument?->documentType;

        $quoteObject = $documentType !== null
            ? $this->getQuoteObject($data['modelType'], $data['quoteId'])
            : false;

        if ($ep === null
            || $transaction === null
            || $transaction->isEmpty()
            || $oldDocument === null
            || $documentType === null
            || $quoteObject === false
        ) {
            return null;
        }

        match ($oldDocument->document_type_code) {
            QuoteDocumentsEnum::POLICY_SCHEDULE => $embeddedTransaction->certificate_number = $data['documentNumber'],
            QuoteDocumentsEnum::CAR_TAX_INVOICE_RAISE_BY_BUYER => $embeddedTransaction->tax_invoice_buyer_no = $data['documentNumber'],
            QuoteDocumentsEnum::CAR_TAX_INVOICE => $embeddedTransaction->tax_invoice_no = $data['documentNumber'],
            QuoteDocumentsEnum::CAR_EP_TAX_INVOICE => $embeddedTransaction->tax_invoice_no = $data['documentNumber'],
            default => null,
        };

        $docNameSuffix = Str::after($oldDocument->doc_name, '_');

        /**
         * Keep insurer-document download naming aligned with existing behavior:
         * doc_name is always prefixed by certificate_number for all EP insurer document types.
         */
        $storedDocName = "{$embeddedTransaction->certificate_number}_{$docNameSuffix}";

        return [
            'embeddedTransaction' => $embeddedTransaction,
            'oldDocument' => $oldDocument,
            'documentType' => $documentType,
            'quoteObject' => $quoteObject,
            'storedDocName' => $storedDocName,
        ];
    }

    /**
     * Stores the manual-override document row and queues watermark processing when applicable.
     */
    private function persistManualOverrideEpDocument(
        EmbeddedTransaction $embeddedTransaction,
        string $storedDocName,
        string $originalName,
        string $filePathAzure,
        DocumentType $documentType,
        string $docUuid,
        string $remarks,
        object $quoteObject,
    ): void {
        $newDocument = $embeddedTransaction->documents()->create([
            'doc_name' => $storedDocName,
            'original_name' => $originalName,
            'doc_url' => $filePathAzure,
            'doc_mime_type' => 'application/pdf',
            'document_type_code' => $documentType->code,
            'document_type_text' => $documentType->text,
            'doc_uuid' => $docUuid,
            'created_by_id' => Auth::id(),
            'is_manual_override' => true,
            'override_remarks' => $remarks,
        ]);

        if ($newDocument->exists && $documentType->code !== QuoteDocumentsEnum::CAR_TAX_INVOICE_RAISE_BY_BUYER) {
            WatermarkDocumentsJob::dispatch($newDocument->id, $quoteObject->uuid, $documentType->id)
                ->delay(now()->addSeconds(10))
                ->afterCommit();
        }
    }
}
