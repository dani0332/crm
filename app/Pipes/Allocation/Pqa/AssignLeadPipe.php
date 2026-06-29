<?php

declare(strict_types=1);

namespace App\Pipes\Allocation\Pqa;

use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use App\Services\PqaAllocation\PqaLeadAllocationService;
use Closure;
use Illuminate\Support\Facades\DB;

class AssignLeadPipe extends BasePqaAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        LoggerService::info(self::class.' - assigning Pre Qualification Advisor to Group Medical lead');

        $this->setRequest($request);

        $advisor = $this->allocationRequest->getAdvisor();

        if (! $advisor || ! $this->lead) {
            $this->allocationRequest->markAsFailed();
            $this->throw('Pre Qualification Advisor not found', self::NOT_FOUND);
        }

        DB::transaction(function () use ($advisor) {
            $previousPqaId = $this->lead->pq_advisor_id !== null ? (int) $this->lead->pq_advisor_id : null;

            if ($previousPqaId === $advisor->id) {
                $this->allocationRequest->markAsSameAdvisor();

                return;
            }

            $this->lead->pq_advisor_id = $advisor->id;
            $this->lead->pq_assigned_at = now();
            $this->lead->save();

            if ($previousPqaId !== null) {
                app(PqaLeadAllocationService::class)->adjustCountsAfterPqaReassignment(
                    $previousPqaId,
                    (int) $this->getPqaQuoteTypeId(),
                );
            }

            LoggerService::info(self::class.' - Updating PQA config', extra: [
                'advisorId' => $advisor->id,
                'quoteTypeId' => $this->getPqaQuoteTypeId(),
            ]);

            app(PqaLeadAllocationService::class)->updatePqaAllocationConfig(
                $advisor->id,
                (int) $this->getPqaQuoteTypeId(),
            );

            $this->allocationRequest->markAsAllocated();
        });

        LoggerService::info(self::class.' - PQA assigned successfully', extra: [
            'leadUuid' => $this->lead->uuid,
            'pqAdvisorId' => $advisor->id,
        ]);

        return $next($request);
    }
}
