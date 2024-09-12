<?php

namespace App\Services;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Models\User;
use App\Traits\TeamHierarchyTrait;
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
            $users = User::join('lead_allocation as la', 'la.user_id', 'users.id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', 'user_team.team_id')
                ->where('users.is_active', 1)
                ->where('teams.name', 'SIC 2.0 Unassisted')
                ->where('quote_type_id', QuoteTypes::TRAVEL->id())
                ->select(
                    'users.id as userId',
                    'users.name as userName',
                    'la.is_hardstop as isHardStop',
                )
                ->distinct('users.id');

            // Restrict by user roles if necessary
            if (! auth()->user()->hasRole(RolesEnum::Admin)) {
                $userTeamIds = $this->getUserTeams(auth()->user()->id)->pluck('id')->toArray();
                $users = $users->whereIn('teams.id', $userTeamIds);
            }

            if (! auth()->user()->hasRole(RolesEnum::SuperManagerLeadAllocation)) {
                $users = $users->where('users.manager_id', auth()->user()->id);
            }

            return $users->get();
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
