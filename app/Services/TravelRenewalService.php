<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypeShortCode;
use App\Enums\TeamNameEnum;
use App\Factories\AllocationFactory;
use App\Jobs\OCB\SendOCBTravelRenewalIntroEmailJob;
use App\Jobs\TravelRenewalLeadCreationJob;
use App\Models\PaymentStatus;
use App\Models\QuoteType;
use App\Models\RenewalBatch;
use App\Models\TravelQuote;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TravelRenewalService extends BaseService
{
    public function getTravelRenewalLeads()
    {
        info('Travel Renewal Leads processing started with Start Date: '.Carbon::now()->subDays(320));
        TravelQuote::whereIn('quote_status_id', [
            QuoteStatusEnum::TransactionApproved,
            QuoteStatusEnum::PolicyBooked,
        ])
            ->whereIn('payment_status_id', [
                PaymentStatusEnum::CAPTURED,
                PaymentStatusEnum::PAID,
                PaymentStatusEnum::PARTIAL_CAPTURED,
                PaymentStatusEnum::CREDIT_APPROVED,
            ])
            ->whereDate('start_date', Carbon::now()->subDays(320))
            ->chunkById(100, function ($quotes) {
                $quoteCount = $quotes->count();
                info("total quotes in chunk: {$quoteCount}");
                if ($quoteCount > 0) {
                    info("processing travel renewals quotes in chunk: {$quoteCount}");
                    $this->processTravelRenewalQuotes($quotes);
                } else {
                    info('No quotes in chunk.');
                }
            });
    }

    public function processTravelRenewalQuotes($quotes)
    {
        foreach ($quotes as $quote) {
            try {
                // Check if the quote is a duplicate
                if ($this->isDuplicateQuote($quote)) {
                    info('Duplicate quote detected for Quote Ref-ID: '.$quote->uuid);

                    continue; // Skip processing this quote
                }
                $this->storeTravelRenewalQuote($quote);
            } catch (\Exception $e) {
                // Log the exception or handle it as needed
                Log::error('Error processing quote ID '.$quote->uuid.': '.$e->getMessage());
            }
        }
    }
    public function isDuplicateQuote($quote)
    {
        $policyExpiryDate = Carbon::parse($quote->policy_expiry_date);
        $currentDate = Carbon::now();
        // Calculate the policy start date based on conditions
        $policyStartDate = $policyExpiryDate->addDay();
        if ($policyStartDate->lt($currentDate)) {
            $policyStartDate = $currentDate;
        }
        // Calculate the policy expiry date based on the start date + 365 days
        $policyExpiryDate = $policyStartDate->copy()->addDays(365);
        info('Calculating policy expiry date'.$policyExpiryDate);

        return TravelQuote::where('customer_id', $quote->customer_id)
            ->whereDate('policy_expiry_date', Carbon::parse($policyExpiryDate)->format('Y-m-d'))
            ->whereDate('start_date', $policyStartDate)
            ->where('coverage_code', $quote->coverage_code)
            ->where('direction_code', $quote->direction_code)
            ->where('nationality_id', $quote->nationality_id)
            ->where('region_cover_for_id', $quote->region_cover_for_id)
            ->exists();
    }
    public function storeTravelRenewalQuote($quote)
    {

        $quoteType = $this->getQuoteTypeByShortCode(QuoteTypeShortCode::TRA);
        $policyExpiryDate = Carbon::parse($quote->policy_expiry_date);
        $currentDate = Carbon::now();

        // Calculate the policy start date based on conditions
        $policyStartDate = $policyExpiryDate->addDay();
        if ($policyStartDate->lt($currentDate)) {
            $policyStartDate = $currentDate;
        }

        // Calculate the policy expiry date based on the start date + 365 days
        $newPolicyExpiryDate = $policyStartDate->copy()->addDays(365);

        $generatedNumber = $this->generateBatchNumber($newPolicyExpiryDate);
        $batch = $this->getRenewalBatch($generatedNumber, $newPolicyExpiryDate);

        $quoteUuid = app(RenewalsUploadService::class)->generateUUID($quoteType->code, $quoteType->id);

        $customer = $this->getCustomerByEamil($quote->customer_email);
        info("Processing renewal for old quote. Reference ID: {$quote->uuid}. Initiating renewal process with updated policy details.");
        $travelQuotePayload = (object) [
            'first_name' => trim($quote->first_name),
            'last_name' => trim($quote->last_name),
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
            'customer_id' => $quote->customer_id,
            'direction_code' => $quote->direction_code,
            'destination' => $quote->destination ?? null,
            'code' => QuoteTypeShortCode::TRA.'-'.$quoteUuid,
            'uuid' => $quoteUuid,
            'renewal_batch' => trim($batch->name),
            'renewal_batch_id' => $batch->id,
            'email' => $quote->email,
            'mobile_no' => $quote->mobile_no,
            'uae_resident' => $quote->uae_resident,
            'nationality_id' => $quote->nationality_id,
            'dob' => $quote->dob,
            'members' => $quote->customerMembers,
            'destination_ids' => collect($quote->TravelDestinations)->pluck('destination_id')->toArray(),
            'emirates_id_number' => $customer->emirates_id_number ?? null,
            'emirates_id_expiry_date' => $customer->emirates_id_expiry_date ?? null,
            'insured_first_name' => $customer->insured_first_name ?? null,
            'insured_last_name' => $customer->insured_last_name ?? null,
            'is_ecommerce' => $quote->is_ecommerce ?? null,
            'start_date' => $policyStartDate,
            'policy_expiry_date' => Carbon::parse($newPolicyExpiryDate)->format('Y-m-d'),
            'coverage_code' => $quote->coverage_code,
            'region_cover_for_id' => $quote->region_cover_for_id,
        ];
        TravelRenewalLeadCreationJob::dispatch($travelQuotePayload)->delay(Carbon::now()->addMinutes(1))->onQueue('travel_renewal_leads');
        info("Travel renewal lead creation job dispatched for Reference ID: {$quote->uuid} | Time:".now());
    }

    // Helper function to save the renewal quote
    protected function createTravelRenewalLead($travelQuote)
    {
       
        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-travel-quote', $travelQuote);
        info("Travel quote successfully saved. Reference ID: {$response->quoteUID} | Time:".now());
        info("Lead allocation process initiated for Reference ID: {$response->quoteUID} | Time:".now());
        $this->leadAllocation($response->quoteUID);
        info("Lead allocation completed for Reference ID: {$response->quoteUID} - | Time: ".now());
    }
    public function getPaymentStatusIdByCode($paymentStatus)
    {
        return PaymentStatus::where('code', strtolower($paymentStatus))->value('id');
    }
    public function getQuoteTypeByShortCode($shortCode)
    {
        return QuoteType::where('short_code', $shortCode)->first();
    }

    public function GenerateBatchNumber($expiryDate)
    {
        $expiryDate = Carbon::parse($expiryDate);
        $currentDate = Carbon::now();
        // Calculate the number of weeks between the current date and the expiry date
        $weeksUntilExpiry = $currentDate->diffInWeeks($expiryDate);

        return strtoupper('W-'.$weeksUntilExpiry);
    }

    public function getRenewalBatch($batchName, $newPolicyExpiryDate)
    {
        $expiryDate = Carbon::parse($newPolicyExpiryDate);
        $startDate = $expiryDate->startOfMonth()->toDateString();  // Start of the expiry month
        $endDate = $expiryDate->endOfMonth()->toDateString();      // End of the expiry month

        return RenewalBatch::firstOrCreate(
            [
                'name' => $batchName,
                'month' => $expiryDate->month,
                'year' => $expiryDate->year,
            ],  // Check if batch with this name exists
            [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]   // If not, create with this name
        );
    }

    public function getCustomerByEamil($customerEmail)
    {
        return CustomerService::getCustomerByEmail($customerEmail);
    }

    public function storeMembers($quote, $members)
    {
        // Prepare data for new members associated with this quote_id
        $membersData = collect($members)->map(function ($member) use ($quote) {
            return array_merge(
                Arr::except($member->toArray(), ['quote_id', 'created_at', 'updated_at', 'id']),
                [
                    'quote_id' => $quote->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        })->toArray();

        // Insert only new member data associated with quote_id
        if (! empty($membersData)) {
            DB::table('customer_members')->insert($membersData);
            info("customer members created successfully for quote Ref-ID: {$quote->uuid}");
        } else {
            info("No valid customer members to insert for quote Ref-ID: {$quote->uuid}");
        }
    }

    public function leadAllocation($quoteUID)
    {
        info('Processing Travel record for Quote Allocation with uuid: '.$quoteUID);
        $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);

        $lead = TravelQuote::where('uuid', $quoteUID)->first();
        // Only apply teamId if the payment status is AUTHORIZED
        $currentTeamId = $lead->payment_status_id == PaymentStatusEnum::AUTHORISED ? $teamId : false;

        $allocationStrategy = AllocationFactory::createStrategy(QuoteTypeId::Travel, $quoteUID, $currentTeamId);
        $response = $allocationStrategy->executeSteps();
        if ($response) {
            info(self::class.' - Going to dispatch SendOCBTravelRenewalIntroEmailJob ................ Ref-ID: '.$quoteUID);
            SendOCBTravelRenewalIntroEmailJob::dispatch($quoteUID)->delay(now()->addSeconds(30));
        }
    }
}
