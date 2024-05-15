<?php

namespace Database\Seeders;

use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Models\LeadAllocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddCrossLOBSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // get all Advisors Car Lead Allocation
        $carLeadAllocations = DB::table('users')
            ->select(
                'users.id as userId',
                'users.name as userName',
                DB::raw('GROUP_CONCAT(DISTINCT t.name) as tiers'),
                DB::raw('GROUP_CONCAT(DISTINCT q.name) as quads'),
                DB::raw('(la.manual_assignment_count + la.auto_assignment_count) as allocationCount'),
                DB::raw("DATE_FORMAT(FROM_UNIXTIME(la.last_allocated), '%d-%m-%Y %H:%i:%s') as lastAllocation"),
                'la.max_capacity as maxCapacity',
                'users.status as isAvailable',
                DB::raw("DATE_FORMAT(users.last_login, '%d-%m-%Y %H:%i:%s') as lastLogin"),
                'la.id as id',
                'la.manual_assignment_count as manualAllocationCount',
                'la.auto_assignment_count as autoAllocationCount',
                'la.reset_cap'
            )
            ->join('tier_users as tu', 'tu.user_id', '=', 'users.id')
            ->join('tiers as t', 't.id', '=', 'tu.tier_id')
            ->leftJoin('quad_users as qu', 'qu.user_id', '=', 'users.id')
            ->leftJoin('quadrants as q', 'q.id', '=', 'qu.quad_id')
            ->join('lead_allocation as la', 'la.user_id', '=', 'users.id')
            ->join('user_team', 'user_team.user_id', '=', 'users.id')
            ->join('teams', 'teams.id', '=', 'user_team.team_id')
            ->whereNull('la.quote_type_id')
            ->where('users.is_active', 1)
            ->groupBy('users.name', 'users.id', 'la.id')
            ->get()->count();

        if ($carLeadAllocations > 0) {
            // Update Car Lead Allocation By Quote Type Id Wise
            DB::table('lead_allocation')
                ->whereIn('user_id', function ($query) {
                    $query->select('userId')
                        ->from(function ($subquery) {
                            $subquery->select('users.id as userId')
                                ->from('users')
                                ->join('tier_users as tu', 'tu.user_id', '=', 'users.id')
                                ->join('tiers as t', 't.id', '=', 'tu.tier_id')
                                ->leftJoin('quad_users as qu', 'qu.user_id', '=', 'users.id')
                                ->leftJoin('quadrants as q', 'q.id', '=', 'qu.quad_id')
                                ->join('lead_allocation as la', 'la.user_id', '=', 'users.id')
                                ->join('user_team', 'user_team.user_id', '=', 'users.id')
                                ->join('teams', 'teams.id', '=', 'user_team.team_id')
                                ->whereNull('la.quote_type_id')
                                ->where('users.is_active', 1)
                                ->groupBy('users.id', 'la.id');
                        });
                })
                ->update(['quote_type_id' => QuoteTypes::CAR->id()]);
        }

        // get all Advisors Health Lead Allocation
        $healthLeadAllocations = LeadAllocation::select([
            'lead_allocation.id as id',
            'lead_allocation.user_id as userId',
            'lead_allocation.allocation_count',
            'lead_allocation.max_capacity',
            'lead_allocation.reset_cap',
            'u.status as is_available',
            'lead_allocation.reset_cap',
            'lead_allocation.last_allocated',
            't.name as teamName',
            'u.name as userName',
        ])
            ->join('users as u', 'lead_allocation.user_id', '=', 'u.id')
            ->join('user_team as ut', 'ut.user_id', '=', 'u.id')
            ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'mhr.role_id')
            ->leftJoin('teams as t', 'ut.team_id', '=', 't.id')
            ->groupBy('u.name', 'u.id', 'lead_allocation.id')
            ->whereIn('t.name', [TeamNameEnum::EBP, TeamNameEnum::RM_NB, TeamNameEnum::RM_SPEED])
            ->whereNull('lead_allocation.quote_type_id')
            ->where('u.is_active', true)
            ->whereIn('r.name', [RolesEnum::EBPAdvisor, RolesEnum::RMAdvisor])->get()->count();

        if ($healthLeadAllocations > 0) {

            // Update Health Lead Allocation By Quote Type Id Wise
            LeadAllocation::join('users as u', 'lead_allocation.user_id', '=', 'u.id')
                ->join('user_team as ut', 'ut.user_id', '=', 'u.id')
                ->join('model_has_roles as mhr', 'mhr.model_id', '=', 'u.id')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->leftJoin('teams as t', 'ut.team_id', '=', 't.id')
                ->whereIn('t.name', [TeamNameEnum::EBP, TeamNameEnum::RM_NB, TeamNameEnum::RM_SPEED])
                ->whereNull('lead_allocation.quote_type_id')
                ->where('u.is_active', true)
                ->whereIn('r.name', [RolesEnum::EBPAdvisor, RolesEnum::RMAdvisor])
                ->update(['lead_allocation.quote_type_id' => QuoteTypes::HEALTH->id()]);
        }
    }
}
