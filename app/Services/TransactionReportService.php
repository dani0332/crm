<?php

namespace App\Services;

use App\Enums\GenericRequestEnum;
use App\Models\LeadSource;
use App\Models\PersonalQuote;
use App\Models\Team;
use App\Strategies\ManagementReport;
use App\Traits\TeamHierarchyTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionReportService implements ManagementReport
{
    use TeamHierarchyTrait;

    public function getReportData(Request $request)
    {
        $groupBy = $request->groupBy;
        $query = PersonalQuote::query()
            ->select(
                DB::raw('SUM(CASE WHEN COALESCE(policy_start_date, policy_number) IS NOT NULL THEN 1 ELSE 0 END) as total_policies'),
                DB::raw('SUM(CASE WHEN send_update_ref_id is not null and send_update_type = "Financial" THEN 1 ELSE 0 END) as total_endorsements'),
            )->get();
    }

    public function getFilterOptions()
    {



        $maxDays = ApplicationStorageService::getValueByKeyName(GenericRequestEnum::MAX_DAYS);

        $loginUserId = auth()->user()->id;

        $teamIds = $this->getUserTeams($loginUserId);

        $teams = Team::whereIn('id', $teamIds->pluck('id'))
            ->select('name', 'id')
            ->orderBy('name')
            ->where('is_active', 1)
            ->get()
            ->keyBy('id')
            ->map(fn ($users) => $users->name)
            ->toArray();
        $leadSources = LeadSource::query()
            ->select('name')
            ->where('is_active', 1)->where('is_applicable_for_rules', 0)
            ->whereNotNull('name')
            ->orderBy('name')
            ->get()
            ->keyBy('name')
            ->map(fn ($users) => $users->name)
            ->toArray();

        return [
            'maxDays' => $maxDays,
            'leadSources' => $leadSources,
            'teams' => $teams,
        ];
    }

    public function getDefaultFilters()
    {
        // implementation goes here
    }

    public function applyFilters($query, $filters)
    {
        // implementation goes here
    }
}
