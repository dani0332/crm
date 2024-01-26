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

class TransactionReportService extends ManagementReport
{
    use TeamHierarchyTrait;

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::TRANSACTION;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::TRANSACTION_PAYMENTS;

        $query = PersonalQuote::query()
            ->select(
                'policy_number',
                DB::raw('CONCAT(p.reference, " ", p.tax_invoice_number) as transactions'),
                'policy_start_date',
                'p.policy_due_date',
                'price_vat_applicable',
                'vat',
                'price_vat_not_applicable',
                'p.discount_value as discount',
                DB::raw('FORMAT(((price_vat_applicable + price_vat_not_applicable + vat) - p.discount_value),2) as total_price'),
                DB::raw('FORMAT(p.commission_vat_applicable,2) as commission_vat_applicable'),
                DB::raw('FORMAT(p.commission_vat,2) as commission_vat'),
                DB::raw('FORMAT(p.commission_vat_not_applicable,2) as commission_vat_not_applicable'),
                DB::raw('FORMAT(p.premium_captured,2) as collected_amount'),
                'p.captured_at as payment_date',
                DB::raw('FORMAT(((price_vat_applicable + price_vat_not_applicable + vat) - p.discount_value) - SUM(p.premium_captured),2) as pending_balance'),
                DB::raw("'collects' as collects"),
                'ip.text as insurer',
                'quote_type.text as line_of_business',
                DB::raw("'sub_type_line_of_business' as sub_type_line_of_business"),
                DB::raw("CONCAT(first_name, ' ', last_name) as customer_name"),
                'u.name as advisor',
                'pi.name as policy_issuer',
                'p.invoice_description as invoice_description',
                'pm.name as payment_method',
                'pg.text as payment_gateway',
                'tax_invoice_number as insurer_invoice_number',
                'insurer_invoice_date as insurer_tax_invoice_date',
                'p.broker_invoice_number',
            )
            ->leftJoin('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->leftJoin('quote_type', 'quote_type.id', '=', 'quote_type_id')
            ->leftJoin('users as u', 'u.id', '=', 'advisor_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
            ->join('user_team as ut', 'ut.user_id', '=', 'u.id')
            ->join('teams as t', 't.id', '=', 'ut.team_id')
            ->join('insurance_provider as ip', 'ip.id', '=', 'p.insurance_provider_id')
            ->join('payment_methods as pm', 'pm.code', '=', 'p.payment_methods_code')
            ->join('payment_gateway as pg', 'pg.id', '=', 'p.payment_gateway_id');

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
            'paymentDueDate' => $defaultDate,
            'reportCategory' => ManagementReportCategoriesEnum::TRANSACTION,
            'reportType' => ManagementReportTypeEnum::TRANSACTION_PAYMENTS,
        ];
    }
}
