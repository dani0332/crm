<?php

namespace App\Services;

use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Models\PersonalQuote;
use App\Strategies\ManagementReport;
use App\Traits\TeamHierarchyTrait;
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
                'policy_start_date',
                'policy_due_date',
                'source',
                'quote_type.code as line_of_business',
                'u.name as advisor_name',
                'pi.name as policy_issuer',
                'tax_invoice_number as insurer_tax_invoice_number',
                'insurer_invoice_date as insurer_tax_invoice_date',
                'payment_status.text as transaction_payment_status',
                'payments.captured_at as date_paid',
                DB::raw('FORMAT(personal_quotes.price_vat_applicable, 2) as price_vat_applicable'),
                DB::raw('FORMAT(payments.discount_value,2) as discount'),
                DB::raw('FORMAT(((price_vat_applicable + price_vat_not_applicable + vat) - payments.discount_value),2) as total_price'),
                DB::raw('FORMAT(payments.commission_vat_applicable,2) as commission_vat_applicable'),
                DB::raw('FORMAT(payments.commission_vat,2) as commission_vat'),
                DB::raw('FORMAT(payments.commission_vat_not_applicable,2) as commission_vat_not_applicable'),
                DB::raw('(commission_vat_applicable + commission_vat) as total_commission'),
                DB::raw("'collects' as collects"),
                DB::raw('FORMAT(personal_quotes.premium_captured,2) as collected_amount'),
                DB::raw("CONCAT(first_name, ' ', last_name) as customer_name"),
                DB::raw("'customer_type' as customer_type"),
                DB::raw("'sub_type_line_of_business' as sub_type_line_of_business"),
            )
            ->leftJoin('payments', 'personal_quotes.code', '=', 'payments.code')
            ->leftJoin('payment_status', 'payment_status.id', '=', 'payments.payment_status_id')
            ->leftJoin('quote_type', 'quote_type.id', '=', 'quote_type_id')
            ->leftJoin('users as u', 'u.id', '=', 'advisor_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'payments.policy_issuer_id')
            ->when($request->groupBy, function ($query, $groupBy) {
                return $query->groupBy($this->resolveGroupByColumn($groupBy));
            });

        $this->applyFilters($query, $request);

        return $query->simplePaginate(10)->withQueryString();
    }

    private function resolveGroupByColumn($groupBy)
    {
        $mapping = [
            'policy_issuer' => 'payments.policy_issuer_id',
            'customer_group' => 'personal_quotes.customer_id',
            'insurer' => 'payments.insurer_id',
            'advisor' => 'u.name',
            'line_of_business' => 'quote_type.code',
        ];

        return $mapping[$groupBy] ?? $groupBy;
    }

    public function getDefaultFilters()
    {
        $advisorAssignedDates = [ManagementReportCategoriesEnum::SALE_DETAIL];

        return [
            'managementReportCategories' => $advisorAssignedDates,
        ];
    }
}
