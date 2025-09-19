<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Models\UserStatusAuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserStatusLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:'.RolesEnum::Engineering);
    }

    public function index(Request $request)
    {
        $isSearchRequest = $request->filled('user_id') && $request->has('date_range') && is_array($request->date_range) && count($request->date_range) === 2;

        if ($isSearchRequest) {
            $startDate = Carbon::parse($request->date_range[0]);
            $endDate = Carbon::parse($request->date_range[1]);

            if ($startDate->diffInDays($endDate) > 7) {
                return back()->withErrors(['date_range' => 'Date range cannot exceed 7 days.']);
            }

            $statusLogs = UserStatusAuditLog::with(['user:id,name,email'])
                ->where('user_id', $request->user_id)
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
                ->whereBetween('status_changed_at', [
                    $startDate->startOfDay(),
                    $endDate->endOfDay()
                ])
                ->orderBy('status_changed_at', 'desc')
                ->simplePaginate(50)
                ->withQueryString();
        } else {
            $statusLogs = UserStatusAuditLog::query()->where('id', -1)->simplePaginate(0);
        }

        $availableStatuses = UserStatusEnum::withLabels();

        $usersWithLogs = User::select('id', 'name', 'email')
            ->whereHas('statusLogs')
            ->orderBy('name')
            ->get();

        return inertia('Admin/UserStatusLogs/Index', [
            'statusLogs' => $statusLogs,
            'availableStatuses' => $availableStatuses,
            'usersWithLogs' => $usersWithLogs,
        ]);
    }
}
