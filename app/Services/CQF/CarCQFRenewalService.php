<?php

namespace App\Services\CQF;

use Illuminate\Support\Carbon;
use App\Enums\ApplicationStorageEnums;
use App\Services\Logger\LoggerService;
use App\Models\CarQuote;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use Illuminate\Support\Sleep;
use App\Enums\LeadSourceEnum;
use App\Enums\AssignmentTypeEnum;
use App\Models\RenewalBatch;


class CarCQFRenewalService
{


    public function processCarCQFRenewalLeads()
    {
        // $renewalDaysThreshold = getAppStorageValueByKey(ApplicationStorageEnums::CAR_CQF_RENEWALS_DAYS_THRESHOLD);
        $renewalDaysThreshold  = 10;
        $startDate = Carbon::now()->subDays((int) $renewalDaysThreshold);
        LoggerService::info(self::class . " - Car CQF Renewal Leads processing started with Start Date: {$startDate}");
        $carQuotes = CarQuote::whereDate('policy_expiry_date', '<=', $startDate)
            ->whereIn('payment_status_id', [
                PaymentStatusEnum::PAID,
                PaymentStatusEnum::PARTIALLY_PAID,
            ])
            ->whereHas('embeddedTransactions', function ($query) {
                $query->whereIn('payment_status_id', [
                    PaymentStatusEnum::CAPTURED,
                ]);
            })
            ->take(5)
            ->chunkById(100, function ($quotes) {
                $quoteCount = $quotes->count();
                LoggerService::info(self::class." - Total quotes in current chunk: {$quoteCount}");
                if ($quoteCount > 0) {
                    LoggerService::info(self::class." - processing cqf car renewals quotes in chunk: {$quoteCount}");
                    $this->createCarCQFRenewalLeads($quotes);
                } else {
                    LoggerService::info(self::class.' - No quotes in chunk');
                }
            });
    }

   

    public function createCarCQFRenewalLeads($quotes)
    {
        foreach ($quotes as $quote) {
            LoggerService::startQuoteLogging($quote, LoggerFeatureEnum::CAR_CQF_RENEWALS);
            try {
                // Check if the quote is a duplicate
                if ($this->isDuplicateQuote($quote)) {
                    LoggerService::info(self::class.' - Duplicate quote detected. Skipping processing');
                    continue; // Skip processing this quote
                }
                LoggerService::info(self::class.' - Processing quote');
                $this->storeCarCQFRenewalQuote($quote);
                Sleep::for(3)->seconds();
            } catch (\Exception $e) {
                // Log the exception or handle it as needed
                LoggerService::error('Error processing quote', exception: $e);
            }

           
        }
    }
    public function isDuplicateQuote($quote)
    {

      return CarQuote::where('previous_quote_id', $quote->id)
        ->where('previous_quote_policy_number', $quote->policy_number)
        ->where('previous_policy_expiry_date', $quote->policy_expiry_date)
        ->where('source', '=', LeadSourceEnum::RENEWAL_UPLOAD)
        ->exists();
    }
    
    public function storeCarCQFRenewalQuote($quote)
    {
        LoggerService::info(self::class.' - Storing car cqf renewal quote');
        $policyExpiryDate = Carbon::parse($quote->policy_expiry_date);
                
        // Calculate the policy expiry date based on the start date + 365 days
        $policyStartDate = $policyExpiryDate->copy()->addDays(1);
        $newPolicyExpiryDate = $policyStartDate->copy()->addDays(365);

        LoggerService::info(self::class.' - Policy Details', [
            'policyExpiryDate' => $policyExpiryDate,
            'policyStartDate' => $policyStartDate,
            'newPolicyExpiryDate' => $newPolicyExpiryDate,
        ]);
        $batch = $this->getRenewalBatch($newPolicyExpiryDate);



    }
    public function getRenewalBatch($newPolicyExpiryDate)
    {
        return RenewalBatch::where('start_date', '<=', $newPolicyExpiryDate)
            ->where('end_date', '>=', $newPolicyExpiryDate)
            ->whereNull('quote_type_id')
            ->first();
    }
    public function mapCarCQFRenewalQuote($quote,$batch)
    {
       $quoteUuid="";


        $quoteData = [
            'customer_id' => $quote->customer_id,
            'first_name' => $quote->first_name,
            'last_name' => $quote->last_name,
            'email' => $quote->email,
            'mobile_no' => $quote->mobile_no,
            'uuid' => $quoteUuid,
            'code' => strtoupper($quote->quote_type).'-'. $quoteUuid,
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
            'advisor_id' => null,
            'assignment_type' =>null,
            'renewal_batch' =>  trim($batch->name),
            'renewal_batch_id' => $batch->id,
            'quote_status_id' => QuoteStatusEnum::NewLead,
            'renewal_import_code' => $quote->e,
            'previous_quote_policy_number' => $quote->policy_number,
            'previous_policy_start_date' => $quote->policy_start_date,
            'previous_policy_expiry_date' => $quote->policy_expiry_date,
            'previous_quote_policy_premium' => $quote->premium,
        ];
    }
}
