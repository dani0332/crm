<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypeShortCode;
use App\Enums\TeamNameEnum;
use App\Enums\TravelQuoteEnum;
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
use App\Enums\QuoteTypes;

class TravelRenewalService extends BaseService
{
    public function getTravelRenewalLeads()
    {
        info('TravelRenewalService Travel Renewal Leads processing started with Start Date: '.Carbon::now()->subDays(320) . ' | Time: '.now());
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
            ->where('direction_code',TravelQuoteEnum::TRAVEL_UAE_OUTBOUND)
            ->whereDate('start_date',Carbon::now()->subDays(320))
            ->chunkById(100, function ($quotes) {
                $quoteCount = $quotes->count();
                info("total quotes in chunk: {$quoteCount} | Time: ".now());
                if ($quoteCount > 0) {
                    info("TravelRenewalService processing travel renewals quotes in chunk: {$quoteCount} | Time: ".now());
                    $this->processTravelRenewalQuotes($quotes);
                } else {
                    info('TravelRenewalService No quotes in chunk. | Time: '.now());
                }
            });
        info('TravelRenewalService Travel Renewal Leads processing completed | Time: '.now());
    }

    public function processTravelRenewalQuotes($quotes)
    {
        foreach ($quotes as $quote) {
            try {
                // Check if the quote is a duplicate
                if ($this->isDuplicateQuote($quote)) {
                    info('TravelRenewalService Duplicate quote detected for Quote Ref-ID: '.$quote->uuid);
                    continue; // Skip processing this quote
                }

                $this->storeTravelRenewalQuote($quote);
            } catch (\Exception $e) {
                // Log the exception or handle it as needed
                Log::error('TravelRenewalService Error processing quote ID '.$quote->uuid.': '.$e->getMessage());
                throw $e;

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
        info('TravelRenewalService Calculating policy expiry date'.$policyExpiryDate . ' | Time: '.now());

        return TravelQuote::where('previous_quote_id', $quote->id)
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

        $customer = $this->getCustomerByEamil($quote->customer_email);
        info("TravelRenewalService Processing renewal for old quote. Reference ID: {$quote->uuid}. Initiating renewal process with updated policy details. | Time:".now());
        $destinationIds = collect($quote->TravelDestinations)->pluck('destination_id')->toArray();
        if(!empty($destinationIds)){
        $travelQuotePayload = (object) [
            'firstName' => trim($quote->first_name),
            'lastName' => trim($quote->last_name),
            'source' => LeadSourceEnum::RENEWAL_UPLOAD,
            'previousQuoteId' => $quote->id,
            'customerId' => $quote->customer_id,
            'directionCode' => $quote->direction_code,
            'destination' => $quote->destination ?? null,
            'renewalBatch' => trim($batch->name),
            'renewalBatchId' => $batch->id,
            'email' => $quote->email,
            'mobileNo' => $quote->mobile_no,
            'uaeResident' => $quote->uae_resident,
            'nationalityId' => $quote->nationality_id,
            'dob' => $quote->dob,
            'members' => $this->mapCustomerMembers($quote->customerMembers,$quote->primary_member_id),
            'destinationIds' =>$destinationIds,
            'emiratesIdNumber' => $customer->emirates_id_number ?? null,
            'emiratesIdExpiryDate' => $customer->emirates_id_expiry_date ?? null,
            'insuredFirstName' => $customer->insured_first_name ?? null,
            'insuredLastName' => $customer->insured_last_name ?? null,
            'isEcommerce' => $quote->is_ecommerce ?? null,
            'startDate' => $policyStartDate,
            'policyExpiryDate' => Carbon::parse($newPolicyExpiryDate)->format('Y-m-d'),
            'coverageCode' => $quote->coverage_code,
            'regionCoverForId' => $quote->region_cover_for_id,
            'tripStarted' => false
        ];

        TravelRenewalLeadCreationJob::dispatch($travelQuotePayload)->delay(Carbon::now()->addMinutes(1))->onQueue('travel_renewal_leads');
        info("TravelRenewalService Travel renewal lead creation job dispatched for Reference ID: {$quote->uuid} | Time:".now());

    }  else     {
        info("TravelRenewalService No destination found for Reference ID: {$quote->uuid} | Time:".now());
    }
}

    public function mapCustomerMembers($members,$primaryMemberId){
        return collect($members)->map(function ($member) use($primaryMemberId) {
        return [
        'id' => $member->id,
        'quoteType' => $member->quote_type,
        'customerEntityId' => $member->customer_entity_id,
        'code' => $member->code,
        'firstName' => $member->first_name,
        'lastName' => $member->last_name,
        'gender' => $member->gender,
        'dob' => $member->dob,
        'nationalityId' => $member->nationality_id,
        'createdAt' => $member->created_at,
        'updatedAt' => $member->updated_at,
        'policyId' => $member->policy_id,
        'quoteId' => $member->quote_id,
        'memberCategoryId' => $member->member_category_id,
        'salaryBandId' => $member->salary_band_id,
        'emirateOfYourVisaId' => $member->emirate_of_your_visa_id,
        'relationCode' => $member->relation_code,
        'customerType' => $member->customer_type,
        'isPayer' => $member->is_payer,
        'isThirdPartyPayer' => $member->is_third_party_payer,
        'oldPrimaryMemberId' => $member->old_primary_member_id,
        'deletedAt' => $member->deleted_at,
        'uaeResident' => $member->uae_resident,
        'passport' => $member->passport,
        'emiratesIdNumber' => $member->emirates_id_number,
        'primary' => app(CustomerService::class)->getPrimaryCustomerById($primaryMemberId)];
    });
    }
    // Helper function to save the renewal quote
    public function createTravelRenewalLead($travelQuote)
    {

        $response = CapiRequestService::sendCAPIRequest('/api/v1-save-travel-quote', $travelQuote);
        info("TravelRenewalService Travel quote successfully saved. Reference ID: {$response->quoteUID} | Time:".now());
        info("TravelRenewalService Lead allocation process initiated for Reference ID: {$response->quoteUID} | Time:".now());
        $this->leadAllocation($response->quoteUID);
        info("TravelRenewalService Lead allocation completed for Reference ID: {$response->quoteUID} - | Time: ".now());
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



    public function leadAllocation($quoteUID)
    {
        info('TravelRenewalService Processing Travel record for Quote Allocation with uuid: '.$quoteUID . ' | Time: '.now());
        $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);

        $lead = TravelQuote::where('uuid', $quoteUID)->first();
        // Only apply teamId if the payment status is AUTHORIZED
        $currentTeamId = $lead->payment_status_id == PaymentStatusEnum::AUTHORISED ? $teamId : false;

        if ($lead) {
            $response =  QuoteTypes::TRAVEL->allocate($quoteUID,$currentTeamId);
            if ($response) {
                info(self::class.' -TravelRenewalService Going to dispatch SendOCBTravelRenewalIntroEmailJob  Ref-ID: '.$quoteUID.' | Time: '.now());
                SendOCBTravelRenewalIntroEmailJob::dispatch($quoteUID)->delay(now()->addSeconds(30));
            }
        }


    }
}
