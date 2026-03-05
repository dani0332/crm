<?php

namespace App\Services;

use App\Models\NationalityPool;

class NationalityPoolConfigurationService
{
    public function getData(): array
    {
        return NationalityPool::select('effective_from', 'health_nationality_group_ids', 'canonical_nationality_codes')
            ->active()
            ->get()
            ->toArray();
    }

    public function saveData(array $data): void
    {
        // Deactivate other records
        NationalityPool::where('is_active', true)->update(['is_active' => false]);

        $codes = collect($data['canonical_nationality_codes'])->implode(',');
        $groupIds = collect($data['health_nationality_group_ids'])->implode(',');

        NationalityPool::create([
            'effective_from' => $data['effective_from'],
            'health_nationality_group_ids' => $groupIds,
            'canonical_nationality_codes' => $codes,
        ]);
    }
}
