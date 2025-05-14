<?php

namespace App\Pipes\Allocation\Health;

use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Models\BuyLeadRequest;
use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use App\Services\HealthEmailService;
use Closure;

class FetchAvailableAdvisorPipe extends BaseAllocationPipe
{
    protected bool $isBuyLeadAdvisor = false;
    protected $buyLeadRequest = null;

    /**
     * Handle the incoming request.
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $advisor = $this->fetchAvailableAdvisor();

        if (!$advisor) {
            LoggerService::warning('No advisors found');

            $this->allocationRequest->markAsFailed();

            // Check if we need to send an apply now email
            if ($this->lead->isApplicationPending() &&
                !$this->lead->isApplyNowEmailSent() &&
                Carbon::parse($this->lead->quote_status_date)->lessThanOrEqualTo(now()->subMinutes(10))) {
                LoggerService::info("Sending Apply Now Email as it's been 10 minutes since quote status was marked as application pending");
                app(HealthEmailService::class)->initiateApplyNowEmail($this->lead);
            }

            $this->throw('Advisor not found', self::NOT_FOUND);
        }

        if ($advisor->id == $this->lead->advisor_id) {
            LoggerService::info('Advisor is same as previous advisor. Skipping for now.');
            $this->lead->endAllocation();

            if ($this->isBuyLeadAdvisor && $this->buyLeadRequest) {
                $this->buyLeadRequest->endProcessing();
            }

            $this->throw('Advisor is same as previous advisor', self::OK);
        }

        $this->allocationRequest->setAdvisor($advisor);

        return $next($request);
    }

    protected function fetchAvailableAdvisor()
    {
        // Reset Buy Lead Advisor flag and Buy Lead Request object
        $this->resetProps();

        $advisor = null;

        if ($this->lead->isBuyLeadApplicable($this->lead->isSIC($this->allocationRequest->getQuoteType())) &&
            ($this->lead->isValueLead() || $this->lead->isVolumeLead())) {
            $advisor = $this->fetchAdvisorByType('getBLAdvisorByStatus');
        }

        if (empty($advisor) || !$advisor) {
            $advisor = $this->fetchAdvisorByType('getAdvisorByStatus');
        }

        return $advisor;
    }

    protected function resetProps()
    {
        $this->isBuyLeadAdvisor = false;
        $this->buyLeadRequest = null;
    }

    protected function fetchAdvisorByType(string $methodName)
    {
        $statusOrder = $this->getOnlineStatusesInOrder();

        foreach ($statusOrder as $status) {
            LoggerService::info(self::class."::fetchAdvisorByType - trying to get advisors for team: {$this->lead->health_team_type} with current status as {$status}");

            $eligibleUser = $this->{$methodName}($status, null);

            if ($eligibleUser) {
                LoggerService::info(self::class."::fetchAdvisorByType - eligible user found for team: {$this->lead->health_team_type} with status: {$status} and user id: {$eligibleUser->id}");

                return $eligibleUser;
            }
        }

        return null;
    }

    protected function getBLAdvisorByStatus($status, $teamId = null)
    {
        LoggerService::info(self::class."::getBLAdvisorByStatus - trying to get advisors for team: {$this->lead->health_team_type} with current status as {$status}");

        $buyLeadRequestedUserIds = BuyLeadRequest::getRequestedUserIds(
            $this->allocationRequest->getQuoteType(),
            $this->lead->isSIC($this->allocationRequest->getQuoteType()),
            $this->lead->isValueLead()
        );

        $teamName = $this->lead->health_team_type;
        $teamId = User::join('user_team as ut', 'ut.user_id', '=', 'users.id')
            ->join('teams as t', 't.id', '=', 'ut.team_id')
            ->where('t.name', $teamName)
            ->value('t.id');

        $advisor = $this->getAdvisorBaseQuery($status, $teamId, [RolesEnum::EBPAdvisor, RolesEnum::RMAdvisor])
            ->join('user_team as ut', 'ut.user_id', '=', 'users.id')
            ->join('teams as t', 't.id', '=', 'ut.team_id')
            ->where('t.name', $teamName)
            ->when($this->lead->isValueLead(), function ($q) {
                $q->isValueUser($this->allocationRequest->getQuoteType());
            }, function ($q) {
                $q->isVolumeUser($this->allocationRequest->getQuoteType());
            })
            ->whereIn('users.id', $buyLeadRequestedUserIds)
            ->where('la.buy_lead_status', true)
            ->where(function ($query) {
                $query->whereRaw('la.buy_lead_allocation_count < la.buy_lead_max_capacity')
                    ->orWhere('la.buy_lead_max_capacity', -1);
            })
            ->orderBy('la.buy_lead_last_allocated', 'asc')
            ->first();

        if ($advisor) {
            LoggerService::info(self::class."::getBLAdvisorByStatus - found Advisor: {$advisor->user_id} for team: {$this->lead->health_team_type} with current status as {$status}");

            $this->buyLeadRequest = BuyLeadRequest::getRequest(
                $this->allocationRequest->getQuoteType(),
                $this->lead->isSIC($this->allocationRequest->getQuoteType()),
                $advisor->user_id,
                $this->lead->isValueLead()
            );

            if ($this->buyLeadRequest) {
                $this->buyLeadRequest->startProcessing();
                $this->isBuyLeadAdvisor = true;
                return User::find($advisor->user_id);
            } else {
                LoggerService::warning(self::class."::getBLAdvisorByStatus - Advisor found but Buy Lead Request not found for Advisor: {$advisor->user_id}");
                return null;
            }
        }

        return null;
    }

    protected function getAdvisorByStatus($onlineStatus, $teamId = null)
    {
        LoggerService::info(self::class."::getAdvisorByStatus - trying to get advisors for team: {$this->lead->health_team_type} with current status as {$onlineStatus}");

        $teamName = $this->lead->health_team_type;
        $teamId = User::join('user_team as ut', 'ut.user_id', '=', 'users.id')
            ->join('teams as t', 't.id', '=', 'ut.team_id')
            ->where('t.name', $teamName)
            ->value('t.id');

        $advisor = $this->getAdvisorBaseQuery($onlineStatus, $teamId, [RolesEnum::EBPAdvisor, RolesEnum::RMAdvisor])
            ->join('user_team as ut', 'ut.user_id', '=', 'users.id')
            ->join('teams as t', 't.id', '=', 'ut.team_id')
            ->where('t.name', $teamName)
            ->where('la.normal_allocation_enabled', true)
            ->where(function ($query) {
                $query->whereRaw('la.allocation_count < la.max_capacity')
                    ->orWhere('la.max_capacity', -1);
            })
            ->orderBy('la.last_allocated', 'asc')
            ->first();

        return $advisor ? User::find($advisor->user_id) : null;
    }
}
