<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\UserStatusEnum;
use App\Models\QuoteBatches;
use App\Models\User;
use App\Services\AllocationService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class BaseAllocation extends AllocationService
{
    abstract protected function fetchAdvisor(int $onlineStatus);

    protected $lead;

    public function __construct(public QuoteTypes $quoteType, public string $uuid, public $teamId = false, public bool $overrideAdvisorId = false, public bool $isReAssignment = false) {}

    private function getQuoteTypeId()
    {
        return in_array($this->quoteType, [QuoteTypes::CORPLINE, QuoteTypes::GROUP_MEDICAL]) ? QuoteTypes::BUSINESS->id() : $this->quoteType->id();
    }

    public function executeSteps()
    {
        $response = [
            'advisorId' => 0,
            'message' => '',
            'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ];

        try {
            info(self::class." - executeSteps: Allocation Started for UUID : {$this->uuid}");
            $this->resolveLead();

            if (! $this->lead) {
                info(self::class." - executeSteps: Lead not found for : {$this->uuid}");
                $response = $this->createResponse(0, 'Lead not found or not under fetch criteria', Response::HTTP_NOT_FOUND);
            } else {
                $advisor = $this->fetchAvailableAdvisor();

                if (! $advisor) {
                    $this->leadAllocationFailed($this->uuid, $this->quoteType);

                    info(self::class." - executeSteps: No advisor found against lead : {$this->lead->uuid}");

                    $response = $this->createResponse(0, 'Advisor not found', Response::HTTP_NOT_FOUND);
                } else {
                    $this->assignLead($advisor);
                    $response = $this->createResponse($advisor->id, 'Advisor assigned successfully!', Response::HTTP_OK);
                }
            }
        } catch (\Throwable $th) {
            $this->leadAllocationFailed($this->uuid, $this->quoteType);

            $message = $th->getMessage() ?? '';
            info('exception occurred in lead allocation with error : '.$message);
            info('exception occurred in lead allocation with error stack as  : '.$th->getTraceAsString());
            $response = $this->createResponse(0, "exception occurred in lead allocation with error : {$message}", Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $response;
    }

    protected function getLeadBaseQuery()
    {
        return $this->quoteType->model()
            ->where('uuid', $this->uuid)
            ->when($this->quoteType->isPersonalQuote(), function ($q) {
                $q->where('quote_type_id', $this->quoteType->id());
            })
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->when(! $this->overrideAdvisorId, fn ($q) => $q->whereNull('advisor_id'));
    }

    protected function resolveLead(): void
    {
        $this->lead = $this->getLeadBaseQuery()->first();
    }

    protected function getAdvisorBaseQuery(int $onlineStatus, array $roles)
    {
        return User::select('users.id as user_id')
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
            ->activeUser()
            ->orderBy('la.last_allocated', 'asc');
    }

    public function fetchAvailableAdvisor()
    {
        info(self::class." - fetchAvailableAdvisor: {$this->isReAssignment} - {$this->teamId} - {$this->uuid}");

        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $this->isReAssignment) {
            $statusOrder[] = UserStatusEnum::UNAVAILABLE;
        }

        foreach ($statusOrder as $status) {
            info(self::class." - trying to get advisors with current status as {$status} for lead uuid: {$this->uuid}");
            $eligibleUser = $this->fetchAdvisor($status);

            if ($eligibleUser) {
                info(self::class." - eligible user found with status: {$status} and user id : {$eligibleUser->user_id} and uuid: {$this->uuid}");

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
            info(self::class." - assignLead: Going to Assign Advisor to Lead: {$this->lead->uuid}");
            $previousAssignmentType = $this->lead->assignment_type;
            $previousUserId = $this->lead->advisor_id;
            $this->lead->advisor_id = $advisor->id;
            $this->lead->assignment_type = $assignmentType;
            $quoteBatch = QuoteBatches::latest()->first();
            $this->lead->quote_batch_id = $quoteBatch->id;
            $this->lead->save();
            info(self::class." - Lead Id {$this->lead->uuid} assigned to advisor : {$advisor->name} Quote Batch with ID: {$quoteBatch->id} and Name: {$quoteBatch->name}");

            $previousAdvisorAssignedDate = $this->updateQuoteDetail($this->lead->id);

            if ($this->lead->source != LeadSourceEnum::REFERRAL) {
                info(self::class.' - lead source is not referral so about to update allocation record');
                if ($assignmentType == AssignmentTypeEnum::SYSTEM_ASSIGNED) {
                    $this->addAllocationCounts($advisor->id, $this->getQuoteTypeId());
                } else {
                    $this->adjustAllocationCounts($advisor->id, $this->lead, $previousUserId, $previousAdvisorAssignedDate, $previousAssignmentType, $this->getQuoteTypeId());
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
        }
    }

    private function updateQuoteDetail()
    {
        info(self::class." - about to update quote detail record for : {$this->lead->uuid}");

        $oldAdvisorAssignedDate = $this->lead->quoteDetail?->advisor_assigned_date ?? '';

        $this->upsertQuoteDetail($this->lead->id, $this->quoteType->detailModel(), $this->quoteType->model()->getForeignKey());

        return $oldAdvisorAssignedDate;
    }
}
