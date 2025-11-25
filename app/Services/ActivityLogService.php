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
     * Get a single activity log by ID with relationships
     *
     * @param int $id
     * @return ActivityLog|null
     */
    public function getActivityLogById(int $id): ?ActivityLog
    {
        return ActivityLog::with(['causer:id,name,email', 'subject'])
            ->find($id);
    }

    /**
     * Get unique log names for filter dropdown
     *
     * @return array
     */
    public function getUniqueLogNames(): array
    {
        return ActivityLog::select('log_name')
            ->distinct()
            ->whereNotNull('log_name')
            ->orderBy('log_name')
            ->pluck('log_name')
            ->toArray();
    }

    /**
     * Get unique features for filter dropdown
     *
     * @return array
     */
    public function getUniqueFeatures(): array
    {
        return ActivityLog::select('feature')
            ->distinct()
            ->whereNotNull('feature')
            ->orderBy('feature')
            ->pluck('feature')
            ->toArray();
    }

    /**
     * Get unique events for filter dropdown
     *
     * @return array
     */
    public function getUniqueEvents(): array
    {
        return ActivityLog::select('event')
            ->distinct()
            ->whereNotNull('event')
            ->orderBy('event')
            ->pluck('event')
            ->toArray();
    }

    /**
     * Get unique subject types for filter dropdown
     *
     * @return array
     */
    public function getUniqueSubjectTypes(): array
    {
        return ActivityLog::select('subject_type')
            ->distinct()
            ->whereNotNull('subject_type')
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->toArray();
    }

    /**
     * Get all users
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllUsers()
    {
        return User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();
    }
}

