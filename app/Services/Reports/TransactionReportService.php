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

class TransactionReportService extends ManagementReport
{
    use TeamHierarchyTrait;

    private $reportDateRange;

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::TRANSACTION;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::TRANSACTION_PAYMENTS;

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
                'personal_quotes.policy_number',
                'personal_quotes.code',
                DB::raw("CONCAT_WS('-', p.insurer_tax_number, p.notes, p.reference) as transactions"),
                DB::raw("DATE_FORMAT(personal_quotes.policy_start_date, '%Y-%m-%d') as policy_start_date"),
                DB::raw("DATE_FORMAT(p.payment_due_date, '%Y-%m-%d') as payment_due_date"),
                DB::raw("DATE_FORMAT(ps.due_date, '%Y-%m-%d') as due_date"),
                'personal_quotes.price_vat_applicable',
                'personal_quotes.vat',
                'personal_quotes.price_vat_not_applicable',
                'p.discount_value as discount',
                DB::raw('FORMAT(((
                    IFNULL( personal_quotes.price_vat_applicable , 0 ) +
                    IFNULL( personal_quotes.price_vat_not_applicable , 0 )  +
                    IFNULL( personal_quotes.vat , 0 )) - IFNULL( p.discount_value , 0 )),2) as total_price'),
                DB::raw('p.commission_vat_applicable as commission_vat_applicable'),
                DB::raw('p.commission_vat as commission_vat'),
                DB::raw('p.commission_vat_not_applicable as commission_vat_not_applicable'),
                DB::raw('p.premium_captured as collected_amount'),
                'p.captured_at as payment_date',
                DB::raw('FORMAT(((
                    IFNULL( personal_quotes.price_vat_applicable , 0 ) +
                    IFNULL( personal_quotes.price_vat_not_applicable , 0 ) +
                    IFNULL( personal_quotes.vat , 0 )) - IFNULL( p.discount_value , 0 )) -
                    SUM(p.premium_captured),2) as pending_balance'),
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
            )
            ->join('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->join('payment_splits as ps', 'p.code', '=', 'ps.code')
            ->join('quote_type', 'quote_type.id', '=', 'quote_type_id')
            ->leftJoin('users as u', 'u.id', '=', 'advisor_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
            ->leftJoin('user_team as ut', 'ut.user_id', '=', 'u.id')
            ->leftJoin('teams as t', 't.id', '=', 'ut.team_id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'p.insurance_provider_id')
            ->leftJoin('payment_methods as pm', 'pm.code', '=', 'p.payment_methods_code')
            ->leftJoin('payment_gateway as pg', 'pg.id', '=', 'p.payment_gateway_id')
            ->leftJoin('business_type_of_insurance as btoi', 'btoi.id', '=', 'personal_quotes.business_type_of_insurance_id');

        $this->applyFilters($query, $request);

        $utmGroupBy = $this->getUtmGroup($request, $query);

        if ($utmGroupBy) {
            $query->groupBy(['personal_quotes.code', $utmGroupBy]);
        } else {
            $query->groupBy('personal_quotes.code');
        }

        if ($request->export == 1) {
            $data = $query->get();

            $data = $data->map(function ($item) {
                $item->payment_due_date = $item->payment_due_date != null ? $item->payment_due_date : ($item->due_date ? $item->due_date : 'N/A');

                return $item;
            });

            // Columns that are not integar and should not be summed
            $nonIntegarIndexes = [0, 1, 2, 3, 13, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27];

            return $this->download(
                'Transaction Report '.$this->reportDateRange,
                $data,
                $this->headings(),
                $nonIntegarIndexes
            );
        } else {
            return $query->simplePaginate(100)->withQueryString();
        }
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
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->policy_number ? '="'.$quote->policy_number.'"' : 'N/A',
            $quote->transactions ?? 0,
            $quote->policy_start_date ?? 'N/A',
            $quote->payment_due_date ? $quote->payment_due_date : ($quote->due_date ?? 'N/A'),
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
        ];
    }
}
