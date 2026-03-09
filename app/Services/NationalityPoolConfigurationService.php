<?php

namespace App\Services;

use App\Models\NationalityPool;
use Illuminate\Support\Facades\Auth;

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
        $codes = collect($data['canonical_nationality_codes'])->implode(',');
        $groupIds = collect($data['health_nationality_group_ids'])->implode(',');

        NationalityPool::updateOrCreate([
            'effective_from' => $data['effective_from'],
        ], [
            'health_nationality_group_ids' => $groupIds,
            'canonical_nationality_codes' => $codes,
            'is_active' => true,
            'logged_by' => Auth::id(),
        ]);
    }
}
