<?php

namespace App\Repositories;

use App\Enums\EmbeddedProductEnum;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\EmbeddedTransaction;
use Illuminate\Support\Collection;

class EmbeddedTransactionRepository extends BaseRepository
{
    public function model()
    {
        return EmbeddedTransaction::class;
    }

    public function fetchEpTransactions($quoteTypeId, $id)
    {
        return $this->with('paymentStatus:id,text')
            ->where('quote_type_id', $quoteTypeId)
            ->where('quote_request_id', $id)
            ->get();
    }

    public function getUnderProcessEpTransactions($quoteTypeId, $quoteId): Collection
    {
        $shortCodes = [...EmbeddedProductEnum::getSukoonMedexCodes(), EmbeddedProductEnum::ECB];

        return $this->with('product:id,embedded_product_id','product.embeddedProduct:id,short_code')
            ->select('id', 'quote_type_id', 'quote_request_id', 'is_selected', 'payment_status_id', 'product_id', 'policy_status')
            ->where(['quote_type_id' => $quoteTypeId, 'quote_request_id' => $quoteId, 'is_selected' => true])
            ->whereHas('product.embeddedProduct', fn ($q) => $q->whereIn('short_code', $shortCodes))
            ->whereIn('payment_status_id', [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::CAPTURED])
            ->whereNot('policy_status', EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE)
            ->get();
    }
}
