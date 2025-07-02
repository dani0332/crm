<?php

namespace App\Pipes\Allocation\Travel;

use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Models\TravelQuote;
use App\Models\User;
use App\Pipes\Allocation\Common\BaseAllocationPipe;
use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;

class FetchAvailableAdvisorPipe extends BaseAllocationPipe
{
    // Default team ID (no specific team assignment) for travel team
    private const DEFAULT_TEAM_ID = false;

    /**
     * Handle the incoming request.
     *
     * @param  mixed  $passable
     * @return mixed
     */
    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $advisor = $this->fetchAvailableAdvisor();

        if (! $advisor) {
            LoggerService::info(self::class.' - No advisor found');

            $this->allocationRequest->markAsFailed();

            $this->throw('Advisor not found', self::OK);
        }

        $this->allocationRequest->setAdvisor($advisor);

        $this->verifyIfAdvisorIsSameAsPreviousAdvisor($advisor);

        return $next($request);
    }

    private function fetchAvailableAdvisor()
    {
        $teamId = $this->evaluateTeamId($this->lead);

        return $this->findAvailableAdvisor($teamId);
    }

    protected function getAdvisorByStatus($onlineStatus, $teamId)
    {
        if ($this->allocationRequest->get('isCHSAdvisor')) {
            LoggerService::info(self::class.' - getAdvisorByStatus: CHS Advisor is required');

            return User::select('users.id as user_id')->chs()->first();
        }

        if ($this->allocationRequest->get('isSICAdvisor')) {
            LoggerService::info(self::class.' - getAdvisorByStatus: SIC Advisor is required');

            $teamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
        }

        return $this->getAdvisorBaseQuery($onlineStatus, $teamId, [RolesEnum::TravelAdvisor])
            ->when(! $teamId, function ($q) {
                $sicUnassistedTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);
                if ($sicUnassistedTeamId) {
                    $q->whereNotIn('users.id', fn ($query) => $query->select('user_id')->from('user_team')->where('team_id', $sicUnassistedTeamId));
                }
            })
            ->when($this->allocationRequest->isSIC(), function ($q) {
                $q->where('la.is_hardstop', true); // fetch users only with hardstop as true as they are eligible for allocation
            })
            ->logRawSql()
            ->first();
    }

    /**
     * Evaluates and sets the appropriate team ID for the travel quote lead
     * based on business rules and lead properties.
     *
     * @param  TravelQuote  $lead  The lead to evaluate
     */
    private function evaluateTeamId(TravelQuote $lead)
    {
        $isSIC = $this->checkLeadMethod($lead, 'isSIC', [$this->allocationRequest->getQuoteType()]);
        $isAIG = $this->checkLeadMethod($lead, 'isAIG', [$this->allocationRequest->getQuoteType()]);
        $isPaymentAuthorizedOnly = $this->checkLeadMethod($lead, 'isPaymentAuthorizedOnly');
        $isPaymentLinkRequested = $this->checkLeadMethod($lead, 'isPaymentLinkRequested');
        $isLeadFromInstantAlfred = $this->checkLeadMethod($lead, 'isLeadFromInstantAlfred');

        $sicUnassistedTeamId = getTeamId(TeamNameEnum::SIC_UNASSISTED);

        $isAIGWithInstantAlfred = $isAIG && $isLeadFromInstantAlfred;

        $teamId = null;
        $reason = '';

        if ($isAIGWithInstantAlfred) {
            // Rule 1: AIG leads from Instant Alfred go to default team
            $teamId = self::DEFAULT_TEAM_ID;
            $reason = 'AIG and Lead from Instant Alfred';
        } elseif ($isAIG && ($isPaymentAuthorizedOnly || $isPaymentLinkRequested)) {
            // Rule 2: AIG leads with payment authorized or link requested
            $teamId = $sicUnassistedTeamId;
            $reason = 'AIG with payment authorized or link requested';
        } elseif ($isSIC && $isPaymentAuthorizedOnly && ! $isPaymentLinkRequested) {
            // Rule 3: SIC lead with payment authorized only (not payment link requested)
            $teamId = $sicUnassistedTeamId;
            $reason = 'SIC travel lead with payment authorized only';
        } elseif ($isSIC && $isPaymentLinkRequested) {
            // Rule 4: SIC lead with payment link requested
            $teamId = null;
            $reason = 'SIC travel lead with payment link requested';
        } elseif (! $isSIC && ! $isAIG && ($isPaymentAuthorizedOnly || $isPaymentLinkRequested)) {
            // Rule 5: Non-SIC, Non-AIG leads with payment authorized or link requested
            $teamId = $sicUnassistedTeamId;
            $reason = 'Non-SIC, Non-AIG lead with payment authorized or link requested';
        } else {
            // Rule 6: Default - all other leads have no specific team
            $teamId = self::DEFAULT_TEAM_ID;
            $reason = 'Default case - no specific team';
        }

        LoggerService::debug('Team assigned for Travel Allocation', extra: [
            'reason' => $reason,
            'teamId' => $teamId,
            'isSIC' => $isSIC,
            'isAIG' => $isAIG,
            'isPaymentAuthorizedOnly' => $isPaymentAuthorizedOnly,
            'isPaymentLinkRequested' => $isPaymentLinkRequested,
            'isLeadFromInstantAlfred' => $isLeadFromInstantAlfred,
        ]);

        return $teamId;
    }

    /**
     * Helper method to safely check if a method exists and call it with parameters
     *
     * @param  TravelQuote  $lead  The lead object
     * @param  string  $methodName  The method name to check and call
     * @param  array  $params  Optional parameters to pass to the method
     * @return bool The result of the method call or false if method doesn't exist
     */
    private function checkLeadMethod(TravelQuote $lead, string $methodName, array $params = []): bool
    {
        if (! method_exists($lead, $methodName)) {
            return false;
        }

        return $lead->{$methodName}(...$params);
    }
}
