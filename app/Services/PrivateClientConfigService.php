<?php

namespace App\Services;

use App\Enums\QuoteTypes;
use App\Models\CarMake;
use App\Models\CurrencyType;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
use App\Models\PrivateClientConfig;
use App\Models\SubArea;
use Illuminate\Support\Facades\DB;

class PrivateClientConfigService
{
    private const LABEL_TEXT = 'text as label';
    private const VALUE_TEXT = 'id as value';

    /**
     * Get all distinct versions ordered by newest first
     */
    public function getAllVersions(): array
    {
        return PrivateClientConfig::select('version')
            ->distinct()
            ->orderBy('version', 'desc')
            ->pluck('version')
            ->toArray();
    }

    /**
     * Get configurations for a specific version
     */
    public function getConfigurationsByVersion(?int $selectedVersion)
    {
        return PrivateClientConfig::query()
            ->when($selectedVersion, function ($query) use ($selectedVersion) {
                return $query->where('version', $selectedVersion);
            })
            ->get();
    }

    /**
     * Check if the selected version is the current (latest) version
     */
    public function isCurrentVersion(?int $selectedVersion, array $allVersions): bool
    {
        return count($allVersions) === 0 || (int) $selectedVersion === (int) $allVersions[0];
    }

    /**
     * Get the latest version number, or return null if no versions exist
     */
    public function getLatestVersion(): ?int
    {
        $allVersions = $this->getAllVersions();

        return count($allVersions) > 0 ? $allVersions[0] : null;
    }

    /**
     * Get latest configuration by quote type ID
     */
    public function getLatestConfigByQuoteType(int $quoteTypeId): array
    {
        // Get all versions for this quote type
        $allVersionsForQuoteType = PrivateClientConfig::where('quote_type_id', $quoteTypeId)
            ->select('version')
            ->distinct()
            ->orderBy('version', 'desc')
            ->pluck('version')
            ->toArray();

        // Get the latest (current) configuration
        $latestConfig = PrivateClientConfig::where('quote_type_id', $quoteTypeId)
            ->where('active_version', true)
            ->first();

        // Get specific dropdown data based on quote type
        $dropdownData = $this->getDropdownDataByQuoteType($quoteTypeId);

        $responseData = [
            'config' => null,
            'version' => null,
            'allVersions' => $allVersionsForQuoteType,
            'isCurrentVersion' => true, // Always true when getting latest
            'dropdownData' => $dropdownData,
        ];

        if ($latestConfig) {
            $configData = $latestConfig->config;

            // Ensure config data has the expected profiles structure
            if (is_array($configData) && ! isset($configData['profiles'])) {
                $configData = ['profiles' => $configData];
            }

            $responseData['config'] = $configData;
            $responseData['version'] = $latestConfig->version;
        }

        return $responseData;
    }

    /**
     * Get dropdown data specific to each quote type
     */
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
            case 1: // Car
                return array_merge($baseData, [
                    'carMakes' => CarMake::select(self::VALUE_TEXT, self::LABEL_TEXT)
                        ->where('is_active', true)
                        ->get(),
                    'insurers' => InsuranceProvider::select(self::VALUE_TEXT, self::LABEL_TEXT)
                        ->where('is_active', true)
                        ->get(),
                ]);

            case 2: // Home
                return array_merge($baseData, [
                    'insurers' => InsuranceProvider::select(self::VALUE_TEXT, self::LABEL_TEXT)
                        ->where('is_active', true)
                        ->get(),
                    'locationAreas' => SubArea::select(self::VALUE_TEXT, self::LABEL_TEXT)
                        ->get(),
                ]);

            case 3: // Health
            case 4: // Life
            case 7: // Yacht
                return array_merge($baseData, [
                    'insurers' => InsuranceProvider::select(self::VALUE_TEXT, self::LABEL_TEXT)
                        ->where('is_active', true)
                        ->get(),
                ]);

            default:
                return $baseData;
        }
    }

    /**
     * Create new configuration version with provided configurations
     */
    public function createNewConfigurationVersion(array $data): void
    {
        DB::beginTransaction();

        try {
            PrivateClientConfig::where('active_version', true)->update(['active_version' => false]);

            $existingVersion = PrivateClientConfig::orderBy('version', 'desc')->first();
            $newVersion = $existingVersion ? $existingVersion->version + 1 : 1;

            // Ensure config data is properly structured with profiles key
            $configData = is_array($data['config']) && ! isset($data['config']['profiles'])
                ? ['profiles' => $data['config']]
                : $data['config'];

            PrivateClientConfig::create([
                'quote_type_id' => $data['quote_type_id'],
                'quote_type' => $data['quote_type'],
                'config' => $configData,
                'version' => $newVersion,
                'status' => 1,
                'active_version' => true,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getConfigByVersionAndQuoteType(int $quoteTypeId, int $version): array
    {
        $allVersionsForQuoteType = PrivateClientConfig::where('quote_type_id', $quoteTypeId)
            ->select('version')
            ->distinct()
            ->orderBy('version', 'desc')
            ->pluck('version')
            ->toArray();

        $latestVersion = count($allVersionsForQuoteType) > 0 ? $allVersionsForQuoteType[0] : null;

        $versionConfig = PrivateClientConfig::where('quote_type_id', $quoteTypeId)
            ->where('version', $version)
            ->first();

        $dropdownData = $this->getDropdownDataByQuoteType($quoteTypeId);

        $responseData = [
            'config' => null,
            'version' => $version,
            'allVersions' => $allVersionsForQuoteType,
            'isCurrentVersion' => $version === $latestVersion,
            'dropdownData' => $dropdownData,
        ];

        if ($versionConfig) {
            $configData = $versionConfig->config;

            // Ensure config data has the expected profiles structure
            if (is_array($configData) && ! isset($configData['profiles'])) {
                $configData = ['profiles' => $configData];
            }

            $responseData['config'] = $configData;
        }

        return $responseData;
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

        $configuration->push($this->buildEntity($config, $profile, 'sum_insured_value_1', 'sum_insured_value'));
        $configuration->push($this->buildEntity($config, $profile, 'sum_insured_value_2', 'sum_insured_value'));
        $configuration->push($this->buildEntity($config, $profile, 'sum_insured_value_3', 'sum_insured_value'));
        $configuration->push($this->buildEntity($config, $profile, 'sum_insured_value_4', 'sum_insured_value'));
        $configuration->push($this->buildEntity($config, $profile, 'insurer'));

        return $configuration;
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
