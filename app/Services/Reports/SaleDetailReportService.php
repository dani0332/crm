<?php

namespace App\Services\Reports;

use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Exports\Reports\SaleDetailReportExport;
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
                'personal_quotes.quote_type_id',
                'personal_quotes.business_type_of_insurance_id',
                'personal_quotes.uuid',
                'personal_quotes.source',
                'personal_quotes.code',
                DB::raw('IFNULL(GROUP_CONCAT(DISTINCT t.name SEPARATOR ", "), "") as team'),
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
                DB::raw('(IFNULL( p.commission_vat_applicable , 0 ) + IFNULL( p.commission_vat , 0 )) as total_commission'),
                'p.collection_type as collects',
                'p.insurer_tax_number as insurer_tax_invoice_number',
                'insurer_invoice_date as insurer_tax_invoice_date',
                'payment_status.text as transaction_payment_status',
                'p.captured_at as date_paid',
                'p.captured_amount as collected_amount',
                'personal_quotes.first_name',
                'personal_quotes.last_name',
                'cm.code as customer_type',
                'ip.code as insurer',
                'quote_type.text as line_of_business',
                'u.name as advisor',
                'dp.name as department',
                'pi.name as policy_issuer',
                'btoi.text as sub_type_line_of_business',
                'p.insurer_commmission_invoice_number',
                'l.text as transaction_type',
                'p.commmission_percentage',
                'personal_quotes.policy_booking_date',
                'ps.sage_reciept_id',
            )
            ->join('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->join('payment_splits as ps', 'p.code', '=', 'ps.code')
            ->join('payment_status', 'payment_status.id', '=', 'p.payment_status_id')
            ->join('quote_type', 'quote_type.id', '=', 'quote_type_id')
            ->join('insurance_provider as ip', 'ip.id', '=', 'p.insurance_provider_id')
            ->leftJoin('users as u', 'u.id', '=', 'advisor_id')
            ->leftJoin('departments as dp', 'u.department_id', '=', 'dp.id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
            ->leftJoin('user_team as ut', 'ut.user_id', '=', 'u.id')
            ->leftJoin('teams as t', 't.id', '=', 'ut.team_id')
            ->leftJoin('customer as cm', 'cm.id', '=', 'personal_quotes.customer_id')
            ->leftJoin('business_type_of_insurance as btoi', 'btoi.id', '=', 'personal_quotes.business_type_of_insurance_id')
            ->leftJoin('lookups as l', 'personal_quotes.transaction_type_id', '=', 'l.id');

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

            return (new SaleDetailReportExport($data))->download("Sale Detail Report {$this->reportDateRange}.xlsx");
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
            $item->transactions = $this->concatValues([$item->insurer_tax_invoice_number, $item->notes, $item->reference], '-');
            $item->policy_start_date = ! empty($item->policy_start_date) ? Carbon::parse($item->policy_start_date)->format('Y-m-d') : null;
            $item->payment_due_date = ! empty($item->payment_due_date) ? Carbon::parse($item->payment_due_date)->format('Y-m-d') : null;
            $item->due_date = ! empty($item->due_date) ? Carbon::parse($item->due_date)->format('Y-m-d') : null;
            $item->total_price = number_format($item->total_price, 2);
            $item->collected_amount = number_format($item->collected_amount, 2);
            $item->total_commission = number_format($item->total_commission, 2);
            $item->collects = strtoupper($item->collects);
            $item->customer_name = $this->concatValues([$item->first_name, $item->last_name], ' ');
            $item->commmission_percentage = number_format($item->commmission_percentage, 2);
            $item->policy_booking_date = ! empty($item->policy_booking_date) ? Carbon::parse($item->policy_booking_date)->format('Y-m-d') : null;
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
}
