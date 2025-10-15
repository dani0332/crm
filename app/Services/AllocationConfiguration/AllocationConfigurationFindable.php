<?php

declare(strict_types=1);

namespace App\Services\AllocationConfiguration;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\QuoteTypes;
use App\Models\Allocation\AllocationConfiguration;
use App\Models\BusinessQuote;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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

        foreach (['micro_brackets', 'non_micro_brackets'] as $bracketType) {
            $brackets = $configuration?->{$bracketType};
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
        $address = Str::lower($lead?->subArea?->text ?? '');

        if (! $configuration || ! $address || (! $lead->hasContents() && ! $lead->hasBuilding() && ! $lead->hasPersonalBelongings())) {
            return [];
        }

        if ($lead->hasContents()) {
            $contentsValue = $lead->contents?->min_value ?? 0;

            $advisorIds = $this->evaluateHomeAdvisorIds($configuration, 'contents_min', 'contents_max', $contentsValue, $address);

            if (! empty($advisorIds)) {
                return $advisorIds;
            }
        }

        if ($lead->hasBuilding()) {
            $buildingValue = (float) $lead->building_value ?? 0;

            $advisorIds = $this->evaluateHomeAdvisorIds($configuration, 'building_min', 'building_max', $buildingValue, $address);

            if (! empty($advisorIds)) {
                return $advisorIds;
            }
        }

        // $personalBelongingsValue = $lead->personalBelongings?->min_value ?? 0;

        return [];
    }

    private function evaluateHomeAdvisorIds(AllocationConfiguration $configuration, string $minKey, string $maxKey, float|int $value, string $address): array
    {
        foreach (['value_brackets', 'volume_brackets'] as $bracketType) {
            $brackets = $configuration?->{$bracketType};
            $bracketData = $this->getMatchingBracket($brackets, $value, $minKey, $maxKey);
            if ($bracketData) {
                $profile = $this->getMatchingProfileData($bracketData['profiles'], 'locations', $address);
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

    private function getAdvisorIds(?array $profile): array
    {
        if (empty($profile) || ! isset($profile['advisorIds'])) {
            return [];
        }

        return $profile['advisorIds'];
    }
}
