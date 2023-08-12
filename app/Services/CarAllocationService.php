<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CarTypeOfInsuranceIdEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\UserStatusEnum;
use App\Jobs\IntroEmailJob;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\LeadAllocation;
use App\Models\LeadSource;
use App\Models\QuoteBatches;
use App\Models\Rule;
use App\Models\Tier;
use App\Models\TierUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CarAllocationService extends AllocationService
{
    public function fetchLeads()
    {
        list($from, $to, $limit, $isFIFO) = $this->getAppStorageValuesForCarLeads();

        info('Car leads fetch start date is: ' . $from . ' and end datetime is: ' . $to . ' and pickup limit is: ' . $limit . ' and Pickup direction FIFO is: ' . $isFIFO);

        $unassignedLeadsCount = $this->getUnassignedLeadsCount();

        $leadsQuery = CarQuote::whereNull('advisor_id')
                    ->where('is_renewal_tier_email_sent', 0)->where('deferred', 0)
                    ->whereBetween('created_at', [$from, $to])
                    ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
                    ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
                    ->orderBy('created_at', $isFIFO ? 'asc' : 'desc')
                    ->limit($limit);


        $this->addDeferredLeadsIfNeeded($unassignedLeadsCount, $limit, $leadsQuery);

        return $this->applyPriority($leadsQuery, $limit);

    }

    /**
     * @return mixed
     */
    protected function getUnassignedLeadsCount(): mixed
    {
        return CarQuote::whereNull('advisor_id')->count();
    }

    /**
     * @param $limit
     * @return mixed
     */
    private function fetchDeferredLeads($limit): mixed
    {
        return CarQuote::where('deferred', 1)
                ->orderBy('deferred_at')
                ->limit($limit);
    }

    /**
     * @param $carLead
     * @return Tier|null
     */
    public function findTier($carLead): Tier|null
    {
        info('Started searching tier for car lead: ' . json_encode($carLead->code));

        $tiersQuery = Tier::where('is_active', 1); // Query to get all active tiers

        info('Car ecommerce info is: ' . json_encode($carLead->is_ecommerce));

        if ($carLead->car_type_insurance_id == CarTypeOfInsuranceIdEnum::ThirdPartyOnly) {
            info('Selecting tier which can handle TPL leads');
            $tiersQuery->where('can_handle_tpl', 1);

            if ($carLead->is_ecommerce) {
                $tiersQuery->where('can_handle_ecommerce', 1);
            }
        }

        if ($carLead->car_type_insurance_id == CarTypeOfInsuranceIdEnum::Comprehensive
            && ($carLead->car_value === null || $carLead->car_value <= 0 || in_array($carLead->car_value, ['?', '']))) {
            info('Selecting tier which can handle null value leads');
            $tiersQuery->where('can_handle_null_value', 1);
        }

        if ($carLead->car_type_insurance_id == CarTypeOfInsuranceIdEnum::Comprehensive && $carLead->car_value > 0) {
            $highestValueTier = Tier::where('is_active', 1)->orderByDesc('max_price')->first();

            if ($carLead->car_value > $highestValueTier->max_price) {
                info('Lead ' . $carLead->uuid . ' has value higher than all tiers, selecting tier ' . $highestValueTier->name);
                return $highestValueTier;
            } else {
                info('Filtering tiers based on car value: ' . $carLead->car_value);
                $tiersQuery->where('min_price', '<=', $carLead->car_value)->where('max_price', '>=', $carLead->car_value);
            }
        }

        if ($carLead->source == LeadSourceEnum::TPL_RENEWALS) {
            info('Filtering tiers for TPL renewals check');
            $tiersQuery->where('is_tpl_renewals', 1);

            if ($carLead->is_ecommerce) {
                $tiersQuery->where('can_handle_ecommerce', 1);
            }
        }

        $tiersSql = $tiersQuery->toSql();
        $tiersBindings = $tiersQuery->getBindings();

        info('Tiers query: ' . $tiersSql . ' with bindings: ' . json_encode($tiersBindings));

        $tiers = $tiersQuery->get();

        if ($tiers->isNotEmpty()) {
            info('First tier after filtration: ' . json_encode($tiers->first()->name));
            return $tiers->first();
        }

        return null;
    }

    /**
     * @param $tierId
     * @return array
     */
    public function getEligibleUsersForAllocation($tierId): array
    {
        $tierUserIds = TierUser::where('tier_id', $tierId)->pluck('user_id');

        info('Tier users: ' . json_encode($tierUserIds));

        $eligibleUsers = LeadAllocation::with('leadAllocationUser')
        ->whereHas('leadAllocationUser', function ($query) {
            $query->where('last_login', '>', DB::raw('DATE_ADD(CURDATE(), INTERVAL 1 SECOND)'))
                ->where('is_available', 1);
        })
        ->where(function ($query) {
            $query->whereRaw('allocation_count < max_capacity')
                ->orWhere('max_capacity', -1);
        })
        ->where('leadAllocationUser.status', UserStatusEnum::ONLINE)
        ->whereIn('user_id', $tierUserIds)
        ->orderByDesc('last_allocated')->get()->pluck('leadAllocationUser.id')->toArray();

        return $eligibleUsers;
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

    /**
     * @return array
     */
    public function getAppStorageValuesForCarLeads(): array
    {
        $from = $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_START_DATE_FOR_LEADS');
        $to = now()->subMinutes(2)->toDateTimeString();
        $limit = $this->getAppStorageValueByKey('CAR_LEAD_PICKUP_LIMIT');
        $isFIFO = $this->getAppStorageValueByKey('CAR_LEAD_PICKUP_FIFO');
        return array($from, $to, $limit, $isFIFO);
    }

    /**
     * @param $unassignedLeadsCount
     * @param mixed $limit
     * @param $leadsQuery
     * @return void
     */
    public function addDeferredLeadsIfNeeded($unassignedLeadsCount, mixed $limit, $leadsQuery): void
    {
        if ($unassignedLeadsCount < $limit) {
            $deferredLeads = $this->fetchDeferredLeads($limit - $unassignedLeadsCount);
            $leadsQuery->union($deferredLeads);
        }
    }

    /**
     * @param $leadsQuery
     * @param mixed $limit
     * @return mixed
     */
    public function applyPriority($leadsQuery, mixed $limit): mixed
    {
        // Prioritize leads with payment_status_id of Authorized and Paid
        $priorityPaymentStatus = [PaymentStatusEnum::AUTHORISED, PaymentStatusEnum::PAID];
        $prioritizedLeads = $leadsQuery->whereIn('payment_status_id', $priorityPaymentStatus)->get();
        $normalLeads = $leadsQuery->whereNotIn('payment_status_id', $priorityPaymentStatus)
            ->take($limit - $prioritizedLeads->count())->get();

        return $prioritizedLeads->concat($normalLeads);
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
            });
    }


    /**
     * @param $lead
     * @param $eligibleUsers
     * @param $rules
     * @return mixed
     */
    public function determineFinalUserId($lead, $eligibleUsers, $rules): mixed
    {
        if (count($rules) > 0) {
            $ruleUserIds = $this->getUserIdsFromRuleRecords($rules);
            $finalEligibleUserIds = array_intersect($eligibleUsers, $ruleUserIds);

            info('Rule found and users against rule are: ' . json_encode($finalEligibleUserIds));
        } else {
            $ruleUsers = $this->getRuleUsers();
            $finalEligibleUserIds = array_filter($eligibleUsers, function ($loginId) use ($ruleUsers) {
                return !in_array($loginId, $ruleUsers);
            });

            info('No rule found against this lead: ' . $lead->uuid . ' so filtering rule users: ' . json_encode($ruleUsers));
            info('Final login and available users after rule exclusion are: ' . json_encode($finalEligibleUserIds));
        }

        return count($finalEligibleUserIds) > 0 ? reset($finalEligibleUserIds) : null;
    }

    /**
     * @param $matchedRuleRecords
     * @return array|int[]
     */
    private function getUserIdsFromRuleRecords($matchedRuleRecords): array
    {
        $leadSourceUsers = $matchedRuleRecords->first()->leadSourceUsers;
        return str_contains($leadSourceUsers, ',')
            ? array_map('intval', explode(',', $leadSourceUsers))
            : [(int) $leadSourceUsers];
    }

    /**
     * @return mixed
     */
    private function getRuleUsers(): mixed
    {
        return Rule::where('is_active', 1)
        ->with('leadSources')->get()
        ->pluck('leadSources.user_id')->flatten()->unique()
        ->toArray();
    }

    /**
     * @param $lead
     * @param $userId
     * @param $tier
     * @return void
     */
    public function processLeadAssignmentAndSendEmail($lead, $userId, $tier): void
    {
        info('About to assign car lead: ' . $lead->uuid . ' to user with id: ' . $userId);

        $carQuote = $this->assignLeadToUserAndGetQuote($lead, $userId, $tier);

        info('advisor and tier assignment done for : '.$carQuote->uuid.' to user with id : '.$userId.' and tier name : '.$tier->name);

        $this->updateCarLeadDetailRecord($lead->id);

        info('updating user record in lead allocation table with count increment userId: '.$userId);

        $this->updateLeadAllocationOnCarAutoAssignment($userId);

        $emailData = $this->buildEmailDataForLMSIntroEmail($userId, $carQuote);

        $this->sendIntroEmailForLeadAssignment($carQuote->code, $emailData);

        info('Completed assignment of lead and lead count update is done for quote: ' . $carQuote->code);
    }

    /**
     * @param $lead
     * @param $userId
     * @param $tier
     * @return mixed
     */
    private function assignLeadToUserAndGetQuote($lead, $userId, $tier): mixed
    {
        $carQuote = CarQuote::findOrFail($lead->id);

        $carQuote->advisor_id = $userId;
        $carQuote->tier_id = $tier->id;
        $carQuote->cost_per_lead = $tier->cost_per_lead;
        $carQuote->auto_assigned = true;

        if ($carQuote->quote_batch_id === null) {
            $quoteBatch = QuoteBatches::latest()->first();
            info('About to assign quote batch with id: ' . $quoteBatch->id . ' and name: ' . $quoteBatch->name . ' to quote: ' . $lead->uuid);
            $carQuote->quote_batch_id = $quoteBatch->id;
        } else {
            info('Quote batch currently attached to quote: ' . $carQuote->uuid . ' and quote id is: ' . $carQuote->quote_batch_id);
        }

        $carQuote->save();

        info('Advisor and tier assignment done for: ' . $lead->uuid . ' to user with id: ' . $userId . ' and tier id: ' . $tier->name);

        return $carQuote;
    }

    /**
     * @param $leadId
     * @return void
     */
    public function updateCarLeadDetailRecord($leadId): void
    {
        info('---- Inside updateCarLeadDetailRecord');

        $carQuoteDetail = CarQuoteRequestDetail::where('car_quote_request_id', $leadId)->first();

        if ($carQuoteDetail) {
            $this->updateExistingCarQuoteDetail($carQuoteDetail);
        } else {
            $this->createNewCarQuoteDetail($leadId);
        }
    }

    /**
     * @param $carQuoteDetail
     * @return void
     */
    private function updateExistingCarQuoteDetail($carQuoteDetail): void
    {
        $carQuoteDetail->advisor_assigned_date = now();
        $carQuoteDetail->advisor_assigned_by_id = auth()->id();
        $carQuoteDetail->save();

        info('---- updateCarLeadDetailRecord - update done for advisor data and by id');
    }

    /**
     * @param $leadId
     * @return void
     */
    private function createNewCarQuoteDetail($leadId): void
    {
        CarQuoteRequestDetail::create([
            'car_quote_request_id' => $leadId,
            'advisor_assigned_date' => now(),
            'advisor_assigned_by_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        info('---- updateCarLeadDetailRecord - record not found, creating new entry');
    }

    /**
     * @param $userId
     * @return void
     */
    public function updateLeadAllocationOnCarAutoAssignment($userId): void
    {
        $leadAllocation = LeadAllocation::where('user_id', $userId)->first();

        if ($leadAllocation) {
            $this->updateLeadAllocationCounts($userId, $leadAllocation);
        }

        info('Count updated for user Id: ' . $userId . ', total count = ' . $leadAllocation->allocation_count . ' and auto count = ' . $leadAllocation->auto_assignment_count);
    }


    /**
     * @param $userId
     * @param $carQuote
     * @return object
     */
    private function buildEmailDataForLMSIntroEmail($userId, $carQuote): object
    {
        $user = User::findOrFail($userId);
        $documentUrl = $this->getLMSIntroEmailAttachmentUrl();

        return (object) [
            'customerEmail' => $carQuote->email,
            'documentUrl' => [$documentUrl], // Replace with a generic URL once document upload section is done
            'clientFullName' => $carQuote->first_name . ' ' . $carQuote->last_name,
            'advisorName' => $user->name,
            'landLine' => $user->landline_no,
            'mobilePhone' => $user->mobile_no,
            'advisorEmail' => $user->email,
            'carQuoteId' => $carQuote->code,
            'quoteLink' => config('constants.ECOM_CAR_INSURANCE_QUOTE_URL') . $carQuote->uuid,
        ];
    }

    /**
     * @param $emailData
     * @return void
     */
    private function sendIntroEmailForLeadAssignment($emailData): void
    {
        $emailTemplateId = $this->getLMSIntroEmailTemplateId();

        IntroEmailJob::dispatch(quoteTypeCode::Car, $emailTemplateId, $emailData, 'send-lms-intro-email');
    }

    /**
     * @return string
     */
    private function getLMSIntroEmailAttachmentUrl(): string
    {
        return $this->getAppStorageValueByKey(ApplicationStorageEnums::LMS_INTRO_EMAIL_ATTACHMENT_URL);
    }

    /**
     * @return int
     */
    private function getLMSIntroEmailTemplateId(): int
    {
        return (int) $this->getAppStorageValueByKey('LMS_INTRO_EMAIL_TEMPLATE_ID');
    }

    /**
     * @param $lead
     * @param $tier
     * @return void
     */
    public function updateLeadTier($lead, $tier): void
    {
        info('login users not found for selected lead so will try to assign only tier');

        $carQuote = CarQuote::where('id', $lead->id)->first();

        if ($carQuote->tier_id == null) {
            $carQuote->tier_id = $tier->id;
            // Update lead as deferred to snooze assignment while available can be assigned
            $carQuote->deferred = 1;
            $carQuote->deferred_at = now();
            $carQuote->save();
            info('Tier with name : '.$tier->name.' and id : '.$tier->id.' is assigned to car lead with uuid : '.$carQuote->uuid);
        } else {
            info('Tier ('.$tier->name.')is already assigned against car lead with uuid : '.$carQuote->uuid);
        }
    }

}
