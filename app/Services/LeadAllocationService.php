<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\CarTypeOfInsuranceIdEnum;
use App\Enums\DaysNameEnum;
use App\Enums\HealthTeamType;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\RuleTypeEnum;
use App\Enums\TeamNameEnum;
use App\Jobs\GetQuotePlansJob;
use App\Jobs\IntroEmailJob;
use App\Mail\HealthAssignmentIssueEmail;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\CommercialKeyword;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\LeadAllocation;
use App\Models\LeadSource;
use App\Models\QuoteBatches;
use App\Models\Rule;
use App\Models\RuleDetail;
use App\Models\Team;
use App\Models\Tier;
use App\Models\TierUser;
use App\Models\User;
use App\Traits\GetUserTreeTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class LeadAllocationService extends BaseService
{
    use GetUserTreeTrait;

    protected $emailDataService;
    protected $sendEmailCustomerService;

    public function __construct(EmailDataService $emailDataService, SendEmailCustomerService $sendEmailCustomerService)
    {
        $this->emailDataService = $emailDataService;
        $this->sendEmailCustomerService = $sendEmailCustomerService;
    }

    public function getGridData()
    {
        try {
            $query = LeadAllocation::select([
                'lead_allocation.id as id',
                'lead_allocation.user_id as userId',
                DB::RAW('(lead_allocation.manual_assignment_count  + lead_allocation.auto_assignment_count) as allocation_count'),
                'lead_allocation.max_capacity',
                'lead_allocation.reset_cap',
                'u.status as is_available',
                'lead_allocation.reset_cap',
                'lead_allocation.last_allocated',
                't.name as teamName',
                'u.name as userName',
                'lead_allocation.buy_lead_max_capacity as BLMaxCapacity',
                'lead_allocation.buy_lead_allocation_count as BLAllocationCount',
                'lead_allocation.buy_lead_status as BLStatus',
                'lead_allocation.normal_allocation_enabled as normalAllocationEnabled',
                'lead_allocation.buy_lead_reset_capacity as blResetCap',
            ])
                ->join('users as u', 'lead_allocation.user_id', '=', 'u.id')
                ->join('user_team as ut', 'ut.user_id', '=', 'u.id')
                ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'u.id')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->leftJoin('teams as t', 'ut.team_id', '=', 't.id')
                ->groupBy('u.name', 'u.id', 'lead_allocation.id')
                ->whereIn('t.name', [TeamNameEnum::EBP, TeamNameEnum::RM_NB, TeamNameEnum::RM_SPEED])
                ->where('lead_allocation.quote_type_id', QuoteTypes::HEALTH->id())
                ->where('u.is_active', true)
                ->whereIn('r.name', [RolesEnum::EBPAdvisor, RolesEnum::RMAdvisor]);

            if (! auth()->user()->hasRole(RolesEnum::SuperManagerLeadAllocation)) {
                $query = $query->where('u.manager_id', auth()->user()->id);
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function createLeadAllocationRecord($userId, $allocationRequest = null)
    {
        $isAllocation = LeadAllocation::where('user_id', $userId);
        if (! empty($allocationRequest->quoteTypeId)) {
            $isAllocation = $isAllocation->where('quote_type_id', $allocationRequest->quoteTypeId);
        }

        $isAllocation = $isAllocation->first();
        if (! empty($isAllocation)) {
            info('User is already allocated');

            return false;
        }
        try {
            $leadAllocation = new LeadAllocation;
            $leadAllocation->user_id = $userId;
            $leadAllocation->allocation_count = 0;
            $leadAllocation->last_allocated = now()->timestamp;
            $leadAllocation->max_capacity = $allocationRequest->maxCapacity ?? 0;
            $leadAllocation->quote_type_id = $allocationRequest->quoteTypeId ?? null;
            $leadAllocation->is_available = false;
            $leadAllocation->save();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function updateUserAllocationRecord($userId, $allocationCount, $maxCapacity, $isAvailable, $quoteTypeId = null)
    {
        try {
            $leadAllocation = LeadAllocation::where('user_id', $userId);
            if (! empty($quoteTypeId)) {
                $leadAllocation = $leadAllocation->where('quote_type_id', $quoteTypeId);
            }
            $leadAllocation = $leadAllocation->first();
            if (! $leadAllocation) {
                return false;
            }
            if (isset($allocationCount)) {
                $leadAllocation->allocation_count = $allocationCount;
            }
            if (isset($max_capacity)) {
                $leadAllocation->max_capacity = $maxCapacity;
            }
            if (isset($isAvailable)) {
                $leadAllocation->is_available = $isAvailable;
            }
            if (isset($quoteTypeId)) {
                $leadAllocation->quote_type_id = $quoteTypeId;
            }

            $leadAllocation->save();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function getHealthUnallocatedLeads()
    {
        try {
            $startDate = $this->getAppStorageValueByKey('LEAD_ALLOCATION_START_DATE_FOR_LEADS');
            $endDate = now();

            info('Health Unallocated Leads from date: '.$startDate.' to date: '.$endDate);

            $unAllocatedLeads = HealthQuote::select('health_quote_request.*')
                ->join('quote_status', 'quote_status.id', '=', 'health_quote_request.quote_status_id')
                ->where('quote_status.id', QuoteStatusEnum::Qualified)
                ->whereNotNull('health_quote_request.price_starting_from')
                ->where('health_quote_request.is_error_email_sent', false)
                ->whereNull('health_quote_request.advisor_id')
                ->whereBetween('health_quote_request.created_at', [$startDate, $endDate])
                ->skip(0)
                ->take(20)
                ->get();

            return $unAllocatedLeads;
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }
    }

    public function assignLead($lead, $advisorId, $isManualAssignment)
    {
        info('assignLead -- started with lead : '.$lead->uuid.' , advisorId : '.$advisorId.' , isManualAssignment : '.$isManualAssignment);
        if ($this->checkIfAdvisorCanTakeLead($advisorId)) {
            if ($lead->advisor_id != null) {
                $this->removeLeadAllocationForOldAdvisor($lead);
            }
            info('Assigning lead '.$lead->uuid.' to advisor '.$advisorId);
            try {
                DB::beginTransaction();
                if ($isManualAssignment && $lead->advisor_id != null && $lead->quote_status_id != QuoteStatusEnum::Quoted) {
                    info('Manual Lead and Advisor Null Check '.$lead->uuid);
                    $lead->quote_status_id = QuoteStatusEnum::Qualified;
                }

                if (str_starts_with($lead->code, 'CAR-')) {
                    $lead->auto_assigned = $isManualAssignment ? false : true;
                }
                $lead->assignment_type = $lead->advisor == null ? AssignmentTypeEnum::MANUAL_ASSIGNED : AssignmentTypeEnum::MANUAL_REASSIGNED;
                $lead->advisor_id = $advisorId;

                $lead->save();
                info('Lead Id '.$lead->uuid.' assigned to advisor '.$advisorId);
                if ($lead->source != LeadSourceEnum::REFERRAL) {
                    $this->updateLeadAllocationRecord($advisorId, $isManualAssignment);
                }
                $this->updateLeadDetailRecord($lead->id, $lead->uuid);
                DB::commit();

                Haystack::build()
                    ->addJob(new GetQuotePlansJob($lead))
                    ->then(function () use ($lead) {
                        if (
                            in_array($lead->health_team_type, [HealthTeamType::EBP, HealthTeamType::RM_NB, HealthTeamType::RM_SPEED])
                            && $lead->quote_status_id == QuoteStatusEnum::Qualified
                        ) {
                            IntroEmailJob::dispatch(quoteTypeCode::Health, 'Capi', $lead->uuid, 'send-rm-intro-email')->delay(now()->addSeconds(15));
                        }
                    })->dispatch();

                return true;
            } catch (\Exception $e) {
                Log::error($e->getMessage());
                DB::rollback();
            }
        } else {
            return false;
        }
    }

    public function autoAssignment($advisorId, $lead)
    {
        $lead->advisor_id = $advisorId;
        $lead->save();

        return true;
    }

    public function manualAssignment($advisorId, $lead)
    {
        if ($this->checkIfAdvisorCanTakeLead($advisorId)) {
            if ($lead->advisor_id != null) {
                $this->removeLeadAllocationForOldAdvisor($lead);
            }
            if ($lead->advisor_id != null) {
                $lead->quote_status_id = QuoteStatusEnum::Qualified;
            }
            $lead->advisor_id = $advisorId;
            $lead->save();

            return true;
        } else {
            return false;
        }
    }

    public function updateLeadDetailRecord($leadId, $leadUId)
    {
        info('updateLeadDetailRecord -- started for lead UUID: '.$leadUId);
        $leadDetail = HealthQuoteRequestDetail::where('health_quote_request_id', $leadId)->first();
        if ($leadDetail) {
            $leadDetail->advisor_assigned_date = now();
            $leadDetail->advisor_assigned_by_id = auth()->id();
            $leadDetail->save();
        }
        info('updateLeadDetailRecord -- completed for lead uuid: '.$leadUId);
    }

    public function removeLeadAllocationForOldAdvisor($lead)
    {
        try {
            DB::beginTransaction();
            info('removeLeadAllocationForOldAdvisor -- started');
            info('Removing lead allocation record for lead id: '.$lead->id.' and advisor id: '.$lead->advisor_id);
            $leadDetail = HealthQuoteRequestDetail::where('health_quote_request_id', $lead->id)->first();
            if ($leadDetail) {
                if ($leadDetail->advisor_assigned_date != null) {
                    if (Carbon::parse($leadDetail->advisor_assigned_date)->startOfDay() == now()->startOfDay()) {
                        LeadAllocation::where('user_id', $lead->advisor_id)->where('allocation_count', '>', 0)->decrement('allocation_count', 1);
                        info('Lead allocation count decremented for advisor id: '.$lead->advisor_id);
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function checkIfAdvisorCanTakeLead($advisorId)
    {
        try {
            $advisor = User::where('id', $advisorId)->first();

            $byPassUsersForAssignment = $this->getAppStorageValueByKey(ApplicationStorageEnums::HEALTH_MANUAL_ASSIGNMENT_USER_BYPASS);

            if (in_array($advisor->email, explode(',', $byPassUsersForAssignment))) {
                return true;
            }

            info('checkIfAdvisorCanTakeLead -- started');
            $leadAllocation = LeadAllocation::where('user_id', $advisorId)->first();
            if ($leadAllocation != null) {
                if ($leadAllocation->is_available == 0) {
                    info('Advisor '.$advisorId.' cannot take lead while he/she is not available');

                    return false;
                }
                if ($leadAllocation->max_capacity == -1 || $leadAllocation->allocation_count < $leadAllocation->max_capacity) {
                    info('Advisor '.$advisorId.' can take lead');

                    return true;
                }
                if ($leadAllocation->max_capacity == $leadAllocation->allocation_count && $leadAllocation->max_capacity != -1) {
                    info('Advisor '.$advisorId.' cannot take lead. Max capacity reached');

                    return false;
                }
            } else {
                info('Advisor '.$advisorId.' has no allocation record');

                return false;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function updateLeadAllocationRecord($userId, $isManualAssignment)
    {
        try {
            DB::beginTransaction();
            info('updateLeadAllocationRecord -- started');
            $leadAllocation = LeadAllocation::where('user_id', $userId)->first();
            info('Max capacity for user '.$userId.' is '.$leadAllocation->max_capacity.' and allocation count is '.$leadAllocation->allocation_count);
            $leadAllocation->allocation_count += 1;
            if ($isManualAssignment) {
                $leadAllocation->manual_assignment_count = $leadAllocation->manual_assignment_count + 1;
            } else {
                $leadAllocation->auto_assignment_count = $leadAllocation->auto_assignment_count + 1;
            }
            $leadAllocation->last_allocated = now()->timestamp;
            $leadAllocation->save();
            DB::commit();
            info('Lead allocation record for user '.$userId.' updated. Current allocation count is '.$leadAllocation->allocation_count);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function getHealthUserSubTeamName($userId)
    {
        try {
            info('getHealthUserSubTeamName -- started');
            $user = User::where('id', $userId)->first();
            if ($user) {
                if ($user->sub_team_id != null) {
                    info('User '.$user->name.' has sub-team '.$user->sub_team_id);
                    $userSubTeam = Team::where('id', $user->sub_team_id)->first();
                    info('User '.$user->name.' belongs to sub team '.$userSubTeam->name);

                    return strtolower($userSubTeam->name);
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function setAdvisorsToUnavailable()
    {
        try {
            DB::beginTransaction();
            info('setAdvisorsToUnavailable -- started');

            $dateTimeNow = now()->toTimeString();

            info('Current time before unavailable is '.$dateTimeNow);

            $currentDayUsers = LeadAllocation::join('users', 'users.id', 'lead_allocation.user_id')
                ->where('allocation_count', '>', 1)->get();

            foreach ($currentDayUsers as $currentDayUser) {
                info(' Current Time is : '.now().' user : '.$currentDayUser->name.' allocation_count :'.$currentDayUser->allocation_count.' , manual_allocation : '.$currentDayUser->manual_allocation.' , auto_allocation : '.$currentDayUser->auto_allocation);
            }

            LeadAllocation::whereNotNull('is_available')->update([
                'is_available' => 0,
                'allocation_count' => 0,
                'manual_assignment_count' => 0,
                'auto_assignment_count' => 0,
            ]);

            info('Advisors are now unavailable and allocation count is set to 0');
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function updateUserMaxCapacity()
    {
        $users = User::join('tier_users as tu', 'tu.user_id', 'users.id')
            ->join('tiers as t', 't.id', 'tu.tier_id')
            ->leftJoin('quad_users as qu', 'qu.user_id', 'users.id')
            ->leftJoin('quadrants as q', 'q.id', 'qu.quad_id')
            ->join('lead_allocation as la', 'la.user_id', 'users.id')
            ->activeUser()
            ->groupBy('users.name', 'users.id', 'la.id')
            ->select(
                'users.id as userId',
                'users.name as userName',
                'users.email as userEmail',
                DB::RAW('GROUP_CONCAT(DISTINCT (t.name)) AS tiers'),
                DB::RAW('GROUP_CONCAT(DISTINCT (q.name)) AS quads'),
                'la.allocation_count as allocationCount',
                'la.last_allocated as lastAllocation',
                'la.max_capacity as maxCapacity',
                'la.is_available as isAvailable',
                'users.last_login as lastLogin',
                'la.id as id'
            )->get();
        foreach ($users as $user) {
            $leadAllocationRecord = LeadAllocation::where('user_id', $user->userId)->first();

            if ($leadAllocationRecord) {
                // updating the max capacity if the quad is 1 then we should reset all the user to 4 otherwise everything should be 5
                $leadAllocationRecord->max_capacity = str_contains($user->quads, '1') ? 4 : 5;

                $leadAllocationRecord->updated_at = now();
                $leadAllocationRecord->save();
            }
        }
    }

    public function carLeadAllocationSwitchStatus()
    {
        return $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_MASTER_SWITCH') ? $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH') : 0;
    }

    public function leadAllocationSwitchStatus()
    {
        return $this->getAppStorageValueByKey('LEAD_ALLOCATION_JOB_SWITCH') == '1';
    }

    public function getLeadAllocationRecordByUserId($userId, $quoteTypeId = null)
    {
        try {
            $leadAllocation = LeadAllocation::where('user_id', $userId);
            if (! empty($quoteTypeId)) {
                $leadAllocation = $leadAllocation->where('quote_type_id', $quoteTypeId);
            }

            return $leadAllocation->first();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function getNextAssignableUserId($lead)
    {
        try {
            $availableUserId = LeadAllocation::join('users as u', 'lead_allocation.user_id', '=', 'u.id')
                ->join('teams as t', 't.id', '=', 'u.sub_team_id')
                ->where('lead_allocation.is_available', 1)
                ->where(function ($query) {
                    $query->whereRaw('lead_allocation.allocation_count < lead_allocation.max_capacity')
                        ->orWhere('lead_allocation.max_capacity', '=', -1);
                })
                ->where(strtolower('t.name'), strtolower($lead->health_team_type))
                ->orderBy('lead_allocation.last_allocated', 'asc');

            return $availableUserId->first();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function getAvailableAdvisors()
    {
        try {
            $availableAdvisors = LeadAllocation::join('users as u', 'lead_allocation.user_id', '=', 'u.id')
                ->join('teams as t', 't.id', '=', 'u.sub_team_id')
                ->where('lead_allocation.is_available', 1)
                ->where(function ($query) {
                    $query->whereRaw('lead_allocation.allocation_count < lead_allocation.max_capacity')
                        ->orWhere('lead_allocation.max_capacity', '=', -1);
                })
                ->orderBy('lead_allocation.last_allocated', 'asc')
                ->select('u.id', 'u.name', 'u.email', 'u.sub_team_id', 't.name as sub_team_name', 'lead_allocation.allocation_count', 'lead_allocation.max_capacity', 'lead_allocation.last_allocated');

            return $availableAdvisors->get();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function processCarLeads()
    {
        try {
            $currentIterationTime = now();

            info('----------------------- CAR LEAD ALLOCATION STARTED AT '.$currentIterationTime.' -----------------------');

            $carUnAllocatedLead = $this->getCarUnallocatedLeads();

            info(count($carUnAllocatedLead).' unassigned car leads found.');

            foreach ($carUnAllocatedLead as $carLead) {
                info('----------------------- CAR LEAD ALLOCATION STARTED FOR LEAD '.$carLead->uuid.' -----------------------');

                info('trying to check tier against the current lead : '.$carLead->code);

                // we will find tier as per the value of the lead and if already assigned then we will simply find the tier,
                // since it might be here for reassignment of advisor
                $selectedTier = $carLead->tier_id == null ? $this->getTierForValue($carLead) : Tier::where('id', $carLead->tier_id)->first();

                if ($selectedTier) {
                    info('Found tier '.$selectedTier->name.' against car lead : '.$carLead->code);

                    $loginAndAvailableUserIds = $this->getTierUsersWithLeadAllocationRecord($selectedTier->id); // now we will try to find users based on selected tier

                    if ($carLead->source == LeadSourceEnum::REVIVAL_REPLIED) {
                        info('Lead source is '.LeadSourceEnum::REVIVAL_REPLIED.' for uuid : '.$carLead->uuid);
                        $loginAndAvailableUserIds = $this->getUsersForRevivalReplied($loginAndAvailableUserIds);
                    }

                    info('Available and Login users against selected tier are : '.json_encode($loginAndAvailableUserIds));

                    $matchedRuleRecords = $this->getRulesByLeadSource($carLead);

                    info('count of matched records =====******======');
                    info(count($matchedRuleRecords));

                    if (count($matchedRuleRecords) > 0) {
                        $ruleUserIds = [];
                        // getting user Ids from rules
                        if (str_contains($matchedRuleRecords?->first()?->leadSourceUsers, ',')) {
                            $ruleUserIds = array_map('intval', explode(',', $matchedRuleRecords->first()->leadSourceUsers));
                        } else {
                            $ruleUserIds[] = (int) $matchedRuleRecords->first()->leadSourceUsers;
                        }
                        info('Rule found and users against rule are '.json_encode($ruleUserIds));

                        $finalAvailableAndLoginAdvisorIds = array_intersect($loginAndAvailableUserIds, $ruleUserIds);

                        info('After intersection of users and rules, output is : '.json_encode($finalAvailableAndLoginAdvisorIds));
                    } else {
                        $ruleUsers = RuleDetail::join('rules', 'rules.id', 'rule_details.rule_id')
                            ->join('rule_users', 'rule_users.rule_id', 'rules.id')
                            ->where('rules.is_active', 1)
                            ->distinct()
                            ->pluck('rule_users.user_id')
                            ->toArray();

                        info('Plucked users ====> ');
                        info(json_encode($ruleUsers));

                        info('No rule found against this lead : '.$carLead->uuid.' so filtering rule users : '.json_encode($ruleUsers));
                        $finalAvailableAndLoginAdvisorIds = [];

                        foreach ($loginAndAvailableUserIds as $loginId) {
                            if (! in_array($loginId, $ruleUsers)) {
                                array_push($finalAvailableAndLoginAdvisorIds, $loginId);
                            }
                        }
                        info('final login and available users after rule exclusion are : '.json_encode($finalAvailableAndLoginAdvisorIds));
                    }
                    info('common users at this point are '.json_encode($finalAvailableAndLoginAdvisorIds));
                    $userId = null;
                    if (count($finalAvailableAndLoginAdvisorIds) > 0) {
                        $userId = reset($finalAvailableAndLoginAdvisorIds);
                    }
                    if ($userId) {
                        try {
                            DB::beginTransaction();
                            info('About to assign car lead : '.$carLead->uuid.' to user with id : '.$userId);

                            $carQuote = CarQuote::where('id', $carLead->id)->first();
                            $carQuote->advisor_id = $userId;
                            $carQuote->tier_id = $selectedTier->id;
                            $carQuote->cost_per_lead = $selectedTier->cost_per_lead;
                            $carQuote->auto_assigned = true;
                            if ($carQuote->quote_batch_id == null) {
                                $quoteBatch = QuoteBatches::latest()->first();
                                info('About to assign quote batch with id : '.$quoteBatch->id.' and with name : '.$quoteBatch->name.' to quote : '.$carLead->uuid);
                                $carQuote->quote_batch_id = $quoteBatch->id;
                            } else {
                                info('quote batch currently attached to quote : '.$carQuote->uuid.' and quote id is : '.$carQuote->quote_batch_id);
                            }
                            $carQuote->save();

                            info('advisor and tier assignment done for : '.$carLead->uuid.' to user with id : '.$userId.' and tier id : '.$selectedTier->name);
                            $this->updateCarLeadDetailRecord($carLead->id); // updating detail table about assignment

                            info('updating user record in lead allocation table with count increment userId: '.$userId);
                            $this->updateLeadAllocationOnCarAutoAssignment($userId); // updating lead allocation record for user

                            $emailData = $this->buildEmailDateForLMSIntroEmail($userId, $carQuote); // create email body for intro email

                            $emailTemplateId = (int) $this->getAppStorageValueByKey('LMS_INTRO_EMAIL_TEMPLATE_ID'); // template id for LMS intro email

                            IntroEmailJob::dispatch(quoteTypeCode::Car, $emailTemplateId, $emailData, 'send-lms-intro-email'); // sending email using email body and template id
                            info('completed assignment of lead and lead count update is done for quote : '.$carQuote->code);
                            DB::commit();
                        } catch (\Throwable $th) {
                            Log::error($th->message);
                            DB::rollBack();
                        }
                    } else {
                        info('login users not found for selected lead so will try to assign only tier');

                        $carQuote = CarQuote::where('id', $carLead->id)->first();

                        if ($carQuote->tier_id == null) {
                            $carQuote->tier_id = $selectedTier->id;
                            $carQuote->save();
                            info('Tier with name : '.$selectedTier->name.' and id : '.$selectedTier->id.' is assigned to car lead with uuid : '.$carQuote->uuid);
                        } else {
                            info('Tier ('.$selectedTier->name.')is already assigned against car lead with uuid : '.$carQuote->uuid);
                        }
                    }
                }
                info('----------------------- CAR LEAD ALLOCATION ENDED FOR LEAD '.$carLead->uuid.' -----------------------');
            }
            info('----------------------- CAR LEAD ALLOCATION ENDED AT '.$currentIterationTime.' -----------------------');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function buildEmailDateForLMSIntroEmail($userId, $carQuote)
    {
        $user = User::where('id', $userId)->first();
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

    public function updateLeadAllocationOnCarAutoAssignment($userId)
    {
        $leadAllocationRecord = LeadAllocation::where('user_id', $userId)->first();
        info('Count before update for user Id : '.$userId.' , total count = '.$leadAllocationRecord->allocation_count.' and auto count = '.$leadAllocationRecord->auto_assignment_count);
        DB::statement("UPDATE lead_allocation SET allocation_count = allocation_count + 1 , auto_assignment_count = auto_assignment_count + 1 ,
             last_allocated = '".Carbon::now()->timestamp."' , updated_at = now() where user_id = ".$userId);
    }

    public function updateCarLeadDetailRecord($leadId)
    {
        info('---- Inside updateCarLeadDetailRecord - leadId : '.$leadId);
        $upsertRecord = CarQuoteRequestDetail::updateOrCreate(
            ['car_quote_request_id' => $leadId],
            [
                'advisor_assigned_date' => now(),
                'advisor_assigned_by_id' => auth()->id(),
            ]
        );
        info('---- updateCarLeadDetailRecord - updateOrCreate done for advisor data and by id - leadId : '.$leadId.' - CarQuoteRequestDetail - created: '.$upsertRecord->wasRecentlyCreated);
    }

    public function getCarUnallocatedLeads()
    {
        $from = now()->subWeeks(2)->startOfDay();

        $to = now()->subMinutes(2)->toDateTimeString();

        $carLeadPickupLimit = $this->getAppStorageValueByKey('CAR_LEAD_PICKUP_LIMIT');

        $isFIFO = $this->getAppStorageValueByKey('CAR_LEAD_PICKUP_FIFO');

        info('Car leads fetch start date is :'.$from.'  and end datetime is : '.$to.' and pickup limit is : '.$carLeadPickupLimit.' and Pickup direction FIFO is : '.$isFIFO);

        return CarQuote::whereNull('advisor_id')
            ->where('is_renewal_tier_email_sent', 0) // this check make sure that Tier R leads are excluded bcz we only send email for Tier R and not assign advisor
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate]) // excluding all Fake leads
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD]) // leads created from IMCRM are excluded because they get assigned to the creator right away
            ->orderBy('created_at', $isFIFO ? 'asc' : 'desc') // pickup order
            ->skip(0)->take($carLeadPickupLimit)->get();
    }

    public function getRulesByLeadSource($carLead)
    {
        $commercialKeywords = CommercialKeyword::select('id', 'name')->get();

        $commercialCarMake = CarMake::where('id', $carLead->car_make_id)
            ->where('is_commercial', true)
            ->select('id')
            ->first();

        $commercialCarModel = CarModel::where('id', $carLead->car_model_id)
            ->where('is_commercial', true)
            ->select('id')
            ->first();

        foreach ($commercialKeywords as $keyword) {
            if (
                str_contains(
                    strtolower(trim($carLead->full_name)),
                    strtolower(trim($keyword->name))
                )
                ||
                ($commercialCarMake && $commercialCarModel)
            ) {
                $records = $this->getCommercialRule();

                info('commercial records: '.json_encode($records->get()));

                return $records->get();
            }
        }

        info('keyword not found and vehicle is not commercial as well, so checking for normal rules');

        $records = LeadSource::leftJoin('rule_details', 'rule_details.lead_source_id', 'lead_sources.id')
            ->join('rules', 'rules.id', 'rule_details.rule_id')
            ->join('rule_users', 'rule_users.rule_id', 'rules.id')
            ->join('users', 'users.id', 'rule_users.user_id')
            ->where('lead_sources.name', $carLead->source)
            ->where('rules.is_active', 1)
            ->where('lead_sources.is_applicable_for_rules', 1)
            ->groupBy('rule_details.lead_source_id')
            ->select(
                'lead_sources.name AS leadSourceName',
                'lead_sources.id AS leadSourceId',
                DB::raw('group_concat(rule_users.user_id) AS leadSourceUsers')
            );

        info('lead source records: '.json_encode($records->get()));

        return $records->get();
    }

    public function checkIfLeadIsRenewal($lead)
    {
        if ($lead->tier_id != null) {
            return false; // we will not check for renewal if the tier_id is already assign because it will be redundant
        }
        /**
         * Following are the criteria to match and find a renewal
         * Search for a lead where source is Renewal_upload
         * Search for a lead where policy expiry date should be in between last 30 days and future 90 days
         * Search for a lead where email OR phone number (last 7 digits) matches
         * Search for a lead where car make and model id is same as what we have from current request.
         *
         * On adding all above criteria's we find a lead then its a renewal otherwise not
         */
        $dateFrom = Carbon::now()->addDays(-30);
        $dateTo = Carbon::now()->addDays(90);

        info('car lead allocation renewal date from : '.$dateFrom.' and date to : '.$dateTo);

        $renewalQuotesCount = CarQuote::select('id')->where('source', LeadSourceEnum::RENEWAL_UPLOAD)
            ->whereBetween('previous_policy_expiry_date', [$dateFrom, $dateTo])
            ->where(function ($query) use ($lead) {
                $query->where('email', $lead->email)
                    ->orWhere('mobile_no', 'like', '%'.substr($lead->mobile_no, -7));
            })
            ->where('car_make_id', $lead->car_make_id)
            ->where('car_model_id', $lead->car_model_id)->count();

        if ($renewalQuotesCount > 0) {
            info('car lead allocation found '.$renewalQuotesCount.' renewal quote(s) for car quote with uuid : '.$lead->uuid);

            return true;
        } else {
            info('car lead allocation did-not found a renewal for uuid : '.$lead->uuid);

            return false;
        }
    }

    public function getTierForValue($carLead)
    {
        info('Started searching tier for car lead : '.json_encode($carLead->code));

        $tiers = Tier::where('is_active', 1); // getting all active tiers so that we can search among them.

        info('car ecommerce info is : '.json_encode($carLead->is_ecommerce));

        if ($carLead->car_type_insurance_id == CarTypeOfInsuranceIdEnum::ThirdPartyOnly) {
            info('since the car type of insurance is : '.$carLead->car_type_insurance_id.' , so select tier which can handle TPL leads ');

            $tiers->where('can_handle_tpl', 1); // filter on tiers to get the tier which can handle TPL

            $tiers->where('can_handle_ecommerce', $carLead->is_ecommerce); // in case if ecommerce check is also applicable
        }

        // checking all the possible null/empty values from request
        if (($carLead->car_value == null || $carLead->car_value <= 0 || $carLead->car_value == '?' || $carLead->car_value == '')
            && $carLead->car_type_insurance_id == CarTypeOfInsuranceIdEnum::Comprehensive
        ) {
            info('Car value is : '.$carLead->car_value.' , so select tier which can handle null value');

            $tiers->where('can_handle_null_value', 1); // filter on tier to get the tier which can handle null value leads.
        }

        // if car value is > zero and its comprehensive then a value comparison is must
        if ($carLead->car_value > 0 && $carLead->car_type_insurance_id == CarTypeOfInsuranceIdEnum::Comprehensive) {
            $highestValueTier = Tier::where('is_active', 1)->orderBy('max_price', 'desc')->first(); // getting tier with highest max_price value

            if ($carLead->car_value > $highestValueTier->max_price) {
                info('lead '.$carLead->uuid.' have value higher then all the tiers so selecting tier '.$highestValueTier->name);

                return $highestValueTier;
            } else {
                info('filtering tiers based on the car value which is : '.$carLead->car_value);
                $tiers->where('min_price', '<=', $carLead->car_value)->where('max_price', '>=', $carLead->car_value);
            }
        }

        // in case if the source of the lead is TPL_RENEWALS
        if ($carLead->source == LeadSourceEnum::TPL_RENEWALS) {
            $tiers->where('is_tpl_renewals', 1); // filter on tier for is TPL renewal check

            $tiers->where('can_handle_ecommerce', $carLead->is_ecommerce); // in case if ecommerce check is also applicable
        }

        info('tiers query is : '.$tiers->toSql().' with binding of : '.json_encode($tiers->getBindings()));

        $tiers = $tiers->get();
        if ($tiers != null) {
            info('First tier after filtration is : '.json_encode($tiers->first()->name));

            return $tiers->first();
        }

        return null;
    }

    public function getTierUsersWithLeadAllocationRecord($tierId)
    {
        $tierUsers = TierUser::where('tier_id', $tierId)->get()->pluck('user_id'); // getting all the users against selected tier

        info('Tier users are :'.json_encode($tierUsers));

        // Following is the criteria to get users for lead allocation
        /**
         * User must be available
         * User's last login date should be from today
         * User's allocation count should be less then his max_capacity OR his max_capacity should be -1.
         */
        $query = LeadAllocation::join('users as u', 'u.id', 'lead_allocation.user_id')
            ->select('u.id', 'u.email')
            ->where('u.last_login', '>', DB::raw('DATE_ADD(CURDATE(), INTERVAL 1 SECOND)'))
            ->where('lead_allocation.is_available', 1) // user must be available
            ->where(function ($query) {
                $query->whereRaw('lead_allocation.allocation_count < lead_allocation.max_capacity')
                    ->orWhere('lead_allocation.max_capacity', '=', -1);
            })
            ->whereIn('u.id', $tierUsers)
            ->orderBy('lead_allocation.last_allocated', 'desc');

        return $query->get()->pluck('id')->toArray();
    }

    public function getAppStorageValueByKey($keyName)
    {
        $query = ApplicationStorage::select('value')
            ->where('key_name', $keyName)
            ->first();

        if (! $query) {
            return false;
        }

        return $query->value;
    }

    public function updateAppStorageValueByKey($keyName, $value)
    {
        ApplicationStorage::where('key_name', $keyName)->update(['value' => $value]);
    }

    public function updateAllocationStatusIfNeeded()
    {
        $currentDay = Carbon::parse(now())->format('l');

        $resetKeyTime = $currentDay == DaysNameEnum::SATURDAY ? 'SATURDAY_CAP_RESET_TIME' : 'NORMAL_CAP_RESET_TIME';

        $endTimeForAllocation = Carbon::parse($this->getAppStorageValueByKey($resetKeyTime))->toTimeString();

        $carLeadAllocationSwitch = $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH');

        $carLeadAllocationStartTime = $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_START_TIME');
        info('updateAllocationStatusIfNeeded -- current time is : '.now()->toTimeString().' , endTime is : '.$endTimeForAllocation.' , Switch is : '.$carLeadAllocationSwitch);

        if ($endTimeForAllocation <= now()->toTimeString() && $carLeadAllocationSwitch == 1) {
            // stopping car lead allocation if the end time for allocation is reached and allocation is still ON
            info('updateAllocationStatusIfNeeded -- Inside reset case');
            $this->updateAppStorageValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH', 0);

            // reset the max capacity for each user as the allocation is now stopped
            $this->updateUserMaxCapacity();
        }

        if ($carLeadAllocationSwitch == 0 && $carLeadAllocationStartTime <= now()->toTimeString()) {
            info('updateAllocationStatusIfNeeded -- Inside start case');
            $this->updateAppStorageValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH', 1);
        }
    }

    public function shouldCarAllocationProceed()
    {
        $shouldProcess = true;
        $masterSwitchConfigValue = (int) config('constants.CAR_LEAD_ALLOCATION_MASTER_SWITCH');
        $masterSwitchValue = $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_MASTER_SWITCH');
        $normalSwitchValue = $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH');
        if ($masterSwitchConfigValue == 0) {
            // if car lead allocation master switch is OFF then we shouldn't proceed further
            $shouldProcess = false;
            info('shouldCarAllocationProceed -- Doppler -- output is : '.json_encode($shouldProcess));

            return false;
        }

        if (! $masterSwitchValue || ! $normalSwitchValue) {
            // if car lead allocation normal switch is OFF then we shouldn't proceed further
            $shouldProcess = false;
        }

        info('shouldCarAllocationProceed -- output is : '.json_encode($shouldProcess).'config value is : '.$masterSwitchConfigValue.' and app storage value is'.$normalSwitchValue.' and '.! $masterSwitchValue);

        return $shouldProcess;
    }

    public function shouldResetUserAssignmentCountAndAvailability()
    {
        $shouldProcess = false;
        $totalResetTime = $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_TOTAL_RESET');

        info('time now is : '.now()->toTimeString().', total reset time is : '.$totalResetTime);
        if ($totalResetTime <= now()->toTimeString()) {
            info('should total reset is true');

            return true;
        }

        info('should total reset is false');

        return false;
    }

    public function assignHealthTeamBasedOnStartingPrice($healthQuote)
    {
        info('Inside assignHealthTeamBasedOnStartingPrice for quote : '.$healthQuote->uuid);

        $priceStartingFrom = $healthQuote->price_starting_from;

        $healthTeam = Team::where('allocation_threshold_enabled', true)
            ->where('min_price', '<=', $priceStartingFrom)
            ->where('max_price', '>=', $priceStartingFrom)
            ->first();

        if ($healthTeam) {
            info('assignHealthTeamBasedOnStartingPrice filtered team is : '.$healthTeam->name);
            $healthQuote->update([
                'health_team_type' => $healthTeam->name,
            ]);
        } else {
            info('assignHealthTeamBasedOnStartingPrice team not found against : '.$healthQuote->uuid);
            $healthQuote->update([
                'is_error_email_sent' => true,
            ]);
            Mail::send(new HealthAssignmentIssueEmail($healthQuote->code, $priceStartingFrom));
        }
    }

    public function shouldHealthAllocationProceed()
    {
        $masterSwitchConfigValue = (int) config('constants.HEALTH_LEAD_ALLOCATION_MASTER_SWITCH');
        if ($masterSwitchConfigValue == 0) {
            info('shouldHealthAllocationProceed -- Doppler -- output is : '.json_encode(false));

            return false;
        } else {
            info('shouldHealthAllocationProceed -- Doppler -- output is : '.json_encode(true));

            return true;
        }
    }

    public function getCommercialRule()
    {
        return Rule::join('rule_details', 'rule_details.rule_id', 'rules.id')
            ->join('rule_users', 'rule_users.rule_id', 'rules.id')
            ->join('users', 'users.id', 'rule_users.user_id')
            ->where('rule_type', RuleTypeEnum::CAR_MAKE_MODEL)
            ->where('rules.is_active', 1)
            ->groupBy('rule_details.rule_id')
            ->select(
                DB::raw('group_concat(rule_users.user_id) AS leadSourceUsers')
            );
    }

    public function getAllocationLeads($quoteTypeIds)
    {
        try {
            $query = LeadAllocation::select([
                'lead_allocation.id as id',
                'lead_allocation.user_id as userId',
                'lead_allocation.allocation_count',
                'lead_allocation.max_capacity',
                'lead_allocation.reset_cap',
                'u.status as is_available',
                'lead_allocation.reset_cap',
                'lead_allocation.updated_at',
                'lead_allocation.last_allocated',
                't.name as teamName',
                'qt.code as quote_type_code',
                'u.name as userName',
            ])
                ->join('users as u', 'lead_allocation.user_id', '=', 'u.id')
                ->join('user_team as ut', 'ut.user_id', '=', 'u.id')
                ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'u.id')
                ->join('quote_type as qt', 'lead_allocation.quote_type_id', '=', 'qt.id')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->leftJoin('teams as t', 'ut.team_id', '=', 't.id')
                ->groupBy('u.name', 'u.id', 'lead_allocation.id')
                ->where('u.is_active', true)
                ->whereIn('lead_allocation.quote_type_id', (array) $quoteTypeIds);

            $query = $query->when(! auth()->user()->hasRole(RolesEnum::SuperManagerLeadAllocation), function ($query) {
                return $query->where('u.manager_id', auth()->user()->id);
            });
            $query = $query->when(! empty(request('userIds')), function ($query) {
                return $query->whereIn('u.id', (array) request('userIds'));
            });
            $query = $query->when(! empty(request('quoteTypeIds')), function ($query) {
                return $query->whereIn('lead_allocation.quote_type_id', (array) request('quoteTypeIds'));
            });

            return $query->simplePaginate(10)->withQueryString();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function isCommercialVehicles($lead)
    {
        $isCommercial = false;
        $commercialCarModel = CarModel::where('id', $lead->car_model_id)
            ->where('is_commercial', true)
            ->count();

        if ($commercialCarModel) {
            $isCommercial = true;
        }

        $commercialKeywords = CommercialKeyword::select('id', 'name')->get();
        $commercialKeywordsCheck = in_array(strtolower(trim($lead->full_name)), array_column($commercialKeywords->toArray(), strtolower(trim('name'))));
        if ($commercialKeywordsCheck) {
            $isCommercial = true;
        }

        return $isCommercial;
    }
}
