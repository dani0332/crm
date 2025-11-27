<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    protected ActivityLogService $activityLogService;
    protected UserService $userService;

    public function __construct(ActivityLogService $activityLogService, UserService $userService)
    {
        $this->activityLogService = $activityLogService;
        $this->userService = $userService;
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
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        // Remove empty filters
        $filters = array_filter($filters, fn($value) => !empty($value));

        // Always fetch activity logs (filters are optional)
        $perPage = (int) $request->get('per_page', 20);
        $perPage = min(max($perPage, 10), 100); // Limit between 10 and 100
        $activityLogs = $this->activityLogService->getActivityLogs($filters, $perPage);
        
        $users = $this->userService->getAllUsers();

        return Inertia::render('Admin/ActivityLogs/Index', [
            'activityLogs' => $activityLogs,
            'users' => $users,
            'filters' => $request->only([
                'user_id',
                'date_from',
                'date_to',
            ]),
        ]);
    }

}

