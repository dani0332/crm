<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CarPlanType;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\TiersEnum;
use App\Enums\UserStatusEnum;
use App\Jobs\IntroEmailJob;
use App\Models\CarQuote;
use App\Models\CarQuotePlanDetail;
use App\Models\CarQuoteRequestDetail;
use App\Models\InsuranceProvider;
use App\Models\LeadAllocation;
use App\Models\LeadSource;
use App\Models\QuoteBatches;
use App\Models\RuleLeadSource;
use App\Models\Tier;
use App\Models\TierUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CarAllocationService extends AllocationService
{
    public function fetchLead($quoteId)
    {
        return CarQuote::where('uuid', $quoteId)
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->where('is_renewal_tier_email_sent', 0)
            ->first();
    }

    public function getTier($tierId)
    {
        return Tier::where('id', $tierId)->first();
    }

    public function getStatusOrder(): array
    {
        $start_time = Carbon::createFromFormat('H:i', $this->getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_START_TIME));
        $end_time = Carbon::createFromFormat('H:i', $this->getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_END_TIME));
        $isBusinessHours = now()->between($start_time, $end_time);

        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $isBusinessHours) {
            $statusOrder[] = UserStatusEnum::UNAVAILABLE;
        }

        return $statusOrder;
    }

    public function getTierBasedOnValue($carLead, $tiersQuery): void
    {
        $valuations = $this->getValuation($carLead->car_model_detail_id, $carLead->year_of_manufacture);

        $axaProvider = InsuranceProvider::where('code', InsuranceProvidersEnum::AXA)->first();

        $axaValuation = array_filter($valuations, function ($provider) use ($axaProvider) {
            return $provider->providerId == $axaProvider->id;
        });

        $carValue = 0;

        if ($axaProvider) {
            $carValue = $axaValuation->carValue;
        }

        info('car value as per valuation engine for GIG is '.$carValue.' for lead : '.$carLead->uuid);

        if ($carLead->car_model_detail_id == null || ! $carValue || $carValue == 0) {
            $tiersQuery->where('name', TiersEnum::TIER_L)->first();

            return;
        } else {
            $tiersQuery->where('min_price', '<=', $carValue)->where('max_price', '>=', $carValue);
        }

        info('At the end tier query for is : '.json_encode($tiersQuery->toSql()));
    }

    protected function getDeferredLeads(): mixed
    {
        return CarQuote::whereNull('advisor_id')->where('deferred', 1)->whereBetween('deferred_at', [now()->subDay(2)->toDateTimeString(), now()]);
    }

    public function findTier($carLead): ?Tier
    {
        info('Started searching tier for car lead: '.json_encode($carLead->code));

        $plans = CarQuotePlanDetail::where('quote_uuid', $carLead->uuid)->where('repair_type', CarPlanType::COMP)->get();

        $tiersQuery = Tier::where('is_active', 1); // Query to get all active tiers

        $yearOfManufacture = now()->subYear(15)->year;

        info('yearOfManufacture is : '.$yearOfManufacture);

        info('number of plans found are : '.count($plans));

        if ($carLead->year_of_manufacture < $yearOfManufacture) { // case when car year of manufacture is newer than 15 years
            info('Inside year of manufacture block and car year of manufacture is : '.$carLead->year_of_manufacture);
            if (count($plans) > 0) {
                info('more than one plans found against car lead  : '.$carLead->uuid);
                $this->getTierBasedOnValue($carLead, $tiersQuery);

                return $tiersQuery->first();
            } else {

                if ($carLead->car_value >= 300000) {
                    return $tiersQuery->Where('name', TiersEnum::TIER_H)->get();
                }

                $userDob = Carbon::createFromFormat('Y-m-d H:i:s', $carLead->dob);
                $ageInYears = $userDob->age;

                if ($carLead->car_value < 300000 || $ageInYears >= 21) {
                    return $tiersQuery->Where('name', $carLead->is_ecommerce ? TiersEnum::TIER6_ECOM : TiersEnum::TIER6_NONECOM)->get();
                }
            }
        } else {
            // case when car year of manufacture is older or equal than 15 years
            info('Inside year of manufacture older block and car year of manufacture is : '.$carLead->year_of_manufacture);
            $this->getTierBasedOnValue($carLead, $tiersQuery);

            return $tiersQuery->first();
        }
    }

    public function getEligibleUserForAllocation($tierId, $advisorId = null)
    {
        $tierUserQuery = TierUser::where('tier_id', $tierId);

        if ($advisorId) {
            $tierUserQuery->where('user_id', '!=', $advisorId);
        }

        $tierUserIds = $tierUserQuery->pluck('user_id');

        info('Tier users: '.json_encode($tierUserIds));

        $statusOrder = $this->getStatusOrder();

        foreach ($statusOrder as $status) {
            $eligibleUsers = $this->getAdvisorsByStatus($status, $tierUserIds);

            if ($eligibleUsers && count($eligibleUsers) > 0) {
                info('result of available users are : '.json_encode(collect($eligibleUsers)->pluck('user_id')));

                return $eligibleUsers->toArray();
            }
        }

        return [];
    }

    public function getAdvisorsByStatus($status, $tierUserIds)
    {
        return LeadAllocation::with('leadAllocationUser')
            ->whereHas('leadAllocationUser', function ($query) use ($status) {
                $query->where('last_login', '>', DB::raw('DATE_ADD(CURDATE(), INTERVAL 1 SECOND)'))
                    ->where('is_available', 1)
                    ->where('status', $status);
            })
            ->where(function ($query) {
                $query->whereRaw('allocation_count < max_capacity')
                    ->orWhere('max_capacity', -1);
            })
            ->whereIn('user_id', $tierUserIds)
            ->orderByDesc('last_allocated')->get();
    }

    public function getRules($carLead)
    {
        // $commercialKeywords = CommercialKeyword::select('id', 'name')->get();

        // foreach ($commercialKeywords as $keyword) {
        //     if (str_contains(strtolower(trim($carLead->full_name)), strtolower(trim($keyword->name)))) {
        //         return $this->getRulesForCarMakeAndModel($carLead->car_make_id, $carLead->car_model_id);
        //     }
        // }

        return $this->getRulesForLeadSource($carLead->source);
    }

    public function getAppStorageValuesForCarLeads(): array
    {
        $from = $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_START_DATE_FOR_LEADS');
        $to = now()->subMinutes(2)->toDateTimeString();
        $limit = $this->getAppStorageValueByKey('CAR_LEAD_PICKUP_LIMIT');
        $isFIFO = $this->getAppStorageValueByKey('CAR_LEAD_PICKUP_FIFO');

        return [$from, $to, $limit, $isFIFO];
    }

    private function getRulesForCarMakeAndModel($carMake, $carModel)
    {
        // return Rule::join('rule_details', 'rule_details.rule_id', 'rules.id')
        //     ->join('rule_users', 'rule_users.rule_id', 'rules.id')
        //     ->join('users', 'users.id', 'rule_users.user_id')
        //     ->where('rule_details.car_make_id', $carMake)
        //     ->where('rule_details.car_model_id', $carModel)
        //     ->where('rules.is_active', 1)
        //     ->groupBy('rule_details.rule_id')
        //     ->select(DB::raw('group_concat(rule_users.user_id) AS leadSourceUsers'))
        //     ->get();
    }

    private function getRulesForLeadSource($leadSource)
    {
        return LeadSource::with(['ruleDetails.rule.users'])
            ->where('name', $leadSource)
            ->whereHas('ruleDetails.rule', function ($query) {
                $query->where('is_active', 1);
            })
            ->where('is_applicable_for_rules', 1)
            ->select(
                'name AS leadSourceName',
                'id AS leadSourceId'
            )
            ->get()
            ->map(function ($leadSource) {
                $leadSource->leadSourceUsers = $leadSource->ruleDetails->flatMap(function ($detail) {
                    return $detail->rule->users->pluck('id');
                });

                return $leadSource;
            })->toArray();
    }

    public function determineFinalUserId($lead, $eligibleUsers, $rules): mixed
    {
        if (count($rules) > 0) {
            $ruleUserIds = $this->getUserIdsFromRuleRecords($rules);
            $finalEligibleUserIds = array_intersect($eligibleUsers, $ruleUserIds);

            info('Rule found and users against rule are: '.json_encode($finalEligibleUserIds));
        } else {
            $ruleUsers = $this->getRuleUsers();
            info('No rule found against this lead: '.$lead->uuid.' so filtering rule users: '.json_encode($ruleUsers));
            $finalEligibleUserIds = [];
            info('eligibleUsers are : '.json_encode(collect($eligibleUsers)->pluck('user_id')));
            foreach ($eligibleUsers as $eligibleUserId) {
                if (! in_array($eligibleUserId, $ruleUsers)) {
                    array_push($finalEligibleUserIds, $eligibleUserId);
                }
            }
            $finalEligibleUserIds = collect($finalEligibleUserIds)->pluck('user_id')->unique()->toArray();
            info('Final login and available users after rule exclusion are: '.json_encode($finalEligibleUserIds));
        }

        return count($finalEligibleUserIds) > 0 ? reset($finalEligibleUserIds) : 0;
    }

    /**
     * @return array|int[]
     */
    private function getUserIdsFromRuleRecords($matchedRuleRecords): array
    {
        $leadSourceUsers = $matchedRuleRecords->first()->leadSourceUsers;

        return str_contains($leadSourceUsers, ',')
            ? array_map('intval', explode(',', $leadSourceUsers))
            : [(int) $leadSourceUsers];
    }

    private function getRuleUsers(): mixed
    {
        return RuleLeadSource::join('rules', 'rule_lead_sources.rule_id', 'rules.id')
            ->where('rules.is_active', 1)->distinct()
            ->pluck('rule_lead_sources.user_id')->toArray();
    }

    public function processLeadAssignment($lead, $userId, $tier, $assignmentType): void
    {
        info('About to assign car lead: '.$lead->uuid.' to user with id: '.$userId);

        $carQuote = $this->assignLeadToUserAndGetQuote($lead, $userId, $tier, $assignmentType);

        info('advisor and tier assignment done for : '.$carQuote->uuid.' to user with id : '.$userId.' and tier name : '.$tier->name);

        $this->updateCarLeadDetailRecord($lead->id);

        info('updating user record in lead allocation table with count increment userId: '.$userId);

        $this->updateLeadAllocationOnCarAutoAssignment($userId);

        info('Completed assignment of lead and lead count update is done for quote: '.$carQuote->code);
    }

    private function assignLeadToUserAndGetQuote($lead, $userId, $tier, $assignmentType): mixed
    {
        $updatedData = [
            'tier_id' => $tier->id,
            'advisor_id' => $userId,
            'cost_per_lead' => $tier->cost_per_lead,
            'auto_assigned' => true,
            'assignment_type' => $assignmentType,
        ];

        if ($lead->quote_batch_id === null) {
            $quoteBatch = QuoteBatches::latest()->first();
            info('About to assign quote batch with id: '.$quoteBatch->id.' and name: '.$quoteBatch->name.' to quote: '.$lead->uuid);
            $updatedData['quote_batch_id'] = $quoteBatch->id;
        } else {
            info('Quote batch currently attached to quote: '.$lead->uuid.' and quote id is: '.$lead->quote_batch_id);
        }

        $lead->fill($updatedData);
        $lead->save();

        return $lead;
    }

    public function updateCarLeadDetailRecord($leadId): void
    {
        info('about to update car quote detail record for : '.$leadId);

        $carQuoteDetail = CarQuoteRequestDetail::where('car_quote_request_id', $leadId)->first();

        if ($carQuoteDetail) {
            $this->updateExistingCarQuoteDetail($carQuoteDetail);
        } else {
            $this->createNewCarQuoteDetail($leadId);
        }
    }

    private function updateExistingCarQuoteDetail($carQuoteDetail): void
    {
        $carQuoteDetail->advisor_assigned_date = now();
        $carQuoteDetail->advisor_assigned_by_id = auth()->id();
        $carQuoteDetail->save();

        info('car quote detail update for lead : '.$carQuoteDetail->car_quote_request_id);
    }

    private function createNewCarQuoteDetail($leadId): void
    {
        CarQuoteRequestDetail::create([
            'car_quote_request_id' => $leadId,
            'advisor_assigned_date' => now(),
            'advisor_assigned_by_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        info('car quote request detail record not found, creating new entry');
    }

    public function updateLeadAllocationOnCarAutoAssignment($userId): void
    {
        $leadAllocation = LeadAllocation::where('user_id', $userId)->first();

        if ($leadAllocation) {
            $this->updateLeadAllocationCounts($userId, $leadAllocation);
        }

        info('allocation count updated for user Id: '.$userId.', total count = '.$leadAllocation->allocation_count.' and auto count = '.$leadAllocation->auto_assignment_count);
    }

    private function buildEmailDataForLMSIntroEmail($userId, $carQuote): object
    {
        $user = User::findOrFail($userId);
        $documentUrl = $this->getLMSIntroEmailAttachmentUrl();

        return (object) [
            'customerEmail' => $carQuote->email,
            'documentUrl' => [$documentUrl], // Replace with a generic URL once document upload section is done
            'clientFullName' => $carQuote->first_name.' '.$carQuote->last_name,
            'advisorName' => $user->name,
            'landLine' => $user->landline_no,
            'mobilePhone' => $user->mobile_no,
            'advisorEmail' => $user->email,
            'carQuoteId' => $carQuote->code,
            'quoteLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$carQuote->uuid,
        ];
    }

    private function sendIntroEmailForLeadAssignment($emailData): void
    {
        $emailTemplateId = $this->getLMSIntroEmailTemplateId();

        IntroEmailJob::dispatch(quoteTypeCode::Car, $emailTemplateId, $emailData, 'send-lms-intro-email');
    }

    private function getLMSIntroEmailAttachmentUrl(): string
    {
        return $this->getAppStorageValueByKey(ApplicationStorageEnums::LMS_INTRO_EMAIL_ATTACHMENT_URL);
    }

    private function getLMSIntroEmailTemplateId(): int
    {
        return (int) $this->getAppStorageValueByKey('LMS_INTRO_EMAIL_TEMPLATE_ID');
    }

    public function updateLeadTier($lead, $tier): void
    {
        info('login users not found for selected lead so will try to assign only tier for lead : '.$lead->uuid);

        CarQuote::where('id', $lead->id)->update([
            'tier_id' => $tier->id,
        ]);

        info('Tier with name : '.$tier->name.' is assigned to car lead with uuid : '.$lead->uuid);
    }

    public function fetchLeadsForReAssignment($advisorId)
    {
        $from = now()->subDay()->setTime(18, 30)->format(config('constants.DB_DATE_FORMAT_MATCH'));
        info('leads will be picked up in reassignment from : '.$from);
        $leads = CarQuote::whereBetween('created_at', [$from, now()])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->whereIn('quote_status_id', [QuoteStatusEnum::NewLead, QuoteStatusEnum::FollowedUp]);
        if ($advisorId != 0) {
            $leads->where('advisor_id', $advisorId);
        }

        return $leads->get();
    }

    public function shouldProceed(): bool
    {
        $start_time = Carbon::createFromFormat('H:i', $this->getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_START_TIME));
        $end_time = Carbon::createFromFormat('H:i', $this->getAppStorageValueByKey(ApplicationStorageEnums::REASSIGNMENT_END_TIME));
        $shouldProceed = now()->between($start_time, $end_time);

        return $shouldProceed;
    }

    public function buildEmailDateForLMSIntroEmail($carQuote)
    {
        $user = User::where('id', $carQuote->advisor_id)->first();
        $documentUrl = $this->getAppStorageValueByKey(ApplicationStorageEnums::LMS_INTRO_EMAIL_ATTACHMENT_URL);
        $emailData = (object) [
            'customerEmail' => $carQuote->email,
            'documentUrl' => [$documentUrl], // this will be replace with a generic URL once document upload section is done
            'clientFullName' => $carQuote->first_name.' '.$carQuote->last_name,
            'advisorName' => $user->name,
            'landLine' => $user->landline_no,
            'mobilePhone' => $user->mobile_no,
            'advisorEmail' => $user->email,
            'carQuoteId' => $carQuote->code,
            'quoteLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL').$carQuote->uuid,
        ];

        return $emailData;
    }

    public function isLeadReassigned($lead)
    {
        $leadDetail = CarQuoteRequestDetail::where('car_quote_request_id', $lead->id)->first();
        if ($leadDetail && $leadDetail->advisor_assigned_date > now()->subMinutes(2)) {
            return true;
        } else {
            return false;
        }
    }
}
