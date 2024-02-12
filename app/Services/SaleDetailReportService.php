<?php

namespace App\Services;

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

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::SALE_DETAIL;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::ISSUED_POLICIES;
        $query = PersonalQuote::query()
            ->select(
                'policy_number',
                DB::raw("CONCAT(p.reference, ' ', p.tax_invoice_number) as transactions"),
                'policy_start_date',
                'p.policy_due_date',
                'source',
                't.name as team',
                'price_vat_applicable',
                'vat',
                'price_vat_not_applicable',
                'p.discount_value as discount',
                DB::raw('FORMAT(((price_vat_applicable + price_vat_not_applicable + vat) - p.discount_value),2) as total_price'),
                DB::raw('FORMAT(p.commission_vat_applicable,2) as commission_vat_applicable'),
                DB::raw('FORMAT(p.commission_vat,2) as commission_vat'),
                DB::raw('FORMAT(p.commission_vat_not_applicable,2) as commission_vat_not_applicable'),
                DB::raw('FORMAT((commission_vat_applicable + commission_vat),2) as total_commission'),
                DB::raw("UPPER(p.collection_type) as collects"),
                'tax_invoice_number as insurer_tax_invoice_number',
                'insurer_invoice_date as insurer_tax_invoice_date',
                'payment_status.text as transaction_payment_status',
                'p.captured_at as date_paid',
                DB::raw('FORMAT(personal_quotes.premium_captured,2) as collected_amount'),
                DB::raw("CONCAT(first_name, ' ', last_name) as customer_name"),
                DB::raw("'customer_type' as customer_type"),
                'ip.code as insurer',
                'quote_type.text as line_of_business',
                DB::raw("'sub_type_line_of_business' as sub_type_line_of_business"),
                'u.name as advisor',
                'pi.name as policy_issuer',
            )
            ->join('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->join('payment_status', 'payment_status.id', '=', 'p.payment_status_id')
            ->join('quote_type', 'quote_type.id', '=', 'quote_type_id')
            ->join('insurance_provider as ip', 'ip.id', '=', 'p.insurance_provider_id')
            ->leftJoin('users as u', 'u.id', '=', 'advisor_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
            ->leftJoin('user_team as ut', 'ut.user_id', '=', 'u.id')
            ->leftJoin('teams as t', 't.id', '=', 'ut.team_id');

        $this->applyFilters($query, $request);

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
}
