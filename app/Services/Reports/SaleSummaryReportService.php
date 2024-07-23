<?php

namespace App\Services\Reports;

use App\Enums\EndorsementStatusEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
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
            ->leftJoin('user_team', 'u.id', '=', 'user_team.user_id')
            ->leftJoin('teams as t', 'user_team.team_id', '=', 't.id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->join('quote_type', 'personal_quotes.quote_type_id', '=', 'quote_type.id')
            ->join('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->selectRaw('
            COUNT(DISTINCT(personal_quotes.uuid)) as total_policies,
            COUNT(DISTINCT(personal_quotes.uuid)) as total_transaction,
            SUM(personal_quotes.price_vat_applicable) / COUNT(DISTINCT(user_team.team_id)) as price_vat_applicable,
            IFNULL(SUM(personal_quotes.vat) / COUNT(DISTINCT(user_team.team_id)),0) as total_vat,
            IFNULL(SUM(personal_quotes.price_vat_not_applicable) / COUNT(DISTINCT(user_team.team_id)),0) as price_vat_not_applicable,
            IFNULL(SUM(p.discount_value) / COUNT(DISTINCT(user_team.team_id)),0) as discount,
            IFNULL(SUM(p.commission_vat_applicable) / COUNT(DISTINCT(user_team.team_id)),0) as commission_vat_applicable,
            IFNULL( ( SUM(personal_quotes.price_vat_applicable) / COUNT(DISTINCT(user_team.team_id)) ), 0) +
                IFNULL( ( SUM(personal_quotes.price_vat_not_applicable) / COUNT(DISTINCT(user_team.team_id)) ), 0) +
                IFNULL( ( SUM(personal_quotes.vat) / COUNT(DISTINCT(user_team.team_id)) ), 0) -
                IFNULL( ( SUM(p.discount_value) / COUNT(DISTINCT(user_team.team_id)) ), 0) as total_price
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

        if ($request['reportType'] == ManagementReportTypeEnum::TRANSACTION_PAYMENTS) {
            $query->joinSub($distinctPaymentSplits, 'ps', function ($join) {
                $join->on('p.code', '=', 'ps.code');
            });
        }

        $this->applyFilters($query, $request);
        $data = $query->get();
        $this->formatData($data);

        if ($request->export == 1) {

            /**
             * Get endorsements data
             */
            $endorsementsData = $this->getEndorsementsData($request);

            /**
             * Process endorsements data for pdf
             */
            $processedData = $this->processEndorsementsData($data, $endorsementsData, $request);

            // Columns that are not integar and should not be summed
            $nonIntegarIndexes = [0];

            return $this->download(
                'Sale Summary Report '.$this->reportDateRange,
                $processedData,
                $this->headings(),
                $nonIntegarIndexes
            );
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
                EndorsementStatusEnum::CANCELLATION_FROM_INCEPTION_AND_REISSUANCE])
            ->pluck('id')->toArray();

        // Subquery to get distinct payment splits with minimum due_date
        $distinctPaymentSplits = DB::table('payment_splits as dps')
            ->select('dps.code', 'due_date')
            ->groupBy('dps.code');

        $endorsementsQuery = SendUpdateLog::query()
            ->leftJoin('personal_quotes', 'send_update_logs.personal_quote_id', '=', 'personal_quotes.id')
            ->leftJoin('lookups as l', 'send_update_logs.category_id', '=', 'l.id')
            ->leftJoin('users as u', 'personal_quotes.advisor_id', '=', 'u.id')
            ->leftJoin('user_team', 'u.id', '=', 'user_team.user_id')
            ->leftJoin('teams as t', 'user_team.team_id', '=', 't.id')
            ->leftJoin('payments as p', 'send_update_logs.id', '=', 'p.send_update_log_id')
            ->join('quote_type', 'personal_quotes.quote_type_id', '=', 'quote_type.id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->selectRaw(
                'COUNT(DISTINCT(send_update_logs.uuid)) as total_endorsements,
                IFNULL( ( SUM(send_update_logs.price_vat_applicable) / COUNT(DISTINCT(user_team.team_id)) ), 0) +
                IFNULL( ( SUM(send_update_logs.price_vat_not_applicable) / COUNT(DISTINCT(user_team.team_id)) ), 0) +
                IFNULL( ( SUM(send_update_logs.total_vat_amount) / COUNT(DISTINCT(user_team.team_id)) ), 0) -
                IFNULL( ( SUM(send_update_logs.discount) / COUNT(DISTINCT(user_team.team_id)) ), 0) as total_endorsement_amount
            '
            )
            ->where('send_update_logs.status', '=', EndorsementStatusEnum::UPDATE_BOOKED)
            ->whereIn('send_update_logs.category_id', $endrosementCategoryIds)
            ->when($request->groupBy, function ($endorsementsQuery, $groupBy) use ($request) {
                $groupByArray = [];
                $groupBy = $this->resolveGroupByColumn($groupBy);
                array_push($groupByArray, $groupBy);
                $utmGroupBy = $this->getUtmGroup($request, $endorsementsQuery);
                if ($utmGroupBy) {
                    array_push($groupByArray, $utmGroupBy);
                }

                return $endorsementsQuery->groupBy($groupByArray);
            });

        if ($request->groupBy == 'advisor') {
            // Endorsements
            $endorsementsQuery->addSelect('u.name as advisor');
            $endorsementsQuery->whereNotNull('personal_quotes.advisor_id');
        }

        if ($request->groupBy == 'customer_group') {
            // Endorsements
            $endorsementsQuery->leftJoin('customer', 'personal_quotes.customer_id', '=', 'customer.id')
                ->addSelect(DB::raw("CONCAT(customer.first_name, ' ', customer.last_name) as customer_group"));
            $endorsementsQuery->whereNotNull('personal_quotes.customer_id');
        }

        if ($request->groupBy == 'insurer') {
            // Endorsements
            $endorsementsQuery->join('insurance_provider', 'insurance_provider.id', '=', 'p.insurance_provider_id')
                ->addSelect('insurance_provider.text as insurer');
            $endorsementsQuery->whereNotNull('p.insurance_provider_id');
        }

        if ($request->groupBy == 'policy_issuer') {
            // Endorsements
            $endorsementsQuery->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
                ->addSelect('pi.name as policy_issuer');
            $endorsementsQuery->whereNotNull('p.policy_issuer_id');
        }

        if ($request->groupBy == 'line_of_business') {
            // Endorsements
            $endorsementsQuery->addSelect('quote_type.code as line_of_business');
            $endorsementsQuery->whereNotNull('quote_type.code');
        }

        if ($request['reportType'] == ManagementReportTypeEnum::TRANSACTION_PAYMENTS) {
            $endorsementsQuery->joinSub($distinctPaymentSplits, 'ps', function ($join) {
                $join->on('p.code', '=', 'ps.code');
            });
        }

        $endorsementsQuery = $this->applyFilters($endorsementsQuery, $request, true);

        return $endorsementsQuery->get();
    }

    private function formatData(&$data)
    {
        $data->map(function ($item) {
            $item->price_vat_applicable = number_format($item->price_vat_applicable, 2);
            $item->total_vat = number_format($item->total_vat, 2);
            $item->price_vat_not_applicable = number_format($item->price_vat_not_applicable, 2);
            $item->discount = number_format($item->discount, 2);
            $item->commission_vat_applicable = number_format($item->commission_vat_applicable, 2);
        });
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
            'policyBookDate' => $defaultDate,
            'reportCategory' => ManagementReportCategoriesEnum::SALE_SUMMARY,
            'reportType' => ManagementReportTypeEnum::BOOKED_POLICIES,
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
            'Total Endorsement Amount',
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
            $quote->endorsements_amount ? \number_format($quote->endorsements_amount, 2, '.', ',') : '0.00',
            $quote->total_price ? \number_format($quote->total_price, 2, '.', ',') : '0.00',
        ];
    }
}
