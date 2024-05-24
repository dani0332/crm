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
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::ISSUED_POLICIES;

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
            ->select(
                DB::raw('DISTINCT(personal_quotes.policy_number)'),
                DB::raw("CONCAT(p.reference, ' ', p.tax_invoice_number) as transactions"),
                DB::raw("DATE_FORMAT(personal_quotes.policy_start_date, '%Y-%m-%d') as policy_start_date"),
                DB::raw("DATE_FORMAT(p.payment_due_date, '%Y-%m-%d') as payment_due_date"),
                DB::raw("DATE_FORMAT(ps.due_date, '%Y-%m-%d') as due_date"),
                'personal_quotes.source',
                't.name as team',
                'personal_quotes.price_vat_applicable',
                'personal_quotes.vat',
                'personal_quotes.price_vat_not_applicable',
                'p.discount_value as discount',
                DB::raw('FORMAT(((
                    IFNULL( personal_quotes.price_vat_applicable , 0 ) +
                    IFNULL( personal_quotes.price_vat_not_applicable , 0 )  +
                    IFNULL( personal_quotes.vat , 0 )) - IFNULL( p.discount_value , 0 )),2) as total_price'),
                DB::raw('FORMAT(p.commission_vat_applicable,2) as commission_vat_applicable'),
                DB::raw('FORMAT(p.commission_vat,2) as commission_vat'),
                DB::raw('FORMAT(p.commission_vat_not_applicable,2) as commission_vat_not_applicable'),
                DB::raw('FORMAT(( IFNULL( commission_vat_applicable , 0 ) + IFNULL( commission_vat , 0 )),2) as total_commission'),                DB::raw('UPPER(p.collection_type) as collects'),
                'tax_invoice_number as insurer_tax_invoice_number',
                'insurer_invoice_date as insurer_tax_invoice_date',
                'payment_status.text as transaction_payment_status',
                'p.captured_at as date_paid',
                DB::raw('FORMAT(personal_quotes.premium_captured,2) as collected_amount'),
                DB::raw("CONCAT(personal_quotes.first_name, ' ', personal_quotes.last_name) as customer_name"),
                'cm.code as customer_type',
                'ip.code as insurer',
                'quote_type.text as line_of_business',
                'btoi.text as sub_type_line_of_business',
                'u.name as advisor',
                'pi.name as policy_issuer',
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
            ->leftJoin('business_quote_request as bqr', 'bqr.code', '=', 'personal_quotes.code')
            ->leftJoin('business_type_of_insurance as btoi', 'btoi.id', '=', 'bqr.business_type_of_insurance_id');

        $this->applyFilters($query, $request);

        $utmGroupBy = $this->getUtmGroup($request, $query);

        if ($utmGroupBy) {
            $query->groupBy($utmGroupBy);
        }

        if ($request->export == 1) {
            $data = $query->get();

            // Columns that are not integar and should not be summed
            $nonIntegarIndexes = [0, 2, 3, 4, 5, 15, 16, 17, 18, 19, 21, 22, 23, 24, 25, 26, 27];

            return $this->download(
                'Sale Detail Report '.$this->reportDateRange,
                $data,
                $this->headings(),
                $nonIntegarIndexes);
        } else {
            return $query->simplePaginate(10)->withQueryString();
        }

        return $query->simplePaginate(10)->withQueryString();
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
            'reportCategory' => ManagementReportCategoriesEnum::SALE_DETAIL,
            'reportType' => ManagementReportTypeEnum::ISSUED_POLICIES,
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
            $quote->policy_number ?? 'N/A',
            $quote->transactions ?? 0,
            $quote->policy_start_date ?? 'N/A',
            $quote->payment_due_date ?? 'N/A',
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
