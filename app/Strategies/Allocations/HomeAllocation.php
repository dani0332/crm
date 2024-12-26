<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use App\Models\HomeQuote;
use Illuminate\Support\Str;

class HomeAllocation extends BaseAllocation
{
    private const CONTENTS_VALUE_THRESHOLD = 100000;
    private const PERSONAL_BELONGINGS_VALUE_THRESHOLD = 100000;
    private const BUILDING_VALUE_THRESHOLD = 5000000;

    protected function fetchAdvisor(int $onlineStatus)
    {
        $emails = [];
        if ($this->isValueLead()) {
            $emails = $this->getValueAdvisors();
        } elseif ($this->isVolumeLead()) {
            $emails = $this->getVolumeAdvisors();
        }

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::HomeAdvisor])
            ->whereIn('users.email', $emails)
            ->first();
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
        return HomeQuote::with('subArea:id,description')
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

        $address = Str::lower($homeQuote?->subArea?->description ?? '');

        return $this->matchesTargetLocations($address);
    }

    public function isValueLead(): bool
    {
        return $this->hasHighValueAssets() || $this->isValueLocation();
    }

    public function isVolumeLead(): bool
    {
        return $this->hasLowValueAssets() || !$this->isValueLocation();
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
}
