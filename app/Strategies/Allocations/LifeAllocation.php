<?php

namespace App\Strategies\Allocations;

use App\Enums\RolesEnum;
use App\Models\Nationality;

class LifeAllocation extends BaseAllocation
{
    private const CAT_A = 'categoryA';
    private const CAT_B = 'categoryB';

    protected function fetchAdvisor(int $onlineStatus)
    {
        $emails = $this->getAdvisorEmails();

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::LifeAdvisor])
            ->whereIn('users.email', $emails)
            ->first();
    }

    private function getAdvisorEmails()
    {
        $category = $this->evaluateCategory();
        $amount = $this->lead->currency?->getAED((float) $this->lead?->sum_insured_value ?? 0);

        $santosh = 'santhosh.ganesan@insurancemarket.ae';
        $gaurav = 'gaurav.sharma@insurancemarket.ae';
        $vivian = 'vivian.sandel@insurancemarket.ae';
        $roshan = 'roshan.tekcham@insurancemarket.ae';

        $emails = [];

        if ($amount < 1000000 && in_array($category, [self::CAT_A])) {
            $emails = [$gaurav, $vivian];
        } elseif ($amount >= 1000000 && $amount <= 2000000 && in_array($category, [self::CAT_A])) {
            $emails = [$vivian];
        } elseif ($amount <= 2000000 && in_array($category, [self::CAT_B])) {
            $emails = [$gaurav, $roshan];
        } elseif ($amount > 2000000 && in_array($category, [self::CAT_A, self::CAT_B])) {
            $emails = [$santosh];
        }

        return $emails;
    }

    private function getCountriesMapping()
    {
        $catACountryMapping = [
            'South African', 'Australian', 'New Zealander', 'Canadian', 'United Kingdom', 'Lebanese', 'Filipino', 'American', 'Europe',
        ];

        $catBCountryMapping = cache()->remember('countries_category_mapping', now()->addHours(24), function () use ($catACountryMapping) {
            return Nationality::whereNotIn('code', [...$catACountryMapping])->pluck('code')->toArray();
        });

        return [
            self::CAT_A => $catACountryMapping,
            self::CAT_B => $catBCountryMapping,
        ];
    }

    private function evaluateCategory()
    {
        $countriesMapping = $this->getCountriesMapping();

        return in_array($this->lead->nationality?->code, $countriesMapping[self::CAT_A])
            ? self::CAT_A
            : self::CAT_B;
    }
}
