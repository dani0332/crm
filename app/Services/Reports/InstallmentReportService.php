<?php

namespace App\Services\Reports;

use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Enums\PaymentFrequency;
use App\Exports\Reports\InstallmentReportExport;
use App\Models\PersonalQuote;
use App\Strategies\ManagementReport;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstallmentReportService extends ManagementReport
{
    use TeamHierarchyTrait;

    private $reportDateRange;

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::INSTALLMENT;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::APPROVED_TRANSACTIONS;

        if ($request['paymentDueDate'] && ! empty($request['paymentDueDate']) && is_array($request['paymentDueDate'])) {
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
                'ps.reference',
                'p.notes',
                'personal_quotes.policy_start_date',
                'ps.due_date',
                DB::raw('IFNULL(personal_quotes.price_vat_applicable, 0) / IFNULL(p.total_payments, 1) as price_vat_applicable'),
                DB::raw('IFNULL(personal_quotes.vat, 0) / IFNULL(p.total_payments, 1) as vat'),
                DB::raw('IFNULL(personal_quotes.price_vat_not_applicable, 0) / IFNULL(p.total_payments, 1) as price_vat_not_applicable'),
                DB::raw('CASE WHEN ps.sr_no=1 THEN IFNULL(p.discount_value, 0) ELSE 0 END as discount'),
                'ps.payment_amount as total_price',
                DB::raw('IFNULL(p.commission_vat_applicable, 0) / IFNULL(p.total_payments, 1) as commission_vat_applicable'),
                DB::raw('CASE WHEN ps.sr_no=1 THEN IFNULL(p.commission_vat, 0) ELSE 0 END as commission_vat'),
                DB::raw('IFNULL(p.commission_vat_not_applicable, 0) / IFNULL(p.total_payments, 1) as commission_vat_not_applicable'),
                DB::raw('IFNULL(ps.collection_amount, 0) as collected_amount'),
                'ps.verified_at as payment_date',
                DB::raw('IFNULL(ps.payment_amount, 0) - IFNULL(ps.collection_amount, 0) as pending_balance'),
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
                'q.text as lead_status',
                'p.insurer_commmission_invoice_number',
                'l.text as transaction_type',
                DB::raw('CASE WHEN ps.sr_no=1 THEN p.commmission_percentage ELSE 0 END as commmission_percentage'),
                'personal_quotes.source',
                'ps.sage_reciept_id',
            )
            ->join('payments as p', function ($join) {
                $join->on('personal_quotes.code', '=', 'p.code')
                    ->where('p.frequency', '<>', PaymentFrequency::UPFRONT);
            })
            ->join('payment_splits as ps', 'p.code', '=', 'ps.code')
            ->join('quote_type', 'quote_type.id', '=', 'quote_type_id')
            ->leftJoin('quote_status as q', 'q.id', '=', 'personal_quotes.quote_status_id')
            ->leftJoin('users as u', 'u.id', '=', 'advisor_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
            ->leftJoin('departments as dp', 'u.department_id', '=', 'dp.id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'p.insurance_provider_id')
            ->leftJoin('payment_methods as pm', 'pm.code', '=', 'ps.payment_method')
            ->leftJoin('payment_gateway as pg', 'pg.id', '=', 'ps.payment_gateway_id')
            ->leftJoin('business_type_of_insurance as btoi', 'btoi.id', '=', 'personal_quotes.business_type_of_insurance_id')
            ->leftJoin('lookups as l', 'personal_quotes.transaction_type_id', '=', 'l.id')
            ->orderBy('personal_quotes.id', 'desc')
            ->orderBy('ps.due_date', 'asc');

        $this->applyFilters($query, $request);
        $this->getUtmGroup($request, $query);

        if ($request->export == 1) {
            $data = $query->get();
            $this->formatData($data);

            return (new InstallmentReportExport($data))->download("Installment Report {$this->reportDateRange}.xlsx");
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
            $item->policy_start_date = ! empty($item->policy_start_date) ? Carbon::parse($item->policy_start_date)->format('Y-m-d') : null;
            $item->due_date = ! empty($item->due_date) ? Carbon::parse($item->due_date)->format('Y-m-d') : null;
            $item->total_price = number_format($item->total_price, 2);
            $item->commission_vat_applicable = number_format($item->commission_vat_applicable, 2);
            $item->commission_vat = number_format($item->commission_vat, 2);
            $item->commission_vat_not_applicable = number_format($item->commission_vat_not_applicable, 2);
            $item->pending_balance = number_format($item->pending_balance, 2);
            $item->collects = strtoupper($item->collects);
            $item->customer_name = $this->concatValues([$item->first_name, $item->last_name], ' ');
            $item->transactions = $this->concatValues([$item->insurer_invoice_number, $item->notes, $item->reference], '-');
            $item->commmission_percentage = number_format($item->commmission_percentage, 2);
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
            'reportCategory' => ManagementReportCategoriesEnum::INSTALLMENT,
            'reportType' => ManagementReportTypeEnum::APPROVED_TRANSACTIONS,
        ];
    }

    public function headings(): array
    {
        return [
            'Ref-ID',
            'Department',
            'Policy Number',
            'Transactions',
            'Policy Start Date',
            'Payment Due Date',
            'Price (VAT applicable)',
            'Total VAT',
            'Price (VAT not applicable)',
            'Discount',
            'Total Price',
            'Commission (VAT applicable)',
            'VAT on Commission',
            'Commission (VAT not applicable)',
            'Collected Amount',
            'Payment Date',
            'Unpaid',
            'Collects',
            'Insurer',
            'Line Of Business',
            'Sub-Type',
            'Customer Name',
            'Advisor',
            'Policy Issuer',
            'Invoice Description',
            'Payment Method',
            'Payment Gateway',
            'Insurer Invoice No.',
            'Insurer Invoice Date',
            'Broker Invoice No',
            'Lead Status',
            'Commission Tax Invoice Number',
            'Commission Percentage',
            'Transaction Type',
            'Source',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->code ?? 'N/A',
            $quote->department ?? 'N/A',
            $quote->policy_number ?? 'N/A',
            $quote->transactions ?? 'N/A',
            $quote->policy_start_date ?? 'N/A',
            $quote->due_date ?? 'N/A',
            $quote->price_vat_applicable ?? '0.00',
            $quote->vat ?? '0.00',
            $quote->price_vat_not_applicable ?? '0.00',
            $quote->discount ?? '0.00',
            $quote->total_price ?? '0.00',
            $quote->commission_vat_applicable ?? '0.00',
            $quote->commission_vat ?? '0.00',
            $quote->commission_vat_not_applicable ?? '0.00',
            $quote->collected_amount ?? '0.00',
            $quote->payment_date ?? 'N/A',
            $quote->pending_balance ?? '0.00',
            $quote->collects ?? 'N/A',
            $quote->insurer ?? 'N/A',
            $quote->line_of_business ?? 'N/A',
            $quote->sub_type_line_of_business ?? 'N/A',
            $quote->customer_name ?? 'N/A',
            $quote->advisor ?? 'N/A',
            $quote->policy_issuer ?? 'N/A',
            $quote->invoice_description ?? 'N/A',
            $quote->payment_method ?? 'N/A',
            $quote->payment_gateway ?? 'N/A',
            $quote->insurer_invoice_number ?? 'N/A',
            $quote->insurer_tax_invoice_date ?? 'N/A',
            $quote->broker_invoice_number ?? 'N/A',
            $quote->lead_status ?? 'N/A',
            $quote->insurer_commmission_invoice_number ?? 'N/A',
            $quote->commmission_percentage ?? 'N/A',
            $quote->transaction_type ?? 'N/A',
            $quote->source ?? 'N/A',
        ];
    }
}
