<?php

namespace App\Services;

use App\Enums\LeadAllocationUserBLStatusFiltersEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Role;
use App\Models\User;
use App\Models\UserManager;
use App\Models\TravelQuote;
use App\Services\Logger\LoggerService;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TravelLeadAllocationDashboardService extends BaseService
{
    use TeamHierarchyTrait;

    protected $applicationStorageService;
    public function __construct(ApplicationStorageService $applicationStorageService)
    {
        $this->applicationStorageService = $applicationStorageService;
    }

    public function getSicUsersGridData(?string $BlStatus = null)
    {
        try {
            $managerRoleIds = Role::where('name', 'like', '%manager%')->pluck('id')->toArray();

            // Fetch users excluding those who have any "manager" role
            $users = User::join('lead_allocation as la', 'la.user_id', 'users.id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', 'user_team.team_id')
                ->activeUser()
                ->where('teams.name', TeamNameEnum::SIC_UNASSISTED)
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

            $data = $users->get();

            // Filters
            if ($BlStatus) {
                $userBlStatus = LeadAllocationUserBLStatusFiltersEnum::from($BlStatus);
                $data = $userBlStatus->applyFilter($data);
            }

            return $data;
        } catch (\Exception $e) {
            // Log the error with relevant context for debugging
            LoggerService::error('Failed to retrieve SIC 2.0 Unassisted users', [
                'message' => $e->getMessage(),
                'user_id' => auth()->user()->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function getTodaysTotalUnAssignedLeadsCount(): int
    {
        $count = TravelQuote::whereRaw('DATE(created_at) = CURDATE()')
            ->whereNull('advisor_id')
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
            ->count();

        return $count;
        /*return CarQuote::leftJoin('tiers', 'tiers.id', 'car_quote_request.tier_id')
        ->whereNull('advisor_id')
        ->whereNotIn('car_quote_request.quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate])
        ->where('tiers.name', '!=', TiersEnum::TIER_R)
        ->whereBetween('car_quote_request.created_at', [now()->startOfDay(), now()->subMinutes(2)->toDateTimeString()])
        ->whereNotIn('car_quote_request.source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD])
        ->whereNotIn('car_quote_request.uuid', function ($query) { // to remove from the query tags table to exlude SIC records from the result set
            $query->distinct()
                ->select('quote_uuid')
                ->from('quote_tags')
                ->join('quote_type', 'quote_type.id', 'quote_tags.quote_type_id')
                ->where('quote_tags.name', 'SIC')
                ->where('quote_type.code', quoteTypeCode::Car);
        })
        ->count();*/
    }
}
