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

class SaleDetailReportService extends ManagementReport
{
    use TeamHierarchyTrait;

    private $reportDateRange;

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::SALE_DETAIL;
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

        $query = PersonalQuote::query()
            ->select(
                DB::raw('DISTINCT(personal_quotes.policy_number)'),
                'p.notes',
                'p.reference',
                'personal_quotes.policy_start_date',
                'p.payment_due_date',
                'ps.due_date',
                'personal_quotes.source',
                'personal_quotes.code',
                't.name as team',
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
                DB::raw('(IFNULL( commission_vat_applicable , 0 ) + IFNULL( commission_vat , 0 )) as total_commission'),
                'p.collection_type as collects',
                'p.insurer_tax_number as insurer_tax_invoice_number',
                'insurer_invoice_date as insurer_tax_invoice_date',
                'payment_status.text as transaction_payment_status',
                'p.captured_at as date_paid',
                'personal_quotes.premium_captured as collected_amount',
                'personal_quotes.first_name',
                'personal_quotes.last_name',
                'cm.code as customer_type',
                'ip.code as insurer',
                'quote_type.text as line_of_business',
                'u.name as advisor',
                'pi.name as policy_issuer',
                'btoi.text as sub_type_line_of_business',
            )
            ->join('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->join('payment_splits as ps', 'p.code', '=', 'ps.code')
            ->join('payment_status', 'payment_status.id', '=', 'p.payment_status_id')
            ->join('quote_type', 'quote_type.id', '=', 'quote_type_id')
            ->join('insurance_provider as ip', 'ip.id', '=', 'p.insurance_provider_id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->leftJoin('users as u', 'u.id', '=', 'advisor_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
            ->leftJoin('user_team as ut', 'ut.user_id', '=', 'u.id')
            ->leftJoin('teams as t', 't.id', '=', 'ut.team_id')
            ->leftJoin('customer as cm', 'cm.id', '=', 'personal_quotes.customer_id')
            ->leftJoin('business_type_of_insurance as btoi', 'btoi.id', '=', 'personal_quotes.business_type_of_insurance_id');

        $this->applyFilters($query, $request);

        $utmGroupBy = $this->getUtmGroup($request, $query);

        if ($utmGroupBy) {
            $query->groupBy($utmGroupBy);
        } else {
            $query->groupBy('personal_quotes.code');
        }

        if ($request->export == 1) {
            $data = $query->get();
            $this->formatData($data);

            // Columns that are not integar and should not be summed
            $nonIntegarIndexes = [0, 1, 2, 3, 4, 5, 15, 16, 17, 18, 19, 21, 22, 23, 24, 25, 26, 27];

            return $this->download(
                'Sale Detail Report '.$this->reportDateRange,
                $data,
                $this->headings(),
                $nonIntegarIndexes
            );
        } else {
            $data = $query->simplePaginate(100)->withQueryString();
            $this->formatData($data);

            return $data;
        }
    }

    private function formatData(&$data)
    {
        $data->map(function ($item) {
            $item->transactions = implode('-', array_filter([$item->insurer_tax_invoice_number, $item->notes, $item->reference], function ($value) { return !empty($value); }));
            $item->policy_start_date = !empty($item->policy_start_date) ? Carbon::parse($item->policy_start_date)->format('Y-m-d') : null;
            $item->payment_due_date = !empty($item->payment_due_date) ? Carbon::parse($item->payment_due_date)->format('Y-m-d') : null;
            $item->due_date = !empty($item->due_date) ? Carbon::parse($item->due_date)->format('Y-m-d') : null;
            $item->total_price = number_format($item->total_price, 2);
            $item->total_commission = number_format($item->total_commission, 2);
            $item->collects = strtoupper($item->collects);
            $item->customer_name = $item->first_name . ' ' . $item->last_name;
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
            'policyBookDate' => $defaultDate,
            'reportCategory' => ManagementReportCategoriesEnum::SALE_DETAIL,
            'reportType' => ManagementReportTypeEnum::BOOKED_POLICIES,
        ];
    }

    public function headings(): array
    {
        return [
            'Policy No.',
            'Transactions',
            'Policy Start Date',
            'Payment Due Date',
            'Source',
            'Team',
            'Price (VAT applicable)',
            'Total VAT',
            'Price (VAT not applicable)',
            'Discount',
            'Total Price',
            'Commission (VAT applicable)',
            'VAT on Commission',
            'Commission (VAT not applicable)',
            'Total Commission',
            'Collects',
            'Tax Invoice Number',
            'Tax Invoice Date',
            'Transaction Payment Status',
            'Date Paid',
            'Collected Amount',
            'Customer Name',
            'Customer Type',
            'Insurer',
            'Line of Business',
            'Sub-Type',
            'Advisor',
            'Policy Issuer ',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->policy_number ? '="'.$quote->policy_number.'"' : 'N/A',
            $quote->transactions ? $quote->transactions : 'N/A',
            $quote->policy_start_date ?? 'N/A',
            $quote->payment_due_date ? $quote->payment_due_date : ($quote->due_date ?? 'N/A'),
            $quote->source ?? 'N/A',
            $quote->team ?? 'N/A',
            $quote->price_vat_applicable ?? '0.00',
            $quote->vat ?? '0.00',
            $quote->price_vat_not_applicable ?? '0.00',
            $quote->discount ?? '0.00',
            $quote->total_price ?? '0.00',
            $quote->commission_vat_applicable ?? '0.00',
            $quote->commission_vat ?? '0.00',
            $quote->commission_vat_not_applicable ?? '0.00',
            $quote->total_commission ?? '0.00',
            $quote->collects ?? 'N/A',
            $quote->insurer_tax_invoice_number ?? 'N/A',
            $quote->insurer_tax_invoice_date ?? 'N/A',
            $quote->transaction_payment_status ?? 'N/A',
            $quote->date_paid ?? 'N/A',
            $quote->collected_amount ?? '0.00',
            $quote->customer_name ?? 'N/A',
            $quote->customer_type ?? 'N/A',
            $quote->insurer ?? 'N/A',
            $quote->line_of_business ?? 'N/A',
            $quote->sub_type_line_of_business ?? 'N/A',
            $quote->advisor ?? 'N/A',
            $quote->policy_issuer ?? 'N/A',
        ];
    }
}
