<?php

namespace App\Services;

use App\Enums\DaysNameEnum;
use App\Enums\HealthTeamType;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Enums\TiersEnum;
use App\Events\AdvisorAssigned;
use App\Jobs\GetQuotePlansJob;
use App\Jobs\SyncSIBContactJob;
use App\Models\ApplicationStorage;
use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\LeadAllocation;
use App\Models\LeadSource;
use App\Models\RuleLeadSource;
use App\Models\Team;
use App\Models\Tier;
use App\Models\TierUser;
use App\Models\User;
use App\Traits\GetUserTreeTrait;
use App\Traits\SendSIBEmail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadAllocationService extends BaseService
{
    use SendSIBEmail, GetUserTreeTrait;

    protected $emailDataService;

    public function __construct(EmailDataService $emailDataService)
    {
        $this->emailDataService = $emailDataService;
    }

    public function getGridData()
    {
        try {
            $query = LeadAllocation::select([
                'lead_allocation.id as id',
                'lead_allocation.user_id as userId',
                'lead_allocation.allocation_count',
                'lead_allocation.max_capacity',
                'lead_allocation.is_available',
                'lead_allocation.last_allocated',
                'st.name as teamName',
                'u.name as userName',
            ])
              ->join('users as u', 'lead_allocation.user_id', '=', 'u.id')
              ->leftJoin('teams as t', 'u.team_id', '=', 't.id')
              ->leftJoin('teams as st', 'st.id', '=', 'u.sub_team_id')
              ->whereNotNull('u.sub_team_id')
              ->where('t.name', '=', quoteTypeCode::Health);

            if (! auth()->user()->hasRole(RolesEnum::SuperManagerLeadAllocation)) {
                $query = $query->where('u.manager_id', auth()->user()->id);
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function createLeadAllocationRecord($userId)
    {
        try {
            DB::beginTransaction();
            $leadAllocation = new LeadAllocation();
            $leadAllocation->user_id = $userId;
            $leadAllocation->allocation_count = 0;
            $leadAllocation->last_allocated = now()->timestamp;
            $leadAllocation->max_capacity = 0;
            $leadAllocation->is_available = false;
            $leadAllocation->save();
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function updateUserAllocationRecord($userId, $allocationCount, $maxCapacity, $isAvailable)
    {
        try {
            DB::beginTransaction();
            $leadAllocation = LeadAllocation::where('user_id', $userId)->first();
            if (! $leadAllocation) {
                DB::commit();

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
            $leadAllocation->save();
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function getUnAllocatedLeads()
    {
        try {
            $unAllocatedLeads = [];
            $to = now();
            $from = $this->getAppStorageValueByKey('LEAD_ALLOCATION_START_DATE_FOR_LEADS');
            info('from date : '.$from.' to date : '.$to);
            $unAllocatedLeads = HealthQuote::select('health_quote_request.*')
                ->join('quote_status', 'quote_status.id', '=', 'health_quote_request.quote_status_id')
                ->where('quote_status.id', QuoteStatusEnum::Qualified)
                ->whereNotNull('health_quote_request.health_team_type')
                ->whereNull('health_quote_request.advisor_id')
                ->whereBetween('health_quote_request.created_at', [$from, $to])->skip(0)->take(20)->get();

            return $unAllocatedLeads;
        } catch (\Exception $e) {
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
                $lead->advisor_id = $advisorId;
                $lead->save();
                info('Lead Id '.$lead->uuid.' assigned to advisor '.$advisorId);
                if ($lead->source != LeadSourceEnum::REFERRAL) {
                    $this->updateLeadAllocationRecord($advisorId);
                }
                $this->updateLeadDetailRecord($lead->id, $lead->uuid);
                $releaseDate = Carbon::parse('2022-10-10 11:00:00')->timestamp;
                $leadCreated = Carbon::parse($lead->created_at)->timestamp;
                if ($lead->health_team_type == HealthTeamType::EBP && $leadCreated > $releaseDate && $lead->quote_status_id == QuoteStatusEnum::Quoted) {
                    SyncSIBContactJob::dispatch($lead);
                }
                GetQuotePlansJob::dispatch($lead);
                DB::commit();

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

    public function updateLeadAllocationRecord($userId)
    {
        try {
            DB::beginTransaction();
            info('updateLeadAllocationRecord -- started');
            $leadAllocation = LeadAllocation::where('user_id', $userId)->first();
            info('Max capacity for user '.$userId.' is '.$leadAllocation->max_capacity.' and allocation count is '.$leadAllocation->allocation_count);
            $leadAllocation->allocation_count += 1;
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
            info('Current time is '.$dateTimeNow);
            $timeForUnavailability = Carbon::parse($this->getAppStorageValueByKey('LEAD_ALLOCATION_UNAVAILABILITY_TIME'))->toTimeString();
            info('Time for advisor unavailability in app storage is '.$timeForUnavailability);
            if ($dateTimeNow >= $timeForUnavailability) {
                info('Current time before unavailable is '.$dateTimeNow);
                info('Setting advisors to unavailable');
                LeadAllocation::whereNotNull('is_available')->update([
                    'is_available' => 0,
                    'allocation_count' => 0,
                ]);
                info('Advisors are now unavailable and allocation count is set to 0');
            }
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function setMaxCapAndAllocationStatus()
    {
        try {
            DB::beginTransaction();
            if (! $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_MASTER_SWITCH')) {
                info('Car lead allocation master switch is off');
                DB::commit();

                return false;
            }
            info('setMaxCapAndAllocationStatus -- started');
            $dateTimeNow = now()->toTimeString();
            $currentDay = Carbon::parse(now())->format('l');
            info('Current time is '.$dateTimeNow);

            $carLeadAllocationSwitch = $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH');
            $timeForEnd = Carbon::parse($this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_END_TIME'))->toTimeString();
            $timeForStart = Carbon::parse($this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_START_TIME'))->toTimeString();
            $saturdayResetTime = Carbon::parse($this->getAppStorageValueByKey('SATURDAY_CAP_RESET_TIME'))->toTimeString();
            $normalResetTime = Carbon::parse($this->getAppStorageValueByKey('NORMAL_CAP_RESET_TIME'))->toTimeString();
            info('carLeadSwitch : '.$carLeadAllocationSwitch.' , End Time : '.$timeForEnd.' , Start Time : '.$timeForStart.', SaturdayResetTime :'.$saturdayResetTime.', Normal ResetTime : '.$normalResetTime.' , time right now : '.$dateTimeNow);
            info('Time for advisor start in app storage is '.$timeForStart.' and end is :'.$timeForEnd.' and the switch right now is : '.$carLeadAllocationSwitch);
            if ($dateTimeNow >= $timeForStart && $carLeadAllocationSwitch == 0) {
                $this->updateAppStorageValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH', 1);
            }
            if ($dateTimeNow >= $timeForEnd && $carLeadAllocationSwitch == 1) {
                info('setMaxCapAndAllocationStatus - going to shutdown the car lead allocation switch');
                $this->updateAppStorageValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH', 0);
            }
            if ($currentDay == DaysNameEnum::SATURDAY && $dateTimeNow >= $saturdayResetTime) {
                $this->updateUserMaxCapacity();
            }
            if ($currentDay != DaysNameEnum::SUNDAY && $dateTimeNow >= $normalResetTime) {
                info('setMaxCapAndAllocationStatus - going to update normal reset cap');
                $this->updateUserMaxCapacity();
            }
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
                            ->where('users.is_active', 1)
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
        info('going to update the max cap for users : '.json_encode($users->pluck('id')));
        foreach ($users as $user) {
            info('lead allocation record is about to update for user : '.$user->userEmail);
            $leadAllocationRecord = LeadAllocation::where('user_id', $user->userId)->first();
            if ($leadAllocationRecord) {
                $leadAllocationRecord->max_capacity = str_contains($user->quads, '1') ? 4 : 5;
                $leadAllocationRecord->allocation_count = 0;
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

    public function getLeadAllocationRecordByUserId($userId)
    {
        try {
            $leadAllocation = LeadAllocation::where('user_id', $userId)->first();

            return $leadAllocation;
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
            info('----------------------- CAR LEAD ALLOCATION STARTED FOR '. $currentIterationTime . ' -----------------------');
            $from = $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_START_DATE_FOR_LEADS');
            $carUnAllocatedLead = $this->getCarUnallocatedLeads($from);
            info(count($carUnAllocatedLead).' unassigned car leads found.');
            foreach ($carUnAllocatedLead as $carLead) {
                info('----------------------- CAR LEAD ALLOCATION STARTED FOR LEAD '. $carLead->uuid . ' -----------------------');
                if ($this->checkIfLeadIsRenewal($carLead)) {
                    info('car lead allocation sending renewal email for uuid : '.$carLead->uuid);
                    if (! $carLead->is_renewal_tier_email_sent) {
                        $this->sendRenewalLeadEmail($carLead);
                        $tier = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();
                        if ($tier) {
                            info('setting tier : '.$tier->name.' against car lead : '.$carLead->uuid);
                            $carLead->tier_id = $tier->id;
                            $carLead->save();
                        } else {
                            info('tier R for sending email is not found');
                        }
                        info('Renewal Email sent for quote : '.$carLead->uuid.' and tier is update with id : '.$tier->id);
                    }
                    continue;
                }
                info('trying to check tier against the current lead : '.$carLead->code);
                $selectedTier = $this->getTierForValue($carLead);
                if ($selectedTier) {
                    info('Tier '.$selectedTier->name.' is selected against car lead : '.$carLead->code);

                    $loginAndAvailableUserIds = $this->getTierUsersWithLeadAllocationRecord($selectedTier->id);

                    info('login and available users right now are '.json_encode($loginAndAvailableUserIds));

                    $matchedRuleRecords = $this->getRulesByLeadSource($carLead->source);

                    if (count($matchedRuleRecords) > 0) {
                        $ruleUserIds = [];
                        if (str_contains($matchedRuleRecords?->first()?->leadSourceUsers, ',')) {
                            $ruleUserIds = array_map('intval', explode(',', $matchedRuleRecords->first()->leadSourceUsers));
                        } else {
                            $ruleUserIds[] = (int) $matchedRuleRecords->first()->leadSourceUsers;
                        }
                        info('Rule found and users against rule are '.json_encode($ruleUserIds));
                        $finalAvailableAndLoginAdvisorIds = array_intersect($loginAndAvailableUserIds, $ruleUserIds);
                        info('after intersection users available are : '.json_encode($finalAvailableAndLoginAdvisorIds));
                    } else {
                        $ruleUsers = RuleLeadSource::join('rules', 'rule_lead_sources.rule_id', 'rules.id')->where('rules.is_active', 1)->distinct()->pluck('rule_lead_sources.user_id')->toArray();
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
                        info('inside common array , userId is : '.json_encode($userId));
                    }
                    if ($userId) {
                        info('about to assign car lead : '.$carLead->uuid.' to user with id : '.$userId);
                        $carQuote = CarQuote::where('id', $carLead->id)->first();
                        $carQuote->advisor_id = $userId;
                        $carQuote->tier_id = $selectedTier->id;
                        $carQuote->save();

                        $carQuoteDetail = CarQuoteRequestDetail::where('car_quote_request_id', $carLead->id)->first();
                        $carQuoteDetail->advisor_assigned_date = now();
                        $carQuoteDetail->save();

                        info('updating user record in lead allocation table with count increment userId: '.$userId);
                        $leadAllocation = LeadAllocation::where('user_id', $userId)->first();
                        $leadAllocation->allocation_count = $leadAllocation->allocation_count + 1;
                        $leadAllocation->last_allocated = Carbon::now()->timestamp;
                        $leadAllocation->updated_at = now();
                        $leadAllocation->save();
                        info('completed assignment of lead and lead count update is done for quote : '.$carQuote->code.' and lead allocation count for user : '.$userId.' is now : '.$leadAllocation->allocation_count);
                    } else {
                        info('login users not found for selected lead so will try to assign only tier');
                        $carQuote = CarQuote::where('id', $carLead->id)->first();
                        if ($carQuote->tier_id == null) {
                            $carQuote->tier_id = $selectedTier->id;
                            $carQuote->save();
                            info('Tier with name : '.$selectedTier->name.' and id : '.$selectedTier->id.' is assigned to car lead with uuid : '.$carQuote->uuid);
                        } else {
                            info('Tier ('.$selectedTier->tier_id.')is already assigned against car lead with uuid : '.$carQuote->uuid);
                        }
                    }
                }
                info('----------------------- CAR LEAD ALLOCATION ENDED FOR LEAD '. $carLead->uuid . ' -----------------------');
            }
            info('----------------------- CAR LEAD ALLOCATION ENDED FOR '. $currentIterationTime . ' -----------------------');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function splitString($separator, $string)
    {
        if (strpos($string, $separator) !== false) {
            $parts = explode($separator, $string);
        } else {
            $parts = [$string];
        }

        return $parts;
    }

    public function getCarUnallocatedLeads($from)
    {
        info('Car leads fetch start date is :'.$from);
        $to = now();
        $isFIFO = $this->getAppStorageValueByKey('CAR_LEAD_PICKUP_FIFO');
        $carLeadPickupLimit = $this->getAppStorageValueByKey('CAR_LEAD_PICKUP_LIMIT');

        return CarQuote::whereNull('advisor_id')
            ->where('is_renewal_tier_email_sent', 0)
            ->whereBetween('created_at', [$from, $to])
            ->where('quote_status_id', '!=', QuoteStatusEnum::Fake)
            ->orderBy('created_at', $isFIFO ? 'asc' : 'desc')
            ->skip(0)->take($carLeadPickupLimit)->get();
    }

    public function getRulesByLeadSource($source)
    {
        $records = LeadSource::join('rule_lead_sources', 'rule_lead_sources.lead_source_id', 'lead_sources.id')
        ->join('users', 'users.id', 'rule_lead_sources.user_id')
        ->join('rules', 'rule_lead_sources.rule_id', 'rules.id')
        ->where('lead_sources.name', $source)
        ->where('rules.is_active', 1)
        ->groupBy('rule_lead_sources.lead_source_id')
        ->select('lead_sources.name AS leadSourceName', 'lead_sources.id AS leadSourceId',
            DB::raw('group_concat(rule_lead_sources.user_id) AS leadSourceUsers'));

        return $records->get();
    }

    public function checkIfLeadIsRenewal($lead)
    {
        $dateFrom = Carbon::now()->addDays(-30);
        $dateTo = Carbon::now()->addDays(90);
        info('car lead allocation renewal date from : '.$dateFrom.' and date to : '.$dateTo);
        $renewalQuote = CarQuote::where('source', LeadSourceEnum::RENEWAL_UPLOAD)
        ->whereBetween('renewal_expiry_date', [$dateFrom, $dateTo])
        ->where(function ($query) use ($lead) {
            $query->where('email', $lead->email)
                ->orWhere('mobile_no', 'like', '%'.substr($lead->mobile_no, -7));
        })
        ->get();
        if (count($renewalQuote) > 0) {
            info('car lead allocation found a renewal quote with uuid : '.$renewalQuote->first()->uuid.' for car quote with uuid : '.$lead->uuid);

            return true;
        } else {
            info('car lead allocation did-not found a renewal for uuid : '.$lead->uuid);

            return false;
        }
    }

    public function sendRenewalLeadEmail($lead)
    {
        info('sendRenewalLeadEmail -- start');

        $renewalEmailRecipients = config('constants.RENEWAL_ALLOCATION_LEAD_EMAIL_RECIPIENTS');

        $emailData = $this->emailDataService->generateTierREmailData($lead);

        $templateId = (int) $this->getAppStorageValueByKey('CAR_RENEWAL_ALLOCATION_LEAD_EMAIL_TEMPLATE_ID');

        $tag = config('constants.APP_ENV').' - motor allocation renewal';

        info('sendRenewalLeadEmail -- start sending email for lead : '. $lead->uuid);

        SIBService::sendEmailUsingSIB($templateId, $emailData, $tag, $renewalEmailRecipients);

        info('sendRenewalLeadEmail -- email sending done for lead : '. $lead->uuid);

        CarQuote::where('id', $lead->id)->update([
            'is_renewal_tier_email_sent' => 1,
        ]);
        info('sendRenewalLeadEmail -- end');
    }

    public function getTierForValue($carLead)
    {
        info('Started searching tier for car lead : '.json_encode($carLead->code));
        $highestValueTier = Tier::where('is_active', 1)->orderBy('max_price', 'desc')->first();
        $tiers = Tier::where('is_active', 1);
        info('car ecommerce info is : '.json_encode($carLead->is_ecommerce));
        if ($carLead->car_type_insurance_id == 2) {
            info('adding tpl check');
            $tiers->where('can_handle_tpl', 1);
            $tiers->where('can_handle_ecommerce', $carLead->is_ecommerce);
        }

        if (($carLead->car_value == null || $carLead->car_value <= 0 || $carLead->car_value == '?' || $carLead->car_value == '') && $carLead->car_type_insurance_id == 1) {
            info('adding null value check');
            $tiers->where('can_handle_null_value', 1);
        }
        if ($carLead->car_value > 0 && $carLead->car_type_insurance_id != 2) {
            if ($carLead->car_value > $highestValueTier->max_price) {
                info('lead '.$carLead->uuid.' have value higher then all the tiers so selecting tier '.$highestValueTier->name);

                return $highestValueTier;
            } else {
                info('adding min and max value check');
                $tiers->where('min_price', '<=', $carLead->car_value)->where('max_price', '>=', $carLead->car_value);
            }
        }
        if ($carLead->source == LeadSourceEnum::TPL_RENEWALS) {
            info('adding tpl renewal check');
            $tiers->where('is_tpl_renewals', 1);
            $tiers->where('can_handle_ecommerce', $carLead->is_ecommerce);
        }

        info('tiers query is : '.$tiers->toSql().' with binding of : '.json_encode($tiers->getBindings()));
        info('First tier after filtration is : '.json_encode($tiers->first()->name));
        $tiers = $tiers->get();
        if ($tiers != null) {
            return $tiers->first();
        }

        return null;
    }

    public function getTierUsersWithLeadAllocationRecord($tierId)
    {
        $tierUsers = TierUser::where('tier_id', $tierId)->get()->pluck('user_id');
        info('Tier users are :'.json_encode($tierUsers));
        $query = LeadAllocation::join('users as u', 'u.id', 'lead_allocation.user_id')
        ->select('u.id', 'u.email')
        ->where('u.last_login', '>', DB::raw('DATE_ADD(CURDATE(), INTERVAL 1 SECOND)'))
        ->where('lead_allocation.is_available', 1)
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
}
