<?php

namespace App\Strategies\Allocations;

use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadSourceEnum;
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
    abstract protected function resolveLead(bool $overrideAdvisorId = false): void;
    abstract protected function fetchAdvisor(int $onlineStatus);

    protected $lead;

    public function __construct(public QuoteTypes $quoteType, public string $uuid, public $teamId = false) {}

    private function createResponse(int $advisorId, string $message, int $status, ?int $tierId = null): array
    {
        $resp = [
            'advisorId' => $advisorId,
            'message' => $message,
            'tierId' => $tierId,
            'status' => $status,
        ];

        if (! $tierId) {
            unset($resp['tierId']);
        }

        return $resp;
    }

    public function executeSteps(bool $overrideAdvisorId = false)
    {
        $response = [
            'advisorId' => 0,
            'message' => '',
            'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ];

        try {
            info(self::class." - executeSteps: Allocation Started for UUID : {$this->uuid}");
            $this->resolveLead($overrideAdvisorId);

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

    private function fetchAvailableAdvisor($isReassignmentJob = false)
    {
        info(self::class." - fetchAvailableAdvisor: {$isReassignmentJob} - {$this->teamId} - {$this->uuid}");

        $statusOrder = [
            UserStatusEnum::ONLINE,
            UserStatusEnum::OFFLINE,
        ];

        if (! $isReassignmentJob) {
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
            $assignmentType = AssignmentTypeEnum::SYSTEM_ASSIGNED;
            info(self::class." - assignLead: Going to Assign Advisor to Lead: {$this->lead->uuid}");
            $previousAssignmentType = $this->lead->assignment_type;
            $previousUserId = $this->lead->advisor_id;
            $this->lead->advisor_id = $advisor->id;
            $this->lead->assignment_type = $assignmentType;
            $this->lead->quote_updated_at = now();
            $quoteBatch = QuoteBatches::latest()->first();
            $this->lead->quote_batch_id = $quoteBatch->id;
            $this->lead->save();
            info(self::class." - Lead Id {$this->lead->uuid} assigned to advisor : {$advisor->name} Quote Batch with ID: {$quoteBatch->id} and Name: {$quoteBatch->name}");

            $previousAdvisorAssignedDate = $this->updateQuoteDetail($this->lead->id);

            if ($this->lead->source != LeadSourceEnum::REFERRAL) {
                info(self::class.' - lead source is not referral so about to update allocation record');
                $assignmentType == AssignmentTypeEnum::SYSTEM_ASSIGNED ? $this->addAllocationCounts($advisor->id, $this->quoteType->id()) : $this->adjustAllocationCounts($advisor->id, $this->lead, $previousUserId, $previousAdvisorAssignedDate, $previousAssignmentType, $this->quoteType->id());
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
