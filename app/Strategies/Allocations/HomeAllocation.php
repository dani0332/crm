<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Models\HomeQuote;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HomeAllocation extends BaseAllocation
{
    private const CONTENTS_VALUE_THRESHOLD = 100000;
    private const PERSONAL_BELONGINGS_VALUE_THRESHOLD = 100000;
    private const BUILDING_VALUE_THRESHOLD = 5000000;

    protected function fetchAdvisor(int $onlineStatus, ?string $uuid = null)
    {
        // corp advisors logic needs to be implemented once its approved from business
        // If UUID is provided and the property is rented, fetch Corp Team advisors
        if ($uuid !== null && $this->isPropertyRentedForHolidayHome($uuid)) {
            // return $this->fetchCorpLineAdvisor($onlineStatus);
            return []; // Corp advisors logic is not implemented yet so returning empty array and lead should be unassigned in this case
        }

        // Default behavior: Fetch value or volume advisors (Home Advisors)
        return $this->fetchHomeAdvisor($onlineStatus);
    }

    private function getValueAdvisors()
    {
        return cache()->remember('home_value_advisors', now()->addHour(), function () {
            return explode(',', getAppStorageValueByKey(ApplicationStorageEnums::HOME_VALUE_ADVISORS));
        });
    }

    private function getVolumeAdvisors()
    {
        return cache()->remember('home_volume_advisors', now()->addHour(), function () {
            return explode(',', getAppStorageValueByKey(ApplicationStorageEnums::HOME_VOLUME_ADVISORS));
        });
    }

    private function getHomeQuoteData(string $uuid): ?HomeQuote
    {
        return HomeQuote::with('subArea:id,text')
            ->where('uuid', $uuid)
            ->first();
    }

    private function matchesTargetLocations(string $address): bool
    {
        $targetKeywords = ['arabian ranches', 'palm jumeriah'];

        foreach ($targetKeywords as $keyword) {
            if (Str::contains($address, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function isValueLocation(): bool
    {
        $homeQuote = $this->getHomeQuoteData($this->lead->uuid);

        $address = Str::lower($homeQuote?->subArea?->text ?? '');

        return $this->matchesTargetLocations($address);
    }

    public function isValueLead(): bool
    {
        return $this->hasHighValueAssets() || $this->isValueLocation();
    }

    public function isVolumeLead(): bool
    {
        return $this->hasLowValueAssets() || ! $this->isValueLocation();
    }

    private function hasHighValueAssets(): bool
    {
        return ($this->lead->has_contents && $this->lead->contents_aed > self::CONTENTS_VALUE_THRESHOLD) ||
            ($this->lead->has_personal_belongings && $this->lead->personal_belongings_aed > self::PERSONAL_BELONGINGS_VALUE_THRESHOLD) ||
            ($this->lead->has_building && $this->lead->building_aed > self::BUILDING_VALUE_THRESHOLD);
    }

    private function hasLowValueAssets(): bool
    {
        return ($this->lead->has_contents && $this->lead->contents_aed < self::CONTENTS_VALUE_THRESHOLD) ||
            ($this->lead->has_personal_belongings && $this->lead->personal_belongings_aed < self::PERSONAL_BELONGINGS_VALUE_THRESHOLD) ||
            ($this->lead->has_building && $this->lead->building_aed < self::BUILDING_VALUE_THRESHOLD);
    }

    /**
     * Fetch emails of advisors belonging to the Corp Team.
     */
    protected function getCorpTeamAdvisorEmails(): array
    {
        $corpTeamId = Team::where('name', TeamNameEnum::MOTOR_COOPERATE_RENEWALS)->value('id');
        if (! $corpTeamId) {
            return [];
        }

        return DB::table('user_team')
            ->join('users', 'user_team.user_id', '=', 'users.id')
            ->where('user_team.team_id', $corpTeamId)
            ->where('users.is_active', 1)
            ->pluck('users.email')
            ->toArray();
    }

    private function getHomePropertyRentedAttribute(string $uuid): HomeQuote
    {
        return HomeQuote::where('uuid', $uuid)->select('owner_occupancy_type_id')->first();
    }

    /**
     * Fetch emails of advisors based on the lead type (value or volume).
     */
    protected function getAdvisorEmailsBasedOnLeadType(): array
    {
        if ($this->isValueLead()) {
            return $this->getValueAdvisors();
        } elseif ($this->isVolumeLead()) {
            return $this->getVolumeAdvisors();
        }

        return [];
    }

    /**
     * Fetch a Home Advisor (value or volume) based on the online status.
     *
     * @return mixed
     */
    protected function fetchHomeAdvisor(int $onlineStatus)
    {
        $emails = $this->getAdvisorEmailsBasedOnLeadType();
        if (empty($emails)) {
            return null; // No eligible advisors found
        }

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::HomeAdvisor])
            ->whereIn('users.email', $emails)
            ->first();
    }

    /**
     * Fetch a Corp Line Advisor based on the online status.
     *
     * @return mixed
     */
    protected function fetchCorpLineAdvisor(int $onlineStatus)
    {
        $corpTeamEmails = $this->getCorpTeamAdvisorEmails();
        if (empty($corpTeamEmails)) {
            return null;
        }

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::CorpLineAdvisor])
            ->whereIn('users.email', $corpTeamEmails)
            ->first();
    }

    /**
     * Check if the property is rented for a holiday home.
     */
    private function isPropertyRentedForHolidayHome(string $uuid): bool
    {
        $homeRented = HomeQuote::where('uuid', $uuid)
            ->value('owner_occupancy_type_id');

        return $homeRented === 2; // 2 represents a short term or holiday home
    }
}
