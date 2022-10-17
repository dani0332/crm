<?php

namespace App\Services;

use App\Enums\DaysNameEnum;
use App\Enums\HealthTeamType;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Events\AdvisorAssigned;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\LeadAllocation;
use App\Models\Nationality;
use App\Models\Team;
use App\Models\Tier;
use App\Models\TierUsers;
use App\Models\User;
use App\Traits\CreateUpdateSIbContact;
use App\Traits\GetUserTree;
use App\Traits\SendSIBEmail;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class LeadAllocationService extends BaseService
{
    use GetUserTree;
    use CreateUpdateSIbContact;
    use SendSIBEmail;

    public function getGridData()
    {
        try {
            DB::beginTransaction();
            $userAgainstManagerWithDetail = LeadAllocation::select('lead_allocation.id as id', 'lead_allocation.user_id as userId', 'lead_allocation.allocation_count', 'lead_allocation.max_capacity', 'lead_allocation.is_available', 'lead_allocation.last_allocated', 'st.name as teamName', 'u.name as userName')
                ->join('users as u', 'lead_allocation.user_id', '=', 'u.id')
                ->leftjoin('teams as t', 'u.team_id', '=', 't.id')
                ->leftjoin('teams as st', 'st.id', '=', 'u.sub_team_id')
                ->whereNotNull('u.sub_team_id')
                ->where(strtolower('t.name'), '=', strtolower(quoteTypeCode::Health));
            if (! auth()->user()->hasRole(RolesEnum::SuperManagerLeadAllocation)) {
                $userAgainstManagerWithDetail = $userAgainstManagerWithDetail->where('u.manager_id', auth()->user()->id);
            }
            DB::commit();

            return $userAgainstManagerWithDetail->get();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
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
            DB::beginTransaction();
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

            DB::commit();

            return $unAllocatedLeads;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
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
                AdvisorAssigned::dispatch($lead);
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
                if ($lead->health_team_type == HealthTeamType::EBP && $leadCreated > $releaseDate) {
                    info('Contact upload Request '.$lead->uuid);
                    $this->sendSibRequest($lead);
                }
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
            DB::beginTransaction();
            info('checkIfAdvisorCanTakeLead -- started');
            $leadAllocation = LeadAllocation::where('user_id', $advisorId)->first();
            if ($leadAllocation != null) {
                if ($leadAllocation->is_available == 0) {
                    info('Advisor '.$advisorId.' cannot take lead while he/she is not available');
                    DB::commit();

                    return false;
                }
                if ($leadAllocation->max_capacity == -1 || $leadAllocation->allocation_count < $leadAllocation->max_capacity) {
                    info('Advisor '.$advisorId.' can take lead');
                    DB::commit();

                    return true;
                }
                if ($leadAllocation->max_capacity == $leadAllocation->allocation_count && $leadAllocation->max_capacity != -1) {
                    info('Advisor '.$advisorId.' cannot take lead. Max capacity reached');
                    DB::commit();

                    return false;
                }
            } else {
                info('Advisor '.$advisorId.' has no allocation record');
                DB::commit();

                return false;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
            throw $e;
        }
    }

    public function updateLeadAllocationRecord($userId)
    {
        try {
            DB::beginTransaction();
            info('updateLeadAllocationRecord -- started');
            $leadAllocation = LeadAllocation::where('user_id', $userId)->first();
            DB::commit();
            info('Max capacity for user '.$userId.' is '.$leadAllocation->max_capacity.' and allocation count is '.$leadAllocation->allocation_count);

            $leadAllocation->allocation_count += 1;
            $leadAllocation->last_allocated = now()->timestamp;
            $leadAllocation->save();
            info('Lead allocation record for user '.$userId.' updated. Current allocation count is '.$leadAllocation->allocation_count);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function getHealthUserSubTeamName($userId)
    {
        try {
            DB::beginTransaction();
            info('getHealthUserSubTeamName -- started');
            $user = User::where('id', $userId)->first();
            if ($user) {
                if ($user->sub_team_id != null) {
                    info('User '.$user->name.' has sub-team '.$user->sub_team_id);
                    $userSubTeam = Team::where('id', $user->sub_team_id)->first();
                    info('User '.$user->name.' belongs to sub team '.$userSubTeam->name);
                    DB::commit();

                    return strtolower($userSubTeam->name);
                } else {
                    return null;
                }
            } else {
                DB::commit();

                return null;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
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
                Redis::command('flushdb');
                User::where('is_active', 1)->update(['logout_at' => null]);
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
            info('setMaxCapAndAllocationStatus -- started');
            $dateTimeNow = now()->toTimeString();
            $currentDay = Carbon::parse(now())->format('l');
            info('Current time is '.$dateTimeNow);

            $carLeadAllocationSwitch = $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH');
            $timeForEnd = Carbon::parse($this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_END_TIME'))->toTimeString();
            $timeForStart = Carbon::parse($this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_START_TIME'))->toTimeString();
            $sundayResetTime = Carbon::parse($this->getAppStorageValueByKey('SUNDAY_CAP_RESET_TIME'))->toTimeString();
            $normalResetTime = Carbon::parse($this->getAppStorageValueByKey('NORMAL_CAP_RESET_TIME'))->toTimeString();
            info('carLeadSwitch : '.$carLeadAllocationSwitch.' , End Time : '.$timeForEnd.' , Start Time : '.$timeForStart.', SundayResetTime :'.$sundayResetTime.', Normal ResetTime : '.$normalResetTime.' , time right now : '.$dateTimeNow);
            info('Time for advisor start in app storage is '.$timeForStart.' and end is :'.$timeForEnd.' and the switch right now is : '.$carLeadAllocationSwitch);
            if ($dateTimeNow >= $timeForStart && $carLeadAllocationSwitch == 0) {
                $this->updateAppStorageValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH', 1);
            }
            if ($dateTimeNow >= $timeForEnd && $carLeadAllocationSwitch == 1) {
                info('setMaxCapAndAllocationStatus - going to shutdown the car lead allocation switch');
                $this->updateAppStorageValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH', 0);
            }
            if ($currentDay == DaysNameEnum::SUNDAY && $dateTimeNow >= $sundayResetTime) {
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
                                'users.name as userName', DB::RAW('GROUP_CONCAT(DISTINCT (t.name)) AS tiers'), DB::RAW('GROUP_CONCAT(DISTINCT (q.name)) AS quads'),
                                'la.allocation_count as allocationCount', 'la.last_allocated as lastAllocation', 'la.max_capacity as maxCapacity', 'la.is_available as isAvailable',
                                'users.last_login as lastLogin', 'la.id as id'
                            )->get();
        foreach ($users as $user) {
            LeadAllocation::where('user_id', $user->userId)->update([
                'max_capacity' => str_contains($user->quads, '1') ? 4 : 5,
                'allocation_count' => 0,
                'updated_at' => now(),
            ]);
        }
    }

    public function carLeadAllocationSwitchStatus()
    {
        return $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH') == '1';
    }
    public function leadAllocationSwitchStatus()
    {
        return $this->getAppStorageValueByKey('LEAD_ALLOCATION_JOB_SWITCH') == '1';
    }

    public function getLeadAllocationRecordByUserId($userId)
    {
        try {
            DB::beginTransaction();
            $leadAllocation = LeadAllocation::where('user_id', $userId)->first();
            DB::commit();

            return $leadAllocation;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function getNextAssignableUserId($lead)
    {
        try {
            DB::beginTransaction();
            $availableUserId = LeadAllocation::join('users as u', 'lead_allocation.user_id', '=', 'u.id')
                                            ->join('teams as t', 't.id', '=', 'u.sub_team_id')
                                            ->where('lead_allocation.is_available', 1)
                                            ->where(function ($query) {
                                                $query->whereRaw('lead_allocation.allocation_count < lead_allocation.max_capacity')
                                                    ->orWhere('lead_allocation.max_capacity', '=', -1);
                                            })
                                            ->where(strtolower('t.name'), strtolower($lead->health_team_type))
                                            ->orderBy('lead_allocation.last_allocated', 'asc');
            DB::commit();

            return $availableUserId->first();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
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
            DB::beginTransaction();
            $from = $this->getAppStorageValueByKey('LEAD_ALLOCATION_START_DATE_FOR_LEADS');
            $carUnAllocatedLead = $this->getCarUnallocatedLeads($from);
            foreach ($carUnAllocatedLead as $carLead) {
                if ($this->checkIfLeadIsRenewal($carLead)) {
                    info('car lead allocation sending renewal email for uuid : '.$carLead->uuid);
                    if (! $carLead->is_renewal_tier_email_sent) {
                        $this->sendRenewalLeadEmail($carLead);
                        $tierTR = Tier::where('name', 'TR')->first();
                        if (isset($tierTR)) {
                            $carLead->tier_id = $tierTR->id;
                            $carLead->save();
                        }
                    }
                    continue;
                }
                info('trying to check tier against the current lead : '.$carLead->uuid);
                $selectedTier = $this->getTierForValue($carLead);
                if ($selectedTier) {
                    info('Tier '.$selectedTier->name.' is selected against car lead : '.$carLead->uuid);

                    $loginUsersRecords = $this->getTierUsersWithLeadAllocationRecord($selectedTier->id);
                    $loginUsersIds = $loginUsersRecords->pluck('id');

                    info('login and available users right now are '.$loginUsersIds);
                    $commonUserIds = $loginUsersIds;
                    $matchedRuleUsersId = $this->getRulesByLeadSource($carLead->source);

                    if (! empty($matchedRuleUsersId)) {
                        info('Rule found against lead source and users against rule are '.$matchedRuleUsersId);

                        $commonUserIds = array_intersect($loginUsersIds, explode(',', $matchedRuleUsersId));
                        info('rules user intersection with login users is '.$commonUserIds);
                    }

                    info('common users at this point are '.$commonUserIds);
                    $userId = $commonUserIds->first();
                    if ($userId) {
                        info('about to assign car lead : '.$carLead->uuid.' to user with id : '.$userId);
                        $carLead->advisor_id = $userId;
                        $carLead->tier_id = $selectedTier->id;
                        $carLead->save();
                        info('updating user record in lead allocation table with count increment userId: '.$userId);
                        LeadAllocation::where('user_id', $userId)->increment('allocation_count', 1,
                            ['last_allocated' => time(), 'updated_at' => Carbon::now()]
                        );
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }

    public function getCarUnallocatedLeads($from)
    {
        info('Car leads fetch start date is :'.$from);
        $to = now();
        $isFIFO = $this->getAppStorageValueByKey('CAR_LEAD_PICKUP_FIFO');

        return CarQuote::whereNull('advisor_id')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('tier_id')
            ->orderBy('created_at', $isFIFO ? 'asc' : 'desc')
            ->skip(0)->take(20)->get();
    }

    public function getRulesByLeadSource($source)
    {
        return DB::select('
                        SELECT ls.name AS leadSourceName
                        ,ls.id AS leadSourceId
                        ,group_concat(rls.user_id) AS leadSourceUsers
                    FROM lead_sources ls
                    INNER JOIN rule_lead_sources rls ON rls.lead_source_id = ls.id
                    INNER JOIN users u ON u.id = rls.user_id
                    WHERE ls.name = ? GROUP BY rls.lead_source_id', [$source]);
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
            info('car lead allocation found a renewal quote with uuid : '.$renewalQuote->first()->uuid);

            return true;
        } else {
            info('car lead allocation did-not found a renewal');

            return false;
        }
    }

    public function sendRenewalLeadEmail($lead)
    {
        info('Inside send car lead allocation renewal lead email');

        $renewalEmailRecipients = config('constants.RENEWAL_ALLOCATION_LEAD_EMAIL_RECIPIENTS');
        $subject = ucwords(config('constants.APP_ENV')).' - '.$lead->first_name.' '.$lead->last_name.' has approached Alfred';

        info('car lead allocation renewal lead email subject is : '.$subject);
        $emailData = [
            'clientFullName' => $lead->first_name.' '.$lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->mobile_no,
            'nationality' => $lead->nationality_id != null ? Nationality::where('id', $lead->nationality_id)->first()->text : '',
            'dob' => $lead->dob,
            'yearsOfDriving' => $lead->year_of_manufacturing,
            'yearOfManufacturing' => $lead->year_of_manufacture,
            'model' => $lead->car_model_id != null ? CarModel::where('id', $lead->car_model_id)->first()->text : '',
            'make' => $lead->car_make_id != null ? CarMake::where('id', $lead->car_make_id)->first()->text : '',
            'carValue' => $lead->car_value,
            'quoteLink' => config('constants.APP_URL').'/quotes/car/'.$lead->uuid,
        ];

        info('car lead allocation renewal lead email data is : '.json_encode($emailData));
        $templateId = (int) config('constants.RENEWAL_ALLOCATION_LEAD_EMAIL_TEMPLATE_ID');
        $tag = config('constants.APP_ENV').' - motor allocation renewal';
        $this->sendEmailUsingSIB($templateId, $emailData, $tag, $renewalEmailRecipients);
        info('Sending email done, going to work on car quote update for lead id : '.$lead->id);
        CarQuote::where('id', $lead->id)->update([
            'is_renewal_tier_email_sent' => 1,
        ]);
    }

    public function getTierForValue($carLead)
    {
        $tiers = Tier::where('is_active', 1)->get();
        if ($carLead->car_value == null) {
            info('get Tier inside the null value filter');
            $tiers = $tiers->filter(function ($value) {
                return $value->can_handle_null_value == 1;
            });
        }
        if ($carLead->is_ecommerce == 1) {
            info('get Tier inside the ecommerce filter');
            $tiers = $tiers->filter(function ($value) {
                return $value->can_handle_ecommerce == 1;
            });
        }
        if ($carLead->car_type_insurance_id == 2) {
            info('get Tier inside the can handle tpl filter');
            $tiers = $tiers->filter(function ($value) {
                return $value->can_handle_tpl == 1;
            });
        }
        if ($carLead->car_value > 0 && $carLead->car_type_insurance_id != 2) {
            info('get Tier inside the car value filter');
            $tiers = $tiers->filter(function ($value) use ($carLead) {
                return $value->min_price <= $carLead->car_value && $value->max_price >= $carLead->car_value;
            });
        }
        if ($carLead->source == LeadSourceEnum::TPL_RENEWALS) {
            info('get Tier inside the car value filter');
            $tiers = $tiers->filter(function ($value) {
                return $value->is_tpl_renewals == 1;
            });
        }
        info('get tier the first tier after filter is : '.json_encode($tiers->first()));
        if ($tiers != null) {
            return $tiers->first();
        }

        return null;
    }

    public function getTierUsersWithLeadAllocationRecord($tierId)
    {
        $tierUsers = TierUsers::where('tier_id', $tierId)->get()->pluck('user_id');

        return LeadAllocation::join('users as u', 'lead_allocation.user_id', '=', 'u.id')
                    ->where('lead_allocation.is_available', 1)
                    ->where(function ($query) {
                        $query->whereRaw('lead_allocation.allocation_count < lead_allocation.max_capacity')
                            ->orWhere('lead_allocation.max_capacity', '=', -1);
                    })
                    ->where('u.last_login', '>', Carbon::now()->addDays(-1)->endOfDay())
                    ->whereNull('u.logout_at')
                    ->whereIn('u.id', $tierUsers)
                    ->select('u.id', 'u.name', 'u.email', 'lead_allocation.allocation_count', 'lead_allocation.max_capacity',
                        'lead_allocation.last_allocated')
                    ->orderBy('lead_allocation.last_allocated', 'asc')->get();
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
