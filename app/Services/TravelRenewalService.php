<?php

namespace App\Services;

use App\Models\TravelQuote;
use Carbon\Carbon;
use App\Enums\QuoteStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\LeadSourceEnum;
use App\Models\QuoteType;
use App\Enums\QuoteTypes;
use Illuminate\Support\Facades\Log;


class TravelRenewalService extends BaseService
{


    public function getTravelRenewalLeads()
    {
        TravelQuote::whereIn('quote_status_id', [
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyBooked
        ])
        // Uncomment if needed
        // ->whereIn('payment_status_id', [
        //     PaymentStatusEnum::CAPTURED,
        //     PaymentStatusEnum::PAID,
        //     PaymentStatusEnum::PARTIAL_CAPTURED,
        //     PaymentStatusEnum::CREDIT_APPROVED
        // ])
        ->where('start_date', '<=', Carbon::now()->subDays(20))
        ->chunkById(100, function ($quotes) {
            foreach ($quotes as $quote) {
                try {
                    dd(count($quotes));
                    // $this->storeTravelRenewalQuote($quote);
                } catch (\Exception $e) {
                    // Log the exception or handle it as needed
                    Log::error('Error processing quote ID ' . $quote->id . ': ' . $e->getMessage());
                }
            }
        });
    }

    public function storeTravelRenewalQuote($quote)
    {
        // Get the quote type using the short code
        $quoteType = $this->getQuoteTypeByShortCode($quote->quote_type);
        // Calculate the plan expiry date based on the start date and coverage duration
        $planExpiry = Carbon::parse($quote->start_date)->addDays($quote->days_cover_for);
        // Generate the batch number based on policy expiry date
        $batchNumber = $this->createBatchNumber($quote->policy_expiry_date);
        // Create the travel quote array with necessary details.
        $destionations = $quote->TravelDestinations;
        $members = $quote->customerMembers;

        $travelQuote = [
            'quoteType' => $quoteType,
            'directionCode' => $quote->direction_code,
            'firstName' => $quote->first_name,
            'lastName' => $quote->last_name,
            'email' => $quote->email,
            'mobileNo' => $quote->mobile_no,
            'nationalityId' => $quote->nationality_id,
            'destinationIds' => collect($destionations )->pluck('id')->values() ?? [],
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
            'referenceUrl' => config('constants.APP_URL'),
            'batchNumber' => $batchNumber,
            'planExpiry' => $planExpiry->format('Y-m-d'),
            'members'=>$members,
        ];

        // Save the quote data or proceed with further processing as needed
        $this->saveTravelRenewalQuote($travelQuote);

        return $travelQuote;
    }

    // Helper function to save the renewal quote
    protected function saveTravelRenewalQuote($travelQuote)
    {
        TravelQuote::create($travelQuote);
    }

    public function getQuoteTypeByShortCode($shortCode)
    {
        return QuoteType::where('short_code', $shortCode)->first();
    }

    public function createBatchNumber($expiryDate){
        return strtoupper(Carbon::parse($expiryDate)->format('MY'));
    }

    public function createQuoteObject($quoteType)
    {
        info('fn: createQuoteObject QuoteType: '.$quoteType);
        $nameSpace = '\\App\\Models\\';

        if (checkPersonalQuotes($quoteType)) {
            $quoteType = QuoteTypes::PERSONAL->value;
        }

        $model = $nameSpace.ucfirst(strtolower($quoteType)).'Quote';

        return (class_exists($model)) ? $model::query() : false;
    }

}
