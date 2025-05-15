<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RegionCoverEnum;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TravelQuoteEnum;
use App\Jobs\Allocation\AssignTravelRenewalLeadJob;
use App\Jobs\OCB\SendOCBTravelRenewalIntroEmailJob;
use App\Jobs\TravelRenewalLeadCreationJob;
use App\Models\LeadAllocation;
use App\Models\Nationality;
use App\Models\RenewalBatch;
use App\Models\TravelQuote;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;

class TravelRenewalService extends BaseService
{
    public function processTravelRenewalLeads()
    {
        $renewalDaysThreshold = getAppStorageValueByKey(ApplicationStorageEnums::TRAVEL_RENEWALS_DAYS_THRESHOLD);
        $startDate = Carbon::now()->subDays((int) $renewalDaysThreshold);
        LoggerService::info(self::class." - Travel Renewal Leads processing started with Start Date: {$startDate}");
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
            ->with('regionCoverFor:id,code,text')
            ->chunkById(100, function ($quotes) {
                $quoteCount = $quotes->count();
                LoggerService::info(self::class." - Total quotes in current chunk: {$quoteCount}");
                if ($quoteCount > 0) {
                    LoggerService::info(self::class." - processing travel renewals quotes in chunk: {$quoteCount}");
                    $this->createTravelRenewalLeads($quotes);
                } else {
                    LoggerService::info(self::class.' - No quotes in chunk');
                }
            });

        LoggerService::info(self::class.' Travel Renewal Leads processing completed');
    }

    public function createTravelRenewalLeads($quotes)
    {
        foreach ($quotes as $quote) {
            LoggerService::startQuoteLogging($quote);
            try {
                // Check if the quote is a duplicate
                if ($this->isDuplicateQuote($quote)) {
                    LoggerService::info(self::class.' - Duplicate quote detected. Skipping processing');

                    continue; // Skip processing this quote
                }
                LoggerService::info(self::class.' - Processing quote');
                $this->storeTravelRenewalQuote($quote);
            } catch (\Exception $e) {
                // Log the exception or handle it as needed
                LoggerService::error('Error processing quote', exception: $e);
            }
        }
    }
    public function isDuplicateQuote($quote)
    {
        LoggerService::info(self::class.' - Checking for duplicate quote');

        return TravelQuote::where('previous_quote_id', $quote->id)->exists();
    }
    public function storeTravelRenewalQuote($quote)
    {
        $travelStartDate = Carbon::parse($quote->start_date);
        LoggerService::info(self::class." - Travel Start Date: {$travelStartDate}");
        // Calculate the policy expiry date based on the start date + 365 days
        $policyExpiryDate = $travelStartDate->copy()->addDays(365);
        $policyStartDate = $policyExpiryDate->copy()->addDays(1);
        $newPolicyExpiryDate = $policyStartDate->copy()->addDays(365);
        LoggerService::info(self::class." - Policy Expiry Date: {$policyExpiryDate}");
        LoggerService::info(self::class." - New Policy Start Date: {$policyStartDate}");
        LoggerService::info(self::class." - New Policy Expiry Date: {$newPolicyExpiryDate}");

        $batch = $this->getRenewalBatch($policyExpiryDate);
        if (empty($batch)) {
            LoggerService::info(self::class." - TravelRenewalService No renewal batch found for Ref-ID: {$quote->uuid}");

            return;
        }

        $customerService = app(CustomerService::class);
        $customer = $customerService->getCustomerByEmail($quote->customer_email);
        LoggerService::info(self::class." Processing renewal for old quote. Ref-ID: {$quote->uuid}. Initiating renewal process with updated policy details.");
        $destinationIds = collect($quote->TravelDestinations)->pluck('destination_id')->toArray();
        if (count($destinationIds) < 1) {
            LoggerService::info(self::class." - TravelRenewalService No destination found for Ref-ID: {$quote->uuid}");
            LoggerService::info(self::class." -  region_cover_for_id: {$quote->region_cover_for_id} Ref-ID: {$quote->uuid}");
            $destinationIds = $this->getDestinationId($quote->regionCoverFor, $quote->uuid);
        }

        $members = $this->mapCustomerMembers($quote->customerMembers, $quote->primary_member_id) ?? [];

        if (! empty($quote->region_cover_for_id) && count($members) > 0) {
            $policyDates = [
                'policyStartDate' => $policyStartDate,
                'newPolicyExpiryDate' => $newPolicyExpiryDate,
                'policyExpiryDate' => $policyExpiryDate,
            ];
            $travelQuotePayload = (object) $this->createTravelRenewalPayload($quote, $batch, $policyDates, $destinationIds, $members, $customer);
            TravelRenewalLeadCreationJob::dispatch($travelQuotePayload)->delay(Carbon::now()->addMinutes(1));
            LoggerService::info(self::class." - Travel renewal lead creation job dispatched for Ref-ID: {$quote->uuid}");
        } else {
            $logData = [
                'message' => 'TravelRenewalService No destination or members found',
                'destination_count' => count($destinationIds),
                'quote_ref_id' => $quote->uuid,
                'region_cover_for_id' => $quote->region_cover_for_id,
                'members_count' => count($members),
                'time' => now(),
            ];
            LoggerService::info(self::class.' - '.json_encode($logData));
        }

    }

    public function createTravelRenewalPayload($quote, $batch, $policyDates, $destinationIds, $members, $customer)
    {
        return [
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
            'members' => $members,
            'destinationIds' => $destinationIds,
            'emiratesIdNumber' => $customer->emirates_id_number ?? null,
            'emiratesIdExpiryDate' => $customer->emirates_id_expiry_date ?? null,
            'insuredFirstName' => isset($customer->insured->first_name) ? $customer->insured->first_name : (isset($customer->insured_first_name) ? $customer->insured_first_name : ''),
            'insuredLastName' => isset($customer->insured->last_name) ? $customer->insured->last_name : (isset($customer->insured_last_name) ? $customer->insured_last_name : ''),
            'isEcommerce' => $quote->is_ecommerce ?? null,
            'startDate' => Carbon::parse($policyDates['policyStartDate'])->format('Y-m-d'),
            'policyExpiryDate' => Carbon::parse($policyDates['newPolicyExpiryDate'])->format('Y-m-d'),
            'coverageCode' => $quote->coverage_code == TravelQuoteEnum::COVERAGE_CODE_ANNUAL_TRIP ? TravelQuoteEnum::COVERAGE_CODE_MULTI_TRIP : $quote->coverage_code,
            'regionCoverForId' => $quote->region_cover_for_id,
            'previousPolicyExpiryDate' => Carbon::parse($policyDates['policyExpiryDate'])->format('Y-m-d'),
            'tripStarted' => false,
        ];
    }
    public function getDestinationId($regionCoverFor, $quoteUID)
    {
        $regionMapping = [
            RegionCoverEnum::SCHENGEN => RegionCoverEnum::NORWAY,
            RegionCoverEnum::WORLDWIDE_EXCL_US_CANADA => RegionCoverEnum::FRANCE,
            RegionCoverEnum::WORLDWIDE_INCL_US_CANADA => RegionCoverEnum::UNITED_STATES,
        ];
        $countryCode = $regionMapping[$regionCoverFor->code];
        LoggerService::info(self::class." - TravelRenewalService Mapping destination for country code: {$countryCode} quote Ref-ID: {$quoteUID}");
        $destination = Nationality::where('code', $countryCode)->first();
        $countryName = $destination->country_name ?? '';
        LoggerService::info(self::class." - TravelRenewalService Destination found for country: {$countryName} quote Ref-ID: {$quoteUID}");

        return [$destination->id ?? null];
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
                'uaeResident' => true,
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
            LoggerService::info(self::class." - TravelRenewalService Travel quote successfully saved. Ref-ID: {$response->quoteUID}");
            LoggerService::info(self::class." -  Lead allocation process initiated for Ref-ID: {$response->quoteUID}");

            // Dispatch the lead allocation job with a delay to avoid race conditions
            $this->dispatchLeadAllocationJob($response->quoteUID);

            LoggerService::info(self::class." -  Lead allocation job dispatched for Ref-ID: {$response->quoteUID} -");
        } catch (\Exception $e) {
            LoggerService::error(self::class." - TravelRenewalService Error saving Travel quote Ref-ID: {$travelQuote->previousQuoteId}", exception: $e);
        }
    }

    /**
     * Dispatch the lead allocation job with a small delay to prevent race conditions
     */
    protected function dispatchLeadAllocationJob($quoteUID)
    {
        // Add a random delay between 5-15 seconds to ensure staggered processing
        $delaySeconds = rand(5, 15);
        AssignTravelRenewalLeadJob::dispatch($quoteUID)->delay(now()->addSeconds($delaySeconds));

        LoggerService::info(self::class." - Lead allocation job dispatched with {$delaySeconds}s delay for Ref-ID: {$quoteUID}");
    }

    public function getRenewalBatch($newPolicyExpiryDate)
    {
        return RenewalBatch::where('start_date', '<=', $newPolicyExpiryDate)
            ->where('end_date', '>=', $newPolicyExpiryDate)
            ->whereNull('quote_type_id')
            ->first();
    }

    public function leadAllocation($quoteUID)
    {
        LoggerService::info(self::class." - Processing Travel record for Quote Allocation with Ref-ID: {$quoteUID}");

        $lead = TravelQuote::where('uuid', $quoteUID)->first();
        if (! $lead) {
            LoggerService::info(self::class." - No lead found for Quote UID: {$quoteUID}");

            return false;
        }

        LoggerService::startFeatureLogging($lead, LoggerFeatureEnum::ALLOCATION);

        LoggerService::info(self::class.' - Lead found');

        [$advisorId, $leadAllocationId] = $this->getTravelRenewalsAdvisor();

        if (! $advisorId) {
            LoggerService::info(self::class.' - No eligible advisor found');
            LoggerService::info(self::class.' - Allocation failed');

            return false;
        }

        LoggerService::info(self::class." - Eligible Advisor {$advisorId} found");

        // Update the last allocated timestamp
        LeadAllocation::where('id', $leadAllocationId)->update([
            'last_allocated' => now()->timestamp,
        ]);

        // Assign the lead to the advisor
        $this->assignLead($lead, $advisorId, AssignmentTypeEnum::SYSTEM_ASSIGNED);

        LoggerService::info(self::class.' - TravelRenewalService Going to dispatch SendOCBTravelRenewalIntroEmailJob');
        SendOCBTravelRenewalIntroEmailJob::dispatch($quoteUID)->delay(now()->addSeconds(30));

        return true;
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
            LoggerService::info(self::class." - Assigning Advisor {$lead->advisor_id} to child lead {$childLead->uuid} for Quote UID: {$lead->uuid}");
            $childLead->advisor_id = $lead->advisor_id;
            $childLead->assignment_type = $lead->assignment_type;
            $childLead->save();
        }
    }

    public function getTravelRenewalsAdvisor()
    {
        $teamId = getTeamId(TeamNameEnum::TRAVEL_RENEWALS);

        $advisor = User::select('users.id as user_id', 'la.id as lead_allocation_id')
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

        if (! $advisor) {
            return [null, null];
        }

        return [$advisor->user_id, $advisor->lead_allocation_id];
    }
}
