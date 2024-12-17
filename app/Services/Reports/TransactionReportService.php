<?php

namespace App\Services\Reports;

use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Exports\Reports\TransactionReportExport;
use App\Models\PersonalQuote;
use App\Strategies\ManagementReport;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionReportService extends ManagementReport
{
    use TeamHierarchyTrait;

    private $reportDateRange;

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::TRANSACTION;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::APPROVED_TRANSACTIONS;

        if ($request['policyBookDate'] && ! empty($request['policyBookDate']) && is_array($request['policyBookDate'])) {
            $this->reportDateRange = Carbon::parse($request['policyBookDate'][0])->toDateString()
                .' - '.
                Carbon::parse($request['policyBookDate'][1])->toDateString();
        } elseif ($request['paymentDueDate'] && ! empty($request['paymentDueDate']) && is_array($request['paymentDueDate'])) {
            $this->reportDateRange = Carbon::parse($request['paymentDueDate'][0])->toDateString()
                .' - '.
                Carbon::parse($request['paymentDueDate'][1])->toDateString();
        }

        $query = PersonalQuote::query()
            ->select(
                'personal_quotes.quote_type_id',
                'personal_quotes.business_type_of_insurance_id',
                'personal_quotes.uuid',
                'personal_quotes.policy_number',
                'personal_quotes.code',
                'p.notes',
                'p.reference',
                'personal_quotes.policy_start_date',
                'p.payment_due_date as payment_due_date',
                'ps.due_date',
                'personal_quotes.price_vat_applicable',
                'personal_quotes.vat',
                'personal_quotes.price_vat_not_applicable',
                'p.discount_value as discount',
                DB::raw('((
                    IFNULL( personal_quotes.price_vat_applicable , 0 ) +
                    IFNULL( personal_quotes.price_vat_not_applicable , 0 )  +
                    IFNULL( personal_quotes.vat , 0 )) - IFNULL( p.discount_value , 0 )) as total_price'),
                'p.commission_vat_applicable',
                'p.commission_vat',
                'p.commission_vat_not_applicable',
                'p.captured_amount as collected_amount',
                DB::raw('(SELECT pss.verified_at
                FROM payment_splits pss
                WHERE pss.code = p.code
                and pss.sr_no = 1
                LIMIT 1) as payment_date'),
                DB::raw('((
                    IFNULL( personal_quotes.price_vat_applicable , 0 ) +
                    IFNULL( personal_quotes.price_vat_not_applicable , 0 ) +
                    IFNULL( personal_quotes.vat , 0 )) - IFNULL( p.discount_value , 0 )) -
                    IFNULL( p.captured_amount, 0) as pending_balance'),
                'p.collection_type as collects',
                'ip.text as insurer',
                'quote_type.text as line_of_business',
                'personal_quotes.first_name',
                'personal_quotes.last_name',
                'u.name as advisor',
                'dp.name as department',
                'pi.name as policy_issuer',
                'p.invoice_description as invoice_description',
                'pm.name as payment_method',
                'pg.text as payment_gateway',
                'p.insurer_tax_number as insurer_invoice_number',
                'insurer_invoice_date as insurer_tax_invoice_date',
                'p.broker_invoice_number',
                'btoi.text as sub_type_line_of_business',
                'p.insurer_commmission_invoice_number',
                'l.text as transaction_type',
                'qs.text as quote_status',
                'p.commmission_percentage',
                'personal_quotes.source',
                'personal_quotes.policy_booking_date',
                'ps.sage_reciept_id',
            )
            ->join('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->join('payment_splits as ps', 'p.code', '=', 'ps.code')
            ->join('quote_type', 'quote_type.id', '=', 'quote_type_id')
            ->leftJoin('users as u', 'u.id', '=', 'advisor_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
            ->leftJoin('departments as dp', 'dp.id', '=', 'u.department_id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'p.insurance_provider_id')
            ->leftJoin('payment_methods as pm', 'pm.code', '=', 'p.payment_methods_code')
            ->leftJoin('payment_gateway as pg', 'pg.id', '=', 'p.payment_gateway_id')
            ->leftJoin('business_type_of_insurance as btoi', 'btoi.id', '=', 'personal_quotes.business_type_of_insurance_id')
            ->leftJoin('lookups as l', 'personal_quotes.transaction_type_id', '=', 'l.id')
            ->join('quote_status as qs', 'qs.id', '=', 'personal_quotes.quote_status_id');

        $this->applyFilters($query, $request, isSSR: true);

        $utmGroupBy = $this->getUtmGroup($request, $query);

        if ($utmGroupBy) {
            $query->groupBy(['personal_quotes.code', $utmGroupBy]);
        } else {
            $query->groupBy('personal_quotes.code');
        }

        if ($request->export == 1) {
            $data = $query->get();
            $this->formatData($data);

            return (new TransactionReportExport($data))->download("Transaction Report {$this->reportDateRange}.xlsx");
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
            $item->transactions = $this->concatValues([$item->insurer_invoice_number, $item->notes, $item->reference], '-');
            $item->policy_start_date = ! empty($item->policy_start_date) ? Carbon::parse($item->policy_start_date)->format('Y-m-d') : null;
            $item->payment_due_date = ! empty($item->payment_due_date) ? Carbon::parse($item->payment_due_date)->format('Y-m-d') : null;
            $item->due_date = ! empty($item->due_date) ? Carbon::parse($item->due_date)->format('Y-m-d') : null;
            $item->total_price = number_format($item->total_price, 2);
            $item->collects = strtoupper($item->collects);
            $item->pending_balance = number_format($item->pending_balance, 2);
            $item->customer_name = $this->concatValues([$item->first_name, $item->last_name], ' ');
            $item->commmission_percentage = number_format($item->commmission_percentage, 2);
            $item->policy_booking_date = ! empty($item->policy_booking_date) ? Carbon::parse($item->policy_booking_date)->format('Y-m-d') : null;
        });
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
            'reportCategory' => ManagementReportCategoriesEnum::TRANSACTION,
            'reportType' => ManagementReportTypeEnum::APPROVED_TRANSACTIONS,
        ];
    }
}
