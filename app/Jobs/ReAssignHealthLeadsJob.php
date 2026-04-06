<?php

namespace App\Jobs;

use App\Enums\AssignmentTypeEnum;
use App\Enums\HealthRoutingSourceEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Models\HealthQuote;
use App\Pipes\Allocation\Common\ApplyRuleExclusionPipe;
use App\Pipes\Allocation\Common\FetchLeadPipe;
use App\Pipes\Allocation\Common\MakeResponsePipe;
use App\Pipes\Allocation\Common\ResetNationalityConfigPipe;
use App\Pipes\Allocation\Common\ValidateNationalityConfigPipe;
use App\Pipes\Allocation\Common\VerifyAlreadyInProgressAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Pipes\Allocation\Health\AssignLeadPipe;
use App\Pipes\Allocation\Health\AssignTeamPipe;
use App\Pipes\Allocation\Health\FetchAvailableAdvisorPipe;
use App\Services\AllocationService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Pipeline;

class ReAssignHealthLeadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 2;
    public $timeout = 15;
    public $backoff = 30;
    private $advisorId;
    private AllocationService $allocationService;

    public function __construct($advisorId)
    {
        $this->allocationService = app(AllocationService::class);
        $this->advisorId = $advisorId;
    }

    public function handle()
    {
        LoggerService::info('-------- Reassignment health job started ---------');
        if (! $this->shouldProceed() || now()->isWeekend()) {
            LoggerService::info('Reassignment job is not proceeding as per business timings or today is weekend', extra: [
                'shouldProceed' => $this->shouldProceed(),
                'isWeekend' => now()->isWeekend(),
                'advisorId' => $this->advisorId,
            ]);

            return false;
        }

        $leads = $this->fetchReAssignmentLeads();

        if (count($leads) == 0) {
            LoggerService::info('No health lead found or either lead is not under assignment criteria');
            LoggerService::info('-------- Reassignment health job ended ---------');

            return false; // when lead is not on criteria or not found
        }

        foreach ($leads as $lead) {
            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);

            LoggerService::info('-------- Reassignment started ---------');

            $allocationRequest = new AllocationRequest(
                quoteType: QuoteTypes::HEALTH,
                quoteUUID: $lead->uuid,
                overrideAdvisorId: true,
                isReassignmentJob: true,
                reAssigFromAdvisorId: $this->advisorId,
                assignmentType: AssignmentTypeEnum::SYSTEM_REASSIGNED,
                source: HealthRoutingSourceEnum::REASSIGNMENT
            );

            try {
                Pipeline::send($allocationRequest)->through([
                    FetchLeadPipe::class,
                    VerifyAlreadyInProgressAllocationPipe::class,
                    AssignTeamPipe::class,
                    ValidateNationalityConfigPipe::class,
                    ApplyRuleExclusionPipe::class,
                    FetchAvailableAdvisorPipe::class,
                    ResetNationalityConfigPipe::class,
                    FetchAvailableAdvisorPipe::class,
                    AssignLeadPipe::class,
                    MakeResponsePipe::class,
                ])->thenReturn();
            } catch (Exception $e) {
                app(AllocationService::class)->resolveAllocationResponse($allocationRequest, $e);
            }

            LoggerService::info('-------- Reassignment ended ---------');
        }

        LoggerService::endLogging();
        LoggerService::info('-------- Reassignment health job ended at : '.now().' ---------');
    }

    private function shouldProceed(): bool
    {
        return $this->allocationService->shouldProceedWithReAllocation('constants.HEALTH_LEAD_ALLOCATION_MASTER_SWITCH');
    }

    private function fetchReAssignmentLeads()
    {
        $advisorId = $this->advisorId;

        $from = now()->subDay()->setTime(12, 30)->format(config('constants.DB_DATE_FORMAT_MATCH'));
        LoggerService::info('leads will be picked up in reassignment from : '.$from);

        $leads = HealthQuote::whereBetween('created_at', [$from, now()])
            ->whereNotNull('health_quote_request.price_starting_from')
            ->where('health_quote_request.is_error_email_sent', false)
            ->whereIn('quote_status_id', [QuoteStatusEnum::Quoted])
            ->where('source', '!=', LeadSourceEnum::IMCRM);
        if ($advisorId != 0) {
            $leads->where('advisor_id', $advisorId);
        } else {
            // If advisor ID is not provided, get unavailable advisors and filter leads by them
            $advisors = $this->allocationService->getUnavailableAdvisor();
            if (count($advisors) > 0) {
                $advisorIds = $advisors->pluck('user_id');
                LoggerService::info('Inside reassignment general run');
                $leads->whereIn('advisor_id', $advisorIds);
            }
        }

        return $leads->get();
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
