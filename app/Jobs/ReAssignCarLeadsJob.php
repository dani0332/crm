<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Enums\AssignmentTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\TiersEnum;
use App\Models\CarQuote;
use App\Models\Tier;
use App\Pipes\Allocation\Car\ApplyRuleExclusionPipe;
use App\Pipes\Allocation\Car\AssignLeadPipe;
use App\Pipes\Allocation\Car\EvaluateTierPipe;
use App\Pipes\Allocation\Car\FetchEligibleAdvisorsPipe;
use App\Pipes\Allocation\Car\FetchTierUsersPipe;
use App\Pipes\Allocation\Car\FinalizeEligibleAdvisorPipe;
use App\Pipes\Allocation\Common\FetchLeadPipe;
use App\Pipes\Allocation\Common\MakeResponsePipe;
use App\Pipes\Allocation\Common\ResetNationalityConfigPipe;
use App\Pipes\Allocation\Common\ValidateNationalityConfigPipe;
use App\Pipes\Allocation\Common\VerifyAlreadyInProgressAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\AllocationService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Pipeline;

class ReAssignCarLeadsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 2;
    public $timeout = 15;
    public $backoff = 30;
    private $advisorId;

    public function __construct($advisorId)
    {
        $this->advisorId = $advisorId;
    }

    public function handle(AllocationService $allocationService)
    {
        LoggerService::info('-------- Reassignment car job started ---------');

        if (! $this->shouldProceed() && ! now()->isWeekend()) {
            LoggerService::info('Reassignment job is not proceeding as per business timings');

            return false;
        }

        // Fetch the leads to process, including deferred leads if needed
        $leads = $this->fetchLeadsForReAssignment();
        if (count($leads) == 0) {
            LoggerService::info('No car lead found or either lead is not under assignment criteria');
            LoggerService::info('-------- Reassignment car job ended ---------');

            return false; // when lead is not on criteria or not found
        }

        foreach ($leads as $lead) {
            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);

            LoggerService::info('--------------- ReAssignment processing ---------------');

            $allocationRequest = new AllocationRequest(
                quoteType: QuoteTypes::CAR,
                quoteUUID: $lead->uuid,
                overrideAdvisorId: true,
                isReassignmentJob: true,
                reAssigFromAdvisorId: $this->advisorId,
                assignmentType: AssignmentTypeEnum::SYSTEM_REASSIGNED
            );

            try {
                Pipeline::send($allocationRequest)->through([
                    FetchLeadPipe::class,
                    VerifyAlreadyInProgressAllocationPipe::class,
                    EvaluateTierPipe::class,
                    ValidateNationalityConfigPipe::class,
                    FetchTierUsersPipe::class,
                    ApplyRuleExclusionPipe::class,
                    FetchEligibleAdvisorsPipe::class,
                    ResetNationalityConfigPipe::class,
                    ApplyRuleExclusionPipe::class,
                    FetchEligibleAdvisorsPipe::class,
                    FinalizeEligibleAdvisorPipe::class,
                    AssignLeadPipe::class,
                    MakeResponsePipe::class,
                ])->thenReturn();
            } catch (Exception $e) {
                $allocationService->resolveAllocationResponse($allocationRequest, $e);
            }

            LoggerService::info('--------------- ReAssignment processing ended ---------------');
        }

        LoggerService::endLogging();
        LoggerService::info('-------- Reassignment car job ended at : '.now().' ---------');
    }

    private function shouldProceed(): bool
    {
        return app(AllocationService::class)->shouldProceedWithReAllocation('constants.CAR_LEAD_ALLOCATION_MASTER_SWITCH');
    }

    public function fetchLeadsForReAssignment()
    {
        $advisorId = $this->advisorId;

        // Calculate the start date for lead retrieval
        $from = now()->subDay()->setTime(12, 30)->format(config('constants.DB_DATE_FORMAT_MATCH'));
        LoggerService::info("Leads will be picked up in reassignment from : {$from}");

        // Check if Dubai Now exclusion should be applied
        $shouldIncludeDubaiNow = getAppStorageValueByKey(ApplicationStorageEnums::APPLY_DUBAI_NOW_EXCLUSION) == 1;

        // List of exempted lead sources
        $exemptedLeadSources = [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY, LeadSourceEnum::REVIVAL];

        // Add Dubai Now to exempted lead sources if $shouldIncludeDubaiNow is true
        if ($shouldIncludeDubaiNow) {
            $exemptedLeadSources[] = LeadSourceEnum::DUBAI_NOW;
        }

        // Get the Tier R
        $tierR = Tier::where('name', TiersEnum::TIER_R)->where('is_active', 1)->first();

        // Query to fetch leads
        $leads = CarQuote::whereBetween('created_at', [$from, now()])
            ->whereNotIn('source', $exemptedLeadSources)
            ->where('quote_status_id', QuoteStatusEnum::NewLead)
            ->where('is_renewal_tier_email_sent', 0);

        // Filter by advisor ID if provided , which mean reassignment is going to run for a single advisor
        if ($advisorId != 0) {
            $leads->where('advisor_id', $advisorId);
            LoggerService::info('Inside reassignment single run and selected advisor is: '.$advisorId);
        } else {
            // If advisor ID is not provided, get unavailable advisors and filter leads by them
            $advisors = app(AllocationService::class)->getUnavailableAdvisor();
            if (count($advisors) > 0) {
                $advisorIds = $advisors->pluck('user_id');
                LoggerService::info('Inside reassignment general run');
                $leads->whereIn('advisor_id', $advisorIds);
            }
        }

        // Filter leads by tier (if applicable)
        if (! empty($tierR)) {
            $leads->where('tier_id', '!=', $tierR->id);
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
