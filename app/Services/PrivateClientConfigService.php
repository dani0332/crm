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
    private const LABEL_TEXT = 'text as label';
    private const VALUE_TEXT = 'id as value';

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
            $config = PrivateClientConfig::byQuoteTypeId($quoteTypeId)->where('version', $version)->first();
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

    public function getDropdownDataByQuoteType(int $quoteTypeId): array
    {
        $baseData = [
            'nationalities' => Nationality::select(self::VALUE_TEXT, self::LABEL_TEXT)
                ->where('is_active', true)
                ->get(),
            'currencies' => CurrencyType::select(self::VALUE_TEXT, self::LABEL_TEXT)
                ->where('is_active', true)
                ->get(),
        ];

        switch ($quoteTypeId) {
            case QuoteTypes::CAR->id():
                return array_merge($baseData, [
                    'carMakes' => CarMake::select(self::VALUE_TEXT, self::LABEL_TEXT)
                        ->where('is_active', true)
                        ->get(),
                    'insurers' => InsuranceProvider::select(self::VALUE_TEXT, self::LABEL_TEXT)
                        ->where('is_active', true)
                        ->get(),
                ]);

            case QuoteTypes::HOME->id():
                return array_merge($baseData, [
                    'insurers' => InsuranceProvider::select(self::VALUE_TEXT, self::LABEL_TEXT)
                        ->where('is_active', true)
                        ->get(),
                    'locationAreas' => SubArea::select(self::VALUE_TEXT, self::LABEL_TEXT)
                        ->get(),
                ]);

            case QuoteTypes::HEALTH->id():
            case QuoteTypes::LIFE->id():
            case QuoteTypes::YACHT->id():
                return array_merge($baseData, [
                    'insurers' => InsuranceProvider::select(self::VALUE_TEXT, self::LABEL_TEXT)
                        ->where('is_active', true)
                        ->get(),
                ]);

            default:
                return $baseData;
        }
    }

    public function createNewConfigurationVersion(array $data): ?PrivateClientConfig
    {
        DB::beginTransaction();

        try {
            PrivateClientConfig::byQuoteTypeId($data['quote_type_id'])->activeVersion()->update(['active_version' => false]);

            $existingVersion = PrivateClientConfig::getLatestVersion($data['quote_type_id']);

            $newVersion = $existingVersion ? $existingVersion->version + 1 : 1;

            $configData = is_array($data['config']) && ! isset($data['config']['profiles'])
                ? ['profiles' => $data['config']]
                : $data['config'];

            $config = PrivateClientConfig::create([
                'quote_type_id' => $data['quote_type_id'],
                'quote_type' => $data['quote_type'],
                'config' => $configData,
                'version' => $newVersion,
                'active_version' => true,
            ]);

            DB::commit();

            return $config;
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function evaluateConfig(QuoteTypes $quoteType, int $nationlityId)
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
            QuoteTypes::CAR => $this->getCarConfiguration($config, $profile),
            QuoteTypes::HOME => $this->getHomeConfiguration($config, $profile),
            QuoteTypes::LIFE => $this->getLifeConfiguration($config, $profile),
            QuoteTypes::YACHT => $this->getYachtConfiguration($config, $profile),
            QuoteTypes::HEALTH => $this->getHealthConfiguration($config, $profile),
            default => null,
        };
    }

    private function getCarConfiguration(PrivateClientConfig $config, array $profile)
    {
        $configuration = collect([]);

        $configuration->push($this->buildEntity($config, $profile, 'car_value'));
        $configuration->push($this->buildEntity($config, $profile, 'car_make_id'));
        $configuration->push($this->buildEntity($config, $profile, 'insurance_provider_id'));
        $configuration->push($this->buildEntity($config, $profile, 'price_with_vat'));

        return $configuration;
    }

    private function getHomeConfiguration(PrivateClientConfig $config, array $profile)
    {
        $configuration = collect([]);

        $configuration->push($this->buildEntity($config, $profile, 'price_with_vat'));
        $configuration->push($this->buildEntity($config, $profile, 'insurance_provider_id'));
        $configuration->push($this->buildEntity($config, $profile, 'sub_area_id'));

        return $configuration;
    }

    private function getLifeConfiguration(PrivateClientConfig $config, array $profile)
    {
        $configuration = collect([]);

        $configuration->push($this->buildEntity($config, $profile, 'policy_sum_assured_value_1', 'policy_sum_assured'));
        $configuration->push($this->buildEntity($config, $profile, 'policy_sum_assured_value_2', 'policy_sum_assured'));
        $configuration->push($this->buildEntity($config, $profile, 'policy_sum_assured_value_3', 'policy_sum_assured'));
        $configuration->push($this->buildEntity($config, $profile, 'policy_sum_assured_value_4', 'policy_sum_assured'));
        $configuration->push($this->buildEntity($config, $profile, 'insurer'));

        return $configuration;
    }

    private function getYachtConfiguration(PrivateClientConfig $config, array $profile)
    {
        $configuration = collect([]);

        $configuration->push($this->buildEntity($config, $profile, 'price_with_vat'));
        $configuration->push($this->buildEntity($config, $profile, 'insurance_provider_id'));

        return $configuration;
    }

    private function getHealthConfiguration(PrivateClientConfig $config, array $profile)
    {
        return $this->getYachtConfiguration($config, $profile);
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

        if ($data['operator'] === 'in') {
            $data['value'] = implode(',', $data['value'] ?? []);
        }

        return (object) $data;
    }
}
