<?php

namespace App\Pipes\Allocation\Health;

use App\Enums\HealthTeamType;
use App\Mail\HealthAssignmentIssueEmail;
use App\Models\Team;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;
use Illuminate\Support\Facades\Mail;

class AssignTeamPipe extends BaseAllocationPipe
{
    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $this->assignTeamBasedOnPrices();

        if (! $this->lead->health_team_type) {
            LoggerService::warning('No health team found');
            $this->throw('No health team found', self::NOT_FOUND);
        }

        $this->lead->refresh();

        $this->allocationRequest->setLead($this->lead);

        return $next($request);
    }

    protected function assignTeamBasedOnPrices()
    {
        LoggerService::info('Inside assignTeamBasedOnPrices');

        $priceStartingFrom = $this->determinePriceStartingFrom();

        if ($priceStartingFrom === null) {
            LoggerService::warning('No team found - price starting from is null');
            $this->lead->is_error_email_sent = true;
            $this->lead->save();
            Mail::send(new HealthAssignmentIssueEmail($this->lead->code, $priceStartingFrom));

            return;
        }

        $healthTeam = Team::where('allocation_threshold_enabled', true)
            ->where('min_price', '<=', $priceStartingFrom)
            ->where('max_price', '>=', $priceStartingFrom)
            ->first();

        if ($healthTeam) {
            LoggerService::info("Filtered team is: {$healthTeam->name}");
            $this->lead->health_team_type = ($healthTeam->name === HealthTeamType::PCP && $this->lead->members->count() > 2)
                ? HealthTeamType::RM_NB
                : $healthTeam->name;
        } else {
            LoggerService::warning('No team found for the given price range');
            $this->lead->is_error_email_sent = true;
            Mail::send(new HealthAssignmentIssueEmail($this->lead->code, $priceStartingFrom));
        }

        $this->lead->save();
    }

    protected function determinePriceStartingFrom()
    {
        if ($this->lead->isSIC($this->allocationRequest->getQuoteType())) {
            $price = ! empty($this->lead->plan_id) && ! empty($this->lead->premium)
                ? $this->lead->premium
                : $this->lead->price_starting_from;

            $planStatus = ! empty($this->lead->plan_id) ? 'found' : 'not found';
            LoggerService::info("Plan {$planStatus} with plan id: {$this->lead->plan_id} | premium: {$this->lead->premium}");
        } else {
            $price = $this->lead->price_starting_from;
            LoggerService::info("No SIC lead | plan id: {$this->lead->plan_id} | premium: {$this->lead->premium}");
        }

        return $price;
    }
}
