<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypeShortCode;
use App\Enums\TeamNameEnum;
use App\Enums\TeamNameEnum;
use App\Factories\AllocationFactory;
use App\Factories\AllocationFactory;
use App\Jobs\OCB\SendOCBTravelRenewalIntroEmailJob;
use App\Models\PaymentStatus;
use App\Models\PaymentStatus;
use App\Models\QuoteType;
use App\Models\TravelQuote;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TravelRenewalService extends BaseService
{
    public function getTravelRenewalLeads()
    {

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
            ->whereDate('start_date', Carbon::now()->subDays(20))
            ->chunkById(100, function ($quotes) {
                $quoteCount = $quotes->count();
                info("Processing total quotes in chunk: $quoteCount");
                if ($quoteCount > 0) {

                    $this->processTravelRenewalQuotes($quotes);
                } else {
                    info('No quotes in chunk.');
                }
            });
    }

    public function processTravelRenewalQuotes($quotes)
    {
        foreach ($quotes as $quote) {
            // try {
            // Check if the quote is a duplicate
            if ($this->isDuplicateQuote($quote)) {
                info('Duplicate quote detected for Quote Ref-ID: '.$quote->uuid);

                continue; // Skip processing this quote
            }
            // dd($quote);
            $this->storeTravelRenewalQuote($quote);
            // } catch (\Exception $e) {
            //     // Log the exception or handle it as needed
            //     Log::error('Error processing quote ID ' . $quote->uuid . ': ' . $e->getMessage());
            // }
        }
    }
    public function isDuplicateQuote($quote)
    {
        $previousPolicyExpiryDate = Carbon::parse($quote->previous_policy_expiry_date);
        $currentDate = Carbon::now();
        // Calculate the policy start date based on conditions
        $policyStartDate = $previousPolicyExpiryDate->addDay();
        if ($policyStartDate->lt($currentDate)) {
            $policyStartDate = $currentDate;
        }
        // Calculate the policy expiry date based on the start date + 365 days
        $policyExpiryDate = $policyStartDate->copy()->addDays(365);
        info('Calculating policy expiry date'.$policyExpiryDate);

        return TravelQuote::where('customer_id', $quote->customer_id)
            ->whereDate('policy_expiry_date', Carbon::parse($policyExpiryDate)->format('Y-m-d'))
            ->where('coverage_code', $quote->coverage_code)
            ->where('direction_code', $quote->direction_code)
            ->where('nationality_id', $quote->nationality_id)
            ->where('region_cover_for_id', $quote->region_cover_for_id)
            ->exists();
        // dd($is_lead,Carbon::parse($policyExpiryDate)->format('Y-m-d'),);
        // dd($is_lead);
    }
    public function storeTravelRenewalQuote($quote)
    {

        $quoteType = $this->getQuoteTypeByShortCode(QuoteTypeShortCode::TRA);
        $previousPolicyExpiryDate = Carbon::parse($quote->previous_policy_expiry_date);
        $currentDate = Carbon::now();

        // Calculate the policy start date based on conditions
        $policyStartDate = $previousPolicyExpiryDate->addDay();
        if ($policyStartDate->lt($currentDate)) {
            $policyStartDate = $currentDate;
        }

        // Calculate the policy expiry date based on the start date + 365 days
        $policyExpiryDate = $policyStartDate->copy()->addDays(365);

        $batchNumber = $this->createBatchNumber($policyExpiryDate);
        $quoteUuid = app(RenewalsUploadService::class)->generateUUID($quoteType->code, $quoteType->id);

        $customer = $this->getCustomerByEamil($quote->customer_email);
        info("old uuid: {$quote->uuid}");
        $travelQuote = (object) [
            'first_name' => trim($quote->first_name),
            'last_name' => trim($quote->last_name),
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
            'customer_id' => $quote->customer_id,
            'direction_code' => $quote->direction_code,
            'destination' => $quote->destination ?? null,
            'code' => QuoteTypeShortCode::TRA.'-'.$quoteUuid,
            'uuid' => $quoteUuid,
            'renewal_batch' => trim($batchNumber),
            'email' => $quote->email,
            'mobile_no' => $quote->mobile_no,
            'uae_resident' => $quote->uae_resident,
            'nationality_id' => $quote->nationality_id,
            'dob' => $quote->dob,
            'members' => $quote->customerMembers,
            'destinations' => $quote->TravelDestinations,
            'emirates_id_number' => $customer->emirates_id_number ?? null,
            'emirates_id_expiry_date' => $customer->emirates_id_expiry_date ?? null,
            'insured_first_name' => $customer->insured_first_name ?? null,
            'insured_last_name' => $customer->insured_last_name ?? null,
            'is_ecommerce' => $quote->is_ecommerce ?? null,
            'start_date' => $policyStartDate,
            'policy_expiry_date' => Carbon::parse($policyExpiryDate)->format('Y-m-d'),
            'coverage_code' => $quote->coverage_code,
            'region_cover_for_id' => $quote->region_cover_for_id,
        ];

        $this->saveTravelRenewalQuote($travelQuote);

        return $travelQuote;
    }

    // Helper function to save the renewal quote
    protected function saveTravelRenewalQuote($travelQuote)
    {
        // $travelQuote;,
        echo "\n";
        echo $travelQuote->uuid." | Travel quote saved to successfully \n";

        $quoteData = [
            'destination' => $travelQuote->destination,
            'first_name' => $travelQuote->first_name,
            'last_name' => $travelQuote->last_name,
            'source' => $travelQuote->source,
            'customer_id' => $travelQuote->customer_id,
            'payment_status_id' => PaymentStatusEnum::DRAFT,
            'quote_status_id' => QuoteStatusEnum::NewLead,
            'code' => $travelQuote->code,
            'uuid' => $travelQuote->uuid,
            'direction_code' => $travelQuote->direction_code,
            'nationality_id' => $travelQuote->nationality_id,
            // 'policy_number' => trim($data['policy_number']),
            // 'advisor_id' => $advisorId,
            // 'premium' => trim($data['premium']),
            'is_ecommerce' => $travelQuote->is_ecommerce,
            'renewal_batch' => $travelQuote->renewal_batch,
            // 'currently_located_in_id' => $currently_located_in_id,
            'policy_expiry_date' => $travelQuote->policy_expiry_date,
            'email' => $travelQuote->email,
            'mobile_no' => $travelQuote->mobile_no,
            'coverage_code' => $travelQuote->coverage_code,
            'start_date' => $travelQuote->start_date,
            'region_cover_for_id' => $travelQuote->region_cover_for_id,

        ];

        $newQuote = TravelQuote::create($quoteData);
        $this->storeMembers($newQuote, $travelQuote->members);
        $this->leadAllocation($newQuote);
        dd('done');
    }
    public function getPaymentStatusIdByCode($paymentStatus)
    {
        return PaymentStatus::where('code', strtolower($paymentStatus))->value('id');
    }
    public function getQuoteTypeByShortCode($shortCode)
    {
        return QuoteType::where('short_code', $shortCode)->first();
    }

    public function createBatchNumber($expiryDate)
    {
        return strtoupper(Carbon::parse($expiryDate)->format('MY'));
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
                    'quote_id' => $quote->id ?? 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        })->toArray();

        // Insert only new member data associated with quote_id
        DB::table('customer_members')->insert($membersData);
        info("customer members created successfully for quote Ref-ID: {$quote->uuid}");
    }

    public function leadAllocation($lead)
    {
        info('Processing Travel record for Quote Allocation with uuid: '.$lead->uuid);
        $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
        // Only apply teamId if the payment status is AUTHORIZED
        $currentTeamId = $lead->payment_status_id == PaymentStatusEnum::AUTHORISED ? $teamId : false;

        $allocationStrategy = AllocationFactory::createStrategy(QuoteTypeId::Travel, $lead->uuid, $currentTeamId);
        $response = $allocationStrategy->executeSteps();
        if ($response) {
            info(self::class.' - Going to dispatch SendOCBTravelRenewalIntroEmailJob ................ Ref-ID:'.$lead->uuid);
            SendOCBTravelRenewalIntroEmailJob::dispatch($lead->uuid)->onQueue('local')->delay(now()->addSeconds(5));
        }
    }

}
