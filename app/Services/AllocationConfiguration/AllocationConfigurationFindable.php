<?php

declare(strict_types=1);

namespace App\Services\AllocationConfiguration;

use App\Enums\EmirateEnum;
use App\Enums\GroupMedicalRegionEnum;
use App\Enums\InvestmentFrequencyEnum;
use App\Enums\QuoteTypes;
use App\Models\Allocation\AllocationConfiguration;
use App\Models\BusinessQuote;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use Illuminate\Support\Collection;

trait AllocationConfigurationFindable
{
    public function findConfig(QuoteTypes $quoteType): ?AllocationConfiguration
    {
        return AllocationConfiguration::where('quote_type', $quoteType)->latest()->first();
    }

    public function getSavingsEligibleAdvisorIds(PersonalQuote $lead): array
    {
        $configuration = $this->findConfig(QuoteTypes::SAVINGS);
        $nationalityId = $lead?->nationality?->id;

        if (! $configuration || ! $nationalityId) {
            return [];
        }

        $frequency = $lead?->savingsQuote?->investmentFrequency?->code;
        $investmentFrequency = InvestmentFrequencyEnum::from($frequency);
        $amount = $lead?->savingsQuote?->currency?->convertToUSD((float) $lead?->savingsQuote?->investment_amount ?? 0);

        $brackets = $investmentFrequency === InvestmentFrequencyEnum::LUMPSUM
            ? $configuration->lumpsum_brackets
            : $configuration->regular_brackets;

        return $this->extractAdvisorIdsFromBrackets($brackets, $amount, $nationalityId);
    }

    public function getCommonEligibleAdvisorIds(QuoteTypes $quoteType): array
    {
        $configuration = $this->findConfig($quoteType);

        if (! $configuration) {
            return [];
        }

        return $configuration->advisor_ids;
    }

    public function getLifeEligibleAdvisorIds(PersonalQuote $lead): array
    {
        $configuration = $this->findConfig(QuoteTypes::LIFE);
        $nationalityId = $lead?->nationality?->id;
        $amount = $lead?->lifeQuote?->currency?->convertToAED((float) $lead?->lifeQuote?->sum_insured_value ?? 0);

        if (! $configuration || ! $nationalityId || empty($amount)) {
            return [];
        }

        foreach (range(1, 4) as $type) {
            $configKey = "type{$type}_brackets";
            $brackets = $configuration->{$configKey};
            $advisorIds = $this->extractAdvisorIdsFromBrackets($brackets, $amount, $nationalityId);
            if (! empty($advisorIds)) {
                return $advisorIds;
            }
        }

        return [];
    }

    public function getGroupMedicalEligibleAdvisorIds(BusinessQuote $lead): array
    {
        $configuration = $this->findConfig(QuoteTypes::GROUP_MEDICAL);
        $numberOfEmployees = $lead?->number_of_employees;
        $healthPlanTypeId = $lead?->health_plan_type_id;

        if (! $configuration || ! $numberOfEmployees || ! $healthPlanTypeId) {
            return [];
        }

        $regionConfig = $this->getGroupMedicalRegionConfig($configuration, $lead);

        foreach (['micro_brackets', 'non_micro_brackets'] as $bracketType) {
            $brackets = isset($regionConfig[$bracketType]) ? $regionConfig[$bracketType] : [];
            $advisorIds = $this->extractAdvisorIdsFromBrackets($brackets, $numberOfEmployees, $healthPlanTypeId, 'planTypeIds', 'employees_min', 'employees_max');
            if (! empty($advisorIds)) {
                return $advisorIds;
            }
        }

        return [];
    }

    public function getCorplineEligibleAdvisorIds(BusinessQuote $lead): array
    {
        $configuration = $this->findConfig(QuoteTypes::CORPLINE);
        $businessTypeId = $lead?->business_type_of_insurance_id;

        if (! $configuration || ! $businessTypeId) {
            return [];
        }

        foreach (['value_profiles', 'volume_profiles'] as $profileType) {
            $profiles = $configuration?->{$profileType};
            $profileData = $this->getMatchingProfileData($profiles, 'businessTypeIds', $businessTypeId);
            $advisorIds = $this->getAdvisorIds($profileData);
            if (! empty($advisorIds)) {
                return $advisorIds;
            }
        }

        return [];
    }

    public function getHomeEligibleAdvisorIds(HomeQuote $lead): array
    {
        $configuration = $this->findConfig(QuoteTypes::HOME);
        $subAreaId = $lead?->sub_area_id;

        if (! $configuration || ! $subAreaId || (! $lead->hasContents() && ! $lead->hasBuilding() && ! $lead->hasPersonalBelongings())) {
            return [];
        }

        if ($lead->hasContents()) {
            $contentsValue = $lead->contents?->min_value ?? 0;

            $advisorIds = $this->evaluateHomeAdvisorIds($configuration, 'contents_min', 'contents_max', $contentsValue, $subAreaId);

            if (! empty($advisorIds)) {
                return $advisorIds;
            }
        }

        if ($lead->hasBuilding()) {
            $buildingValue = (float) $lead->building_value ?? 0;

            $advisorIds = $this->evaluateHomeAdvisorIds($configuration, 'building_min', 'building_max', $buildingValue, $subAreaId);

            if (! empty($advisorIds)) {
                return $advisorIds;
            }
        }

        // $personalBelongingsValue = $lead->personalBelongings?->min_value ?? 0;

        return [];
    }

    private function evaluateHomeAdvisorIds(AllocationConfiguration $configuration, string $minKey, string $maxKey, float|int $value, int $subAreaId): array
    {
        foreach (['value_brackets', 'volume_brackets'] as $bracketType) {
            $brackets = $configuration?->{$bracketType};
            $bracketData = $this->getMatchingBracket($brackets, $value, $minKey, $maxKey);
            if ($bracketData) {
                $profile = $this->getMatchingProfileDataForLocation($bracketData['profiles'], 'locations', $subAreaId);
                $advisorIds = $this->getAdvisorIds($profile);

                if (! empty($advisorIds)) {
                    return $advisorIds;
                }
            }
        }

        return [];
    }

    private function extractAdvisorIdsFromBrackets(
        array $brackets,
        int|float $bracketValue,
        int|string $profileValue,
        string $profileKey = 'nationalityIds',
        string $bracketMinKey = 'min',
        string $bracketMaxKey = 'max'
    ): array {
        $matchingBracket = $this->getMatchingBracket($brackets, $bracketValue, $bracketMinKey, $bracketMaxKey);

        $matchingProfile = $this->getMatchingProfile($matchingBracket, $profileKey, $profileValue);

        return $this->getAdvisorIds($matchingProfile);
    }

    private function getMatchingBracket(array $brackets, int|float $value, string $minKey = 'min', string $maxKey = 'max'): ?array
    {
        return collect($brackets)
            ->map(function ($bracket) use ($minKey, $maxKey) {
                $bracket[$minKey] = (float) $bracket[$minKey];
                $bracket[$maxKey] = (float) $bracket[$maxKey];

                return $bracket;
            })
            ->where($minKey, '<=', $value)
            ->where($maxKey, '>=', $value)
            ->first();
    }

    private function getMatchingProfile(?array $bracket, string $key, int|string $value): ?array
    {
        if (empty($bracket) || ! isset($bracket['profiles'])) {
            return null;
        }

        $profiles = collect($bracket['profiles']);

        return $this->getMatchingProfileData($profiles, $key, $value);
    }

    private function getMatchingProfileData(Collection|array|null $profiles, string $key, int|string $value): ?array
    {
        if (empty($profiles)) {
            return null;
        }

        $profiles = is_array($profiles) ? collect($profiles) : $profiles;

        return $profiles->first(fn ($profile) => in_array($value, $profile[$key]));
    }

    private function getMatchingProfileDataForLocation(Collection|array|null $profiles, string $key, int|string $value): ?array
    {
        if (empty($profiles)) {
            return null;
        }

        $profiles = is_array($profiles) ? collect($profiles) : $profiles;

        return $profiles->filter(fn ($profile) => isset($profile[$key]) && $this->matchesTargetLocations($value, $profile[$key]))->first();
    }

    private function matchesTargetLocations(int $subAreaId, array $locations): bool
    {
        return in_array($subAreaId, $locations);
    }

    private function getAdvisorIds(?array $profile): array
    {
        if (empty($profile) || ! isset($profile['advisorIds'])) {
            return [];
        }

        return $profile['advisorIds'];
    }

    private function getGroupMedicalRegionConfig(AllocationConfiguration $configuration, BusinessQuote $lead): array
    {
        $config = $configuration->config ?? [];

        $emirateOfRegistrationId = $lead->emirate_of_registration_id ?? null;

        // if emirates of registration is not null and is abu dhabi then return auh else non auh
        $regionKey = ($emirateOfRegistrationId !== null && $emirateOfRegistrationId == EmirateEnum::ABU_DHABI)
            ? GroupMedicalRegionEnum::AUH
            : GroupMedicalRegionEnum::NON_AUH;

        $regionConfigKey = $regionKey->value;

        if (isset($config[$regionConfigKey]) && is_array($config[$regionConfigKey])) {
            return $config[$regionConfigKey];
        }

        return [];
    }
}
