<?php

namespace App\Services\Reports;

use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Exports\Reports\ActivePoliciesReportExport;
use App\Models\PersonalQuote;
use App\Strategies\ManagementReport;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivePoliciesReportService extends ManagementReport
{
    use TeamHierarchyTrait;

    private $reportDateRange;

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::ACTIVE_POLICIES;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::ACTIVE_POLICIES;
        if ($request['createdAt'] && ! empty($request['createdAt'])) {
            $this->reportDateRange = Carbon::parse($request['createdAt'])->toDateString();
        }

        $query = PersonalQuote::query()
            ->select(
                DB::raw('COUNT(personal_quotes.id) as active_policy_count'),
                DB::raw('FORMAT(SUM(personal_quotes.price_vat_applicable), 2) as price_with_vat'),
                DB::raw('FORMAT(SUM(price_vat_not_applicable), 2) as price_without_vat'),
                'ip.text as insurer',
                'quote_type.code as line_of_business',
            )
            ->join('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->join('quote_type', 'quote_type.id', '=', 'quote_type_id')
            ->join('insurance_provider as ip', 'ip.id', '=', 'p.insurance_provider_id')
            ->leftJoin('users as u', 'personal_quotes.advisor_id', '=', 'u.id')
            ->groupBy('ip.text', 'personal_quotes.quote_type_id');

        $this->applyFilters($query, $request, isSSR: true);

        if ($request->export == 1) {
            $data = $query->get();

            return (new ActivePoliciesReportExport($data))->download("Active Policies Report {$this->reportDateRange}.xlsx");
        } else {
            return $query->simplePaginate(100)->withQueryString();
        }
    }

    public function getDefaultFilters()
    {
        return [
            'reportCategory' => ManagementReportCategoriesEnum::ACTIVE_POLICIES,
        ];
    }
}
