<?php

namespace App\Services;

use App\Models\TravelQuote;
use Carbon\Carbon;
use App\Enums\QuoteStatusEnum;
use App\Enums\PaymentStatusEnum;


class TravelRenewalService extends BaseService
{


    public function getTravelRenewalLeads(){

         TravelQuote::whereIn('quote_status_id',[QuoteStatusEnum::TransactionApproved,QuoteStatusEnum::PolicyBooked])
            ->whereIn('payment_status_id',[PaymentStatusEnum::CAPTURED,PaymentStatusEnum::PAID,PaymentStatusEnum::PARTIAL_CAPTURED,PaymentStatusEnum::CREDIT_APPROVED])
            ->where('start_date', '<=', Carbon::now()->subDays(320))
            ->chunkById(100, function ($leads) {
                foreach ($leads as $lead) {
                    $planExpiry = Carbon::parse($lead->start_date)->addDays($lead->days_cover_for);

                }
            });


    }
}
