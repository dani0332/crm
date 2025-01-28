<?php

namespace App\Strategies\Allocations;

use App\Enums\ApplicationStorageEnums;
use App\Enums\RolesEnum;
use Illuminate\Support\Str;

class HomeAllocation extends BaseAllocation
{
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

    private function isValueLocation(): bool
    {
        $address = Str::lower($this->lead->address);

        return Str::contains('arabian ranches', $address) || Str::contains('palm jumeriah', $address);
    }

    private function isValueLead(): bool
    {
        return ($this->lead->has_contents && $this->lead->contents_aed > 100000) ||
        ($this->lead->has_personal_belongings && $this->lead->personal_belongings_aed > 100000) ||
        ($this->lead->has_building && $this->lead->building_aed > 5000000) ||
        $this->isValueLocation();
    }

    private function isVolumeLead(): bool
    {
        return ($this->lead->has_contents && $this->lead->contents_aed < 100000) ||
            ($this->lead->has_personal_belongings && $this->lead->personal_belongings_aed < 100000) ||
            ($this->lead->has_building && $this->lead->building_aed < 5000000) ||
            ! $this->isValueLocation();
    }
}
