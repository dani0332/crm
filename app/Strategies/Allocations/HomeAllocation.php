<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Models\HomeQuote;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HomeAllocation extends BaseAllocation
{
    private const CONTENTS_VALUE_THRESHOLD = 50000;
    private const PERSONAL_BELONGINGS_VALUE_THRESHOLD = 50000;
    private const BUILDING_VALUE_THRESHOLD = 3000000;
    private const SHORT_TERM_CODE = 'short_term';

    protected function fetchAdvisor(int $onlineStatus)
    {
        Log::info('HomeAllocation: fetchAdvisor started', [
            'leadId' => $this->lead->id ?? null,
            'uuid' => $this->lead->uuid ?? null,
            'onlineStatus' => $onlineStatus
        ]);

        // corp advisors logic needs to be implemented once its approved from business
        // If UUID is provided and the property is rented, fetch Corp Team advisors
        if ($this->lead->uuid !== null && $this->isPropertyRentedForHolidayHome($this->lead->uuid)) {
            Log::info('HomeAllocation: Property is rented for holiday home, using Corp Team logic', [
                'leadId' => $this->lead->id ?? null,
                'uuid' => $this->lead->uuid ?? null
            ]);
            // return $this->fetchCorpLineAdvisor($onlineStatus);
            Log::info('HomeAllocation: Corp advisors logic not implemented, returning empty array');
            return []; // Corp advisors logic is not implemented yet so returning empty array and lead should be unassigned in this case
        }

        // Default behavior: Fetch value or volume advisors (Home Advisors)
        Log::info('HomeAllocation: Fetching Home Advisor');
        $advisor = $this->fetchHomeAdvisor($onlineStatus);
        Log::info('HomeAllocation: Home Advisor fetch result', ['advisorFound' => !empty($advisor), 'advisor' => $advisor]);
        return $advisor;
    }

    private function getValueAdvisors()
    {
        $advisors = explode(',', getAppStorageValueByKey(ApplicationStorageEnums::HOME_VALUE_ADVISORS, useCache: true));
        Log::info('HomeAllocation: Value Advisors fetched', ['count' => count($advisors), 'advisors' => $advisors]);
        return $advisors;
    }

    private function getVolumeAdvisors()
    {
        $advisors = explode(',', getAppStorageValueByKey(ApplicationStorageEnums::HOME_VOLUME_ADVISORS, useCache: true));
        Log::info('HomeAllocation: Volume Advisors fetched', ['count' => count($advisors), 'advisors' => $advisors]);
        return $advisors;
    }

    private function getHomeQuoteData(string $uuid): ?HomeQuote
    {
        Log::info('HomeAllocation: Fetching HomeQuote data', ['uuid' => $uuid]);
        $homeQuote = HomeQuote::with('subArea:id,text')
            ->where('uuid', $uuid)
            ->first();
        Log::info('HomeAllocation: HomeQuote data result', [
            'found' => !is_null($homeQuote),
            'subArea' => $homeQuote?->subArea?->text ?? null
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

    private function isValueLocation(): bool
    {
        Log::info('HomeAllocation: Checking if location is a value location', ['uuid' => $this->lead->uuid ?? null]);
        $homeQuote = $this->getHomeQuoteData($this->lead->uuid);

        $address = Str::lower($homeQuote?->subArea?->text ?? '');
        Log::info('HomeAllocation: Processing address for value location check', ['address' => $address]);

        $isValueLocation = $this->matchesTargetLocations($address);
        Log::info('HomeAllocation: Value location check result', ['isValueLocation' => $isValueLocation]);
        return $isValueLocation;
    }

    public function isValueLead(): bool
    {
        Log::info('HomeAllocation: Checking if lead is a value lead', ['leadId' => $this->lead->id ?? null]);
        $hasHighValueAssets = $this->hasHighValueAssets();
        $isValueLocation = $this->isValueLocation();
        $result = $hasHighValueAssets || $isValueLocation;
        
        Log::info('HomeAllocation: Value lead check result', [
            'isValueLead' => $result,
            'hasHighValueAssets' => $hasHighValueAssets,
            'isValueLocation' => $isValueLocation
        ]);
        
        return $result;
    }

    public function isVolumeLead(): bool
    {
        Log::info('HomeAllocation: Checking if lead is a volume lead', ['leadId' => $this->lead->id ?? null]);
        $hasLowValueAssets = $this->hasLowValueAssets();
        $isNotValueLocation = !$this->isValueLocation();
        $result = $hasLowValueAssets || $isNotValueLocation;
        
        Log::info('HomeAllocation: Volume lead check result', [
            'isVolumeLead' => $result,
            'hasLowValueAssets' => $hasLowValueAssets,
            'isNotValueLocation' => $isNotValueLocation
        ]);
        
        return $result;
    }

    private function hasHighValueAssets(): bool
    {
        Log::info('HomeAllocation: Checking for high value assets', [
            'hasContents' => $this->lead->has_contents ?? false,
            'contentsValue' => $this->lead->contents_aed ?? 0,
            'hasPersonalBelongings' => $this->lead->has_personal_belongings ?? false,
            'personalBelongingsValue' => $this->lead->personal_belongings_aed ?? 0,
            'hasBuilding' => $this->lead->has_building ?? false,
            'buildingValue' => $this->lead->building_aed ?? 0,
            'thresholds' => [
                'contents' => self::CONTENTS_VALUE_THRESHOLD,
                'personalBelongings' => self::PERSONAL_BELONGINGS_VALUE_THRESHOLD,
                'building' => self::BUILDING_VALUE_THRESHOLD
            ]
        ]);

        $result = ($this->lead->has_contents && $this->lead->contents_aed > self::CONTENTS_VALUE_THRESHOLD) ||
            ($this->lead->has_personal_belongings && $this->lead->personal_belongings_aed > self::PERSONAL_BELONGINGS_VALUE_THRESHOLD) ||
            ($this->lead->has_building && $this->lead->building_aed > self::BUILDING_VALUE_THRESHOLD);
            
        Log::info('HomeAllocation: High value assets check result', ['hasHighValueAssets' => $result]);
        return $result;
    }

    private function hasLowValueAssets(): bool
    {
        Log::info('HomeAllocation: Checking for low value assets', [
            'hasContents' => $this->lead->has_contents ?? false,
            'contentsValue' => $this->lead->contents_aed ?? 0,
            'hasPersonalBelongings' => $this->lead->has_personal_belongings ?? false,
            'personalBelongingsValue' => $this->lead->personal_belongings_aed ?? 0,
            'hasBuilding' => $this->lead->has_building ?? false,
            'buildingValue' => $this->lead->building_aed ?? 0,
            'thresholds' => [
                'contents' => self::CONTENTS_VALUE_THRESHOLD,
                'personalBelongings' => self::PERSONAL_BELONGINGS_VALUE_THRESHOLD,
                'building' => self::BUILDING_VALUE_THRESHOLD
            ]
        ]);

        $result = ($this->lead->has_contents && $this->lead->contents_aed <= self::CONTENTS_VALUE_THRESHOLD) ||
            ($this->lead->has_personal_belongings && $this->lead->personal_belongings_aed <= self::PERSONAL_BELONGINGS_VALUE_THRESHOLD) ||
            ($this->lead->has_building && $this->lead->building_aed <= self::BUILDING_VALUE_THRESHOLD);
            
        Log::info('HomeAllocation: Low value assets check result', ['hasLowValueAssets' => $result]);
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

    private function getHomePropertyRentedAttribute(string $uuid): HomeQuote
    {
        Log::info('HomeAllocation: Fetching home property rented attribute', ['uuid' => $uuid]);
        $homeQuote = HomeQuote::where('uuid', $uuid)->select('owner_occupancy_type_id')->first();
        Log::info('HomeAllocation: Home property rented attribute result', [
            'found' => !is_null($homeQuote),
            'owner_occupancy_type_id' => $homeQuote->owner_occupancy_type_id ?? null
        ]);
        return $homeQuote;
    }

    /**
     * Fetch emails of advisors based on the lead type (value or volume).
     */
    protected function getAdvisorEmailsBasedOnLeadType(): array
    {
        Log::info('HomeAllocation: Getting advisor emails based on lead type', ['leadId' => $this->lead->id ?? null]);
        
        if ($this->isValueLead()) {
            Log::info('HomeAllocation: Lead is a value lead, fetching value advisors');
            $advisors = $this->getValueAdvisors();
            return $advisors;
        } elseif ($this->isVolumeLead()) {
            Log::info('HomeAllocation: Lead is a volume lead, fetching volume advisors');
            $advisors = $this->getVolumeAdvisors();
            return $advisors;
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
            'advisorFound' => !is_null($advisor),
            'advisorId' => $advisor->id ?? null,
            'advisorName' => $advisor->name ?? null,
            'advisorEmail' => $advisor->email ?? null
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
            'advisorFound' => !is_null($advisor),
            'advisorId' => $advisor->id ?? null,
            'advisorName' => $advisor->name ?? null,
            'advisorEmail' => $advisor->email ?? null
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
            'rangeLookupId' => $quoteRequest->rangeLookup->id ?? null
        ]);
        
        return $result;
    }
}
