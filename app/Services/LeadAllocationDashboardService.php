<?php

namespace App\Services;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Enums\TeamTypeEnum;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\UserManager;
use App\Services\Logger\LoggerService;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeadAllocationDashboardService extends BaseService
{
    use TeamHierarchyTrait;

    public function getAdvisors($quoteType)
    {
        try {
            $managerRoleIds = Role::where('name', 'like', '%manager%')->pluck('id')->toArray();

            $teamName = $this->getTeamName($quoteType);
            $team = Team::where('type', TeamTypeEnum::PRODUCT)->where('name', $teamName)->first();
            $advisorRoles = $quoteType->advisorRoles();

            if ($quoteType == QuoteTypes::SAVINGS) {
                $advisorRoles[] = RolesEnum::SavingsManager;
            }

            $users = User::activeUser()
                ->select(
                    'users.id as userId',
                    'users.name as userName',
                    DB::RAW('(la.manual_assignment_count  + la.auto_assignment_count) as allocationCount'),
                    DB::RAW("DATE_FORMAT(FROM_UNIXTIME(la.last_allocated), '%d-%m-%Y %H:%i:%s') as lastAllocation"),
                    DB::RAW("DATE_FORMAT(users.last_login, '%d-%m-%Y %H:%i:%s') as lastLogin"),
                    'la.max_capacity as maxCapacity',
                    'users.status as isAvailable',
                    'la.id as id',
                    'la.manual_assignment_count as manualAllocationCount',
                    'la.auto_assignment_count as autoAllocationCount',
                    'la.normal_allocation_enabled as normalAllocationEnabled',
                    'la.reset_cap',
                    DB::RAW("
                        GROUP_CONCAT(
                            CASE
                                WHEN teams.parent_team_id = {$team?->id} THEN teams.name
                                ELSE NULL
                            END
                            ORDER BY teams.name ASC SEPARATOR ', '
                        ) AS teamNames
                    ")
                )
                ->join('lead_allocation as la', 'la.user_id', 'users.id')
                ->join('user_team', 'user_team.user_id', 'users.id')
                ->join('teams', 'teams.id', 'user_team.team_id')
                ->join('model_has_roles as mhr', 'mhr.model_id', 'users.id')
                ->join('roles as r', 'r.id', 'mhr.role_id')
                ->where('la.quote_type_id', in_array($quoteType, [QuoteTypes::CORPLINE, QuoteTypes::GROUP_MEDICAL]) ? QuoteTypes::BUSINESS->id() : $quoteType->id())
                ->whereIn('r.name', $advisorRoles)
                ->when($quoteType !== QuoteTypes::SAVINGS, function ($query) use ($managerRoleIds) {
                    // subquery to exclude users with any kind of "manager" roles
                    $query->whereNotExists(function ($query) use ($managerRoleIds) {
                        $query->select(DB::raw(1))
                            ->from('model_has_roles as mr')
                            ->join('roles as r', 'r.id', '=', 'mr.role_id')
                            ->whereColumn('mr.model_id', 'users.id')
                            ->whereIn('r.id', $managerRoleIds);
                    });
                })
                ->groupBy('users.name', 'users.id', 'la.id');

            if (! auth()->user()->hasRole(RolesEnum::Admin)) {
                $userTeamIds = $this->getUserTeams(auth()->id())->pluck('id')->toArray();
                $users = $users->whereIn('teams.id', $userTeamIds);
            }

            if (! auth()->user()->hasRole(RolesEnum::SuperManagerLeadAllocation)) {
                $userIds = UserManager::where('manager_id', Auth::id())->pluck('user_id')->toArray();
                $users = $users->whereIn('users.id', $userIds);
            }

            return $users->get();
        } catch (\Exception $e) {
            LoggerService::error($e->getMessage());

            return [];
        }
    }

    private function getQuotesBaseQuery($quoteType)
    {
        $from = now()->startOfDay();
        $to = now()->endOfDay();

        return $quoteType->model()
            ->whereBetween('created_at', [$from, $to])
            ->when($quoteType->isPersonalQuote(), function ($q) use ($quoteType) {
                $q->where('quote_type_id', $quoteType->id());
            })
            ->whereNotIn('quote_status_id', [QuoteStatusEnum::Fake, QuoteStatusEnum::Duplicate, QuoteStatusEnum::Lost])
            ->whereNotIn('source', [LeadSourceEnum::IMCRM, LeadSourceEnum::RENEWAL_UPLOAD, LeadSourceEnum::INSLY]);
    }

    public function getTodaysTotalLeadsCount(QuoteTypes $quoteType)
    {
        return $this->getQuotesBaseQuery($quoteType)->count();
    }

    public function getTodaysTotalUnAssignedLeadsCount(QuoteTypes $quoteType)
    {
        return $this->getQuotesBaseQuery($quoteType)
            ->whereNull('advisor_id')
            ->isNonSICLead($quoteType)
            ->count();
    }

    public function getTodaysFrequencyBasedUnAssignedLeadsCount(QuoteTypes $quoteType, InvestmentFrequencyEnum $frequency)
    {
        return $this->getQuotesBaseQuery($quoteType)
            ->whereNull('advisor_id')
            ->isNonSICLead($quoteType)
            ->whereHas('savingsQuote.investmentFrequency', function ($query) use ($frequency) {
                $query->where('code', $frequency->value);
            })
            ->count();
    }

    private function getTeamName(QuoteTypes $quoteType): string
    {
        return match ($quoteType) {
            QuoteTypes::CYBER => TeamNameEnum::CYBER,
            default => $quoteType->value,
        };
    }

}
