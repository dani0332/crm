<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\V2\Admin\IndexActivityLogRequest;
use App\Services\ActivityLogService;
use App\Services\UserService;
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
     * @param IndexActivityLogRequest $request
     * @return Response
     */
    public function index(IndexActivityLogRequest $request): Response
    {
        $filters = $request->getFilters();
        $activityLogs = $this->activityLogService->getActivityLogs($filters);
        
        $users = $this->userService->getAllUsers();

        return Inertia::render('Admin/ActivityLogs/Index', [
            'activityLogs' => $activityLogs,
            'users' => $users,
            'filters' => $request->getFiltersForResponse(),
        ]);
    }

}

