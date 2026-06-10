<?php

namespace App\Services;

use App\Models\NationalityPool;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class NationalityPoolConfigurationService
{
    public function __construct(public CanonicalNationalityService $canonicalNationalityService) {}

    public function getData(?int $id = null): ?array
    {
        $data = NationalityPool::selectRaw("DATE_FORMAT(effective_from, '%Y-%m-%d') as effective_from,
                DATE_FORMAT(effective_to, '%Y-%m-%d') as effective_to,
                health_nationality_group_ids,
                canonical_nationality_codes");

        if ($id) {
            $data->where('id', $id);
        } else {
            $data->whereDate('effective_from', '<=', Carbon::today()->toDateString())
                ->orderByDesc('effective_from');
        }

        return $data->first()?->toArray();
    }

    public function getAuditLogs(string $type)
    {
        $today = Carbon::today()->toDateString();
        $logs = NationalityPool::query();

        if ($type == 'audit') {
            $logs->whereDate('effective_from', '<=', $today);
        } else {
            $logs->whereDate('effective_from', '>', $today)->withTrashed();
        }

        return $logs->with('user')->orderByDesc('created_at')->get();
    }

    public function saveData(array $data): void
    {
        $codes = collect($data['canonical_nationality_codes'] ?? [])
            ->filter(fn (mixed $code) => filled($code));

        $groupIds = collect($data['health_nationality_group_ids'] ?? [])
            ->filter(fn (mixed $id) => filled($id));

        $effectiveFrom = Carbon::parse($data['effective_from'])->startOfDay();

        NationalityPool::updateOrCreate([
            'effective_from' => $effectiveFrom,
        ], [
            'health_nationality_group_ids' => $groupIds->isNotEmpty() ? $groupIds->implode(',') : null,
            'canonical_nationality_codes' => $codes->isNotEmpty() ? $codes->implode(',') : null,
            'is_active' => true,
            'logged_by' => Auth::id(),
        ]);

    }

    public function deleteAuditLog(int $id): void
    {
        NationalityPool::destroy($id);
    }

    public function getAllEffetiveFromDates(): ?array
    {
        return NationalityPool::select('effective_from')
            ->pluck('effective_from')
            ->toArray();
    }
}
