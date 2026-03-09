<?php

namespace App\Services;

use App\Models\NationalityPool;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class NationalityPoolConfigurationService
{
    public function __construct(public CanonicalNationalityService $canonicalNationalityService) {}

    public function getData(): array
    {
        return NationalityPool::select('effective_from', 'health_nationality_group_ids', 'canonical_nationality_codes')
            ->whereDate('effective_from', '>=', Carbon::today()->toDateString())
            ->orderBy('effective_from', 'asc')
            ->first()
            ->toArray();
    }

    public function getAuditLogs(string $type)
    {
        $today = Carbon::today()->toDateString();
        $logs = NationalityPool::query();

        if ($type == 'audit') {
            $logs->whereDate('effective_from', '<', $today);
        } else {
            $logs->whereDate('effective_from', '>=', $today);
        }

        return $logs->with('user')->get();
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
