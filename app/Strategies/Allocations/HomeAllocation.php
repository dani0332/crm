<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Models\HomeQuote;
use App\Models\RangeLookup;
use App\Models\Team;
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
        Log::info('HomeAllocation: fetchAdvisor started', [
            'leadId' => $this->lead->id ?? null,
            'uuid' => $this->lead->uuid ?? null,
            'onlineStatus' => $onlineStatus,
        ]);

        // corp advisors logic needs to be implemented once its approved from business
        // If UUID is provided and the property is rented, fetch Corp Team advisors
        if ($this->lead->uuid !== null && $this->isPropertyRentedForHolidayHome($this->lead->uuid)) {
            Log::info('HomeAllocation: Property is rented for holiday home, using Corp Team logic', [
                'leadId' => $this->lead->id ?? null,
                'uuid' => $this->lead->uuid ?? null,
            ]);
            // return $this->fetchCorpLineAdvisor($onlineStatus);
            Log::info('HomeAllocation: Corp advisors logic not implemented, returning empty array');

            return []; // Corp advisors logic is not implemented yet so returning empty array and lead should be unassigned in this case
        }

        // Default behavior: Fetch value or volume advisors (Home Advisors)
        Log::info('HomeAllocation: Fetching Home Advisor');
        $advisor = $this->fetchHomeAdvisor($onlineStatus);
        Log::info('HomeAllocation: Home Advisor fetch result', ['advisorFound' => ! empty($advisor), 'advisor' => $advisor]);

        return $advisor;
    }

    private function getHomeQuoteData(string $uuid): ?HomeQuote
    {
        Log::info('HomeAllocation: Fetching HomeQuote data', ['uuid' => $uuid]);
        $homeQuote = HomeQuote::with('subArea:id,text')
            ->where('uuid', $uuid)
            ->first();
        Log::info('HomeAllocation: HomeQuote data result', [
            'found' => ! is_null($homeQuote),
            'subArea' => $homeQuote?->subArea?->text ?? null,
        ]);

        return $homeQuote;
    }

    private function matchesTargetLocations(string $address): bool
    {
        $targetKeywords = ['arabian ranches', 'palm jumeriah'];
        Log::info('HomeAllocation: Checking if address matches target locations', ['address' => $address, 'targetKeywords' => $targetKeywords]);

        foreach ($targetKeywords as $keyword) {
            if (Str::contains($address, $keyword)) {
                Log::info('HomeAllocation: Address matches target location', ['address' => $address, 'matchedKeyword' => $keyword]);

                return true;
            }
        }

        Log::info('HomeAllocation: Address does not match any target location', ['address' => $address]);

        return false;
    }

    private function isValueLocation(HomeQuote $lead): bool
    {
        Log::info('HomeAllocation: Checking if location is a value location', ['uuid' => $this->lead->uuid ?? null]);

        $address = Str::lower($lead?->subArea?->text ?? '');
        Log::info('HomeAllocation: Processing address for value location check', ['address' => $address]);

        $isValueLocation = $this->matchesTargetLocations($address);
        Log::info('HomeAllocation: Value location check result', ['isValueLocation' => $isValueLocation]);

        return $isValueLocation;
    }

    public function isValueLead(HomeQuote $lead): bool
    {
        Log::info('HomeAllocation: Checking if lead is a value lead', ['leadId' => $this->lead->id ?? null]);
        $hasHighValueAssets = $this->hasHighValueAssets($lead);
        $isValueLocation = $this->isValueLocation($lead);
        $result = $hasHighValueAssets || $isValueLocation;

        Log::info('HomeAllocation: Value lead check result', [
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
            Log::info('HomeAllocation: Range lookup is null, returning false');

            return false;
        }

        $min = $rangeLookup->min_value ?? 0;
        $max = $rangeLookup->max_value ?? 0;

        Log::info('HomeAllocation: Checking if value is in range', [
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

        Log::info('HomeAllocation: High value assets check result', [
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
        Log::info('HomeAllocation: Fetching Corp Team advisor emails');
        $corpTeamId = Team::where('name', TeamNameEnum::MOTOR_COOPERATE_RENEWALS)->value('id');

        if (! $corpTeamId) {
            Log::warning('HomeAllocation: Corp Team not found');

            return [];
        }

        $emails = DB::table('user_team')
            ->join('users', 'user_team.user_id', '=', 'users.id')
            ->where('user_team.team_id', $corpTeamId)
            ->where('users.is_active', 1)
            ->pluck('users.email')
            ->toArray();

        Log::info('HomeAllocation: Corp Team advisor emails fetched', ['count' => count($emails), 'emails' => $emails]);

        return $emails;
    }

    /**
     * Fetch emails of advisors based on the lead type (value or volume).
     */
    protected function getAdvisorEmailsBasedOnLeadType(): array
    {
        Log::info('HomeAllocation: Getting advisor emails based on lead type', ['leadId' => $this->lead->id ?? null]);

        $homeQuote = $this->getHomeQuoteData($this->lead->uuid);

        if (! $homeQuote) {
            Log::warning('HomeAllocation: No home quote data found');

            return [];
        }

        if ($this->isValueLead($homeQuote)) {
            Log::info('HomeAllocation: Lead is a value lead, fetching value advisors');

            return $this->getAdvisorEmails(ApplicationStorageEnums::HOME_VALUE_ADVISORS);
        } elseif ($this->isVolumeLead($homeQuote)) {
            Log::info('HomeAllocation: Lead is a volume lead, fetching volume advisors');

            return $this->getAdvisorEmails(ApplicationStorageEnums::HOME_VOLUME_ADVISORS);
        }

        Log::warning('HomeAllocation: Lead is neither value nor volume, returning empty array');

        return [];
    }

    /**
     * Fetch a Home Advisor (value or volume) based on the online status.
     *
     * @return mixed
     */
    protected function fetchHomeAdvisor(int $onlineStatus)
    {
        Log::info('HomeAllocation: Fetching Home Advisor', ['onlineStatus' => $onlineStatus, 'leadId' => $this->lead->id ?? null]);

        $emails = $this->getAdvisorEmailsBasedOnLeadType();
        if (empty($emails)) {
            Log::warning('HomeAllocation: No eligible advisors found');

            return null; // No eligible advisors found
        }

        Log::info('HomeAllocation: Getting advisor from base query', ['emailsCount' => count($emails), 'roleId' => RolesEnum::HomeAdvisor]);
        $advisor = $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::HomeAdvisor])
            ->whereIn('users.email', $emails)
            ->first();

        Log::info('HomeAllocation: Home Advisor fetch result', [
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
        Log::info('HomeAllocation: Fetching Corp Line Advisor', ['onlineStatus' => $onlineStatus, 'leadId' => $this->lead->id ?? null]);

        $corpTeamEmails = $this->getCorpTeamAdvisorEmails();
        if (empty($corpTeamEmails)) {
            Log::warning('HomeAllocation: No Corp Team emails found');

            return null;
        }

        Log::info('HomeAllocation: Getting Corp Line Advisor from base query', ['emailsCount' => count($corpTeamEmails), 'roleId' => RolesEnum::CorpLineAdvisor]);
        $advisor = $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CorpLineAdvisor])
            ->whereIn('users.email', $corpTeamEmails)
            ->first();

        Log::info('HomeAllocation: Corp Line Advisor fetch result', [
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
        Log::info('HomeAllocation: Checking if property is rented for holiday home', ['uuid' => $uuid]);

        $quoteRequest = HomeQuote::where('uuid', $uuid)
            ->with(['rangeLookup' => function ($query) {
                $query->where('code', self::SHORT_TERM_CODE);
            }])
            ->first();

        if (! $quoteRequest || ! $quoteRequest->rangeLookup) {
            Log::info('HomeAllocation: No matching record found for holiday home check', ['uuid' => $uuid]);

            return false;
        }

        // Compare the owner_occupancy_type_id with the range lookup's id
        $result = $quoteRequest->owner_occupancy_type_id === $quoteRequest->rangeLookup->id;
        Log::info('HomeAllocation: Property rented for holiday home check result', [
            'isRented' => $result,
            'owner_occupancy_type_id' => $quoteRequest->owner_occupancy_type_id ?? null,
            'rangeLookupId' => $quoteRequest->rangeLookup->id ?? null,
        ]);

        return $result;
    }
}
