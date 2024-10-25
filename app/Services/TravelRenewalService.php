<?php

namespace App\Services;

use App\Models\TravelQuote;
use Carbon\Carbon;
use App\Enums\QuoteStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\LeadSourceEnum;


class TravelRenewalService extends BaseService
{


    public function getTravelRenewalLeads(){

         TravelQuote::whereIn('quote_status_id',[QuoteStatusEnum::TransactionApproved,QuoteStatusEnum::PolicyBooked])
            ->whereIn('payment_status_id',[PaymentStatusEnum::CAPTURED,PaymentStatusEnum::PAID,PaymentStatusEnum::PARTIAL_CAPTURED,PaymentStatusEnum::CREDIT_APPROVED])
            ->where('start_date', '<=', Carbon::now()->subDays(320))
            ->chunkById(100, function ($quotes) {
                foreach ($quotes as $quote) {


                }
            });


    }

    public function storeTravelRenewalQuote($quote){
        $members = [];
        $travelQuote = [
            'directionCode' => $quote->direction_code,
            'regionCoverForId' => 3,
            'firstName' => $quote->first_name,
            'lastName' => $quote->last_name,
            'email' => $quote->email,
            'mobileNo' => $quote->mobile_no,
            'nationalityId' => $quote->nationality_id,
            'destinationIds' => $quote->destination_ids ?? [],
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
            'referenceUrl' => config('constants.APP_URL'),
        ];

        $planExpiry = Carbon::parse($quote->start_date)->addDays($quote->days_cover_for);
    }
}
