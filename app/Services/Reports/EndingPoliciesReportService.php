<?php

namespace App\Services\Reports;

use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Models\PersonalQuote;
use App\Strategies\ManagementReport;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EndingPoliciesReportService extends ManagementReport
{
    use TeamHierarchyTrait;

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::ENDING_POLICIES;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::EXPIRING_POLICIES;

        $query = PersonalQuote::query()
            ->leftJoin('users as u', 'u.id', '=', 'advisor_id')
            ->leftJoin('quote_type as qt', 'qt.id', '=', 'quote_type_id')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'insurance_provider_id')
            ->leftJoin('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'p.payment_status_id')
            ->leftJoin('customer as c', 'c.id', '=', 'customer_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->join('user_team as ut', 'ut.user_id', '=', 'u.id')
            ->join('teams as t', 't.id', '=', 'ut.team_id')
            ->select(
                DB::raw('CONCAT(c.first_name, " ", c.last_name) as customer_name'),
                'policy_number',
                'ip.text as insurer',
                'qt.code as line_of_business',
                DB::raw("DATE_FORMAT(personal_quotes.policy_start_date, '%Y-%m-%d') as policy_start_date"),
                DB::raw("DATE_FORMAT(p.policy_expiry_date, '%Y-%m-%d') as policy_end_date"),
                DB::raw('FORMAT(SUM(premium), 2) as collected_amount'),
                DB::raw('FORMAT(SUM(price_vat_applicable), 2) as price_vat_applicable'),
                DB::raw('FORMAT(SUM(vat), 2) as total_vat'),
                DB::raw('FORMAT(SUM(price_vat_not_applicable), 2) as price_vat_not_applicable'),
                DB::raw('FORMAT(SUM(p.discount_value), 2) as discount'),
                DB::raw('FORMAT(SUM(price_vat_applicable + price_vat_not_applicable + vat - p.discount_value), 2) as total_price'),
                DB::raw('FORMAT((SUM(price_vat_applicable + price_vat_not_applicable + vat - p.discount_value) - SUM(premium)), 2) as pending_balance'),
                DB::raw('FORMAT(SUM(p.commission_vat_applicable), 2) as commission_vat_applicable'),
                DB::raw('FORMAT(SUM(p.commission_vat), 2) as commission_vat'),
                DB::raw('FORMAT(SUM(p.commission_vat_not_applicable), 2) as commission_vat_not_applicable'),
                'pi.name as policy_issuer',
                'u.name as advisor',
                'personal_quotes.source',
                'personal_quotes.notes',
            );

        $this->applyFilters($query, $request);

        $utmGroupBy = $this->getUtmGroup($request, $query);

        if ($utmGroupBy) {
            $query->groupBy(['personal_quotes.code', $utmGroupBy]);
        } else {
            $query->groupBy('personal_quotes.code');
        }

        return $query->simplePaginate(100)->withQueryString();
    }
    public function getDefaultFilters()
    {
        $dateFormat = config('constants.DATE_FORMAT_ONLY');
        $defaultDate = [
            Carbon::parse(now())->startOfDay()->format($dateFormat),
            Carbon::parse(now())->endOfDay()->format($dateFormat),
        ];

        return [
            'policyExpiredDate' => $defaultDate,
            'reportCategory' => ManagementReportCategoriesEnum::ENDING_POLICIES,
            'reportType' => ManagementReportTypeEnum::EXPIRING_POLICIES,
        ];
    }
}
