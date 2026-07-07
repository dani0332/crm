<?php

namespace App\Strategies\Allocations;

use App\Enums\LeadSourceEnum;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Facades\AllocationConfigurer;
use App\Models\DttRevival;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use App\Models\Team;
use App\Models\User;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HomeAllocation extends BaseAllocation
{
    private const SHORT_TERM_CODE = 'short_term';

    protected function fetchAdvisor(int $onlineStatus)
    {
        $parentAdvisorId = $this->getParentLeadAdvisorId();
        $revivalFlags = $this->getParentLeadRevivalFlags();

        LoggerService::info('HomeAllocation: fetchAdvisor started', extra: [
            'leadId' => $this->lead->id ?? null,
            'uuid' => $this->lead->uuid ?? null,
            'onlineStatus' => $onlineStatus,
            'source' => $this->lead->source ?? null,
            'parentAdvisorId' => $parentAdvisorId,
            'isRevived' => $revivalFlags['is_revived'],
            'isAnnualRevived' => $revivalFlags['is_annual_revived'],
        ]);

        // corp advisors logic needs to be implemented once its approved from business
        // If UUID is provided and the property is rented, fetch Corp Team advisors
        if ($this->lead->uuid !== null && $this->isPropertyRentedForHolidayHome($this->lead->uuid)) {
            LoggerService::info('HomeAllocation: Property is rented for holiday home, using Corp Team logic', extra: [
                'leadId' => $this->lead->id ?? null,
                'uuid' => $this->lead->uuid ?? null,
            ]);
            // return $this->fetchCorpLineAdvisor($onlineStatus);
            LoggerService::info('HomeAllocation: Corp advisors logic not implemented, returning null');

            return null; // Corp advisors logic is not implemented yet so returning null and lead should be unassigned in this case
        }

        // Annual revival: try to assign the same advisor as the original enquiry lead first.
        // Determined by is_annual_revived on the parent lead (source is Revival_replied/paid at allocation time).
        // Falls back to the standard pool if that advisor is unavailable.
        if ($parentAdvisorId !== null && $revivalFlags['is_annual_revived']) {
            LoggerService::info('HomeAllocation: Annual revival — attempting to assign original enquiry advisor', extra: [
                'parentAdvisorId' => $parentAdvisorId,
            ]);

            $advisor = $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::HomeAdvisor])
                ->where('users.id', $parentAdvisorId)
                ->logRawSql()
                ->first();

            if ($advisor) {
                LoggerService::info('HomeAllocation: Annual revival — original advisor available, assigning', extra: [
                    'advisorId' => $advisor->user_id,
                ]);

                return $advisor;
            }

            LoggerService::info('HomeAllocation: Annual revival — original advisor unavailable, falling back to standard pool');
        }

        // Short revival: exclude the original enquiry advisor so a different advisor is assigned.
        // Determined by is_revived on the parent lead (source is Revival_replied/paid at allocation time).
        $excludedAdvisorId = ($parentAdvisorId !== null && $revivalFlags['is_revived'])
            ? $parentAdvisorId
            : null;

        if ($excludedAdvisorId !== null) {
            LoggerService::info('HomeAllocation: Short revival — excluding original enquiry advisor from pool', extra: [
                'excludedAdvisorId' => $excludedAdvisorId,
            ]);
        }

        // EA_IMCRM: bypass email-based routing — permission gate in getAdvisorBaseQuery handles filtering
        if ($this->lead?->source === LeadSourceEnum::EA_IMCRM) {
            LoggerService::info('HomeAllocation: EA_IMCRM lead detected, bypassing standard advisor fetch and using permission gate');

            return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::HomeAdvisor])->first();
        }

        // Default behavior: Fetch value or volume advisors (Home Advisors)
        LoggerService::info('HomeAllocation: Fetching Home Advisor');
        $advisor = $this->fetchHomeAdvisor($onlineStatus, $excludedAdvisorId);
        LoggerService::info('HomeAllocation: Home Advisor fetch result', extra: ['advisorFound' => ! empty($advisor), 'advisor' => $advisor]);

        return $advisor;
    }

    private function getHomeQuoteData(string $uuid): ?HomeQuote
    {
        LoggerService::startQuoteLogging($uuid);
        LoggerService::info('HomeAllocation: Fetching HomeQuote data');
        $homeQuote = HomeQuote::with('subArea:id,text')
            ->where('uuid', $uuid)
            ->first();
        LoggerService::info('HomeAllocation: HomeQuote data result', extra: [
            'found' => ! is_null($homeQuote),
            'subArea' => $homeQuote?->subArea?->text ?? null,
        ]);

        return $homeQuote;
    }

    /**
     * Fetch emails of advisors belonging to the Corp Team.
     */
    protected function getCorpTeamAdvisorEmails(): array
    {
        LoggerService::info('HomeAllocation: Fetching Corp Team advisor emails');
        $corpTeamId = getTeamId(TeamNameEnum::MOTOR_COOPERATE_RENEWALS);

        if (! $corpTeamId) {
            LoggerService::warning('HomeAllocation: Corp Team not found');

            return [];
        }

        $emails = DB::table('user_team')
            ->join('users', 'user_team.user_id', '=', 'users.id')
            ->where('user_team.team_id', $corpTeamId)
            ->where('users.is_active', 1)
            ->pluck('users.email')
            ->toArray();

        LoggerService::info('HomeAllocation: Corp Team advisor emails fetched', extra: ['count' => count($emails), 'emails' => $emails]);

        return $emails;
    }

    /**
     * Fetch emails of advisors based on the lead type (value or volume).
     */
    protected function getAdvisorEmailsBasedOnLeadType(): array
    {
        LoggerService::info('HomeAllocation: Getting advisor emails based on lead type');

        $homeQuote = $this->getHomeQuoteData($this->lead->uuid);
        if (! $homeQuote) {
            LoggerService::warning('HomeAllocation: No home quote data found');

            return [];
        }
        $emails = app(RuleService::class)->getEmailsByLeadSource($this->lead->source, $this->lead->quote_type_id);
        if (count($emails) > 0) {
            LoggerService::info(self::class.": Found advisor emails from rules | quote Ref-ID: {$this->lead->uuid} ", extra: ['emails' => $emails]);

            return $emails;
        }

        $this->skipRuleUsers = true;

        $advisorIds = AllocationConfigurer::getHomeEligibleAdvisorIds($homeQuote);

        return User::whereIn('id', $advisorIds)->pluck('email')->toArray();
    }

    /**
     * Fetch a Home Advisor (value or volume) based on the online status.
     *
     * @return mixed
     */
    protected function fetchHomeAdvisor(int $onlineStatus, ?int $excludedAdvisorId = null)
    {
        LoggerService::info('HomeAllocation: Fetching Home Advisor', extra: ['onlineStatus' => $onlineStatus, 'leadId' => $this->lead->id ?? null]);

        $emails = $this->getAdvisorEmailsBasedOnLeadType();
        if (empty($emails)) {
            Log::warning('HomeAllocation: No eligible advisors found');

            return null; // No eligible advisors found
        }

        LoggerService::info('HomeAllocation: Getting advisor from base query', extra: ['emailsCount' => count($emails), 'roleId' => RolesEnum::HomeAdvisor]);
        $advisor = $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::HomeAdvisor])
            ->whereIn('users.email', $emails)
            ->when($excludedAdvisorId !== null, fn ($q) => $q->where('users.id', '!=', $excludedAdvisorId))
            ->logRawSql()
            ->first();

        LoggerService::info('HomeAllocation: Home Advisor fetch result', extra: [
            'advisorFound' => ! is_null($advisor),
            'advisorId' => $advisor->id ?? null,
            'advisorName' => $advisor->name ?? null,
            'advisorEmail' => $advisor->email ?? null,
        ]);

        return $advisor;
    }

    /**
     * Fetch a Corp Line Advisor based on the online status.
     *
     * @return mixed
     */
    protected function fetchCorpLineAdvisor(int $onlineStatus)
    {
        LoggerService::info('HomeAllocation: Fetching Corp Line Advisor', extra: ['onlineStatus' => $onlineStatus, 'leadId' => $this->lead->id ?? null]);

        $corpTeamEmails = $this->getCorpTeamAdvisorEmails();
        if (empty($corpTeamEmails)) {
            LoggerService::warning('HomeAllocation: No Corp Team emails found');

            return null;
        }

        LoggerService::info('HomeAllocation: Getting Corp Line Advisor from base query', extra: ['emailsCount' => count($corpTeamEmails), 'roleId' => RolesEnum::CorpLineAdvisor]);
        $advisor = $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CorpLineAdvisor])
            ->whereIn('users.email', $corpTeamEmails)
            ->first();

        LoggerService::info('HomeAllocation: Corp Line Advisor fetch result', extra: [
            'advisorFound' => ! is_null($advisor),
            'advisorId' => $advisor->id ?? null,
            'advisorName' => $advisor->name ?? null,
            'advisorEmail' => $advisor->email ?? null,
        ]);

        return $advisor;
    }

    /**
     * Check if the property is rented for a holiday home.
     */
    private function isPropertyRentedForHolidayHome(string $uuid): bool
    {
        LoggerService::info('HomeAllocation: Checking if property is rented for holiday home', extra: ['uuid' => $uuid]);

        $quoteRequest = HomeQuote::where('uuid', $uuid)
            ->with(['rangeLookup' => function ($query) {
                $query->where('code', self::SHORT_TERM_CODE);
            }])
            ->first();

        if (! $quoteRequest || ! $quoteRequest->rangeLookup) {
            LoggerService::info('HomeAllocation: No matching record found for holiday home check', extra: ['uuid' => $uuid]);

            return false;
        }

        // Compare the owner_occupancy_type_id with the range lookup's id
        $result = $quoteRequest->owner_occupancy_type_id === $quoteRequest->rangeLookup->id;
        LoggerService::info('HomeAllocation: Property rented for holiday home check result', extra: [
            'isRented' => $result,
            'owner_occupancy_type_id' => $quoteRequest->owner_occupancy_type_id ?? null,
            'rangeLookupId' => $quoteRequest->rangeLookup->id ?? null,
        ]);

        return $result;
    }

    private function getParentLeadRevivalFlags(): array
    {
        $revivalLead = DttRevival::where('uuid', $this->lead->uuid)->select('previous_quote_id')->first();

        if (! $revivalLead) {
            return ['is_revived' => false, 'is_annual_revived' => false];
        }

        $parentLead = PersonalQuote::where('id', $revivalLead->previous_quote_id)
            ->select('is_revived', 'is_annual_revived')
            ->first();

        return [
            'is_revived' => (bool) ($parentLead?->is_revived ?? false),
            'is_annual_revived' => (bool) ($parentLead?->is_annual_revived ?? false),
        ];
    }

    /**
     * Revival leads from Short, Annual, Replied, or Revival Paid sources bypass the duplicate
     * lead check entirely and are allocated directly via the standard Short/Annual advisor pool.
     * This prevents the system from re-assigning them to the original duplicate's advisor.
     */
    protected function shouldHandleDuplicateLead(): bool
    {
        if ($this->isRevivalLeadWithBypassSource()) {
            LoggerService::info('HomeAllocation: Bypassing duplicate lead check — revival lead with bypass source', extra: [
                'source' => $this->lead->source,
                'uuid' => $this->lead->uuid,
            ]);

            return false;
        }

        return parent::shouldHandleDuplicateLead();
    }

    protected function isRevivalLeadWithBypassSource(): bool
    {
        $bypassSources = [
            LeadSourceEnum::REVIVAL_SHORT,
            LeadSourceEnum::REVIVAL_ANNUAL,
            LeadSourceEnum::REVIVAL_REPLIED,
            LeadSourceEnum::REVIVAL_PAID,
        ];

        if (! in_array($this->lead->source, $bypassSources)) {
            return false;
        }

        return DttRevival::where('uuid', $this->lead->uuid)->exists();
    }
}
