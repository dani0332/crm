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

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::TRANSACTION;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::TRANSACTION_PAYMENTS;

        $query = PersonalQuote::query()
            ->select(
                'personal_quotes.policy_number', 'personal_quotes.code',
                DB::raw('CONCAT(p.reference, " ", p.tax_invoice_number) as transactions'),
                DB::raw("DATE_FORMAT(personal_quotes.policy_start_date, '%Y-%m-%d') as policy_start_date"),
                DB::raw("DATE_FORMAT(p.payment_due_date, '%Y-%m-%d') as payment_due_date"),
                DB::raw("DATE_FORMAT(ps.due_date, '%Y-%m-%d') as due_date"),
                'personal_quotes.price_vat_applicable',
                'personal_quotes.vat',
                'personal_quotes.price_vat_not_applicable',
                'p.discount_value as discount',
                DB::raw('FORMAT(((personal_quotes.price_vat_applicable + personal_quotes.price_vat_not_applicable + personal_quotes.vat) - p.discount_value),2) as total_price'),
                DB::raw('FORMAT(p.commission_vat_applicable,2) as commission_vat_applicable'),
                DB::raw('FORMAT(p.commission_vat,2) as commission_vat'),
                DB::raw('FORMAT(p.commission_vat_not_applicable,2) as commission_vat_not_applicable'),
                DB::raw('FORMAT(p.premium_captured,2) as collected_amount'),
                'p.captured_at as payment_date',
                DB::raw('FORMAT(((personal_quotes.price_vat_applicable + personal_quotes.price_vat_not_applicable + personal_quotes.vat) - p.discount_value) - SUM(p.premium_captured),2) as pending_balance'),
                DB::raw('UPPER(p.collection_type) as collects'),
                'ip.text as insurer',
                'quote_type.text as line_of_business',
                DB::raw("CONCAT(personal_quotes.first_name, ' ', personal_quotes.last_name) as customer_name"),
                'u.name as advisor',
                'pi.name as policy_issuer',
                'p.invoice_description as invoice_description',
                'pm.name as payment_method',
                'pg.text as payment_gateway',
                'tax_invoice_number as insurer_invoice_number',
                'insurer_invoice_date as insurer_tax_invoice_date',
                'p.broker_invoice_number',
                'personal_quotes.business_type_of_insurance_id as sub_type_line_of_business',
            )
            ->join('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->join('payment_splits as ps', 'p.code', '=', 'ps.code')
            ->join('quote_type', 'quote_type.id', '=', 'quote_type_id')
            ->join('users as u', 'u.id', '=', 'advisor_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
            ->leftJoin('user_team as ut', 'ut.user_id', '=', 'u.id')
            ->leftJoin('teams as t', 't.id', '=', 'ut.team_id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'p.insurance_provider_id')
            ->leftJoin('payment_methods as pm', 'pm.code', '=', 'p.payment_methods_code')
            ->leftJoin('payment_gateway as pg', 'pg.id', '=', 'p.payment_gateway_id');

        $this->applyFilters($query, $request);

        $utmGroupBy = $this->getUtmGroup($request, $query);

        if ($utmGroupBy) {
            $query->groupBy(['personal_quotes.code', $utmGroupBy]);
        } else {
            $query->groupBy('personal_quotes.code');
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
            'paymentDueDate' => $defaultDate,
            'reportCategory' => ManagementReportCategoriesEnum::TRANSACTION,
            'reportType' => ManagementReportTypeEnum::TRANSACTION_PAYMENTS,
        ];
    }
}
