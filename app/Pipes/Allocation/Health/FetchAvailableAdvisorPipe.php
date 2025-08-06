<?php

namespace App\Pipes\Allocation\Health;

use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\BuyLeadRequest;
use App\Models\Team;
use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\HealthEmailService;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
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

        if ($this->allocationRequest->get('skipAdvisorEligibilityFetch', false)) {
            LoggerService::info(self::class.' - Skipping advisor eligibility fetch');

            return $next($request);
        }

        $advisor = $this->fetchAvailableAdvisor();

        if (! $advisor) {
            LoggerService::warning('No advisors found');

            if ($this->allocationRequest->get('skipAdvisorEligibilityFetch', false)) {
                LoggerService::info(self::class.' - Second call after reset - throwing exception');
                $this->allocationRequest->markAsFailed();

                // Check if we need to send an apply now email
                if ($this->lead->isApplicationPending() && ! $this->lead->isApplyNowEmailSent() && Carbon::parse($this->lead->quote_status_date)->lessThanOrEqualTo(now()->subMinutes(10))) {
                    LoggerService::info("Sending Apply Now Email as it's been 10 minutes since quote status was marked as application pending");
                    app(HealthEmailService::class)->initiateApplyNowEmail($this->lead);
                }

                $this->throw('Advisor not found', self::OK);
            } else {
                LoggerService::info(self::class.' - First call - continuing to ResetNationalityConfigPipe');

                return $next($request);
            }
        }

        $this->allocationRequest->setAdvisor($advisor);

        $this->verifyIfAdvisorIsSameAsPreviousAdvisor($advisor);

        return $next($request);
    }

    protected function fetchAvailableAdvisor()
    {
        $advisor = null;

        if ($this->lead->isBuyLeadApplicable($this->allocationRequest->isSIC()) && ($this->lead->isValueLead() || $this->lead->isVolumeLead())) {
            $advisor = $this->fetchAdvisorByType('getBLAdvisorByStatus');
        }

        if (empty($advisor) || ! $advisor) {
            $advisor = $this->fetchAdvisorByType('getAdvisorByStatus');
        }

        return $advisor;
    }

    protected function fetchAdvisorByType(string $methodName)
    {
        $statusOrder = $this->getOnlineStatusesInOrder();

        $parentTeamId = Team::where('name', TeamNameEnum::HEALTH)->active()->where('type', TeamTypeEnum::PRODUCT)->value('id');

        $teamId = Team::where('name', $this->lead->health_team_type)->active()->where('type', TeamTypeEnum::TEAM)->where('parent_team_id', $parentTeamId)->value('id');

        foreach ($statusOrder as $status) {
            LoggerService::info(self::class."::fetchAdvisorByType - trying to get advisors for team: {$this->lead->health_team_type} with current status as {$status}");

            $eligibleUser = $this->{$methodName}($status, $teamId);

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
            $this->allocationRequest->isSIC(),
            $this->lead->isValueLead()
        );

        $advisor = $this->getAdvisorBaseQuery($status, $teamId, [RolesEnum::EBPAdvisor, RolesEnum::RMAdvisor], true)
            ->when($this->lead->isValueLead(), function ($q) {
                $q->isValueUser($this->allocationRequest->getQuoteType());
            }, function ($q) {
                $q->isVolumeUser($this->allocationRequest->getQuoteType());
            })
            ->whereIn('users.id', $buyLeadRequestedUserIds)
            ->logRawSql()
            ->first();

        if ($advisor) {
            LoggerService::info(self::class."::getBLAdvisorByStatus - found Advisor: {$advisor->user_id} for team: {$this->lead->health_team_type} with current status as {$status}");

            $buyLeadRequest = BuyLeadRequest::getRequest(
                $this->allocationRequest->getQuoteType(),
                $this->allocationRequest->isSIC(),
                $advisor->user_id,
                $this->lead->isValueLead()
            );

            if ($buyLeadRequest) {
                $this->allocationRequest->setBuyLeadRequest($buyLeadRequest);

                $buyLeadRequest->startProcessing();

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

        $advisor = $this->getAdvisorBaseQuery($onlineStatus, $teamId, [RolesEnum::EBPAdvisor, RolesEnum::RMAdvisor])
            ->where('la.normal_allocation_enabled', true)
            ->logRawSql()
            ->first();

        return $advisor ? User::find($advisor->user_id) : null;
    }
}
