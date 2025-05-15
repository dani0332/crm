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
use Closure;
use Illuminate\Support\Facades\DB;
use Sammyjo20\LaravelHaystack\Models\Haystack;

class AssignLeadPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        dd('here');

        DB::beginTransaction();

        try {
            $this->assign(function ($isReAssignment, $previousAdvisorId) {
                $this->sendIntroEmail($isReAssignment, $previousAdvisorId);
            });

            $this->allocationRequest->markAsAllocated();

            DB::commit();
        } catch (\Exception $e) {
            $this->allocationRequest->markAsFailed();

            $this->allocationRequest->endBuyLeadProcessing();

            DB::rollBack();
            LoggerService::error($e->getMessage(), exception: $e);

            $this->throw('Lead allocation failed', self::SERVER_ERROR);
        }

        return $next($request);
    }

    private function sendIntroEmail($isReAssignment, $previousAdvisorId)
    {
        Haystack::build()
            ->addJob(new GetQuotePlansJob($this->lead))
            ->then(function () use ($previousAdvisorId, $isReAssignment) {
                if (in_array($this->lead->health_team_type, [HealthTeamType::EBP, HealthTeamType::RM_NB, HealthTeamType::RM_SPEED, HealthTeamType::PCP])) {
                    IntroEmailJob::dispatch(
                        quoteTypeCode::Health,
                        'Capi',
                        $this->lead->uuid,
                        'send-rm-intro-email',
                        $previousAdvisorId,
                        $isReAssignment
                    )->delay(now()->addSeconds(15));
                }
            })->dispatch();
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
