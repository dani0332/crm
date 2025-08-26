<?php

namespace App\Services;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\Role;
use App\Models\User;
use App\Models\UserManager;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TravelLeadAllocationDashboardService extends BaseService
{
    use TeamHierarchyTrait;

    protected $applicationStorageService;
    public function __construct(ApplicationStorageService $applicationStorageService)
    {
        $this->applicationStorageService = $applicationStorageService;
    }

    public function getSicUsersGridData()
    {
        try {
            $managerRoleIds = Role::where('name', 'like', '%manager%')->pluck('id')->toArray();

            // Fetch users excluding those who have any "manager" role
            $users = User::join('lead_allocation as la', 'la.user_id', 'users.id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', 'user_team.team_id')
                ->activeUser()
                ->where('teams.name', 'SIC 2.0 Unassisted')
                ->where('quote_type_id', QuoteTypes::TRAVEL->id())
                // subquery to exclude users with any kind of "manager" roles
                ->whereNotExists(function ($query) use ($managerRoleIds) {
                    $query->select(DB::raw(1))
                        ->from('model_has_roles as mr')
                        ->join('roles as r', 'r.id', '=', 'mr.role_id')
                        ->whereColumn('mr.model_id', 'users.id')
                        ->whereIn('r.id', $managerRoleIds);
                })
                ->select(
                    'users.id as userId',
                    'users.name as userName',
                    'la.is_hardstop as isHardStop',
                    DB::RAW('(la.manual_assignment_count  + la.auto_assignment_count) as allocationCount'),
                    DB::RAW("DATE_FORMAT(FROM_UNIXTIME(la.last_allocated), '%d-%m-%Y %H:%i:%s') as lastAllocation"),
                    'la.max_capacity as maxCapacity',
                    'users.status as isAvailable',
                    DB::RAW("DATE_FORMAT(users.last_login, '%d-%m-%Y %H:%i:%s') as lastLogin"),
                    'la.id as id',
                    'la.manual_assignment_count as manualAllocationCount',
                    'la.auto_assignment_count as autoAllocationCount',
                    'la.reset_cap',
                    'la.buy_lead_max_capacity as BLMaxCapacity',
                    'la.buy_lead_allocation_count as BLAllocationCount',
                    'la.buy_lead_status as BLStatus',
                    'la.normal_allocation_enabled as normalAllocationEnabled',
                    'la.buy_lead_reset_capacity as blResetCap',
                )
                ->distinct('users.id');

            if (! auth()->user()->hasRole(RolesEnum::Admin)) {
                $userTeamIds = $this->getUserTeams(auth()->user()->id)->pluck('id')->toArray();
                $users = $users->whereIn('teams.id', $userTeamIds);
            }

            if (! auth()->user()->hasRole(RolesEnum::SuperManagerLeadAllocation)) {
                $userIds = UserManager::where('manager_id', Auth::id())->pluck('user_id')->toArray();
                $users = $users->whereIn('users.id', $userIds);
            }

            //return $users->get();
            dd($users->toSql());
        } catch (\Exception $e) {
            // Log the error with relevant context for debugging
            Log::error('Failed to retrieve SIC 2.0 Unassisted users', [
                'message' => $e->getMessage(),
                'user_id' => auth()->user()->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
