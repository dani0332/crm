<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Models\ApplicationStorage;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HealthQuoteRequestDetail;
use App\Models\LeadAllocation;
use App\Models\Nationality;
use App\Models\Team;
use App\Models\TierUsers;
use App\Models\User;
use App\Traits\GetUserTree;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class LeadAllocationService extends BaseService
{
    use GetUserTree;

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
            $from = ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_START_DATE_FOR_LEADS')->first()->value;
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
        info('assignLead -- started');
        if ($this->checkIfAdvisorCanTakeLead($advisorId)) {
            if ($lead->advisor_id != null) {
                $this->removeLeadAllocationForOldAdvisor($lead);
            }

            info('Assigning lead '.$lead->uuid.' to advisor '.$advisorId);
            try {
                DB::beginTransaction();

                if ($isManualAssignment && $lead->advisor_id != null) {
                    $lead->quote_status_id = QuoteStatusEnum::Qualified;
                }
                $lead->advisor_id = $advisorId;
                $lead->save();
                info('Lead Id '.$lead->uuid.' assigned to advisor '.$advisorId);
                if ($lead->source != LeadSourceEnum::REFERRAL) {
                    $this->updateLeadAllocationRecord($advisorId);
                }
                $this->updateLeadDetailRecord($lead->id, $lead->uuid);
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
            $timeForUnavailability = Carbon::parse(ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_UNAVAILABILITY_TIME')->first()->value)->toTimeString();
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

    public function carLeadAllocationSwitchStatus()
    {
        try {
            DB::beginTransaction();
            $leadAllocationSwitch = ApplicationStorage::where('key_name', 'CAR_LEAD_ALLOCATION_JOB_SWITCH')->first();
            DB::commit();

            return $leadAllocationSwitch && $leadAllocationSwitch->value == '1';
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
    }
    public function leadAllocationSwitchStatus()
    {
        try {
            DB::beginTransaction();
            $leadAllocationSwitch = ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_JOB_SWITCH')->first();
            DB::commit();

            return $leadAllocationSwitch && $leadAllocationSwitch->value == '1';
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollback();
        }
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
            $from = ApplicationStorage::where('key_name', 'LEAD_ALLOCATION_START_DATE_FOR_LEADS')->first()->value;
            $carUnAllocatedLead = $this->getCarUnallocatedLeads($from);
            foreach ($carUnAllocatedLead as $carLead) {
                if ($this->checkIfLeadIsRenewal($carLead)) {
                    $this->sendRenewalLeadEmail($carLead);
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

        return CarQuote::whereNull('advisor_id')
            ->whereBetween('created_at', [$from, $to])
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
        $renewalQuote = CarQuote::where('source', LeadSourceEnum::RENEWALUPLOAD)
        ->whereBetween('renewal_expiry_date', [$dateFrom, $dateTo])
        ->where(function ($query) use ($lead) {
            $query->where('email', $lead->email)
                ->orWhere('mobile_no', 'like', '%'.substr($lead->mobile_no, -7));
        })
        ->get();
        if ($renewalQuote) {
            return true;
        } else {
            return false;
        }
    }

    public function sendRenewalLeadEmail($lead)
    {
        $errorEmailRecipients = config('constants.RENEWAL_ALLOCATION_LEAD_EMAIL_RECIPIENTS');
        $errorEmailRecipients = explode(',', $errorEmailRecipients);
        $subject = $lead->first_name.' '.$lead->last_name.' has approached Alfred';
        MailService::sendEmail('CarAllocationRenewalLeadTemplate', [
            'clientFullName' => $lead->first_name.' '.$lead->last_name,
            'email' => $lead->email,
            'phone' => $lead->mobile_no,
            'nationality' => Nationality::where('id', $lead->nationality_id)->first()->text,
            'dob' => $lead->dob,
            'yearsOfDriving' => $lead->year_of_manufacturing,
            'yearOfManufacturing' => $lead->year_of_manufacture,
            'model' => CarModel::where('id', $lead->car_model_id)->first()->text,
            'make' => CarMake::where('id', $lead->car_make_id)->first()->text,
            'carValue' => $lead->car_value,
            'quoteLink' => config('constants.APP_URL').'/quotes/car/'.$lead->uuid,
        ], $subject, $errorEmailRecipients);
    }

    public function getTierForValue($carLead)
    {
        $query = 'select * from `tiers` where `is_active` = 1  ';
        if ($carLead->car_value) {
            $query = $query.' and can_handle_null_value = 1 limit 1';
        } elseif ($carLead->is_ecommerce) {
            $query = $query.' and can_handle_ecommerce = 1 limit 1';
        } else {
            $query = $query.' and (`min_price` <= ? OR min_price is null) and ( `max_price` >= ? or max_price is null ) limit 1';
        }

        $selectedTier = DB::select($query, [$carLead, $carLead]);
        if ($selectedTier != null) {
            return $selectedTier[0];
        }

        return $selectedTier;
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
}
