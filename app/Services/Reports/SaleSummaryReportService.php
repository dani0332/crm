<?php

namespace App\Services\Reports;

use App\Enums\EndorsementStatusEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Exports\Reports\SaleSummaryReportExport;
use App\Models\Lookup;
use App\Models\PersonalQuote;
use App\Models\SendUpdateLog;
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
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::BOOKED_POLICIES;
        $request['groupBy'] = $request->groupBy ?? 'advisor';
        $this->groupByColumn = $request['groupBy'];

        if ($request['policyBookDate'] && ! empty($request['policyBookDate']) && is_array($request['policyBookDate'])) {
            $this->reportDateRange = Carbon::parse($request['policyBookDate'][0])->toDateString()
                .' - '.
                Carbon::parse($request['policyBookDate'][1])->toDateString();
        } elseif ($request['paymentDueDate'] && ! empty($request['paymentDueDate']) && is_array($request['paymentDueDate'])) {
            $this->reportDateRange = Carbon::parse($request['paymentDueDate'][0])->toDateString()
            .' - '.
            Carbon::parse($request['paymentDueDate'][1])->toDateString();
        }

        // Subquery to get distinct payment splits with minimum due_date
        $distinctPaymentSplits = DB::table('payment_splits as dps')
            ->selectRaw('DISTINCT(code), due_date');

        $query = PersonalQuote::query()
            ->leftJoin('users as u', 'personal_quotes.advisor_id', '=', 'u.id')
            ->leftJoin('departments as dp', 'dp.id', '=', 'u.department_id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->join('quote_type', 'personal_quotes.quote_type_id', '=', 'quote_type.id')
            ->join('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->selectRaw('
            COUNT(DISTINCT(personal_quotes.uuid)) as total_policies,
            COUNT(DISTINCT(personal_quotes.uuid)) as total_transaction,
            SUM(personal_quotes.price_vat_applicable) as price_vat_applicable,
            IFNULL(SUM(personal_quotes.vat),0) as total_vat,
            IFNULL(SUM(personal_quotes.price_vat_not_applicable),0) as price_vat_not_applicable,
            IFNULL(SUM(p.discount_value) ,0) as discount,
            IFNULL(SUM(p.commission_vat_applicable) ,0) as commission_vat_applicable,
            IFNULL(SUM(p.commission_vat), 0) as commission_vat,
            IFNULL(SUM(p.commission_vat_not_applicable) ,0) as commission_vat_not_applicable,
            IFNULL( ( SUM(personal_quotes.price_vat_applicable)  ), 0) +
                IFNULL( ( SUM(personal_quotes.price_vat_not_applicable) ), 0) +
                IFNULL( ( SUM(personal_quotes.vat)  ), 0) -
                IFNULL( ( SUM(p.discount_value) ), 0) as total_price
            ')
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
            $query
                ->addSelect(DB::raw('IFNULL(u.name, "N/A") as advisor'))
                ->addSelect(DB::raw('IFNULL(dp.name, "N/A") as department'));
        }

        if ($request->groupBy == 'department') {
            $query->addSelect(DB::raw('IFNULL(dp.name, "N/A") as department'));
        }

        if ($request->groupBy == 'customer_group') {
            $query->join('customer', 'personal_quotes.customer_id', '=', 'customer.id')
                ->addSelect(DB::raw("CONCAT(customer.first_name, ' ', customer.last_name) as customer_name"))
                ->addSelect('customer.id as customer_group');
        }

        if ($request->groupBy == 'insurer') {
            $query->join('insurance_provider', 'insurance_provider.id', '=', 'p.insurance_provider_id')
                ->addSelect(DB::raw('IFNULL(insurance_provider.text, "N/A") as insurer'));
        }

        if ($request->groupBy == 'policy_issuer') {
            $query
                ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
                ->addSelect('p.policy_issuer_id as policy_issuer')
                ->addSelect('pi.name as policy_issuer_name');
        }

        if ($request->groupBy == 'line_of_business') {
            $query->addSelect('quote_type.code as line_of_business');
        }

        if ($request['reportType'] == ManagementReportTypeEnum::APPROVED_TRANSACTIONS) {
            $query->joinSub($distinctPaymentSplits, 'ps', function ($join) {
                $join->on('p.code', '=', 'ps.code');
            });
        } elseif ($request['reportType'] == ManagementReportTypeEnum::PAID_TRANSACTIONS) {
            $PaymentSplits = DB::table('payment_splits as dps')
                ->selectRaw('dps.code, dps.verified_at')
                ->groupBy('dps.code');
            $this->getDateFilter($PaymentSplits, $request, 'verified_at', 'paymentDate');
            $query->joinSub($PaymentSplits, 'ps', function ($join) {
                $join->on('p.code', '=', 'ps.code');
            });
        }
        $this->applyFilters($query, $request, false, true);

        $data = $query->get();

        if ($request->export == 1) {

            /**
             * Get endorsements data
             */
            $endorsementsData = $this->getEndorsementsData($request);

            /**
             * Process endorsements data for pdf
             */
            $processedData = $this->processEndorsementsData($data, $endorsementsData, $request);

            $this->formatData($processedData);

            return (new SaleSummaryReportExport($processedData, $this->groupByColumn))->download("Sale Summary Report {$this->reportDateRange}.xlsx");
        } else {
            return $data;
        }
    }

    /**
     * Get endorsements data
     *
     * @return mixed
     */
    public function getEndorsementsData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::SALE_SUMMARY;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::BOOKED_POLICIES;
        $request['groupBy'] = $request->groupBy ?? 'advisor';
        $this->groupByColumn = $request['groupBy'];

        // lookupQuery
        $endrosementCategoryIds = Lookup::query()
            ->select('id')
            ->whereIn('code', [
                EndorsementStatusEnum::ENDORSEMENT_FINANCIAL_CODE,
                EndorsementStatusEnum::CANCELLATION_FROM_INCEPTION,
                EndorsementStatusEnum::CANCELLATION_FROM_INCEPTION_AND_REISSUANCE,
                EndorsementStatusEnum::CORRECTION_OF_POLICY_DETAILS])
            ->pluck('id')->toArray();

        // Subquery to get distinct payment splits with minimum due_date
        $distinctPaymentSplits = DB::table('payment_splits as dps')
            ->select('dps.code', 'due_date')
            ->groupBy('dps.code');

        $query = SendUpdateLog::query()
            ->leftJoin('personal_quotes', 'send_update_logs.personal_quote_id', '=', 'personal_quotes.id')
            ->leftJoin('payments as pq', 'pq.code', '=', 'personal_quotes.code')
            ->leftJoin('payments as p', 'send_update_logs.id', '=', 'p.send_update_log_id')
            ->leftJoin('payment_splits as ps', 'p.code', '=', 'ps.code')
            ->join('quote_type', 'personal_quotes.quote_type_id', '=', 'quote_type.id')
            ->leftJoin('users as u', 'u.id', '=', 'personal_quotes.advisor_id')
            ->leftJoin('departments as dp', 'dp.id', '=', 'u.department_id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->leftJoin('lookups as l', 'send_update_logs.option_id', '=', 'l.id')
            ->select(
                DB::raw('COUNT(send_update_logs.uuid) as total_endorsements'),
                DB::raw('((
                    sum(IFNULL( ps.price_vat_applicable , IFNULL( send_update_logs.price_vat_applicable , 0 ) + IFNULL( send_update_logs.price_vat_not_applicable , 0 ))) +
                    sum(IFNULL( IFNULL(ps.price_vat, send_update_logs.total_vat_amount) , 0 ))) -
                    sum(IFNULL( IF(ps.discount_value IS NULL OR ps.discount_value = 0, send_update_logs.discount, ps.discount_value) , 0 ))) as total_endorsement_amount'),
                DB::raw('sum(CASE WHEN ps.sr_no is NULL OR ps.sr_no=1 THEN IFNULL(send_update_logs.commission_vat_applicable, 0) ELSE 0 END) as commission_vat_applicable'),
                DB::raw('sum(CASE WHEN ps.sr_no is NULL OR ps.sr_no=1 THEN IFNULL(send_update_logs.vat_on_commission, 0) ELSE 0 END) as commission_vat'),
                DB::raw('sum(CASE WHEN ps.sr_no is NULL OR ps.sr_no=1 THEN IFNULL(p.commission_vat_not_applicable, IFNULL(send_update_logs.commission_vat_not_applicable, 0)) ELSE 0 END) as commission_vat_not_applicable'),
            )
            ->where('send_update_logs.status', '=', EndorsementStatusEnum::UPDATE_BOOKED)
            ->whereIn('send_update_logs.category_id', $endrosementCategoryIds)
            ->when($request->groupBy, function ($query, $groupBy) use ($request) {
                $groupByArray = [];
                $groupBy = $this->resolveGroupByColumn($groupBy, true);
                array_push($groupByArray, $groupBy);
                $utmGroupBy = $this->getUtmGroup($request, $query);
                if ($utmGroupBy) {
                    array_push($groupByArray, $utmGroupBy);
                }

                return $query->groupBy($groupByArray);
            });

        if ($request->groupBy == 'advisor') {
            // Endorsements
            $query
                ->addSelect(DB::raw('IFNULL(u.name, "N/A") as advisor'))
                ->addSelect(DB::raw('IFNULL(dp.name, "N/A") as department'));
        }

        if ($request->groupBy == 'department') {
            $query->addSelect(DB::raw('IFNULL(dp.name, "N/A") as department'));
        }

        if ($request->groupBy == 'customer_group') {
            // Endorsements
            $query->leftJoin('customer', 'personal_quotes.customer_id', '=', 'customer.id')
                ->addSelect(DB::raw("CONCAT(customer.first_name, ' ', customer.last_name) as customer_name"))
                ->addSelect('customer.id as customer_group');
        }

        if ($request->groupBy == 'insurer') {
            // Endorsements
            $query
                ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'pq.insurance_provider_id')
                ->leftJoin('insurance_provider as ip2', 'ip2.id', '=', 'send_update_logs.insurance_provider_id')
                ->addSelect(DB::raw('CASE WHEN l.code="CII"  OR ip.text is null THEN IFNULL(ip2.text, "N/A") ELSE IFNULL(ip.text, "N/A") END as insurer'));
        }

        if ($request->groupBy == 'policy_issuer') {
            // Endorsements
            $query
                ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
                ->addSelect('p.policy_issuer_id as policy_issuer')
                ->addSelect('pi.name as policy_issuer_name');
        }

        if ($request->groupBy == 'line_of_business') {
            // Endorsements
            $query->addSelect('quote_type.code as line_of_business');
        }
        $query = $this->applyFilters($query, $request, true, true);

        $reversalQuery = SendUpdateLog::query()
            ->leftJoin('personal_quotes', 'send_update_logs.personal_quote_id', '=', 'personal_quotes.id')
            ->leftJoin('payments as pq', 'pq.code', '=', 'personal_quotes.code')
            ->leftJoin('payments as p', 'send_update_logs.reversal_invoice', '=', 'p.insurer_tax_number')
            ->leftJoin('send_update_logs as S2', 'send_update_logs.reversal_invoice', '=', 's2.insurer_tax_invoice_number')
            ->join('quote_type', 'personal_quotes.quote_type_id', '=', 'quote_type.id')
            ->leftJoin('users as u', 'u.id', '=', 'personal_quotes.advisor_id')
            ->leftJoin('departments as dp', 'dp.id', '=', 'u.department_id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->leftJoin('lookups as l', 'send_update_logs.option_id', '=', 'l.id')
            ->whereNotNull('send_update_logs.reversal_invoice')
            ->select(
                DB::raw('COUNT(send_update_logs.uuid) as total_endorsements'),
                DB::raw('-1 * ((
                 sum(IFNULL(s2.price_vat_applicable, IFNULL(p.price_vat_applicable, 0))) +
                 sum(IFNULL(IFNULL(s2.total_vat_amount, IFNULL(p.price_vat, 0)), 0))) -
                 sum(IFNULL(p.discount_value, 0))) as total_endorsement_amount'),
                DB::raw('sum(-1 * IFNULL(s2.commission_vat_applicable, IFNULL(p.commission_vat_applicable, 0))) as commission_vat_applicable'),
                DB::raw('sum(-1 * IFNULL(p.commission_vat, IFNULL(s2.vat_on_commission, 0))) as commission_vat'),
                DB::raw('sum(-1 * IFNULL(s2.commission_vat_not_applicable, IFNULL(p.commission_vat_not_applicable, 0))) as commission_vat_not_applicable'),
            )
            ->where('send_update_logs.status', '=', EndorsementStatusEnum::UPDATE_BOOKED)
            ->whereIn('send_update_logs.category_id', $endrosementCategoryIds)
            ->when($request->groupBy, function ($reversalQuery, $groupBy) use ($request) {
                $groupByArray = [];
                $groupBy = $this->resolveGroupByColumn($groupBy, true);
                array_push($groupByArray, $groupBy);
                $utmGroupBy = $this->getUtmGroup($request, $reversalQuery);
                if ($utmGroupBy) {
                    array_push($groupByArray, $utmGroupBy);
                }

                return $reversalQuery->groupBy($groupByArray);
            });

        if ($request->groupBy == 'advisor') {
            // Endorsements
            $reversalQuery
                ->addSelect(DB::raw('IFNULL(u.name, "N/A") as advisor'))
                ->addSelect(DB::raw('IFNULL(dp.name, "N/A") as department'));
        }

        if ($request->groupBy == 'department') {
            $reversalQuery->addSelect(DB::raw('IFNULL(dp.name, "N/A") as department'));
        }

        if ($request->groupBy == 'customer_group') {
            // Endorsements
            $reversalQuery->leftJoin('customer', 'personal_quotes.customer_id', '=', 'customer.id')
                ->addSelect(DB::raw("CONCAT(customer.first_name, ' ', customer.last_name) as customer_name"))
                ->addSelect('customer.id as customer_group');
        }

        if ($request->groupBy == 'insurer') {
            // Endorsements
            $reversalQuery
                ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'pq.insurance_provider_id')
                ->leftJoin('insurance_provider as ip2', 'ip2.id', '=', 'send_update_logs.insurance_provider_id')
                ->addSelect(DB::raw('CASE WHEN l.code="CII"  OR ip.text is null THEN IFNULL(ip2.text, "N/A") ELSE IFNULL(ip.text, "N/A") END as insurer'));
        }

        if ($request->groupBy == 'policy_issuer') {
            // Endorsements
            $reversalQuery
                ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
                ->addSelect('p.policy_issuer_id as policy_issuer')
                ->addSelect('pi.name as policy_issuer_name');
        }

        if ($request->groupBy == 'line_of_business') {
            // Endorsements
            $reversalQuery->addSelect('quote_type.code as line_of_business');
        }

        if ($request['reportType'] == ManagementReportTypeEnum::APPROVED_TRANSACTIONS) {
            $reversalQuery->leftJoinSub($distinctPaymentSplits, 'ps', function ($join) {
                $join->on('p.code', '=', 'ps.code');
            });
        } elseif ($request['reportType'] == ManagementReportTypeEnum::PAID_TRANSACTIONS) {
            $PaymentSplits = DB::table('payment_splits as dps')
                ->selectRaw('dps.code, dps.verified_at')
                ->groupBy('dps.code');
            $this->getDateFilter($PaymentSplits, $request, 'verified_at', 'paymentDate');
            $reversalQuery->leftJoinSub($PaymentSplits, 'ps', function ($join) {
                $join->on('p.code', '=', 'ps.code');
            });
        }

        $reversalQuery = $this->applyFilters($reversalQuery, $request, true, true);
        $endorsementsQuery = $query->unionAll($reversalQuery);

        $data = $endorsementsQuery->get();

        $groupByColumn = $request->groupBy;
        $data = collect($data
            ->groupBy($groupByColumn)
            ->map(function ($group, $groupByColumn) use ($request) {
                return (object) [
                    $request->groupBy => $groupByColumn,
                    'total_endorsements' => $group->sum('total_endorsements'),
                    'total_endorsement_amount' => $group->sum('total_endorsement_amount'),
                    'commission_vat_applicable' => $group->sum('commission_vat_applicable'),
                    'commission_vat' => $group->sum('commission_vat'),
                    'commission_vat_not_applicable' => $group->sum('commission_vat_not_applicable'),
                ];
            })->values());

        return $data;
    }

    public function formatData(&$data)
    {
        $data->map(function ($item) {
            $item->price_vat_applicable = isset($item->price_vat_applicable) ? number_format($item->price_vat_applicable, 2) : '0.00';
            $item->total_vat = isset($item->total_vat) ? number_format($item->total_vat, 2) : '0.00';
            $item->price_vat_not_applicable = isset($item->price_vat_not_applicable) ? number_format($item->price_vat_not_applicable, 2) : '0.00';
            $item->discount = isset($item->discount) ? number_format($item->discount, 2) : '0.00';
            $item->commission_vat_applicable = isset($item->commission_vat_applicable) ? number_format($item->commission_vat_applicable, 2) : '0.00';
            $item->commission_vat = isset($item->commission_vat) ? number_format($item->commission_vat, 2) : '0.00';
            $item->commission_vat_not_applicable = isset($item->commission_vat_not_applicable) ? number_format($item->commission_vat_not_applicable, 2) : '0.00';
        });
    }

    private function resolveGroupByColumn($groupBy, $isEndorsementQuery = false)
    {
        $mapping = [
            'policy_issuer' => 'p.policy_issuer_id',
            'customer_group' => 'personal_quotes.customer_id',
            'insurer' => 'p.insurance_provider_id',
            'advisor' => 'u.name',
            'line_of_business' => 'quote_type.code',
            'department' => 'u.department_id',
        ];

        if ($isEndorsementQuery) {
            $mapping['insurer'] = 'insurer';
        }

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
            'policyBookDate' => $defaultDate,
            'reportCategory' => ManagementReportCategoriesEnum::SALE_SUMMARY,
            'reportType' => ManagementReportTypeEnum::BOOKED_POLICIES,
        ];
    }
}
