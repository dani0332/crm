<?php

namespace App\Services;

use App\Enums\CarTypeOfInsuranceIdEnum;
use App\Enums\DaysNameEnum;
use App\Enums\HealthTeamType;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Jobs\CarRenewalEmailJob;
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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                $lead->auto_assigned = $isManualAssignment ? false : true;
                $lead->advisor_id = $advisorId;
                $lead->save();
                info('Lead Id '.$lead->uuid.' assigned to advisor '.$advisorId);
                if ($lead->source != LeadSourceEnum::REFERRAL) {
                    $this->updateLeadAllocationRecord($advisorId, $isManualAssignment);
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

        foreach ($users as $user) {
            info('max_capacity for user : '.$user->userEmail.' is about to get reset ');

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
            if (! $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_MASTER_SWITCH')) {
                info('Car lead allocation master switch is off');

                return false;
            }

            $currentIterationTime = now();

            info('----------------------- CAR LEAD ALLOCATION STARTED FOR '.$currentIterationTime.' -----------------------');

            $carUnAllocatedLead = $this->getCarUnallocatedLeads();

            info(count($carUnAllocatedLead).' unassigned car leads found.');

            foreach ($carUnAllocatedLead as $carLead) {
                info('----------------------- CAR LEAD ALLOCATION STARTED FOR LEAD '.$carLead->uuid.' -----------------------');

                if ($this->checkIfLeadIsRenewal($carLead)) {
                    info('Renewal found against quote Id : '.$carLead->uuid);

                    if (! $carLead->is_renewal_tier_email_sent) {
                        info('About to send Renewal Tier R email for quote Id : '.$carLead->uuid);

                        CarRenewalEmailJob::dispatch($carLead);
                    }

                    continue; // since we found renewal against current lead we will skip advisor assignment
                }
                info('trying to check tier against the current lead : '.$carLead->code);

                // we will find tier as per the value of the lead and if already assigned then we will simply find the tier,
                // since it might be here for reassignment of advisor
                $selectedTier = $carLead->tier_id == null ? $this->getTierForValue($carLead) : Tier::where('id', $carLead->tier_id)->first();

                if ($selectedTier) {
                    info('Found tier '.$selectedTier->name.' against car lead : '.$carLead->code);

                    $loginAndAvailableUserIds = $this->getTierUsersWithLeadAllocationRecord($selectedTier->id); // now we will try to find users based on selected tier

                    info('Available and Login users against selected tier are : '.json_encode($loginAndAvailableUserIds));

                    $matchedRuleRecords = $this->getRulesByLeadSource($carLead->source);

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
                    }
                    if ($userId) {
                        info('About to assign car lead : '.$carLead->uuid.' to user with id : '.$userId);

                        $carQuote = CarQuote::where('id', $carLead->id)->first();
                        $carQuote->advisor_id = $userId;
                        $carQuote->tier_id = $selectedTier->id;
                        $carQuote->cost_per_lead = $selectedTier->cost_per_lead;
                        $carQuote->save();

                        info('advisor and tier assignment done for : '.$carLead->uuid.' to user with id : '.$userId.' and tier id : '.$selectedTier->name);
                        $this->updateCarLeadDetailRecord($carLead->id); // updating detail table about assignment

                        info('updating user record in lead allocation table with count increment userId: '.$userId);
                        $this->updateLeadAllocationOnCarAutoAssignment($userId); // updating lead allocation record for user

                        $emailData = $this->buildEmailDateForLMSIntroEmail($userId, $carQuote); // create email body for intro email

                        $emailTemplateId = (int) $this->getAppStorageValueByKey('LMS_INTRO_EMAIL_TEMPLATE_ID'); // template id for LMS intro email

                        $this->sendEmailCustomerService->sendLMSIntroEmail($emailTemplateId, $emailData, 'send-lms-intro-email'); // sending email using email body and template id

                        info('completed assignment of lead and lead count update is done for quote : '.$carQuote->code);
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
            info('----------------------- CAR LEAD ALLOCATION ENDED FOR '.$currentIterationTime.' -----------------------');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function buildEmailDateForLMSIntroEmail($userId, $carQuote)
    {
        $user = User::where('id', $userId)->first();
        $emailData = (object) [
            'customerEmail' => $carQuote->email,
            'documentUrl' => ['https://insurancemarket.blob.core.windows.net/imcrmdev/myAlfred%20Offers%20Flyer_Jan2023.pdf'], // this will be replace with a generic URL once document upload section is done
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
        $leadAllocation = LeadAllocation::where('user_id', $userId)->first();
        $leadAllocation->allocation_count = $leadAllocation->allocation_count + 1;
        $leadAllocation->auto_assignment_count = $leadAllocation->auto_assignment_count + 1;
        $leadAllocation->last_allocated = Carbon::now()->timestamp;
        $leadAllocation->updated_at = now();
        $leadAllocation->save();
    }

    public function updateCarLeadDetailRecord($leadId)
    {
        info('---- Inside updateCarLeadDetailRecord');
        $carQuoteDetail = CarQuoteRequestDetail::where('car_quote_request_id', $leadId)->first();
        if ($carQuoteDetail != null) {
            $carQuoteDetail->advisor_assigned_date = now();
            $carQuoteDetail->advisor_assigned_by_id = auth()->id();
            $carQuoteDetail->save();
            info('---- updateCarLeadDetailRecord - update done for advisor data and by id');
        } else {
            info('---- updateCarLeadDetailRecord - record not found creating new entry');
            CarQuoteRequestDetail::create([
                'car_quote_request_id' => $leadId,
                'advisor_assigned_date' => now(),
                'advisor_assigned_by_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function getCarUnallocatedLeads()
    {
        $from = $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_START_DATE_FOR_LEADS');

        $to = now()->subMinutes(2)->toDateTimeString();

        $carLeadPickupLimit = $this->getAppStorageValueByKey('CAR_LEAD_PICKUP_LIMIT');

        $isFIFO = $this->getAppStorageValueByKey('CAR_LEAD_PICKUP_FIFO');

        info('Car leads fetch start date is :'.$from.'  and end datetime is : '.$to.' and pickup limit is : '.$carLeadPickupLimit.' and Pickup direction FIFO is : '.$isFIFO);

        return CarQuote::whereNull('advisor_id')
            ->where('is_renewal_tier_email_sent', 0) // this check make sure that Tier R leads are excluded bcz we only send email for Tier R and not assign advisor
            ->whereBetween('created_at', [$from, $to])
            ->where('quote_status_id', '!=', QuoteStatusEnum::Fake) // excluding all Fake leads
            ->where('source', '!=', LeadSourceEnum::IMCRM) // leads created from IMCRM are excluded because they get assigned to the creator right away
            ->orderBy('created_at', $isFIFO ? 'asc' : 'desc') // pickup order
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
        ->select(
            'lead_sources.name AS leadSourceName',
            'lead_sources.id AS leadSourceId',
            DB::raw('group_concat(rule_lead_sources.user_id) AS leadSourceUsers')
        );

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
         * Search for a lead where renewal expiry date should be in between last 30 days and future 90 days
         * Search for a lead where email OR phone number (last 7 digits) matches
         * Search for a lead where car make and model id is same as what we have from current request
         *
         * IF combining all above criteria's we find a lead then its a renewal otherwise not
         */
        $dateFrom = Carbon::now()->addDays(-30);
        $dateTo = Carbon::now()->addDays(90);

        info('car lead allocation renewal date from : '.$dateFrom.' and date to : '.$dateTo);

        $renewalQuote = CarQuote::where('source', LeadSourceEnum::RENEWAL_UPLOAD)
        ->whereBetween('renewal_expiry_date', [$dateFrom, $dateTo])
        ->where(function ($query) use ($lead) {
            $query->where('email', $lead->email)
                ->orWhere('mobile_no', 'like', '%'.substr($lead->mobile_no, -7));
        })->where('car_make_id', $lead->car_make_id)->where('car_model_id', $lead->car_model_id)->get();

        if (count($renewalQuote) > 0) {
            info('car lead allocation found a renewal quote with uuid : '.$renewalQuote->first()->uuid.' for car quote with uuid : '.$lead->uuid);

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
            && $carLead->car_type_insurance_id == CarTypeOfInsuranceIdEnum::Comprehensive) {
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

        info('First tier after filtration is : '.json_encode($tiers->first()->name));

        $tiers = $tiers->get();
        if ($tiers != null) {
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
         * User's allocation count should be less then his max_capacity OR his max_capacity should be -1
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

        info('updateAllocationStatusIfNeeded -- current time is : '.now()->toTimeString().' , endTime is : '.$endTimeForAllocation.' , Switch is : '.$carLeadAllocationSwitch);

        if (now()->toTimeString() >= $endTimeForAllocation && $carLeadAllocationSwitch == 1) {
            // stopping car lead allocation if the end time for allocation is reached and allocation is still ON
            info('updateAllocationStatusIfNeeded -- Inside reset case');
            $this->updateAppStorageValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH', 0);

            // reset the max capacity for each user as the allocation is now stopped
            $this->updateUserMaxCapacity();
        }
    }

    public function shouldCarAllocationProceed()
    {
        $shouldProcess = true;

        if (config('constants.CAR_LEAD_ALLOCATION_MASTER_SWITCH') == '0' || config('constants.CAR_LEAD_ALLOCATION_MASTER_SWITCH') == 0) {
            // if car lead allocation master switch is OFF then we shouldn't proceed further
            $shouldProcess = false;
        }

        if (! $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_JOB_SWITCH')) {
            // if car lead allocation normal switch is OFF then we shouldn't proceed further
            $shouldProcess = false;
        }

        info('shouldCarAllocationProceed -- output is : '.json_encode($shouldProcess));

        return $shouldProcess;
    }

    public function shouldResetUserAssignmentCountAndAvailability()
    {
        $shouldProcess = false;
        $totalResetTime = $this->getAppStorageValueByKey('CAR_LEAD_ALLOCATION_TOTAL_RESET');

        info('time now is : '.now()->toTimeString().', total reset time is : '.$totalResetTime);
        if (now()->toTimeString() >= $totalResetTime) {
            info('should total reset is true');
            $shouldProcess = true;
        } else {
            info('should total reset is false');
        }

        return $shouldProcess;
    }
}
