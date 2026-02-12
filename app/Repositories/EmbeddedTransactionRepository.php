<?php

namespace App\Repositories;

use App\Enums\EmbeddedProductEnum;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\CarQuote;
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

    public function getDraftEpTransactions($quoteId, $quoteTypeId, $epShortCodes = []): Collection
    {
        return $this->with('product:id,embedded_product_id', 'product.embeddedProduct:id,short_code')
            ->select('id', 'code', 'quote_type_id', 'quote_request_id', 'quote_request_type', 'is_selected', 'payment_status_id', 'product_id', 'policy_status')
            ->where(['quote_request_id' => $quoteId, 'quote_type_id' => $quoteTypeId, 'payment_status_id' => PaymentStatusEnum::DRAFT])
            ->whereHas('product.embeddedProduct', fn ($q) => $q->whereIn('short_code', $epShortCodes))
            ->whereHas('quoteRequest', fn ($q) => $q->where('quote_status_id', QuoteStatusEnum::PolicyBooked))
            ->get();
    }

    public function getRetargetingCarEpReminderData($carQuoteRequestId, $embeddedTransactionCode)
    {
        return EmbeddedTransaction::select('id', 'code', 'quote_type_id', 'quote_request_id', 'quote_request_type', 'is_selected', 'payment_status_id', 'product_id')
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
            ->where('quote_request_id', $carQuoteRequestId)
            ->where('quote_request_type', CarQuote::class)
            ->where('is_active', true)
            ->where('payment_status_id', PaymentStatusEnum::DRAFT)
            ->whereHas('quoteRequest', fn ($q) => $q->where('quote_status_id', QuoteStatusEnum::PolicyBooked))
            ->whereHas('product.embeddedProduct', fn ($q) => $q->whereIn('short_code', EmbeddedProductEnum::CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS))
            ->first();
    }
}
