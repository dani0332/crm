<?php

namespace App\Services;

use App\Models\NationalityPool;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class NationalityPoolConfigurationService
{
    public function getData(): array
    {
        return NationalityPool::select('effective_from', 'health_nationality_group_ids', 'canonical_nationality_codes')
            ->whereDate('effective_from', '>=', Carbon::today()->toDateString())
            ->orderBy('effective_from', 'asc')
            ->first()
            ->toArray();
    }

    public function getAuditLogs(string $type): array
    {
        $logs = NationalityPool::query();

        if ($type == 'audit') {
            $logs->whereDate('effective_from', '<', Carbon::today()->toDateString());
        }
        $data = $logs->get();

        $data = $data->map(function ($item) {
            $item->user = $item->user->name;

            // $item->nationalities = collect($item[canonical_nationality_codes'])->implode(',');
            return $item;
        });

        return $data->toArray();
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
