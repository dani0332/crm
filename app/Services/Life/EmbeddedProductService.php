<?php

namespace App\Services\Life;

use Carbon\Carbon;
use App\Enums\RolesEnum;
use App\Models\QuoteType;
use App\Enums\EpCategoryEnum;
use App\Services\BaseService;
use App\Enums\QuoteStatusEnum;
use App\Models\CustomerAddress;
use App\Models\EmbeddedProduct;
use App\Enums\PaymentStatusEnum;
use App\Enums\EmbeddedProductEnum;
use App\Models\EmbeddedTransaction;
use App\Traits\GenericQueriesAllLobs;
use App\Strategies\EmbeddedProducts\EmbeddedProduct as EmbeddedProductStrategy;

class EmbeddedProductService extends BaseService
{
    use GenericQueriesAllLobs;

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
                    $documentCount = ($isDocPresent == true) ? $transaction[0]->documents()->count() : 0;
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
        if (! $transaction->isEmpty() && in_array($transaction->first()->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PARTIAL_CAPTURED])) {
            if (
                $productCategory == EpCategoryEnum::STAND_ALONE ||
                ($productCategory == EpCategoryEnum::BOLT_ON && in_array($quoteStatusId, $this->canSendDocumentEnums()))) {
                return true;
            }
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
}
