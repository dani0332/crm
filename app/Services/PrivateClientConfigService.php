<?php

namespace App\Services;

use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypes;
use App\Models\CarMake;
use App\Models\CurrencyType;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
use App\Models\PrivateClientConfig;
use App\Models\QuoteType;
use App\Models\SubArea;
use Illuminate\Support\Facades\DB;

class PrivateClientConfigService
{
    public function getAllowedQuoteTypes()
    {
        return QuoteType::withActive()
            ->whereIn('code', [
                quoteTypeCode::Car,
                quoteTypeCode::Health,
                quoteTypeCode::Life,
                quoteTypeCode::Home,
                quoteTypeCode::Yacht,
            ])
            ->select('id', 'text', 'code')
            ->get();
    }

    public function getLatestConfigByQuoteType(int $quoteTypeId, ?int $version = null): array
    {
        $allVersionsForQuoteType = PrivateClientConfig::byQuoteTypeId($quoteTypeId)
            ->select('version')
            ->distinct()
            ->orderBy('version', 'desc')
            ->pluck('version')
            ->toArray();

        if ($version) {
            $config = PrivateClientConfig::findByVersion($quoteTypeId, $version);
        } else {
            $config = PrivateClientConfig::getCurrentVersion($quoteTypeId);
        }

        $dropdownData = $this->getDropdownDataByQuoteType($quoteTypeId);

        $responseData = [
            'config' => null,
            'version' => null,
            'allVersions' => $allVersionsForQuoteType,
            'isCurrentVersion' => true,
            'dropdownData' => $dropdownData,
        ];

        if ($config) {
            $responseData['config'] = $config->config;
            $responseData['version'] = $config->version;
        }

        if ($version) {
            $latestConfig = PrivateClientConfig::getLatestVersion($quoteTypeId);

            $responseData['version'] = $version;
            $responseData['isCurrentVersion'] = $latestConfig?->version == $version;
        }

        return $responseData;
    }

    private function getDropdownDataByQuoteType(int $quoteTypeId): array
    {
        $baseData = [
            'nationalities' => Nationality::getOptions(),
            'currencies' => CurrencyType::getOptions(),
            'insurers' => InsuranceProvider::getOptions(),
        ];

        if ($quoteTypeId == QuoteTypes::CAR->id()) {
            $baseData['carMakes'] = CarMake::getOptions(withActive: false, active: true);
        }

        if ($quoteTypeId == QuoteTypes::HOME->id()) {
            $baseData['locationAreas'] = SubArea::getOptions(withActive: false, active: false);
        }

        return $baseData;
    }

    public function createNewConfigurationVersion(array $data): ?PrivateClientConfig
    {
        return DB::transaction(function () use ($data) {
            $currentVersion = PrivateClientConfig::getCurrentVersion($data['quote_type_id']);
            if ($currentVersion) {
                $currentVersion->update(['active_version' => false]);
            }

            $existingVersion = PrivateClientConfig::getLatestVersion($data['quote_type_id']);

            $newVersion = $existingVersion ? $existingVersion->version + 1 : 1;

            $configData = is_array($data['config']) && ! isset($data['config']['profiles'])
                ? ['profiles' => $data['config']]
                : $data['config'];

            return PrivateClientConfig::create([
                'quote_type_id' => $data['quote_type_id'],
                'quote_type' => $data['quote_type'],
                'config' => $configData,
                'version' => $newVersion,
                'active_version' => true,
            ]);
        });
    }

    public function evaluateConfig(QuoteTypes $quoteType, ?int $nationlityId)
    {
        $config = PrivateClientConfig::activeVersion()
            ->where('quote_type_id', $quoteType->id())
            ->first();

        if (! $config) {
            return null;
        }

        $configData = $config->config;

        $profiles = collect($configData && isset($configData['profiles']) ? $configData['profiles'] : []);

        $profile = $profiles->filter(fn ($profile) => in_array($nationlityId, $profile['nationalityIds']))->first();
        $profile = $profile ?: $profiles->firstWhere('isDefaultCriteria', true);

        if (! $profile) {
            return null;
        }

        return match ($quoteType) {
            QuoteTypes::CAR => $this->buildConfig($config, $profile, ['car_value', 'car_make_id', 'insurance_provider_id', 'price_with_vat']),
            QuoteTypes::HOME => $this->buildConfig($config, $profile, ['price_with_vat', 'insurance_provider_id', 'sub_area_id']),
            QuoteTypes::LIFE => $this->getLifeConfiguration($config, $profile),
            QuoteTypes::YACHT => $this->buildConfig($config, $profile, ['price_with_vat', 'insurance_provider_id']),
            QuoteTypes::HEALTH => $this->buildConfig($config, $profile, ['price_with_vat', 'insurance_provider_id']),
            default => null,
        };
    }

    private function buildConfig(PrivateClientConfig $config, array $profile, array $fields)
    {
        $configuration = collect([]);

        foreach ($fields as $field) {
            $fieldName = $field;
            $customKeyName = null;

            if (is_array($field)) {
                $fieldName = key($field);
                $customKeyName = $field[$fieldName];
            }

            $configuration->push($this->buildEntity($config, $profile, $fieldName, $customKeyName));
        }

        return $configuration;
    }

    private function getLifeConfiguration(PrivateClientConfig $config, array $profile)
    {
        return $this->buildConfig($config, $profile, [
            ['policy_sum_assured_value_1' => 'policy_sum_assured'],
            ['policy_sum_assured_value_2' => 'policy_sum_assured'],
            ['policy_sum_assured_value_3' => 'policy_sum_assured'],
            ['policy_sum_assured_value_4' => 'policy_sum_assured'],
            'insurer',
        ]);
    }

    private function buildEntity(PrivateClientConfig $config, array $profile, string $key, ?string $customKey = null)
    {
        $profileData = $profile[$key] ?? null;

        $data = [
            'version' => $config->version,
            'field_name' => $customKey ?? $key,
            'operator' => null,
            'value' => null,
            'currency_type_id' => null,
        ];

        if ($profileData && $profileData['isEnabled']) {
            $data['operator'] = $profileData['operator'] ?? null;
            $data['value'] = $profileData['value'] ?? null;
            $data['currency_type_id'] = $profileData['currencyId'] ?? null;
        }

        if ($data['operator'] === 'in' && is_array($data['value'])) {
            $data['value'] = implode(',', $data['value'] ?? []);
        }

        return (object) $data;
    }
}
