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
        $statusLogs = UserStatusAuditLog::with(['user:id,name,email'])
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->user_id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($query) => $query->where('status_changed_at', '>=', Carbon::createFromFormat('Y-m-d', $request->date_from)->startOfDay()))
            ->when($request->filled('date_to'), fn ($query) => $query->where('status_changed_at', '<=', Carbon::createFromFormat('Y-m-d', $request->date_to)->endOfDay()))
            ->orderBy('status_changed_at', 'desc')
            ->simplePaginate(50)
            ->withQueryString();

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
