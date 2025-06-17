<?php

namespace App\Services;

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
            $configData = json_decode($latestConfig->config, true);
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
    public function createNewConfigurationVersion(array $configurations): void
    {
        DB::beginTransaction();

        try {
            // Set active_version=false for all existing configurations
            PrivateClientConfig::where('active_version', true)->update(['active_version' => false]);

            $existingVersion = PrivateClientConfig::orderBy('version', 'desc')->first();
            $newVersion = $existingVersion ? $existingVersion->version + 1 : 1;

            foreach ($configurations as $config) {
                // Create new config with new version
                PrivateClientConfig::create([
                    'quote_type_id' => $config['quote_type_id'],
                    'config' => json_encode(['profiles' => $config['profiles']]),
                    'version' => $newVersion,
                    'status' => 1,
                    'active_version' => true,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate configuration data before saving
     */
    public function validateConfigurations(array $configurations): array
    {
        $rules = [
            'configurations' => 'required|array',
            'configurations.*.quote_type_id' => 'integer',
            'configurations.*.profiles' => 'required|array',
            'configurations.*.profiles.*.nationalityIds' => 'nullable|array',
        ];

        // Add validation for each profile field dynamically
        foreach ($configurations as $configIndex => $config) {
            if (isset($config['profiles'])) {
                foreach ($config['profiles'] as $profileIndex => $profile) {
                    foreach ($profile as $fieldName => $fieldValue) {
                        if ($fieldName !== 'nationalityIds') {
                            $rules["configurations.{$configIndex}.profiles.{$profileIndex}.{$fieldName}"] = 'nullable';
                        }
                    }
                }
            }
        }

        return $rules;
    }
}
