<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TravelQuoteEnum;
use App\Jobs\OCB\SendOCBTravelRenewalIntroEmailJob;
use App\Jobs\TravelRenewalLeadCreationJob;
use App\Models\RenewalBatch;
use App\Models\TravelQuote;
use App\Models\User;
use Carbon\Carbon;

class TravelRenewalService extends BaseService
{
    public function processTravelRenewalLeads()
    {
        $renewalDaysThreshold = getAppStorageValueByKey(ApplicationStorageEnums::TRAVEL_RENEWALS_DAYS_THRESHOLD);
        $startDate = Carbon::now()->subDays((int) $renewalDaysThreshold);
        info(self::class." - Travel Renewal Leads processing started with Start Date: {$startDate} | Time: ".now());
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
            ->whereIn('coverage_code', [TravelQuoteEnum::COVERAGE_CODE_MULTI_TRIP, TravelQuoteEnum::COVERAGE_CODE_ANNUAL_TRIP]) // only for testing purpose
            ->where('direction_code', TravelQuoteEnum::TRAVEL_UAE_OUTBOUND)
            ->whereDate('start_date', $startDate)
            ->chunkById(100, function ($quotes) {
                $quoteCount = $quotes->count();
                info(self::class." - Total quotes in current chunk: {$quoteCount} | Time: ".now());
                if ($quoteCount > 0) {
                    info(self::class." - processing travel renewals quotes in chunk: {$quoteCount} | Time: ".now());
                    $this->createTravelRenewalLeads($quotes);
                } else {
                    info(self::class.' - No quotes in chunk. | Time: '.now());
                }
            });

        info(self::class.' Travel Renewal Leads processing completed | Time: '.now());
    }

    public function createTravelRenewalLeads($quotes)
    {
        foreach ($quotes as $quote) {
            try {
                // Check if the quote is a duplicate
                if ($this->isDuplicateQuote($quote)) {
                    info(self::class." - Duplicate quote detected. Skipping processing for Quote Ref-ID:  {$quote->uuid} | Time: ".now());

                    continue; // Skip processing this quote
                }
                info(self::class." - Processing quote with Ref-ID: {$quote->uuid} | Time: ".now());
                $this->storeTravelRenewalQuote($quote);
            } catch (\Exception $e) {
                // Log the exception or handle it as needed
                info(self::class." - Error processing quote Ref-ID: {$quote->uuid} : Error: {$e->getMessage()}  Line: {$e->getLine()} | Time: ".now());
                throw $e;
            }
        }
    }
    public function isDuplicateQuote($quote)
    {
        info(self::class.' - Checking for duplicate quote with Ref-ID: '.$quote->uuid.'| Time: '.now());

        return TravelQuote::where('previous_quote_id', $quote->id)->exists();
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

        $generatedBatchNumber = $this->generateBatchNumber($newPolicyExpiryDate);
        $batch = $this->getRenewalBatch($generatedBatchNumber, $newPolicyExpiryDate);

        $customerService = app(CustomerService::class);
        $customer = $customerService->getCustomerByEmail($quote->customer_email);
        info(self::class." Processing renewal for old quote. Ref-ID: {$quote->uuid}. Initiating renewal process with updated policy details. | Time:".now());
        $destinationIds = collect($quote->TravelDestinations)->pluck('destination_id')->toArray();
        if (! empty($destinationIds)) {
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
                'members' => $this->mapCustomerMembers($quote->customerMembers, $quote->primary_member_id),
                'destinationIds' => $destinationIds,
                'emiratesIdNumber' => $customer->emirates_id_number ?? null,
                'emiratesIdExpiryDate' => $customer->emirates_id_expiry_date ?? null,
                'insuredFirstName' => $customer->insured_first_name ?? null,
                'insuredLastName' => $customer->insured_last_name ?? null,
                'isEcommerce' => $quote->is_ecommerce ?? null,
                'startDate' => $policyStartDate,
                'policyExpiryDate' => Carbon::parse($newPolicyExpiryDate)->format('Y-m-d'),
                'coverageCode' => $quote->coverage_code == TravelQuoteEnum::COVERAGE_CODE_ANNUAL_TRIP ? TravelQuoteEnum::COVERAGE_CODE_MULTI_TRIP : $quote->coverage_code,
                'regionCoverForId' => $quote->region_cover_for_id,
                'previousPolicyExpiryDate' => $quote->policy_expiry_date,
                'tripStarted' => false,
            ];

            TravelRenewalLeadCreationJob::dispatch($travelQuotePayload)->delay(Carbon::now()->addMinutes(1));
            info(self::class." - Travel renewal lead creation job dispatched for Ref-ID: {$quote->uuid} | Time:".now());
        } else {
            info(self::class." - TravelRenewalService No destination found for Ref-ID: {$quote->uuid} | Time:".now());
        }
    }

    public function mapCustomerMembers($members, $primaryMemberId)
    {
        return collect($members)->map(function ($member) use ($primaryMemberId) {

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
                'primary' => app(CustomerService::class)->getPrimaryCustomerById($primaryMemberId, $member->id),
            ];
        });
    }

    public function createTravelRenewalLead($travelQuote)
    {
        try {
            $response = CapiRequestService::sendCAPIRequest('/api/v1-save-travel-quote', $travelQuote);
            info(self::class." - TravelRenewalService Travel quote successfully saved. Ref-ID: {$response->quoteUID} | Time:".now());
            info(self::class." -  Lead allocation process initiated for Ref-ID: {$response->quoteUID} | Time:".now());
            $this->leadAllocation($response->quoteUID);
            info(self::class." -  Lead allocation completed for Ref-ID: {$response->quoteUID} - | Time: ".now());
        } catch (\Exception $e) {
            info(self::class." - TravelRenewalService Error saving Travel quote Ref-ID: {$travelQuote->previousQuoteId} | Time:".now());
            throw $e;
        }

    }

    public function GenerateBatchNumber($expiryDate)
    {
        $expiryDate = Carbon::parse($expiryDate);
        $currentDate = Carbon::now();
        // Calculate the number of weeks between the current date and the expiry date
        $weeksUntilExpiry = $currentDate->diffInWeeks($expiryDate);

        return strtoupper('W-'.(int) $weeksUntilExpiry);
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

    public function leadAllocation($quoteUID)
    {
        info(self::class." - Processing Travel record for Quote Allocation with Ref-ID: {$quoteUID} | Time: ".now());

        $lead = TravelQuote::where('uuid', $quoteUID)->first();
        if ($lead) {
            info(self::class." - Lead found for Quote UID: {$quoteUID} | Time: ".now());
            $eligibleUser = $this->getTravelRenewalsAdvisor();
            if ($eligibleUser) {
                info(self::class." - Eligible Advisor {$eligibleUser->user_id} found for Quote UID: {$quoteUID} | Time: ".now());
                $this->assignLead($lead, $eligibleUser->user_id, AssignmentTypeEnum::SYSTEM_ASSIGNED);
                info(self::class.' - TravelRenewalService Going to dispatch SendOCBTravelRenewalIntroEmailJob  Ref-ID: '.$quoteUID.' | Time: '.now());
                SendOCBTravelRenewalIntroEmailJob::dispatch($quoteUID)->delay(now()->addSeconds(30));
            } else {
                info(self::class." - No eligible advisor found for Quote UID: {$quoteUID} | Time: ".now());
                info(self::class." - Allocation failed for Quote UID: {$quoteUID} | Time: ".now());
            }

        } else {
            info(self::class." - No lead found for Quote UID: {$quoteUID} | Time: ".now());
        }
    }

    public function assignLead(TravelQuote $lead, $advisorId, $assignmentType)
    {
        $lead->advisor_id = $advisorId;
        $lead->assignment_type = $assignmentType;
        $lead->save();

        $this->assignToChildLead($lead);
    }

    private function assignToChildLead($lead)
    {
        $childLead = TravelQuote::where('parent_id', $lead->id)->first();

        if ($childLead) {
            info(self::class." - Assigning Advisor {$lead->advisor_id} to child lead {$childLead->uuid} for Quote UID: {$lead->uuid}");
            $childLead->advisor_id = $lead->advisor_id;
            $childLead->assignment_type = $lead->assignment_type;
            $childLead->save();
        }
    }

    public function getTravelRenewalsAdvisor()
    {
        $teamId = getTeamId(TeamNameEnum::TRAVEL_RENEWALS);

        return User::select('users.id as user_id')
            ->join('lead_allocation as la', 'la.user_id', '=', 'users.id')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->when($teamId, function ($q) use ($teamId) {
                $q->whereIn('users.id', fn ($query) => $query->select('user_id')->from('user_team')->where('team_id', $teamId));
            })
            ->whereIn('r.name', [RolesEnum::TravelAdvisor])
            ->where('la.quote_type_id', QuoteTypes::TRAVEL->id())
            ->orderBy('la.last_allocated', 'asc')
            ->activeUser()
            ->first();
    }

}
