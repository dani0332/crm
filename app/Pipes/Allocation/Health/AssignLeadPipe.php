<?php

namespace App\Pipes\Allocation\Health;

use App\Enums\HealthTeamType;
use App\Enums\quoteTypeCode;
use App\Jobs\GetQuotePlansJob;
use App\Jobs\IntroEmailJob;
use App\Models\HealthQuoteRequestDetail;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Support\Facades\Bus;

class AssignLeadPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $this->assign(function ($isReAssignment, $previousAdvisorId) {
            $this->sendIntroEmail($isReAssignment, $previousAdvisorId);
        });

        return $next($request);
    }

    private function sendIntroEmail($isReAssignment, $previousAdvisorId)
    {
        // Ignore sending email in local environment
        if (app()->environment('local')) {
            return;
        }

        try {
            $lead = $this->lead;

            Bus::batch(
                [
                    new GetQuotePlansJob($lead),
                ]
            )
                ->then(function () use ($lead, $isReAssignment, $previousAdvisorId) {
                    if (in_array($lead->health_team_type, [HealthTeamType::EBP, HealthTeamType::RM_NB, HealthTeamType::RM_SPEED, HealthTeamType::PCP])) {
                        IntroEmailJob::dispatch(
                            quoteTypeCode::Health,
                            'Capi',
                            $lead->uuid,
                            'send-rm-intro-email',
                            $previousAdvisorId,
                            $isReAssignment
                        )->delay(Carbon::now()->addSeconds(15));
                    }
                })->dispatch();
        } catch (Exception $e) {
            LoggerService::error($e->getMessage(), exception: $e);
        }
    }

    protected function updateQuoteDetail()
    {
        LoggerService::info('about to update health quote detail record');

        $quoteDetail = HealthQuoteRequestDetail::where('health_quote_request_id', $this->lead->id)->first();
        $oldAdvisorAssignedDate = $quoteDetail->advisor_assigned_date ?? '';
        $this->upsertQuoteDetail($this->lead->id, HealthQuoteRequestDetail::class, 'health_quote_request_id');

        return $oldAdvisorAssignedDate;
    }
}
