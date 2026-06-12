<?php

namespace App\Repositories;

use App\Enums\EmbeddedProductEnum;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypes;
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
        return $this->with($this->getWithRelations($quoteTypeId))
            ->select('id', 'code', 'quote_type_id', 'quote_request_id', 'quote_request_type', 'is_selected', 'is_active', 'payment_status_id', 'product_id', 'policy_status')
            ->where(['quote_request_id' => $quoteId, 'quote_type_id' => $quoteTypeId])
            ->when($isActive !== null, fn ($q) => $q->IsActive($isActive))
            ->when($paymentStatusId !== null, fn ($q) => $q->where('payment_status_id', $paymentStatusId))
            ->when($quoteStatusId !== null, fn ($q) => $q->quoteRequestStatusId($quoteStatusId))
            ->when($epShortCode !== null, fn ($q) => $q->epShortCode($epShortCode))
            ->get();
    }

    private function getWithRelations(int $quoteTypeId, $withDetails = false): array
    {
        $with = [
            'product:id,embedded_product_id',
            'product.embeddedProduct:id,short_code',
        ];

        if ($withDetails) {
            $withQuoteRequest = 'quoteRequest:id,uuid,quote_status_id,policy_booking_date,customer_id,email,first_name,last_name';

            if ($quoteTypeId == QuoteTypes::CAR->id()) {
                $withQuoteRequest .= ',advisor_id,plan_id,vehicle_use,is_modified,car_make_id,car_model_id';
                $with = array_merge($with, [
                    'quoteRequest.carMake:id,text,code',
                    'quoteRequest.carModel:id,text,code',
                    'quoteRequest.advisor:id,email',
                    'quoteRequest.plan:id,provider_id,repair_type',
                    'quoteRequest.plan.insuranceProvider:id,code',
                ]);
            }
            array_push($with, $withQuoteRequest);
        }

        return $with;
    }

    public function fetchFindEmbededTransactionWithDetails(int $quoteId, int $quoteTypeId, ?string $embeddedTransactionCode = null, string|array|null $epShortCode = null, ?bool $isActive = null, ?int $paymentStatusId = null, ?int $quoteStatusId = null)
    {
        return EmbeddedTransaction::select('id', 'code', 'quote_type_id', 'quote_request_id', 'quote_request_type', 'is_selected', 'is_active', 'payment_status_id', 'product_id')
            ->with($this->getWithRelations($quoteTypeId, true))
            ->where(['quote_request_id' => $quoteId, 'quote_type_id' => $quoteTypeId])
            ->when($embeddedTransactionCode !== null, fn ($q) => $q->where('code', $embeddedTransactionCode))
            ->when($isActive !== null, fn ($q) => $q->IsActive($isActive))
            ->when($paymentStatusId !== null, fn ($q) => $q->where('payment_status_id', $paymentStatusId))
            ->when($quoteStatusId !== null, fn ($q) => $q->quoteRequestStatusId($quoteStatusId))
            ->when($epShortCode !== null, fn ($q) => $q->epShortCode($epShortCode))
            ->first();
    }
}
