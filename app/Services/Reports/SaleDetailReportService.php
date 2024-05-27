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

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::SALE_DETAIL;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::ISSUED_POLICIES;
        $query = PersonalQuote::query()
            ->select(
                DB::raw('DISTINCT(personal_quotes.policy_number)'),
                DB::raw("CONCAT(p.reference, ' ', p.tax_invoice_number) as transactions"),
                DB::raw("DATE_FORMAT(personal_quotes.policy_start_date, '%Y-%m-%d') as policy_start_date"),
                DB::raw("DATE_FORMAT(p.payment_due_date, '%Y-%m-%d') as payment_due_date"),
                DB::raw("DATE_FORMAT(ps.due_date, '%Y-%m-%d') as due_date"),
                'personal_quotes.source', 'personal_quotes.code',
                't.name as team',
                'personal_quotes.price_vat_applicable',
                'personal_quotes.vat',
                'personal_quotes.price_vat_not_applicable',
                'p.discount_value as discount',
                DB::raw('FORMAT(((personal_quotes.price_vat_applicable + personal_quotes.price_vat_not_applicable + personal_quotes.vat) - p.discount_value),2) as total_price'),
                DB::raw('FORMAT(p.commission_vat_applicable,2) as commission_vat_applicable'),
                DB::raw('FORMAT(p.commission_vat,2) as commission_vat'),
                DB::raw('FORMAT(p.commission_vat_not_applicable,2) as commission_vat_not_applicable'),
                DB::raw('FORMAT((commission_vat_applicable + commission_vat),2) as total_commission'),
                DB::raw('UPPER(p.collection_type) as collects'),
                'tax_invoice_number as insurer_tax_invoice_number',
                'insurer_invoice_date as insurer_tax_invoice_date',
                'payment_status.text as transaction_payment_status',
                'p.captured_at as date_paid',
                DB::raw('FORMAT(personal_quotes.premium_captured,2) as collected_amount'),
                DB::raw("CONCAT(personal_quotes.first_name, ' ', personal_quotes.last_name) as customer_name"),
                'cm.code as customer_type',
                'ip.code as insurer',
                'quote_type.text as line_of_business',
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
            ->leftJoin('customer as cm', 'cm.id', '=', 'personal_quotes.customer_id');

        $this->applyFilters($query, $request);

        $utmGroupBy = $this->getUtmGroup($request, $query);

        if ($utmGroupBy) {
            $query->groupBy($utmGroupBy);
        }

        return $query->simplePaginate(10)->withQueryString()->through(function ($item) {
            return $this->businessSubTypeMapper($item);
        });    }

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
