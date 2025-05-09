<?php

namespace App\Services;

use App\Models\NationalityAllocationConfiguration;
use Illuminate\Support\Collection;

class NationalityAllocationService
{
    public function findUsersForAllocation(int $quoteTypeId, int $nationalityId): Collection
    {
        $config = NationalityAllocationConfiguration::with(['users'])
            ->where('quote_type_id', $quoteTypeId)
            ->where('nationality_id', $nationalityId)
            ->active()
            ->first();

        if (! $config) {
            return collect();
        }

        return $config->users;
    }

    public function getAllConfigurations(): Collection
    {
        return NationalityAllocationConfiguration::with(['nationality', 'quoteType', 'users'])->get();
    }

    public function createConfiguration(array $data, int $userId): NationalityAllocationConfiguration
    {
        $configuration = NationalityAllocationConfiguration::create([
            'quote_type_id' => $data['quote_type_id'],
            'nationality_id' => $data['nationality_id'],
            'is_sic_enabled' => $data['is_sic_enabled'] ?? false,
            'activated_at' => $data['activated_at'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $configuration->users()->attach($data['user_ids']);

        return $configuration;
    }

    public function deleteConfiguration(int $configId): bool
    {
        $config = NationalityAllocationConfiguration::find($configId);

        if (! $config) {
            return false;
        }

        $config->users()->detach();

        return $config->delete();
    }
}
