<?php

namespace App\Services;

use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Models\PersonalQuote;
use App\Strategies\ManagementReport;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleSummaryReportService extends ManagementReport
{
    use TeamHierarchyTrait;

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::SALE_SUMMARY;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::ISSUED_POLICIES;
        $request['groupBy'] = $request->groupBy ?? 'advisor';

        $query = PersonalQuote::query()
            ->leftJoin('send_update_logs as sul', 'personal_quotes.id', '=', 'sul.personal_quote_id')
            ->leftJoin('lookups as l', 'sul.category_id', '=', 'l.id')
            ->leftJoin('users', 'personal_quotes.advisor_id', '=', 'users.id')
            ->leftJoin('user_team', 'users.id', '=', 'user_team.user_id')
            ->leftJoin('teams', 'user_team.team_id', '=', 'teams.id')
            ->join('quote_type', 'personal_quotes.quote_type_id', '=', 'quote_type.id')
            ->join('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->selectRaw("
            FORMAT(SUM(CASE WHEN COALESCE(policy_issuance_date, personal_quotes.policy_number) IS NOT NULL THEN 1 ELSE 0 END), 2) as total_policies,
            FORMAT(SUM(CASE WHEN sul.id IS NOT NULL AND l.code = 'EF' THEN 1 ELSE 0 END), 2) as total_endorsements,
            FORMAT(SUM(CASE WHEN COALESCE(policy_issuance_date, personal_quotes.policy_number) IS NOT NULL THEN 1 ELSE 0 END) + SUM(CASE WHEN sul.id IS NOT NULL AND l.code = 'EF' THEN 1 ELSE 0 END), 2) as total_transaction,
            FORMAT(IFNULL(SUM(price_vat_applicable),0), 2) as price_vat_applicable,
            FORMAT(IFNULL(SUM(price_vat_applicable) * 0.05,0), 2) as total_vat,
            FORMAT(IFNULL(SUM(price_vat_not_applicable), 0), 2) as price_vat_not_applicable,
            FORMAT(IFNULL(SUM(p.discount_value), 0), 2) as discount,
            FORMAT(IFNULL(SUM(p.commission_vat_applicable), 0), 2) as commission_vat_applicable,
            FORMAT(IFNULL(SUM(price_vat_applicable), 0) + IFNULL(SUM(price_vat_not_applicable), 0) + IFNULL(SUM(price_vat_applicable) * 0.05, 0) - IFNULL(SUM(p.discount_value), 0), 2) as total_price
            ")
            ->when($request->groupBy, function ($query, $groupBy) {
                return $query->groupBy($this->resolveGroupByColumn($groupBy));
            });

        if ($request->groupBy == 'advisor') {
            $query->addSelect('users.name as advisor');
            $query->whereNotNull('advisor_id');
        }

        if ($request->groupBy == 'customer_group') {
            $query->leftJoin('customer', 'personal_quotes.customer_id', '=', 'customer.id')
                ->addSelect(DB::raw("CONCAT(customer.first_name, ' ', customer.last_name) as customer_group"));
            $query->whereNotNull('customer_id');
        }

        if ($request->groupBy == 'insurer') {
            $query->join('insurance_provider', 'insurance_provider.id', '=', 'p.insurance_provider_id')
                ->addSelect('insurance_provider.text as insurer');
            $query->whereNotNull('p.insurance_provider_id');
        }

        if ($request->groupBy == 'policy_issuer') {
            $query->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
                ->addSelect('pi.name as policy_issuer');
            $query->whereNotNull('p.policy_issuer_id');
        }

        if ($request->groupBy == 'line_of_business') {
            $query->addSelect('quote_type.code as line_of_business');
            $query->whereNotNull('quote_type.code');
        }

        $this->applyFilters($query, $request);

        //dd($query->toSql(), $query->getBindings());
        return $query->simplePaginate(10)->withQueryString();
    }

    private function resolveGroupByColumn($groupBy)
    {
        $mapping = [
            'policy_issuer' => 'p.policy_issuer_id',
            'customer_group' => 'personal_quotes.customer_id',
            'insurer' => 'p.insurance_provider_id',
            'advisor' => 'users.name',
            'line_of_business' => 'quote_type.code',
        ];

        return $mapping[$groupBy] ?? $groupBy;
    }

    public function getDefaultFilters()
    {
        $dateFormat = config('constants.DATE_FORMAT_ONLY');
        $defaultDate = [
            Carbon::parse(now())->startOfDay()->format($dateFormat),
            Carbon::parse(now())->endOfDay()->format($dateFormat),
        ];

        return [
            'policyIssuanceDate' => $defaultDate,
            'reportCategory' => ManagementReportCategoriesEnum::SALE_SUMMARY,
            'reportType' => ManagementReportTypeEnum::ISSUED_POLICIES,
        ];
    }
}
