<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Enums\BusinessTypeOfInsuranceIdEnum;
use App\Enums\EaModelEnum;
use App\Enums\LeadAssignmentTriggerEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\UserStatusEnum;
use App\Jobs\SendSavingsOCAEmailJob;
use App\Models\DttRevival;
use App\Models\PersonalQuote;
use App\Models\QuoteBatches;
use App\Models\User;
use App\Services\AllocationService;
use App\Services\Logger\LoggerService;
use App\Services\NationalityAllocationService;
use App\Services\RuleService;
use App\Traits\LeadDuplicatable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

abstract class BaseAllocation extends AllocationService implements Allocation
{
    use LeadDuplicatable;

    abstract protected function fetchAdvisor(int $onlineStatus);

    protected $lead;
    protected bool $hasNationalityConfig = false;
    protected array $advisorIDs = [];
    protected array $excludedAdvisorIds = [];
    protected bool $skipRuleUsers = false;

    public function __construct(public QuoteTypes $quoteType, public string $uuid, public $teamId = false, public bool $overrideAdvisorId = false, public bool $isReAssignment = false) {}

    private function getQuoteTypeId()
    {
        return in_array($this->quoteType, [QuoteTypes::CORPLINE, QuoteTypes::GROUP_MEDICAL]) ? QuoteTypes::BUSINESS->id() : $this->quoteType->id();
    }

    protected function getParentLeadAdvisorId(): ?int
    {
        $revivalLead = DttRevival::where('uuid', $this->lead->uuid)->select('previous_quote_id')->first();

        if (! $revivalLead) {
            return null;
        }

        $parentLead = PersonalQuote::where('id', $revivalLead->previous_quote_id)->select('advisor_id')->first();

        return $parentLead?->advisor_id;
    }

    public function execute()
    {
        $response = [
            'advisorId' => 0,
            'message' => '',
            'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ];

        try {
            LoggerService::info(self::class.' - execute: Allocation Started');
            $this->resolveLead();

            if ($this->lead?->source === LeadSourceEnum::EA_IMCRM) {
                LoggerService::info(self::class.' - execute: EA_IMCRM lead detected', extra: [
                    'uuid' => $this->lead->uuid,
                    'ea_model' => $this->lead->ea_model?->value,
                    'source' => $this->lead->source,
                ]);
            }

            if ($this->lead && $this->shouldHandleDuplicateLead()) {
                $this->resolveDuplicateLeadInfo();
            }

            if (! $this->lead) {
                LoggerService::info(self::class.' - execute: Lead not found');
                $response = $this->createResponse(0, 'Lead not found or not under fetch criteria', Response::HTTP_NOT_FOUND);
            } else {
                $advisor = null;
                if ($this->hasDuplicateLead) {
                    $advisor = $this->getAdvisorForDuplicateLeadAssignment();
                    LoggerService::info(self::class.' - execute: Duplicate lead handling result', extra: [
                        'found_advisor' => $advisor ? true : false,
                        'advisor_id' => $advisor?->id,
                    ]);
                }

                if (! $advisor) {
                    $advisor = $this->fetchAvailableAdvisor();
                }

                // if advisor still not found, then we need to fail the lead allocation
                if (! $advisor) {
                    $this->leadAllocationFailed($this->uuid, $this->quoteType);
                    $this->sendNonAdvisorEmail();

                    LoggerService::info(self::class.' - execute: No advisor found');

                    $response = $this->createResponse(0, 'Advisor not found', Response::HTTP_NOT_FOUND);
                } else {
                    $this->assignLead($advisor);
                    $response = $this->createResponse($advisor->id, 'Advisor assigned successfully!', Response::HTTP_OK);
                }
            }
        } catch (\Throwable $th) {
            $this->leadAllocationFailed($this->uuid, $this->quoteType);

            $message = $th->getMessage() ?? '';
            LoggerService::error('exception occurred in lead allocation with error : '.$message);
            LoggerService::error('exception occurred in lead allocation with error stack as  : '.$th->getTraceAsString());
            $response = $this->createResponse(0, "exception occurred in lead allocation with error : {$message}", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $response;
    }

    protected function getLeadBaseQuery()
    {
        return $this->quoteType->model()
            ->with('quoteDetail')
            ->where('uuid', $this->uuid)
            ->when($this->quoteType->isPersonalQuote(), function ($q) {
                $q->where('quote_type_id', $this->quoteType->id());
            })
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->when($this->quoteType === QuoteTypes::GROUP_MEDICAL, function ($q) {
                $q->where('business_type_of_insurance_id', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL);
            })
            ->when($this->quoteType === QuoteTypes::CORPLINE, function ($q) {
                $q->where('business_type_of_insurance_id', '!=', BusinessTypeOfInsuranceIdEnum::GROUP_MEDICAL);
            })
            ->when($this->quoteType === QuoteTypes::LIFE, function ($q) {
                $q->where(function ($lifeQuery) {
                    $lifeQuery->where('source', '!=', LeadSourceEnum::REVIVAL)
                        ->orWhereNull('source');
                });
            })
            ->when(in_array($this->quoteType, [QuoteTypes::HOME, QuoteTypes::HOME_REVIVAL]), function ($q) {
                $q->where(function ($lifeQuery) {
                    $lifeQuery->whereNotIn('source', [LeadSourceEnum::REVIVAL_SHORT, LeadSourceEnum::REVIVAL_ANNUAL])
                        ->orWhereNull('source');
                });
            })
            ->when(! $this->overrideAdvisorId, function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNull('advisor_id')
                        ->orWhere(function ($sq) {
                            $sq->where('source', LeadSourceEnum::EA_IMCRM)
                                ->where('ea_model', EaModelEnum::Collaborate->value)
                                ->whereNull('expert_advisor_id');
                        });
                });
            });
    }

    protected function resolveLead(): void
    {
        $this->lead = $this->getLeadBaseQuery()->first();
    }

    protected function getAdvisorBaseQuery(int $onlineStatus, array $roles)
    {
        LoggerService::info('BaseAllocation: Starting getAdvisorBaseQuery', extra: [
            'onlineStatus' => $onlineStatus,
            'roles' => $roles,
            'quoteTypeId' => $this->getQuoteTypeId(),
            'teamId' => $this->teamId,
        ]);

        $query = User::select('users.id as user_id')
            ->join('lead_allocation as la', 'la.user_id', '=', 'users.id')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->where('users.status', $onlineStatus)
            ->where(function ($query) {
                $query->whereRaw('la.allocation_count < la.max_capacity')->orWhere('la.max_capacity', -1);
            })
            ->when($this->teamId, function ($q) {
                $q->whereIn('users.id', fn ($query) => $query->select('user_id')->from('user_team')->where('team_id', $this->teamId));
            })
            ->whereIn('r.name', $roles)
            ->where('la.quote_type_id', $this->getQuoteTypeId())
            ->when(
                $this->hasNationalityConfig,
                fn ($q) => $q->whereIn('users.id', $this->advisorIDs),
                function ($q) {
                    if (! empty($this->excludedAdvisorIds)) {
                        LoggerService::info(self::class.' - Excluding advisors from nationality config', extra: [
                            'excluded_advisor_ids' => $this->excludedAdvisorIds ?? [],
                        ]);
                        $q->whereNotIn('users.id', $this->excludedAdvisorIds);
                    }
                },
            )
            ->activeUser()
            ->when($this->skipRuleUsers, function ($q) {
                $ruleUserIds = app(RuleService::class)->getRuleUserIds($this->quoteType);
                $ruleUserIds = $this->finalizeExcludedAdvisorIds($ruleUserIds);
                LoggerService::info(self::class.' - Excluding advisors from rules', extra: [
                    'excluded_rule_user_ids' => $ruleUserIds ?? [],
                ]);
                $q->whereNotIn('users.id', $ruleUserIds);
            })
            ->when(
                $this->lead?->source === LeadSourceEnum::EA_IMCRM && $this->lead?->ea_model === EaModelEnum::Collaborate,
                fn ($q) => $q->whereHas('permissions', fn ($pq) => $pq->where('name', PermissionsEnum::AssignedExpertAdvisor))
            )
            ->when(
                $this->lead?->source === LeadSourceEnum::EA_IMCRM && $this->lead?->ea_model === EaModelEnum::Referral,
                fn ($q) => $q->whereHas('permissions', fn ($pq) => $pq->where('name', PermissionsEnum::AssignedReferralAdvisor))
            )
            ->orderBy('la.last_allocated', 'asc');

        if ($this->lead?->source === LeadSourceEnum::EA_IMCRM) {
            LoggerService::info(self::class.' - getAdvisorBaseQuery: EA_IMCRM lead, filtering advisor pool by permission', extra: [
                'uuid' => $this->lead->uuid,
                'ea_model' => $this->lead->ea_model?->value,
                'permission' => $this->lead->ea_model === EaModelEnum::Collaborate
                    ? PermissionsEnum::AssignedExpertAdvisor
                    : PermissionsEnum::AssignedReferralAdvisor,
            ]);
        }

        return $query;
    }

    public function fetchAvailableAdvisor()
    {
        LoggerService::info(self::class." - fetchAvailableAdvisor: {$this->isReAssignment} - {$this->teamId}");

        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $this->isReAssignment) {
            $statusOrder[] = UserStatusEnum::UNAVAILABLE;
        }

        if (! $this->isBusinessHours()) {
            $statusOrder[] = UserStatusEnum::MANUAL_OFFLINE;
        }

        $this->resolveNationalityConfig();

        foreach ($statusOrder as $status) {
            LoggerService::info(self::class." - trying to get advisors with current status as {$status}");
            $eligibleUser = $this->fetchAdvisor($status);

            if ($eligibleUser) {
                LoggerService::info(self::class." - eligible user found with status: {$status} and user id : {$eligibleUser->user_id}");

                return User::find($eligibleUser->user_id);
            }
        }

        return null;
    }

    private function assignLead(User $advisor)
    {
        DB::beginTransaction();
        try {
            $assignmentType = $this->isReAssignment ? AssignmentTypeEnum::SYSTEM_REASSIGNED : AssignmentTypeEnum::SYSTEM_ASSIGNED;
            LoggerService::info(self::class.' - assignLead: Going to Assign Advisor');
            $isEACollaborate = $this->lead->source === LeadSourceEnum::EA_IMCRM
                && $this->lead->ea_model === EaModelEnum::Collaborate;
            $previousAssignmentType = $this->lead->assignment_type;
            $previousUserId = $this->lead->advisor_id;
            if ($this->lead->source === LeadSourceEnum::EA_IMCRM) {
                LoggerService::info(self::class.' - assignLead: EA_IMCRM lead assignment', extra: [
                    'uuid' => $this->lead->uuid,
                    'ea_model' => $this->lead->ea_model?->value,
                    'assigning_to' => $isEACollaborate ? 'expert_advisor_id' : 'advisor_id',
                    'advisor_id' => $advisor->id,
                ]);
            }
            if ($isEACollaborate) {
                $this->lead->expert_advisor_id = $advisor->id;
            } else {
                $this->lead->advisor_id = $advisor->id;
                $this->lead->assignment_type = $assignmentType;
            }
            LoggerService::info(self::class.' - assignLead: Checking lead_assignment_trigger', extra: [
                'current_value' => $this->lead->lead_assignment_trigger ?? 'null',
            ]);
            if (empty($this->lead->lead_assignment_trigger)) {
                LoggerService::info(self::class.' - assignLead: Setting lead_assignment_trigger to LEAD_AUTO_ASSIGNED');
                $this->lead->lead_assignment_trigger = LeadAssignmentTriggerEnum::LEAD_AUTO_ASSIGNED;
            }
            $quoteBatch = QuoteBatches::latest()->first();
            $this->lead->quote_batch_id = $quoteBatch->id;
            $this->lead->save();
            LoggerService::info(self::class." - Assigned to advisor : {$advisor->name} Quote Batch with ID: {$quoteBatch->id} and Name: {$quoteBatch->name}");

            $previousAdvisorAssignedDate = $this->updateQuoteDetail($this->lead->id);

            if ($this->lead->source != LeadSourceEnum::REFERRAL && ! $isEACollaborate) {
                LoggerService::info(self::class.' - lead source is not referral so about to update allocation record');
                if ($assignmentType == AssignmentTypeEnum::SYSTEM_ASSIGNED) {
                    $this->addAllocationCounts($advisor->id, $this->getQuoteTypeId());
                } else {
                    $this->adjustAllocationCounts($advisor->id, $this->lead, $previousUserId, $previousAdvisorAssignedDate, $previousAssignmentType, $this->getQuoteTypeId());
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            LoggerService::error($e->getMessage());
        }
    }

    private function updateQuoteDetail()
    {
        LoggerService::info(self::class.' - about to update quote detail record');

        $oldAdvisorAssignedDate = $this->lead->quoteDetail?->advisor_assigned_date ?? '';

        $this->upsertQuoteDetail($this->lead->id, $this->quoteType->detailModel(), $this->quoteType->model()->getForeignKey());

        return $oldAdvisorAssignedDate;
    }

    protected function getAdvisorEmails($storageKey)
    {
        $emails = getAppStorageValueByKey($storageKey, useCache: true);

        if (empty($emails)) {
            return [];
        }

        $emails = explode(',', $emails);
        $emails = array_map('trim', $emails);
        $emails = array_filter($emails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

        LoggerService::info(self::class.' - Advisor Emails fetched', ['count' => count($emails), 'advisors' => $emails]);

        return $emails;
    }

    private function resolveNationalityConfig()
    {
        $config = NationalityAllocationService::find($this->quoteType, $this->lead->nationality_id);

        if ($config) {
            $this->hasNationalityConfig = true;
            $this->advisorIDs = NationalityAllocationService::getUserIDs($config);
            LoggerService::info(self::class." - Nationality Config found for Nationality ID: {$this->lead->nationality_id} | Advisor IDs: ".implode(', ', $this->advisorIDs));
        } else {
            $this->resolveExcludedAdvisorIds();
        }

        return $config;
    }

    private function resolveExcludedAdvisorIds()
    {
        $excludedAdvisorIds = NationalityAllocationService::getExcludedUserIds($this->quoteType);

        if (empty($excludedAdvisorIds)) {
            LoggerService::info(self::class.' - No excluded advisor IDs from nationality config');

            return;
        }

        LoggerService::info(self::class.' - Found excluded advisor IDs from nationality config', extra: [
            'excluded_advisor_ids_before_finalize' => $excludedAdvisorIds,
        ]);

        $excludedAdvisorIds = $this->finalizeExcludedAdvisorIds($excludedAdvisorIds);

        LoggerService::info(self::class.' - Finalized excluded advisor IDs (after removing super advisors)', extra: [
            'excluded_advisor_ids_after_finalize' => $excludedAdvisorIds,
        ]);

        $this->excludedAdvisorIds = $excludedAdvisorIds;
    }

    private function sendNonAdvisorEmail()
    {
        $lobsToSend = [QuoteTypes::SAVINGS];

        if (! in_array($this->quoteType, $lobsToSend)) {
            return;
        }

        if ($this->lead->isNonAdvisorEmailSent()) {
            LoggerService::info(self::class.' - Non Advisor Email already sent to customer');

            return;
        }
        // temporary disable non advisor email for savings quote
        if (! $this->lead->isSuppressIntroEmail()) {
            SendSavingsOCAEmailJob::dispatch($this->lead->uuid)->delay(now()->addSeconds(10));
            LoggerService::info(self::class.' - Non Advisor Email job dispatched');
        }
    }

    protected function getAdvisorsByEmailsOrIds(int $onlineStatus, array $roles, ?array $emails = null, ?array $advisorIds = null)
    {
        return $this->getAdvisorBaseQuery($onlineStatus, $roles)
            ->when(! is_null($emails), fn ($q) => $q->whereIn('users.email', $emails))
            ->when(! is_null($advisorIds), fn ($q) => $q->whereIn('users.id', $advisorIds))
            ->logRawSql()
            ->first();
    }
    protected function finalizeExcludedAdvisorIds(?array $excludedAdvisorIds): array
    {
        if (empty($excludedAdvisorIds)) {
            return [];
        }

        $superAdvisorIds = User::whereHas('permissions', function ($query) {
            $query->where('name', PermissionsEnum::NONRULE_LEADALLOCATION);
        })->pluck('id')->toArray();

        $excludedAdvisorIds = array_diff($excludedAdvisorIds, $superAdvisorIds);
        $excludedAdvisorIds = array_values($excludedAdvisorIds);

        return $excludedAdvisorIds;
    }
}
