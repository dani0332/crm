<?php

namespace App\Services;

use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Strategies\ManagementReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleSummaryReportService implements ManagementReport
{
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
        // implementation goes here
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
