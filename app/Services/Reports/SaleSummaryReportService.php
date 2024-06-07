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

class SaleSummaryReportService extends ManagementReport
{
    use TeamHierarchyTrait;

    private $groupByColumn;
    private $reportDateRange;

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::SALE_SUMMARY;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::ISSUED_POLICIES;
        $request['groupBy'] = $request->groupBy ?? 'advisor';
        $this->groupByColumn = $request['groupBy'];

        if ($request['policyIssuanceDate'] && ! empty($request['policyIssuanceDate']) && is_array($request['policyIssuanceDate'])) {
            $this->reportDateRange = Carbon::parse($request['policyIssuanceDate'][0])->toDateString()
                .' - '.
                Carbon::parse($request['policyIssuanceDate'][1])->toDateString();
        } elseif ($request['paymentDueDate'] && ! empty($request['paymentDueDate']) && is_array($request['paymentDueDate'])) {
            $this->reportDateRange = Carbon::parse($request['paymentDueDate'][0])->toDateString()
            .' - '.
            Carbon::parse($request['paymentDueDate'][1])->toDateString();
        }

        $query = PersonalQuote::query()
            ->leftJoin('send_update_logs as sul', 'personal_quotes.id', '=', 'sul.personal_quote_id')
            ->leftJoin('lookups as l', 'sul.category_id', '=', 'l.id')
            ->leftJoin('users as u', 'personal_quotes.advisor_id', '=', 'u.id')
            ->leftJoin('user_team', 'u.id', '=', 'user_team.user_id')
            ->leftJoin('teams as t', 'user_team.team_id', '=', 't.id')
            ->join('quote_type', 'personal_quotes.quote_type_id', '=', 'quote_type.id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->join('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->join('payment_splits as ps', 'p.code', '=', 'ps.code')
            ->selectRaw("
            (CAST(SUM(CASE WHEN personal_quotes.policy_issuance_date IS NOT NULL AND personal_quotes.policy_number IS NOT NULL THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED)) as total_policies,
            (CAST(SUM(CASE WHEN sul.id IS NOT NULL AND l.code = 'EF' THEN 1 ELSE 0 END) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED)) as total_endorsements,
            (CAST((SUM(CASE WHEN personal_quotes.policy_issuance_date IS NOT NULL AND personal_quotes.policy_number IS NOT NULL THEN 1 ELSE 0 END) + SUM(CASE WHEN sul.id IS NOT NULL AND l.code = 'EF' THEN 1 ELSE 0 END)) / COUNT(DISTINCT(user_team.team_id)) AS UNSIGNED)) as total_transaction,
            FORMAT(IFNULL(SUM(personal_quotes.price_vat_applicable) / COUNT(DISTINCT(user_team.team_id)) ,0), 2) as price_vat_applicable,
            FORMAT(IFNULL(SUM(personal_quotes.price_vat_applicable) / COUNT(DISTINCT(user_team.team_id)) * 0.05,0), 2) as total_vat,
            FORMAT(IFNULL(SUM(personal_quotes.price_vat_not_applicable) / COUNT(DISTINCT(user_team.team_id)),0), 2) as price_vat_not_applicable,
            FORMAT(IFNULL(SUM(p.discount_value) / COUNT(DISTINCT(user_team.team_id)),0), 2) as discount,
            FORMAT(IFNULL(SUM(p.commission_vat_applicable) / COUNT(DISTINCT(user_team.team_id)),0), 2) as commission_vat_applicable,
            FORMAT(
                IFNULL( ( SUM(personal_quotes.price_vat_applicable) / COUNT(DISTINCT(user_team.team_id)) ), 0) +
                IFNULL( ( SUM(personal_quotes.price_vat_not_applicable) / COUNT(DISTINCT(user_team.team_id)) ), 0) +
                IFNULL( ( SUM(personal_quotes.price_vat_applicable) / COUNT(DISTINCT(user_team.team_id)) ) * 0.05, 0) -
                IFNULL( ( SUM(p.discount_value) / COUNT(DISTINCT(user_team.team_id)) ), 0), 2) as total_price
            ")
            ->when($request->groupBy, function ($query, $groupBy) use ($request) {
                $groupByArray = [];
                $groupBy = $this->resolveGroupByColumn($groupBy);
                array_push($groupByArray, $groupBy);
                $utmGroupBy = $this->getUtmGroup($request, $query);
                if ($utmGroupBy) {
                    array_push($groupByArray, $utmGroupBy);
                }

                return $query->groupBy($groupByArray);
            });

        if ($request->groupBy == 'advisor') {
            $query->addSelect('u.name as advisor');
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

        if ($request->export == 1) {
            $data = $query->get();

            // Columns that are not integar and should not be summed
            $nonIntegarIndexes = [0];

            return $this->download(
                'Sale Summary Report '.$this->reportDateRange,
                $data,
                $this->headings(),
                $nonIntegarIndexes);
        } else {
            return $query->simplePaginate(10)->withQueryString();
        }
    }

    private function resolveGroupByColumn($groupBy)
    {
        $mapping = [
            'policy_issuer' => 'p.policy_issuer_id',
            'customer_group' => 'personal_quotes.customer_id',
            'insurer' => 'p.insurance_provider_id',
            'advisor' => 'u.name',
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

    public function headings(): array
    {
        return [
            ucwords(str_replace('_', ' ', $this->groupByColumn)),
            'Total Policies',
            'Total Endorsements',
            'Total Transactions',
            'Price (VAT applicable)',
            'Total VAT',
            'Price (VAT not applicable)',
            'Discount',
            'Commission',
            'Total Price',
        ];
    }

    public function map($quote): array
    {
        $groupBy = $this->groupByColumn;

        return [
            $quote->$groupBy ?? 'N/A',
            $quote->total_policies ?? 0,
            $quote->total_endorsements ?? 0,
            $quote->total_transaction ?? 0,
            $quote->price_vat_applicable ?? '0.00',
            $quote->total_vat ?? '0.00',
            $quote->price_vat_not_applicable ?? '0.00',
            $quote->discount ?? '0.00',
            $quote->commission_vat_applicable ?? '0.00',
            $quote->total_price ?? '0.00',
        ];
    }
}
