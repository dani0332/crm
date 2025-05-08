<?php

namespace App\Services;

use App\Models\NationalityAllocationConfiguration;
use App\Models\User;
use Illuminate\Support\Collection;

class NationalityAllocationService
{
    /**
     * Find appropriate users for a given quote based on nationality and quote type.
     *
     * @param int $quoteTypeId The quote type ID
     * @param int $nationalityId The nationality ID
     * @return Collection<User> Collection of users who can handle this quote
     */
    public function findUsersForAllocation(int $quoteTypeId, int $nationalityId): Collection
    {
        // Find matching allocation configuration
        $config = NationalityAllocationConfiguration::with(['users'])
            ->where('quote_type_id', $quoteTypeId)
            ->where('nationality_id', $nationalityId)
            ->first();

        // If no configuration found, return empty collection
        if (!$config) {
            return collect();
        }

        return $config->users;
    }

    /**
     * Get all nationality allocation configurations with relationships.
     *
     * @return Collection<NationalityAllocationConfiguration>
     */
    public function getAllConfigurations(): Collection
    {
        return NationalityAllocationConfiguration::with(['nationality', 'quoteType', 'users'])->get();
    }

    /**
     * Create a new nationality allocation configuration.
     *
     * @param array $data Configuration data
     * @param int $userId User creating the configuration
     * @return NationalityAllocationConfiguration
     */
    public function createConfiguration(array $data, int $userId): NationalityAllocationConfiguration
    {
        $config = NationalityAllocationConfiguration::create([
            'quote_type_id' => $data['quote_type_id'],
            'nationality_id' => $data['nationality_id'],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        if (isset($data['user_ids']) && is_array($data['user_ids'])) {
            $config->users()->sync($data['user_ids']);
        }

        return $config;
    }

    /**
     * Delete a nationality allocation configuration.
     *
     * @param int $configId Configuration ID to delete
     * @return bool
     */
    public function deleteConfiguration(int $configId): bool
    {
        $config = NationalityAllocationConfiguration::find($configId);

        if (!$config) {
            return false;
        }

        $config->users()->detach();
        return $config->delete();
    }
}
