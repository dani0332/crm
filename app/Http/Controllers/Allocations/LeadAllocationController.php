<?php

namespace App\Http\Controllers\Allocations;

use App\Enums\LeadSourceEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadAllocationController extends Controller
{
    use TeamHierarchyTrait;

    private QuoteTypes $quoteType;

    public function __construct()
    {
        $this->quoteType = QuoteTypes::from(request('quoteType'));
        $permission = match ($this->quoteType) {
            QuoteTypes::CORPLINE => PermissionsEnum::CORPLINE_LEAD_ALLOCATION_DASHBOARD,
            QuoteTypes::CYCLE => PermissionsEnum::CYCLE_LEAD_ALLOCATION_DASHBOARD,
            QuoteTypes::PET => PermissionsEnum::PET_LEAD_ALLOCATION_DASHBOARD,
            QuoteTypes::YACHT => PermissionsEnum::YACHT_LEAD_ALLOCATION_DASHBOARD,
            QuoteTypes::LIFE => PermissionsEnum::LIFE_LEAD_ALLOCATION_DASHBOARD,
            QuoteTypes::HOME => PermissionsEnum::HOME_LEAD_ALLOCATION_DASHBOARD,
        };
        $this->middleware("permission:{$permission}", ['only' => ['index']]);
    }

    private function getAdvisors()
    {
        try {
            $managerRoleIds = Role::where('name', 'like', '%manager%')->pluck('id')->toArray();

            $users = User::activeUser()
                ->select(
                    'users.id as userId',
                    'users.name as userName',
                    DB::RAW('(la.manual_assignment_count  + la.auto_assignment_count) as allocationCount'),
                    DB::RAW("DATE_FORMAT(FROM_UNIXTIME(la.last_allocated), '%d-%m-%Y %H:%i:%s') as lastAllocation"),
                    'la.max_capacity as maxCapacity',
                    'users.status as isAvailable',
                    'la.id as id',
                    'la.manual_assignment_count as manualAllocationCount',
                    'la.auto_assignment_count as autoAllocationCount',
                    'la.reset_cap',
                    DB::RAW('GROUP_CONCAT(teams.name ORDER BY teams.name ASC SEPARATOR ", ") as teamNames')
                )
                ->join('lead_allocation as la', 'la.user_id', 'users.id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', 'user_team.team_id')
                ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'users.id')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->where('la.quote_type_id', in_array($this->quoteType, [QuoteTypes::CORPLINE, QuoteTypes::GROUP_MEDICAL]) ? QuoteTypes::BUSINESS->id() : $this->quoteType->id())
                ->whereIn('r.name', $this->quoteType->advisorRoles())
                // subquery to exclude users with any kind of "manager" roles
                ->whereNotExists(function ($query) use ($managerRoleIds) {
                    $query->select(DB::raw(1))
                        ->from('model_has_roles as mr')
                        ->join('roles as r', 'r.id', '=', 'mr.role_id')
                        ->whereColumn('mr.model_id', 'users.id')
                        ->whereIn('r.id', $managerRoleIds);
                })
                ->groupBy('users.name', 'users.id', 'la.id');
            if (! auth()->user()->hasRole(RolesEnum::Admin)) {
                $userTeamIds = $this->getUserTeams(auth()->id())->pluck('id')->toArray();
                $users = $users->whereIn('teams.id', $userTeamIds);
            }
            if (! auth()->user()->hasRole(RolesEnum::SuperManagerLeadAllocation)) {
                $users = $users->where('users.manager_id', auth()->id());
            }

            return $users->get();
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return [];
        }
    }

    private function getQuotesBaseQuery()
    {
        $from = now()->startOfDay();
        $to = now()->endOfDay();

        return $this->quoteType->model()
            ->whereBetween('created_at', [$from, $to])
            ->when($this->quoteType->isPersonalQuote(), function ($q) {
                $q->where('quote_type_id', $this->quoteType->id());
            })
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY]);
    }

    private function getTodaysTotalLeadsCount(QuoteTypes $quoteType)
    {
        return $this->getQuotesBaseQuery($quoteType)->count();
    }

    private function getTodaysTotalUnAssignedLeadsCount(QuoteTypes $quoteType)
    {
        return $this->getQuotesBaseQuery($quoteType)
            ->whereNull('advisor_id')
            ->isNonSICLead($quoteType)
            ->count();
    }

    public function index(QuoteTypes $quoteType)
    {
        $totalAssignedLeadCount = 0;
        $availableUsers = 0;
        $unAvailableUsers = 0;

        $todayTotalLeadCount = $this->getTodaysTotalLeadsCount($quoteType);
        $todayTotalUnAssignedLeadCount = $this->getTodaysTotalUnAssignedLeadsCount($quoteType);

        $data = $this->getAdvisors($quoteType);
        foreach ($data as $value) {
            $totalAssignedLeadCount = $totalAssignedLeadCount + $value->allocationCount;
            $value->isAvailable == 1 ? $availableUsers++ : $unAvailableUsers++;
        }

        return inertia('LeadAllocation/Index', [
            'totalAssignedLeadCount' => $totalAssignedLeadCount,
            'availableUsers' => $availableUsers,
            'unAvailableUsers' => $unAvailableUsers,
            'todayTotalLeadCount' => $todayTotalLeadCount,
            'todayTotalUnAssignedLeadCount' => $todayTotalUnAssignedLeadCount,
            'quoteType' => $quoteType->value,
            'data' => $data,
        ]);
    }
}
