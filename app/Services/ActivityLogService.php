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

        // Filter by user (causer)
        if (!empty($filters['user_id'])) {
            $query->where('causer_id', $filters['user_id'])
                ->where('causer_type', User::class);
        }

        // Filter by log name
        if (!empty($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        // Filter by feature
        if (!empty($filters['feature'])) {
            $query->where('feature', $filters['feature']);
        }

        // Filter by event
        if (!empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        // Filter by subject type
        if (!empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $dateFrom = Carbon::parse($filters['date_from'])->startOfDay();
            $query->where('created_at', '>=', $dateFrom);
        }

        if (!empty($filters['date_to'])) {
            $dateTo = Carbon::parse($filters['date_to'])->endOfDay();
            $query->where('created_at', '<=', $dateTo);
        }

        // Filter by code
        if (!empty($filters['code'])) {
            $query->where('code', $filters['code']);
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
     * Get users who have activity logs
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersWithLogs()
    {
        $userIds = ActivityLog::select('causer_id')
            ->where('causer_type', User::class)
            ->whereNotNull('causer_id')
            ->distinct()
            ->pluck('causer_id')
            ->toArray();

        return User::select('id', 'name', 'email')
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get();
    }
}

