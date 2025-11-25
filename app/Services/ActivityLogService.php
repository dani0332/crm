<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityLogService extends BaseService
{
    /**
     * Get paginated activity logs with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActivityLogs(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ActivityLog::with(['causer:id,name,email', 'subject'])
            ->orderBy('created_at', 'desc');

        // Filter by user (causer) - required
        if (!empty($filters['user_id'])) {
            $query->where('causer_id', $filters['user_id'])
                ->where('causer_type', User::class);
        }

        // Filter by date range - required
        if (!empty($filters['date_from'])) {
            $dateFrom = Carbon::parse($filters['date_from'])->startOfDay();
            $query->where('created_at', '>=', $dateFrom);
        }

        if (!empty($filters['date_to'])) {
            $dateTo = Carbon::parse($filters['date_to'])->endOfDay();
            $query->where('created_at', '<=', $dateTo);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get all users for filter dropdown
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function getAllUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();
    }
}

