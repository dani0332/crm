<?php

namespace App\Jobs;

use App\Enums\AssignmentTypeEnum;
use App\Models\HealthQuote;
use App\Services\HealthAllocationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReAssignHealthLeadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 2;
    public $timeout = 15;
    public $backoff = 30;
    private $healthAllocationService;
    private $advisorId;

    public function __construct(HealthAllocationService $healthAllocationService, $advisorId)
    {
        $this->healthAllocationService = $healthAllocationService;
        $this->advisorId = $advisorId;
    }

    public function handle()
    {
        info('-------- Reassignment health job started at : '.now().' ---------');
        if (! $this->healthAllocationService->shouldProceed() && ! now()->isWeekend()) {
            info('Reassignment job is not proceeding as per business timings');

            return false;
        }

        $leads = $this->fetchLead();

        if (count($leads) == 0) {
            info('No health lead found or either lead is not under assignment criteria');
            info('-------- Reassignment health job ended at : '.now().' ---------');

            return false; // when lead is not on criteria or not found
        }

        foreach ($leads as $lead) {
            info('-------- Reassignment of lead : '.$lead->uuid.' started ---------');

            if ($lead->isAllocationInProgress()) {
                info("Allocation is already started for lead: {$lead->uuid} at {$lead->allocation_started_at}");

                continue;
            }

            $lead->startAllocation();

            $this->assignTeamBasedOnPrices($lead);

            if (! $lead->health_team_type) {
                info('No health team found against lead : '.$lead->uuid);

                $lead->endAllocation();

                continue;
            }

            $advisor = $this->fetchAvailableAdvisor($lead->health_team_type, $lead);

            if (! $advisor) {
                info('No advisors found against lead : '.$lead->uuid);

                $lead->endAllocation();

                continue;
            }

            $this->assignLead($lead, $advisor); // Assign the lead to the advisor

            $lead->endAllocation();
            info('-------- Reassignment of lead : '.$lead->uuid.' ended ---------');
        }
        info('-------- Reassignment health job ended at : '.now().' ---------');
    }

    private function fetchLead()
    {
        return $this->healthAllocationService->fetchReAssignmentLead($this->advisorId);
    }

    private function assignTeamBasedOnPrices($lead)
    {
        $this->healthAllocationService->assignTeamBasedOnPrices($lead);
    }

    private function fetchAvailableAdvisor($leadTeam, HealthQuote $lead)
    {
        return $this->healthAllocationService->fetchAvailableAdvisor($leadTeam, true, $lead);
    }

    private function assignLead($lead, $advisor)
    {
        DB::beginTransaction();
        try {
            $this->healthAllocationService->assignLead($lead, $advisor, AssignmentTypeEnum::SYSTEM_REASSIGNED);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $this->healthAllocationService->endBuyLeadProcessing();
            Log::error($e->getMessage());
        }
    }

    public function middleware()
    {
        // Lock Job in storage session only if advisor id is not zero
        if ($this->advisorId) {
            return [(new WithoutOverlapping($this->advisorId))->dontRelease()];
        }

        return [];
    }
}
