<?php

namespace App\Services\Reports;

use App\Enums\LeadSourceEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Enums\QuoteTypeId;
use App\Enums\TravelQuoteEnum;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Strategies\ManagementReport;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EndingPoliciesReportService extends ManagementReport
{
    use TeamHierarchyTrait;

    private $reportDateRange;

    public function getReportQueryBuilder(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::ENDING_POLICIES;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::EXPIRING_POLICIES;

        if ($request['policyExpiredDate'] && ! empty($request['policyExpiredDate']) && is_array($request['policyExpiredDate'])) {
            $this->reportDateRange = (isset($request['policyExpiredDate'][0]) && isValidDate($request['policyExpiredDate'][0]) ? Carbon::parse($request['policyExpiredDate'][0])->toDateString() : today()->toDateString())
                .' - '.
                (isset($request['policyExpiredDate'][1]) && isValidDate($request['policyExpiredDate'][1]) ? Carbon::parse($request['policyExpiredDate'][1])->toDateString() : today()->toDateString());
        }

        $query = PersonalQuote::query();
        $this->paymentJoin($query, null, 'p', 'leftJoin', request: $request);
        $query->leftJoin('users as u', 'u.id', '=', 'advisor_id')
            ->join('quote_type as qt', 'qt.id', '=', 'quote_type_id')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'personal_quotes.insurance_provider_id')
            ->leftJoin('insurance_provider_plans as ipp', 'ipp.id', '=', 'p.plan_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'p.payment_status_id')
            ->leftJoin('customer as c', 'c.id', '=', 'customer_id')
            ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
            ->leftJoin('departments as dp', 'dp.id', '=', 'u.department_id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->leftJoin('insurance_provider as ciw', 'personal_quotes.currently_insured_with_id', '=', 'ciw.id')
            ->leftJoin('lookups as ls', 'personal_quotes.sub_source_id', '=', 'ls.id')
            ->leftJoin('lookups as sso', 'personal_quotes.sub_source_options_id', '=', 'sso.id')
            ->leftJoin('car_quote_request as cqr', function ($join) {
                $join->on('personal_quotes.quote_id', '=', 'cqr.id')
                    ->where('personal_quotes.quote_type_id', '=', QuoteTypeId::Car);
            })
            ->leftJoin('health_quote_request as hqr', function ($join) {
                $join->on('personal_quotes.quote_id', '=', 'hqr.id')
                    ->where('personal_quotes.quote_type_id', '=', QuoteTypeId::Health);
            })
            ->leftJoin('travel_quote_request as tqr', function ($join) {
                $join->on('personal_quotes.quote_id', '=', 'tqr.id')
                    ->where('personal_quotes.quote_type_id', '=', QuoteTypeId::Travel);
            })
            ->leftJoin('currently_located_in as cli', 'cli.id', '=', 'tqr.currently_located_in_id')
            ->leftJoin('nationality as n', 'n.id', '=', 'tqr.destination_id')
            ->leftJoin('business_quote_request as bqr', function ($join) {
                $join->on('personal_quotes.quote_id', '=', 'bqr.id')
                    ->whereIn('personal_quotes.quote_type_id', [QuoteTypeId::Business, QuoteTypeId::Corpline, QuoteTypeId::GroupMedical]);
            })
            ->leftJoin('users as pqa_user', 'pqa_user.id', '=', 'bqr.pq_advisor_id')
            ->leftJoin('users as health_pqa_user', 'health_pqa_user.id', '=', 'hqr.pq_advisor_id')
            ->select(
                'c.first_name',
                'c.last_name',
                'personal_quotes.policy_number',
                'ip.text as insurer',
                'qt.code as line_of_business',
                'personal_quotes.policy_start_date',
                'personal_quotes.policy_expiry_date as policy_end_date',
                DB::raw('SUM(personal_quotes.premium) as collected_amount'),
                DB::raw('SUM(personal_quotes.price_vat_applicable) as price_vat_applicable'),
                DB::raw('SUM(personal_quotes.vat) as total_vat'),
                DB::raw('SUM(personal_quotes.price_vat_not_applicable) as price_vat_not_applicable'),
                DB::raw('SUM(p.discount_value) as discount'),
                DB::raw('SUM(personal_quotes.price_vat_applicable + personal_quotes.price_vat_not_applicable + personal_quotes.vat - p.discount_value) as total_price'),
                DB::raw('(SUM(personal_quotes.price_vat_applicable + personal_quotes.price_vat_not_applicable + personal_quotes.vat - p.discount_value) - SUM(personal_quotes.premium)) as pending_balance'),
                DB::raw('SUM(p.commission_vat_applicable) as commission_vat_applicable'),
                DB::raw('SUM(p.commission_vat) as commission_vat'),
                DB::raw('SUM(p.commission_vat_not_applicable) as commission_vat_not_applicable'),
                'pi.name as policy_issuer',
                'u.name as advisor',
                'dp.name as department',
                'personal_quotes.source',
                'ls.text as sub_source',
                'sso.text as sub_source_option',
                'personal_quotes.notes',
                'ciw.text as currently_insured_with_text',
                'cqr.currently_insured_with as currently_insured_with',
                'personal_quotes.quote_type_id',
                DB::raw('CASE WHEN hqr.id IS NULL THEN "N/A" WHEN hqr.pec_marked_at IS NOT NULL THEN "Yes" ELSE "No" END as pec_flag'),
                'tqr.coverage_code as travel_coverage_code',
                'tqr.days_cover_for as travel_days_cover_for',
                'tqr.direction_code as travel_direction_code',
                'cli.text as travel_currently_located_in_id_text',
                'tqr.region_cover_for_id as travel_region_cover_for_id',
                'n.text as travel_destination_id_text',
                'ip.text as insurance_provider_name',
                'p.frequency as payment_frequency',
                'personal_quotes.created_at as quote_created_at',
                'ipp.text as plan_name',
                DB::raw('CASE WHEN personal_quotes.is_branch_applicable = 1 THEN b.name ELSE "N/A" END as branch_name'),
                DB::raw('IFNULL(COALESCE(pqa_user.name, health_pqa_user.name), "N/A") as pqa'),
            );

        $this->branchJoin($query, $request);
        $this->applyFilters($query, $request, isSSR: true);

        $utmGroupBy = $this->getUtmGroup($request, $query);

        if ($utmGroupBy) {
            $query->groupBy(['personal_quotes.code', $utmGroupBy]);
        } else {
            $query->groupBy('personal_quotes.code');
        }

        return $query;
    }

    public function getReportData(Request $request)
    {

        $query = $this->getReportQueryBuilder($request);

        LoggerService::sql(self::class.' - Ending Policies Report Query', $query);

        if ($request->export == 1) {
            $data = $query->get();
            $this->formatData($data);

            return $data;

        } else {
            $data = $query->simplePaginate(100)->withQueryString();
            $this->formatData($data);

            return $data;
        }
    }

    public function formatData(&$data)
    {
        $data->map(function ($item) {
            $item->customer_name = $this->concatValues([$item->first_name, $item->last_name], ' ');
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
            $item->currently_insured_with_text = $item->quote_type_id == QuoteTypeId::Car
                ? ($item->currently_insured_with_text ?? $item->currently_insured_with ?? 'N/A')
                : ($item->currently_insured_with_text ?? 'N/A');

            if ($item->quote_type_id == QuoteTypeId::Travel) {
                $item->travel_coverage = $item->source == LeadSourceEnum::RENEWAL_UPLOAD
                    ? TravelQuoteEnum::COVERAGE_CODE_MULTI_TRIP
                    : ($item->travel_coverage_code != null
                        ? $item->travel_coverage_code
                        : ($item->travel_days_cover_for !== null && $item->travel_days_cover_for <= 92
                            ? TravelQuoteEnum::COVERAGE_CODE_SINGLE_TRIP
                            : ($item->travel_days_cover_for !== null
                                ? TravelQuoteEnum::COVERAGE_CODE_ANNUAL_TRIP.
                                '/'.
                                TravelQuoteEnum::COVERAGE_CODE_MULTI_TRIP
                                : 'N/A')));

                $item->traveling_where = $item->travel_direction_code !== null
                    ? $item->travel_direction_code
                    : (
                        ($item->travel_currently_located_in_id_text == TravelQuoteEnum::LOCATION_UAE_TEXT &&
                            $item->travel_region_cover_for_id != TravelQuoteEnum::REGION_COVER_ID_UAE
                        ) ? TravelQuoteEnum::TRAVEL_UAE_OUTBOUND
                        : (
                            ($item->travel_destination_id_text == TravelQuoteEnum::LOCATION_UNITED_ARAB_EMIRATES_TEXT ||
                                $item->travel_region_cover_for_id == TravelQuoteEnum::REGION_COVER_ID_UAE
                            ) ? TravelQuoteEnum::TRAVEL_UAE_INBOUND
                            : 'N/A'
                        )
                    );
            } else {
                $item->travel_coverage = 'N/A';
                $item->traveling_where = 'N/A';
            }

            if ($item->quote_type_id != QuoteTypeId::Life) {
                $item->insurance_provider_name = 'N/A';
                $item->payment_frequency = 'N/A';
                $item->plan_name = 'N/A';
                $item->quote_created_at = 'N/A';
            } else {
                $item->quote_created_at = ! empty($item->quote_created_at) ? Carbon::parse($item->quote_created_at)->format('Y-m-d') : null;
            }
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
}
