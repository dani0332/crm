<?php

namespace App\Services;

use App\Models\NationalityPool;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NationalityPoolConfigurationService
{
    public function __construct(public CanonicalNationalityService $canonicalNationalityService) {}

    public function getData(?int $id = null): ?array
    {
        $data = NationalityPool::selectRaw("DATE_FORMAT(effective_from, '%Y-%m-%d') as effective_from,
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
            $logs->whereDate('effective_from', '<', $today);
        } else {
            $logs->whereDate('effective_from', '>=', $today)->withTrashed();
        }

        return $logs->with('user')->get();
    }

    public function saveData(array $data): void
    {
        $codes = collect($data['canonical_nationality_codes'])->implode(',');
        $groupIds = collect($data['health_nationality_group_ids'])->implode(',');

        DB::transaction(function () use ($data, $codes, $groupIds) {
            $nationalityPool = NationalityPool::updateOrCreate([
                'effective_from' => $data['effective_from'],
            ], [
                'health_nationality_group_ids' => $groupIds,
                'canonical_nationality_codes' => $codes,
                'is_active' => true,
                'logged_by' => Auth::id(),
            ]);

            // If new configuration
            if ($nationalityPool->wasRecentlyCreated) {
                // Update effective to of previous record
                $lastRecord = NationalityPool::orderByDesc('effective_from')
                    ->limit(1)
                    ->offset(1)
                    ->first();

                if ($lastRecord) {
                    $lastRecord->update([
                        'effective_to' => Carbon::parse($data['effective_from'])->subDay()->format('Y-m-d'),
                    ]);
                }
            }
        });
    }

    public function deleteAuditLog(int $id): void
    {
        NationalityPool::destroy($id);
    }
}
