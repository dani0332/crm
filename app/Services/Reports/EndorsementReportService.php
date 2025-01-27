<?php

namespace App\Services\Reports;

use App\Enums\EndorsementStatusEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Exports\Reports\EndorsementReportExport;
use App\Models\Lookup;
use App\Models\SendUpdateLog;
use App\Strategies\ManagementReport;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EndorsementReportService extends ManagementReport
{
    use TeamHierarchyTrait;

    private $reportDateRange;

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::ENDORSEMENT;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::BOOKED_POLICIES;

        if ($request['policyBookDate'] && ! empty($request['policyBookDate']) && is_array($request['policyBookDate'])) {
            $this->reportDateRange = Carbon::parse($request['policyBookDate'][0])->toDateString()
                .' - '.
                Carbon::parse($request['policyBookDate'][1])->toDateString();
        } elseif ($request['paymentDueDate'] && ! empty($request['paymentDueDate']) && is_array($request['paymentDueDate'])) {
            $this->reportDateRange = Carbon::parse($request['paymentDueDate'][0])->toDateString()
                .' - '.
                Carbon::parse($request['paymentDueDate'][1])->toDateString();
        }

        // lookupQuery
        $endrosementCategoryIds = Lookup::query()
            ->select('id')
            ->whereIn('code', [
                EndorsementStatusEnum::ENDORSEMENT_FINANCIAL_CODE,
                EndorsementStatusEnum::CANCELLATION_FROM_INCEPTION,
                EndorsementStatusEnum::CANCELLATION_FROM_INCEPTION_AND_REISSUANCE,
                EndorsementStatusEnum::CORRECTION_OF_POLICY_DETAILS,
            ])
            ->pluck('id')->toArray();

        $query = SendUpdateLog::query()
            ->select(
                'send_update_logs.id',
                'send_update_logs.uuid',
                'personal_quotes.quote_type_id',
                'personal_quotes.business_type_of_insurance_id',
                'personal_quotes.code as main_lead_code',
                'send_update_logs.quote_uuid',
                'send_update_logs.policy_number',
                'personal_quotes.policy_number as main_lead_policy_number',
                'send_update_logs.code',
                'p.insurer_tax_number',
                'p.notes',
                'ps.reference',
                'send_update_logs.start_date as policy_start_date',
                'personal_quotes.policy_start_date as main_lead_policy_start_date',
                'send_update_logs.invoice_date as payment_due_date',
                'ps.due_date as due_date',

                DB::raw('CASE WHEN send_update_logs.price_vat_applicable is not null OR send_update_logs.price_vat_applicable != 0.00
                THEN IFNULL( ps.price_vat_applicable , send_update_logs.price_vat_applicable )
                ELSE 0 END as price_vat_applicable'),

                DB::raw('IFNULL(ps.price_vat, send_update_logs.total_vat_amount) as vat'),

                DB::raw('CASE WHEN send_update_logs.price_vat_applicable is null OR send_update_logs.price_vat_applicable = 0.00
                THEN IFNULL( ps.price_vat_applicable , send_update_logs.price_vat_not_applicable )
                ELSE 0 END as price_vat_not_applicable'),

                DB::raw('IF(ps.discount_value IS NULL OR ps.discount_value = 0, send_update_logs.discount, ps.discount_value) as discount'),

                DB::raw('((
                    IFNULL( ps.price_vat_applicable , IFNULL( send_update_logs.price_vat_applicable , 0 ) + IFNULL( send_update_logs.price_vat_not_applicable , 0 ) ) +
                    IFNULL( IFNULL(ps.price_vat, send_update_logs.total_vat_amount) , 0 )) - IFNULL( IF(ps.discount_value IS NULL OR ps.discount_value = 0, send_update_logs.discount, ps.discount_value) , 0 )) as total_price'),

                DB::raw('CASE WHEN ps.sr_no is NULL OR ps.sr_no=1 THEN send_update_logs.commission_vat_applicable ELSE 0 END as commission_vat_applicable'),

                DB::raw('CASE WHEN ps.sr_no is NULL  OR ps.sr_no=1 THEN send_update_logs.vat_on_commission ELSE 0 END as commission_vat'),

                DB::raw('CASE WHEN ps.sr_no is NULL  OR ps.sr_no=1 THEN IFNULL(p.commission_vat_not_applicable, send_update_logs.commission_vat_not_applicable) ELSE 0 END as commission_vat_not_applicable'),

                DB::raw('IFNULL(ps.collection_amount, send_update_logs.price_with_vat) as collected_amount'),

                'ps.verified_at as payment_date',
                DB::raw('((
                    IFNULL( ps.price_vat_applicable , IFNULL( send_update_logs.price_vat_applicable , 0 ) + IFNULL( send_update_logs.price_vat_not_applicable , 0 ) ) +
                    IFNULL( IFNULL(ps.price_vat, send_update_logs.total_vat_amount) , 0 )) - IFNULL( IF(ps.discount_value IS NULL OR ps.discount_value = 0, send_update_logs.discount, ps.discount_value) , 0 )) -
                    IFNULL( IFNULL(ps.collection_amount, send_update_logs.price_with_vat), 0) as pending_balance'),
                'pq.collection_type as collects',
                DB::raw('CASE WHEN l.code="CII" OR ip.text is null THEN IFNULL(ip2.text, ip3.text) ELSE ip.text END as insurer'),
                'quote_type.text as line_of_business',
                'personal_quotes.first_name',
                'personal_quotes.last_name',
                'u.name as advisor',
                'dp.name as department',
                'pi.name as policy_issuer',
                'send_update_logs.invoice_description as invoice_description',
                'pm.name as payment_method',
                'pg.text as payment_gateway',
                'send_update_logs.insurer_tax_invoice_number as insurer_invoice_number',
                'send_update_logs.invoice_date as insurer_tax_invoice_date',
                'send_update_logs.broker_invoice_number',
                'btoi.text as sub_type_line_of_business',
                DB::raw('IFNULL(l.text, lc.text) as endorsement_sub_type'),
                'send_update_logs.booking_date',
                DB::raw('IFNULL(send_update_logs.insurer_commission_invoice_number, p.insurer_commmission_invoice_number) as insurer_commmission_invoice_number'),
                DB::raw('CASE WHEN ps.sr_no is NULL OR ps.sr_no=1 THEN IFNULL(send_update_logs.commission_percentage, p.commmission_percentage) ELSE 0 END as commmission_percentage'),
                DB::raw("'Endorsement' as transaction_type"),
                'personal_quotes.source',
                'send_update_logs.status',
                'ps.sage_reciept_id',
            )
            ->leftJoin('personal_quotes', 'personal_quotes.id', '=', 'send_update_logs.personal_quote_id')
            ->leftJoin('payments as pq', 'pq.code', '=', 'personal_quotes.code')
            ->leftJoin('payments as p', 'send_update_logs.id', '=', 'p.send_update_log_id')
            ->leftJoin('payment_splits as ps', 'p.code', '=', 'ps.code')
            ->join('quote_type', 'quote_type.id', '=', 'personal_quotes.quote_type_id')
            ->leftJoin('users as u', 'u.id', '=', 'personal_quotes.advisor_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'send_update_logs.created_by')
            ->leftJoin('departments as dp', 'dp.id', '=', 'u.department_id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'pq.insurance_provider_id')
            ->leftJoin('insurance_provider as ip2', 'ip2.id', '=', 'send_update_logs.insurance_provider_id')
            ->leftJoin('insurance_provider as ip3', 'ip3.id', '=', 'personal_quotes.insurance_provider_id')
            ->leftJoin('payment_methods as pm', 'pm.code', '=', 'ps.payment_method')
            ->leftJoin('payment_gateway as pg', 'pg.id', '=', 'ps.payment_gateway_id')
            ->leftJoin('business_type_of_insurance as btoi', 'btoi.id', '=', 'personal_quotes.business_type_of_insurance_id')
            ->leftJoin('lookups as l', 'send_update_logs.option_id', '=', 'l.id')
            ->leftJoin('lookups as lc', 'send_update_logs.category_id', '=', 'lc.id')
            ->where('send_update_logs.status', '=', EndorsementStatusEnum::UPDATE_BOOKED)
            ->whereIn('send_update_logs.category_id', $endrosementCategoryIds);
        $this->getUtmGroup($request, $query);
        $this->applyFilters($query, $request);

        $reversalQuery = SendUpdateLog::query()
            ->select(
                'send_update_logs.id',
                'send_update_logs.uuid',
                'personal_quotes.quote_type_id',
                'personal_quotes.business_type_of_insurance_id',
                'personal_quotes.code as main_lead_code',
                'send_update_logs.quote_uuid',
                'send_update_logs.policy_number',
                'personal_quotes.policy_number as main_lead_policy_number',
                'send_update_logs.code',
                'p.insurer_tax_number',
                'p.notes',
                'p.reference',
                'send_update_logs.start_date as policy_start_date',
                'personal_quotes.policy_start_date as main_lead_policy_start_date',
                DB::raw('IFNULL(p.insurer_invoice_date, IFNULL(s2.invoice_date, "")) as payment_due_date'),
                'send_update_logs.invoice_date as due_date',
                DB::raw('-1 * (CASE WHEN send_update_logs.price_vat_applicable is not null THEN
                IFNULL(s2.price_vat_applicable, IFNULL(p.price_vat_applicable, 0))
                ELSE 0 END) as price_vat_applicable'),
                DB::raw('-1 * IFNULL(s2.total_vat_amount, IFNULL(p.price_vat, 0)) as vat'),
                DB::raw('-1 * (CASE WHEN send_update_logs.price_vat_applicable is null THEN
                IFNULL(s2.price_vat_applicable, IFNULL(p.price_vat_applicable, 0))
                ELSE 0 END) as price_vat_not_applicable'),
                DB::raw('-1 * IFNULL(p.discount_value, 0) as discount'),
                DB::raw('-1 * ((
                 IFNULL(s2.price_vat_applicable, IFNULL(p.price_vat_applicable, 0)) +
                 IFNULL(IFNULL(s2.total_vat_amount, IFNULL(p.price_vat, 0)), 0)) -
                 IFNULL(p.discount_value, 0)) as total_price'),
                DB::raw('-1 * IFNULL(s2.commission_vat_applicable, IFNULL(p.commission_vat_applicable, 0)) as commission_vat_applicable'),
                DB::raw('-1 * IFNULL(p.commission_vat, IFNULL(s2.vat_on_commission, 0)) as commission_vat'),
                DB::raw('-1 * IFNULL(s2.commission_vat_not_applicable, IFNULL(p.commission_vat_not_applicable, 0)) as commission_vat_not_applicable'),
                DB::raw('-1 * IFNULL(p.total_price, IFNULL(s2.price_with_vat, 0)) as collected_amount'),
                DB::raw("'N/A' as payment_date"),
                DB::raw("'0.00' as pending_balance"),
                'pq.collection_type as collects',
                DB::raw('CASE WHEN l.code="CII" OR ip.text is null THEN IFNULL(ip2.text, ip3.text) ELSE ip.text END as insurer'),
                'quote_type.text as line_of_business',
                'personal_quotes.first_name',
                'personal_quotes.last_name',
                'u.name as advisor',
                'dp.name as department',
                'pi.name as policy_issuer',
                DB::raw('IFNULL(p.invoice_description, IFNULL(s2.invoice_description, "")) as invoice_description'),
                DB::raw("'N/A' as payment_method"),
                DB::raw("'N/A' as payment_gateway"),
                DB::raw('IFNULL(CONCAT(p.insurer_tax_number, "-REV"), CONCAT(s2.insurer_tax_invoice_number, "-REV")) as insurer_invoice_number'),
                DB::raw('IFNULL(p.insurer_invoice_date, IFNULL(s2.invoice_date, "")) as insurer_tax_invoice_date'),
                DB::raw('IFNULL(CONCAT(p.broker_invoice_number, "-REV"), CONCAT(s2.broker_invoice_number, "-REV")) as broker_invoice_number'),
                'btoi.text as sub_type_line_of_business',
                DB::raw('IFNULL(l.text, lc.text) as endorsement_sub_type'),
                'send_update_logs.booking_date',
                DB::raw('IFNULL(CONCAT(p.insurer_commmission_invoice_number, "-REV"), IFNULL(CONCAT(send_update_logs.insurer_commission_invoice_number, "-REV"), null)) as insurer_commmission_invoice_number'),
                DB::raw('-1 * IFNULL(send_update_logs.commission_percentage, IFNULL(p.commmission_percentage, 0)) as commmission_percentage'),
                DB::raw("'Endorsement' as transaction_type"),
                'personal_quotes.source',
                'send_update_logs.status',
                DB::raw("'N/A' as sage_reciept_id"),
            )
            ->leftJoin('personal_quotes', 'personal_quotes.id', '=', 'send_update_logs.personal_quote_id')
            ->leftJoin('payments as pq', 'pq.code', '=', 'personal_quotes.code')
            ->leftJoin('payments as p', 'send_update_logs.reversal_invoice', '=', 'p.insurer_tax_number')
            ->leftJoin('send_update_logs as S2', 'send_update_logs.reversal_invoice', '=', 's2.insurer_tax_invoice_number')
            ->join('quote_type', 'quote_type.id', '=', 'personal_quotes.quote_type_id')
            ->leftJoin('users as u', 'u.id', '=', 'personal_quotes.advisor_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'send_update_logs.created_by')
            ->leftJoin('departments as dp', 'dp.id', '=', 'u.department_id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'pq.insurance_provider_id')
            ->leftJoin('insurance_provider as ip2', 'ip2.id', '=', 'send_update_logs.insurance_provider_id')
            ->leftJoin('insurance_provider as ip3', 'ip3.id', '=', 'personal_quotes.insurance_provider_id')
            ->leftJoin('business_type_of_insurance as btoi', 'btoi.id', '=', 'personal_quotes.business_type_of_insurance_id')
            ->leftJoin('lookups as l', 'send_update_logs.option_id', '=', 'l.id')
            ->leftJoin('lookups as lc', 'send_update_logs.category_id', '=', 'lc.id')
            ->where('send_update_logs.status', '=', EndorsementStatusEnum::UPDATE_BOOKED)
            ->whereNotNull('send_update_logs.reversal_invoice')
            ->whereIn('send_update_logs.category_id', $endrosementCategoryIds);
        $this->getUtmGroup($request, $reversalQuery);

        if ($request['reportType'] == ManagementReportTypeEnum::APPROVED_TRANSACTIONS) {
            $distinctPaymentSplits = DB::table('payment_splits as dps')
                ->select('dps.code', 'due_date')
                ->groupBy('dps.code');
            $reversalQuery->leftJoinSub($distinctPaymentSplits, 'ps', function ($join) {
                $join->on('p.code', '=', 'ps.code');
            });
        } elseif ($request['reportType'] == ManagementReportTypeEnum::PAID_TRANSACTIONS) {
            $distinctPaymentSplits = DB::table('payment_splits as dps')
                ->select('dps.code', 'dps.verified_at')
                ->groupBy('dps.code');
            $this->getDateFilter($distinctPaymentSplits, $request, 'dps.verified_at', 'paymentDate');
            $reversalQuery->leftJoinSub($distinctPaymentSplits, 'ps', function ($join) {
                $join->on('p.code', '=', 'ps.code');
            });
        }

        $this->applyFilters($reversalQuery, $request);

        $query = $query->unionAll($reversalQuery);
        $query = $query->orderBy('id', 'desc');

        if ($request->export == 1) {
            $data = $query->get();
            $this->formatData($data);

            return (new EndorsementReportExport($data))->download("Endorsement Report {$this->reportDateRange}.xlsx");
        } else {
            $data = $query->simplePaginate(100)->withQueryString();
            $data->map(function ($item) {
                $item->routeName = $this->getQuoteRouteName($item->quote_type_id, $item->business_type_of_insurance_id);
            });
            $this->formatData($data);

            return $data;
        }
    }

    private function formatData(&$data)
    {
        $data->map(function ($item) {

            $item->transactions = $this->concatValues([$item->insurer_tax_number, $item->notes, $item->reference], '-');
            $item->policy_start_date = ! empty($item->policy_start_date) ? Carbon::parse($item->policy_start_date)->format('Y-m-d') : null;
            $item->main_lead_policy_start_date = ! empty($item->main_lead_policy_start_date) ? Carbon::parse($item->main_lead_policy_start_date)->format('Y-m-d') : null;
            $item->payment_due_date = ! empty($item->payment_due_date) ? Carbon::parse($item->payment_due_date)->format('Y-m-d') : null;
            $item->due_date = ! empty($item->due_date) ? Carbon::parse($item->due_date)->format('Y-m-d') : null;
            $item->booking_date = ! empty($item->booking_date) ? Carbon::parse($item->booking_date)->format('Y-m-d') : null;
            $item->total_price = number_format($item->total_price, 2);
            $item->pending_balance = number_format($item->pending_balance, 2);
            $item->collects = strtoupper($item->collects);
            $item->customer_name = $this->concatValues([$item->first_name, $item->last_name], ' ');
            $item->commmission_percentage = number_format($item->commmission_percentage, 2);
            $item->status = ucwords(str_replace('_', ' ', strtolower($item->status)));
        });
    }

    protected function filterTeams($query, $teamIds, $isSSR = false)
    {
        if (! empty($teamIds) || auth()->user()->isDepartmentManager()) {
            $userIds = $this->getUsersByTeamIds($teamIds)->pluck('id')->toArray();
            $query->whereIn('personal_quotes.advisor_id', $userIds);
        }

        return $query;
    }

    public function getDefaultFilters()
    {
        $dateFormat = config('constants.DATE_FORMAT_ONLY');
        $defaultDate = [
            Carbon::parse(now())->startOfDay()->format($dateFormat),
            Carbon::parse(now())->endOfDay()->format($dateFormat),
        ];

        return [
            'paymentDueDate' => $defaultDate,
            'reportCategory' => ManagementReportCategoriesEnum::ENDORSEMENT,
            'reportType' => ManagementReportTypeEnum::BOOKED_POLICIES,
        ];
    }
}
