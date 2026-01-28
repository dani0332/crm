<?php

namespace App\Repositories;

use App\Enums\EmbeddedProductEnum;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\CarQuote;
use App\Models\EmbeddedTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
            ->get();
    }

    public function getRetargetingCarEpReminderData($carQuoteRequestId, $embeddedTransactionCode)
    {
        return DB::table('car_quote_request as cqr')
            ->select(
                'cqr.id as quote.id',
                'cqr.uuid as quote.uuid',
                'cqr.code as quote.code',
                'cqr.quote_status_id as quote.quote_status_id',
                'cqr.policy_booking_date as quote.policy_booking_date',
                'ep.short_code as embeddedTransaction.ep_short_code',
                'et.code as embeddedTransaction.code',
                'et.is_selected as embeddedTransaction.is_selected',
                'et.payment_status_id as embeddedTransaction.payment_status_id',
                'et.product_id as embeddedTransaction.product_id',
                'et.policy_status as embeddedTransaction.policy_status',
                'c_make.text as vehicle.make',
                'c_model.text as vehicle.model',
                'cqr.customer_id as customer.id',
                'cqr.email as customer.email',
                'cqr.mobile_no as customer.mobile_no',
                'cqr.first_name as customer.first_name',
                'cqr.last_name as customer.last_name',
                'cqr.advisor_id as advisor.id',
                'adv.email as advisor.email',
                'adv.name as advisor.name',
                'adv.mobile_no as advisor.mobile_no',
                'adv.landline_no as advisor.landline_no',
                'adv.profile_photo_path as advisor.profile_photo_path',
                'cqr.plan_id as plan.id',
                'ip.code as plan.provider_code',
            )
            ->leftJoin('embedded_transactions as et', function ($join) use ($embeddedTransactionCode) {
                $join->on('et.quote_request_id', '=', 'cqr.id')
                    ->where('et.quote_request_type', '=', CarQuote::class)
                    ->where('et.code', '=', $embeddedTransactionCode);
            })
            ->leftJoin('embedded_product_options as epo', 'et.product_id', '=', 'epo.id')
            ->leftJoin('embedded_products as ep', 'epo.embedded_product_id', '=', 'ep.id')
            ->leftJoin('car_make as c_make', 'cqr.car_make_id', '=', 'c_make.id')
            ->leftJoin('car_model as c_model', 'cqr.car_model_id', '=', 'c_model.id')
            ->leftJoin('users as adv', 'cqr.advisor_id', '=', 'adv.id')
            ->leftJoin('car_plan as cp', 'cqr.plan_id', '=', 'cp.id')
            ->leftJoin('insurance_provider as ip', 'cp.provider_id', '=', 'ip.id')
            ->where('cqr.id', $carQuoteRequestId)
            ->first();
    }
}
