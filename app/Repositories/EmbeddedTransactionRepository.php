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

        return $this->with('product:id,embedded_product_id', 'product.embeddedProduct:id,short_code')
            ->select('id', 'quote_type_id', 'quote_request_id', 'is_selected', 'payment_status_id', 'product_id', 'policy_status')
            ->where(['quote_type_id' => $quoteTypeId, 'quote_request_id' => $quoteId, 'is_selected' => true])
            ->whereHas('product.embeddedProduct', fn ($q) => $q->whereIn('short_code', $shortCodes))
            ->whereIn('payment_status_id', [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::CAPTURED])
            ->whereNot('policy_status', EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE)
            ->get();
    }

    public function fetchFilterEpTransactions($quoteId, $quoteTypeId, ?bool $isActive = null, ?int $paymentStatusId = null, ?int $quoteStatusId = null, string|array|null $epShortCode = null): Collection
    {
        return $this->with('product:id,embedded_product_id', 'product.embeddedProduct:id,short_code')
            ->select('id', 'code', 'quote_type_id', 'quote_request_id', 'quote_request_type', 'is_selected', 'is_active', 'payment_status_id', 'product_id', 'policy_status')
            ->where(['quote_request_id' => $quoteId, 'quote_type_id' => $quoteTypeId])
            ->when($isActive !== null, fn ($q) => $q->IsActive($isActive))
            ->when($paymentStatusId, fn ($q) => $q->where('payment_status_id', $paymentStatusId))
            ->when($quoteStatusId, fn ($q) => $q->quoteRequestStatusId($quoteStatusId))
            ->when($epShortCode !== null, fn ($q) => $q->epShortCode($epShortCode))
            ->get();
    }

    public function fetchFindEmbededTransactionWithDetails(string $embeddedTransactionCode, ?bool $isActive = null, ?int $paymentStatusId = null, ?int $quoteStatusId = null)
    {
        return $this->select('id', 'code', 'quote_type_id', 'quote_request_id', 'quote_request_type', 'is_selected', 'is_active', 'payment_status_id', 'product_id')
            ->with(
                'product:id,embedded_product_id',
                'product.embeddedProduct:id,short_code',
                'quoteRequest:id,uuid,quote_status_id,policy_booking_date,customer_id,email,first_name,last_name,car_make_id,car_model_id,advisor_id,plan_id',
                'quoteRequest.carMake:id,text',
                'quoteRequest.carModel:id,text',
                'quoteRequest.advisor:id,email',
                'quoteRequest.plan:id,provider_id',
                'quoteRequest.plan.insuranceProvider:id,code',
            )
            ->where('code', $embeddedTransactionCode)
            ->when($isActive !== null, fn ($q) => $q->IsActive($isActive))
            ->when($paymentStatusId, fn ($q) => $q->where('payment_status_id', $paymentStatusId))
            ->when($quoteStatusId, fn ($q) => $q->quoteRequestStatusId($quoteStatusId))
            ->first();
    }
}
