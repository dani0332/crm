<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Models\HomeQuote;
use App\Models\RangeLookup;
use App\Models\Team;
use App\Services\Logger\LoggerService;
use App\Services\RuleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HomeAllocation extends BaseAllocation
{
    private const CONTENTS_VALUE_THRESHOLD = 50001;
    private const PERSONAL_BELONGINGS_VALUE_THRESHOLD = 50001;
    private const BUILDING_VALUE_THRESHOLD = 3000000;
    private const SHORT_TERM_CODE = 'short_term';

    protected function fetchAdvisor(int $onlineStatus)
    {
        LoggerService::info('HomeAllocation: fetchAdvisor started', extra: [
            'leadId' => $this->lead->id ?? null,
            'uuid' => $this->lead->uuid ?? null,
            'onlineStatus' => $onlineStatus,
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

        // Default behavior: Fetch value or volume advisors (Home Advisors)
        LoggerService::info('HomeAllocation: Fetching Home Advisor');
        $advisor = $this->fetchHomeAdvisor($onlineStatus);
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

    private function matchesTargetLocations(string $address): bool
    {
        $targetKeywords = ['arabian ranches', 'palm jumeriah'];
        LoggerService::info('HomeAllocation: Checking if address matches target locations', extra: ['address' => $address, 'targetKeywords' => $targetKeywords]);

        foreach ($targetKeywords as $keyword) {
            if (Str::contains($address, $keyword)) {
                LoggerService::info('HomeAllocation: Address matches target location', extra: ['address' => $address, 'matchedKeyword' => $keyword]);

                return true;
            }
        }

        LoggerService::info('HomeAllocation: Address does not match any target location', extra: ['address' => $address]);

        return false;
    }

    private function isValueLocation(HomeQuote $lead): bool
    {
        LoggerService::startQuoteLogging($lead->uuid);
        LoggerService::info('HomeAllocation: Checking if location is a value location');

        $address = Str::lower($lead?->subArea?->text ?? '');
        LoggerService::info('HomeAllocation: Processing address for value location check', extra: ['address' => $address]);

        $isValueLocation = $this->matchesTargetLocations($address);
        LoggerService::info('HomeAllocation: Value location check result', extra: ['isValueLocation' => $isValueLocation]);

        return $isValueLocation;
    }

    public function isValueLead(HomeQuote $lead): bool
    {
        LoggerService::info('HomeAllocation: Checking if lead is a value lead', ['leadId' => $lead->id ?? null]);
        $hasHighValueAssets = $this->hasHighValueAssets($lead);
        $isValueLocation = $this->isValueLocation($lead);
        $result = $hasHighValueAssets || $isValueLocation;

        LoggerService::info('HomeAllocation: Value lead check result', [
            'isValueLead' => $result,
            'hasHighValueAssets' => $hasHighValueAssets,
            'isValueLocation' => $isValueLocation,
        ]);

        return $result;
    }

    public function isVolumeLead(HomeQuote $lead): bool
    {
        return ! $this->isValueLead($lead);
    }

    private function inRange($thresholdValue, ?RangeLookup $rangeLookup): bool
    {
        if (is_null($rangeLookup)) {
            LoggerService::info('HomeAllocation: Range lookup is null, returning false');

            return false;
        }

        $min = $rangeLookup->min_value ?? 0;
        $max = $rangeLookup->max_value ?? 0;

        LoggerService::info('HomeAllocation: Checking if value is in range', [
            'thresholdValue' => $thresholdValue,
            'min' => $min,
            'max' => $max,
        ]);

        return $min >= $thresholdValue;
    }

    private function hasHighValueAssets(HomeQuote $lead): bool
    {
        $result = ($lead->hasContents() && $this->inRange(self::CONTENTS_VALUE_THRESHOLD, $lead->contents)) ||
            ($lead->hasPersonalBelongings() && $this->inRange(self::PERSONAL_BELONGINGS_VALUE_THRESHOLD, $lead->personalBelongings)) ||
            ($lead->hasBuilding() && $lead->building_value > self::BUILDING_VALUE_THRESHOLD);

        LoggerService::info('HomeAllocation: High value assets check result', extra: [
            'hasHighValueAssets' => $result,
            'hasContents' => $lead->hasContents(),
            'hasPersonalBelongings' => $lead->hasPersonalBelongings(),
            'hasBuilding' => $lead->hasBuilding(),
        ]);

        return $result;
    }

    /**
     * Fetch emails of advisors belonging to the Corp Team.
     */
    protected function getCorpTeamAdvisorEmails(): array
    {
        LoggerService::info('HomeAllocation: Fetching Corp Team advisor emails');
        $corpTeamId = Team::where('name', TeamNameEnum::MOTOR_COOPERATE_RENEWALS)->value('id');

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

        if ($this->isValueLead($homeQuote)) {
            LoggerService::info('HomeAllocation: Lead is a value lead, fetching value advisors');

            return $this->getAdvisorEmails(ApplicationStorageEnums::HOME_VALUE_ADVISORS);
        } elseif ($this->isVolumeLead($homeQuote)) {
            LoggerService::info('HomeAllocation: Lead is a volume lead, fetching volume advisors');

            return $this->getAdvisorEmails(ApplicationStorageEnums::HOME_VOLUME_ADVISORS);
        }

        LoggerService::warning('HomeAllocation: Lead is neither value nor volume, returning empty array');

        return [];
    }

    /**
     * Fetch a Home Advisor (value or volume) based on the online status.
     *
     * @return mixed
     */
    protected function fetchHomeAdvisor(int $onlineStatus)
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
}
