<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Display a listing of activity logs
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $filters = [
            'user_id' => $request->get('user_id'),
            'log_name' => $request->get('log_name'),
            'feature' => $request->get('feature'),
            'event' => $request->get('event'),
            'subject_type' => $request->get('subject_type'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'code' => $request->get('code'),
        ];

        // Remove empty filters
        $filters = array_filter($filters, fn($value) => !empty($value));

        // Only fetch activity logs if filters are applied
        $activityLogs = null;
        if (!empty($filters)) {
            $perPage = (int) $request->get('per_page', 20);
            $perPage = min(max($perPage, 10), 100); // Limit between 10 and 100
            $activityLogs = $this->activityLogService->getActivityLogs($filters, $perPage);
        }
        $users = $this->activityLogService->getUsersWithLogs();
        $logNames = $this->activityLogService->getUniqueLogNames();
        $features = $this->activityLogService->getUniqueFeatures();
        $events = $this->activityLogService->getUniqueEvents();
        $subjectTypes = $this->activityLogService->getUniqueSubjectTypes();

        return Inertia::render('Admin/ActivityLogs/Index', [
            'activityLogs' => $activityLogs,
            'users' => $users,
            'logNames' => $logNames,
            'features' => $features,
            'events' => $events,
            'subjectTypes' => $subjectTypes,
            'filters' => $request->only([
                'user_id',
                'log_name',
                'feature',
                'event',
                'subject_type',
                'date_from',
                'date_to',
                'code',
            ]),
        ]);
    }

}

