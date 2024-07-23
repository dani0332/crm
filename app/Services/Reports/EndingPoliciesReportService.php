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

class EndingPoliciesReportService extends ManagementReport
{
    use TeamHierarchyTrait;

    private $reportDateRange;

    public function getReportData(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::ENDING_POLICIES;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::EXPIRING_POLICIES;

        if ($request['policyExpiredDate'] && ! empty($request['policyExpiredDate']) && is_array($request['policyExpiredDate'])) {
            $this->reportDateRange = Carbon::parse($request['policyExpiredDate'][0])->toDateString()
                .' - '.
                Carbon::parse($request['policyExpiredDate'][1])->toDateString();
        }

        $query = PersonalQuote::query()
            ->leftJoin('users as u', 'u.id', '=', 'advisor_id')
            ->leftJoin('quote_type as qt', 'qt.id', '=', 'quote_type_id')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'insurance_provider_id')
            ->leftJoin('payments as p', 'personal_quotes.code', '=', 'p.code')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'p.payment_status_id')
            ->leftJoin('customer as c', 'c.id', '=', 'customer_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->join('user_team as ut', 'ut.user_id', '=', 'u.id')
            ->join('teams as t', 't.id', '=', 'ut.team_id')
            ->select(
                'c.first_name',
                'c.last_name',
                'policy_number',
                'ip.text as insurer',
                'qt.code as line_of_business',
                'personal_quotes.policy_start_date',
                'p.policy_expiry_date as policy_end_date',
                DB::raw('SUM(premium) as collected_amount'),
                DB::raw('SUM(price_vat_applicable) as price_vat_applicable'),
                DB::raw('SUM(vat) as total_vat'),
                DB::raw('SUM(price_vat_not_applicable) as price_vat_not_applicable'),
                DB::raw('SUM(p.discount_value) as discount'),
                DB::raw('SUM(price_vat_applicable + price_vat_not_applicable + vat - p.discount_value) as total_price'),
                DB::raw('(SUM(price_vat_applicable + price_vat_not_applicable + vat - p.discount_value) - SUM(premium)) as pending_balance'),
                DB::raw('SUM(p.commission_vat_applicable) as commission_vat_applicable'),
                DB::raw('SUM(p.commission_vat) as commission_vat'),
                DB::raw('SUM(p.commission_vat_not_applicable) as commission_vat_not_applicable'),
                'pi.name as policy_issuer',
                'u.name as advisor',
                'personal_quotes.source',
                'personal_quotes.notes',
            );

        $this->applyFilters($query, $request);

        $utmGroupBy = $this->getUtmGroup($request, $query);

        if ($utmGroupBy) {
            $query->groupBy(['personal_quotes.code', $utmGroupBy]);
        } else {
            $query->groupBy('personal_quotes.code');
        }

        if ($request->export == 1) {
            $data = $query->get();
            $this->formatData($data);

            //Columns that are not integar and should not be summed
            $nonIntegarIndexes = [0, 1, 2, 3, 4, 5, 16, 17, 18, 19];

            return $this->download(
                'Ending Policies Report '.$this->reportDateRange,
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
            $item->customer_name = $item->first_name.' '.$item->last_name;
            $item->policy_start_date = ! empty($item->policy_start_date) ? Carbon::parse($item->policy_start_date)->format('Y-m-d') : null;
            $item->policy_end_date = ! empty($item->policy_end_date) ? Carbon::parse($item->policy_end_date)->format('Y-m-d') : null;
            $item->collected_amount = number_format($item->collected_amount, 2);
            $item->price_vat_applicable = number_format($item->price_vat_applicable, 2);
            $item->total_vat = number_format($item->total_vat, 2);
            $item->price_vat_not_applicable = number_format($item->price_vat_not_applicable, 2);
            $item->discount = number_format($item->discount, 2);
            $item->total_price = number_format($item->total_price, 2);
            $item->pending_balance = number_format($item->pending_balance, 2);
            $item->commission_vat_applicable = number_format($item->commission_vat_applicable, 2);
            $item->commission_vat = number_format($item->commission_vat, 2);
            $item->commission_vat_not_applicable = number_format($item->commission_vat_not_applicable, 2);
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
            'policyExpiredDate' => $defaultDate,
            'reportCategory' => ManagementReportCategoriesEnum::ENDING_POLICIES,
            'reportType' => ManagementReportTypeEnum::EXPIRING_POLICIES,
        ];
    }

    public function headings(): array
    {
        return [
            'Customer Name',
            'Policy Number',
            'Insurer',
            'Line Of Business',
            'Policy Start Date',
            'Policy Expiry Date',
            'Collected Amount',
            'Price (VAT applicable)',
            'Total VAT',
            'Price (VAT not applicable)',
            'Discount',
            'Total Price',
            'Pending Balance',
            'Commission (VAT applicable)',
            'VAT on Commission',
            'Commission (VAT not applicable)',
            'Policy Issuer ',
            'Advisor',
            'Lead Source',
            'Notes',
        ];
    }

    public function map($quote): array
    {
        return [
            $quote->customer_name ?? 'N/A',
            $quote->policy_number ? '="'.$quote->policy_number.'"' : 'N/A',
            $quote->insurer ?? 'N/A',
            $quote->line_of_business ?? 'N/A',
            $quote->policy_start_date ?? 'N/A',
            $quote->policy_end_date ?? 'N/A',
            $quote->collected_amount ?? '0.00',
            $quote->price_vat_applicable ?? '0.00',
            $quote->total_vat ?? '0.00',
            $quote->price_vat_not_applicable ?? '0.00',
            $quote->discount ?? '0.00',
            $quote->total_price ?? '0.00',
            $quote->pending_balance ?? '0.00',
            $quote->commission_vat_applicable ?? '0.00',
            $quote->commission_vat ?? '0.00',
            $quote->commission_vat_not_applicable ?? '0.00',
            $quote->policy_issuer ?? 'N/A',
            $quote->advisor ?? 'N/A',
            $quote->source ?? 'N/A',
            $quote->notes ?? 'N/A',
        ];
    }
}
