<?php

namespace App\Strategies\Allocations;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\RolesEnum;
use App\Models\Nationality;

class SavingsAllocation extends BaseAllocation
{
    private const CAT_A = 'categoryA';
    private const CAT_B = 'categoryB';

    protected function fetchAdvisor(int $onlineStatus)
    {
        $emails = $this->getAdvisorEmails();

        return $this->getAdvisorBaseQuery($onlineStatus, [RolesEnum::SavingsAdvisor])
            ->whereIn('users.email', $emails)
            ->first();
    }

    private function getAdvisorEmails()
    {
        $category = $this->evaluateCategory();
        $amount = $this->lead?->savingsQuote?->currency?->convertToUSD((float) $this->lead?->savingsQuote?->amount ?? 0);
        $frequency = $this->lead?->savingsQuote?->investment_frequency;

        $santosh = 'santhosh.ganesan@insurancemarket.ae';
        $gaurav = 'gaurav.sharma@insurancemarket.ae';
        $vivian = 'vivian.sandel@insurancemarket.ae';

        $threshold = match ($frequency) {
            InvestmentFrequencyEnum::REGULAR => 750,
            InvestmentFrequencyEnum::LUMPSUM => 50000,
            default => null,
        };

        if ($threshold === null) {
            return [];
        }

        return match (true) {
            $amount <= $threshold && $category === self::CAT_A => [$vivian],
            $amount <= $threshold && $category === self::CAT_B => [$gaurav],
            $amount > $threshold && in_array($category, [self::CAT_A, self::CAT_B]) => [$santosh],
            default => [],
        };
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
