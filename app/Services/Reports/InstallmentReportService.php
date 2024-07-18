<?php

namespace App\Services\Reports;

use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Enums\PaymentFrequency;
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
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::TRANSACTION_PAYMENTS;

        if ($request['paymentDueDate'] && ! empty($request['paymentDueDate']) && is_array($request['paymentDueDate'])) {
            $this->reportDateRange = Carbon::parse($request['paymentDueDate'][0])->toDateString()
                .' - '.
                Carbon::parse($request['paymentDueDate'][1])->toDateString();
        }

        $query = PersonalQuote::query()
            ->select(
                'personal_quotes.policy_number',
                'personal_quotes.code',
                DB::raw('CONCAT(ps.reference, " ", p.tax_invoice_number) as transactions'),
                DB::raw("DATE_FORMAT(personal_quotes.policy_start_date, '%Y-%m-%d') as policy_start_date"),
                DB::raw("DATE_FORMAT(ps.due_date, '%Y-%m-%d') as due_date"),
                DB::raw('IFNULL(personal_quotes.price_vat_applicable, 0) / IFNULL(p.total_payments, 1) as price_vat_applicable'),
                DB::raw('IFNULL(personal_quotes.vat, 0) / IFNULL(p.total_payments, 1) as vat'),
                DB::raw('IFNULL(personal_quotes.price_vat_not_applicable, 0) / IFNULL(p.total_payments, 1) as price_vat_not_applicable'),
                'ps.discount_value as discount',
                DB::raw('FORMAT(ps.payment_amount, 2) as total_price'),
                DB::raw('FORMAT(IFNULL(p.commission_vat_applicable, 0) / IFNULL(p.total_payments, 1),2) as commission_vat_applicable'),
                DB::raw('FORMAT(CASE WHEN ps.sr_no=1 THEN IFNULL(p.commission_vat, 0) ELSE 0 END, 2) as commission_vat'),
                DB::raw('FORMAT(IFNULL(p.commission_vat_not_applicable, 0) / IFNULL(p.total_payments, 1),2) as commission_vat_not_applicable'),
                DB::raw('IFNULL(ps.collection_amount, 0) as collected_amount'),
                'ps.verified_at as payment_date',
                DB::raw('FORMAT(IFNULL(ps.payment_amount, 0) - IFNULL(ps.collection_amount, 0), 2) as pending_balance'),
                DB::raw('UPPER(p.collection_type) as collects'),
                'ip.text as insurer',
                'quote_type.text as line_of_business',
                DB::raw("CONCAT(personal_quotes.first_name, ' ', personal_quotes.last_name) as customer_name"),
                'u.name as advisor',
                'pi.name as policy_issuer',
                'p.invoice_description as invoice_description',
                'pm.name as payment_method',
                'pg.text as payment_gateway',
                'p.insurer_tax_number as insurer_invoice_number',
                'insurer_invoice_date as insurer_tax_invoice_date',
                'p.broker_invoice_number',
                'btoi.text as sub_type_line_of_business',
                'q.text as lead_status',
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
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'p.insurance_provider_id')
            ->leftJoin('payment_methods as pm', 'pm.code', '=', 'ps.payment_method')
            ->leftJoin('payment_gateway as pg', 'pg.id', '=', 'ps.payment_gateway_id')
            ->leftJoin('business_type_of_insurance as btoi', 'btoi.id', '=', 'personal_quotes.business_type_of_insurance_id')
            ->orderBy('personal_quotes.id', 'desc')
            ->orderBy('ps.due_date', 'asc');

        $this->applyFilters($query, $request);
        $this->getUtmGroup($request, $query);
        
        if ($request->export == 1) {
            $data = $query->get();

            // Columns that are not integar and should not be summed
            $nonIntegarIndexes = [0, 1, 2, 3, 13, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27];

            return $this->download(
                'Installment Report '.$this->reportDateRange,
                $data,
                $this->headings(),
                $nonIntegarIndexes
            );
        } else {
            return $query->simplePaginate(100)->withQueryString();
        }
    }

    protected function filterTeams($query, $teamIds)
    {
        if (empty($teamIds)) {
            $teamIds = $this->getUserTeams(auth()->user()->id)->pluck('id')->toArray();
        }

        $userIds = $this->getUsersByTeamIds($teamIds)->pluck('id')->toArray();
        $query->whereIn('personal_quotes.advisor_id', $userIds);

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
            'reportType' => ManagementReportTypeEnum::TRANSACTION_PAYMENTS,
        ];
    }

    public function headings(): array
    {
        return [
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
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->policy_number ?? 'N/A',
            $quote->transactions ?? 0,
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
        ];
    }
}
