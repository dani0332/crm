<?php

namespace App\Services\Reports;

use App\Enums\LeadSourceEnum;
use App\Enums\ManagementReportCategoriesEnum;
use App\Enums\ManagementReportTypeEnum;
use App\Enums\QuoteTypeId;
use App\Enums\TravelQuoteEnum;
use App\Models\Customer;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Strategies\ManagementReport;
use App\Traits\TeamHierarchyTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionReportService extends ManagementReport
{
    use TeamHierarchyTrait;

    private $reportDateRange;

    public function getReportQueryBuilder(Request $request)
    {
        $request['reportCategory'] = $request->reportCategory ?? ManagementReportCategoriesEnum::TRANSACTION;
        $request['reportType'] = $request->reportType ?? ManagementReportTypeEnum::APPROVED_TRANSACTIONS;

        if ($request['policyBookDate'] && ! empty($request['policyBookDate']) && is_array($request['policyBookDate'])) {
            $this->reportDateRange = (isset($request['policyBookDate'][0]) && isValidDate($request['policyBookDate'][0]) ? Carbon::parse($request['policyBookDate'][0])->toDateString() : today()->toDateString())
                .' - '.
                (isset($request['policyBookDate'][1]) && isValidDate($request['policyBookDate'][1]) ? Carbon::parse($request['policyBookDate'][1])->toDateString() : today()->toDateString());
        } elseif ($request['paymentDueDate'] && ! empty($request['paymentDueDate']) && is_array($request['paymentDueDate'])) {
            $this->reportDateRange = (isset($request['paymentDueDate'][0]) && isValidDate($request['paymentDueDate'][0]) ? Carbon::parse($request['paymentDueDate'][0])->toDateString() : today()->toDateString())
                .' - '.
                (isset($request['paymentDueDate'][1]) && isValidDate($request['paymentDueDate'][1]) ? Carbon::parse($request['paymentDueDate'][1])->toDateString() : today()->toDateString());
        }

        $query = PersonalQuote::query()
            ->select(
                'personal_quotes.quote_type_id',
                'personal_quotes.business_type_of_insurance_id',
                'personal_quotes.uuid',
                'personal_quotes.policy_number',
                'personal_quotes.code',
                'p.notes',
                'p.reference',
                'personal_quotes.policy_start_date',
                'p.payment_due_date as payment_due_date',
                'ps.due_date',
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
                'p.captured_amount as collected_amount',
                DB::raw('(SELECT pss.verified_at
                FROM payment_splits pss
                WHERE pss.code = p.code
                and pss.sr_no = 1
                LIMIT 1) as payment_date'),
                DB::raw('((
                    IFNULL( personal_quotes.price_vat_applicable , 0 ) +
                    IFNULL( personal_quotes.price_vat_not_applicable , 0 ) +
                    IFNULL( personal_quotes.vat , 0 )) - IFNULL( p.discount_value , 0 )) -
                    IFNULL( p.captured_amount, 0) as pending_balance'),
                'p.collection_type as collects',
                'ip.text as insurer',
                'quote_type.text as line_of_business',
                'personal_quotes.first_name',
                'personal_quotes.last_name',
                'u.name as advisor',
                'support_user.name as support_user',
                'dp.name as department',
                'pi.name as policy_issuer',
                'p.invoice_description as invoice_description',
                'pm.name as payment_method',
                'pg.text as payment_gateway',
                'p.insurer_tax_number as insurer_invoice_number',
                'insurer_invoice_date as insurer_tax_invoice_date',
                'p.broker_invoice_number',
                'btoi.text as sub_type_line_of_business',
                'ls.text as sub_source',
                'sso.text as sub_source_option',
                'p.insurer_commmission_invoice_number',
                'l.text as transaction_type',
                'qs.text as quote_status',
                'p.commmission_percentage',
                'personal_quotes.source',
                'personal_quotes.policy_booking_date',
                'ps.sage_reciept_id',
                DB::raw(Customer::formattedPcpTagCase().' as pcp_tag_formatted'),
                'ciw.text as currently_insured_with_text',
                'cqr.currently_insured_with as currently_insured_with',
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
            );
        $this->paymentJoin($query, request: $request);
        $query->join('payment_splits as ps', 'p.code', '=', 'ps.code')
            ->join('quote_type', 'quote_type.id', '=', 'quote_type_id')
            ->leftJoin('users as u', 'u.id', '=', 'advisor_id')
            ->leftJoin('users as support_user', 'personal_quotes.support_user_id', '=', 'support_user.id')
            ->leftJoin('users as pi', 'pi.id', '=', 'p.policy_issuer_id')
            ->leftJoin('departments as dp', 'dp.id', '=', 'u.department_id')
            ->leftJoin('personal_quote_details as pqd', 'personal_quotes.id', '=', 'pqd.personal_quote_id')
            ->leftJoin('insurance_provider as ip', 'ip.id', '=', 'p.insurance_provider_id')
            ->leftJoin('insurance_provider_plans as ipp', 'ipp.id', '=', 'p.plan_id')
            ->leftJoin('payment_methods as pm', 'pm.code', '=', 'p.payment_methods_code')
            ->leftJoin('payment_gateway as pg', 'pg.id', '=', 'p.payment_gateway_id')
            ->leftJoin('business_type_of_insurance as btoi', 'btoi.id', '=', 'personal_quotes.business_type_of_insurance_id')
            ->leftJoin('lookups as l', 'personal_quotes.transaction_type_id', '=', 'l.id')
            ->leftJoin('lookups as ls', 'personal_quotes.sub_source_id', '=', 'ls.id')
            ->leftJoin('lookups as sso', 'personal_quotes.sub_source_options_id', '=', 'sso.id')
            ->leftJoin('customer as c', 'c.id', '=', 'personal_quotes.customer_id')
            ->join('quote_status as qs', 'qs.id', '=', 'personal_quotes.quote_status_id')
            ->leftJoin('insurance_provider as ciw', 'personal_quotes.currently_insured_with_id', '=', 'ciw.id')
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
            ->leftJoin('nationality as n', 'n.id', '=', 'tqr.destination_id');

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

        LoggerService::sql(self::class.' - Transaction Report Query', $query);

        if ($request->export == 1) {
            $data = $query->get();
            $this->formatData($data);

            return $data;
        } else {
            $data = $query->simplePaginate(100)->withQueryString();
            $data->map(function ($item) {
                $item->routeName = $this->getQuoteRouteName($item->quote_type_id, $item->business_type_of_insurance_id);
            });
            $this->formatData($data);

            return $data;
        }
    }

    public function formatData(&$data)
    {
        $data->map(function ($item) {
            $item->transactions = $this->concatValues([$item->insurer_invoice_number, $item->notes, $item->reference], '-');
            $item->policy_start_date = ! empty($item->policy_start_date) ? Carbon::parse($item->policy_start_date)->format('Y-m-d') : null;
            $item->payment_due_date = ! empty($item->payment_due_date) ? Carbon::parse($item->payment_due_date)->format('Y-m-d') : null;
            $item->due_date = ! empty($item->due_date) ? Carbon::parse($item->due_date)->format('Y-m-d') : null;
            $item->total_price = number_format($item->total_price, 2);
            $item->collects = strtoupper($item->collects);
            $item->pending_balance = number_format($item->pending_balance, 2);
            $item->customer_name = $this->concatValues([$item->first_name, $item->last_name], ' ');
            $item->commmission_percentage = number_format(strToFloat($item->commmission_percentage), 2);
            $item->policy_booking_date = ! empty($item->policy_booking_date) ? Carbon::parse($item->policy_booking_date)->format('Y-m-d') : null;
            $item->insurer_tax_invoice_date = ! empty($item->insurer_tax_invoice_date) ? Carbon::parse($item->insurer_tax_invoice_date)->format(config('constants.DATE_DISPLAY_SLASH_FORMAT')) : null;
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
            'paymentDueDate' => $defaultDate,
            'reportCategory' => ManagementReportCategoriesEnum::TRANSACTION,
            'reportType' => ManagementReportTypeEnum::APPROVED_TRANSACTIONS,
        ];
    }
}
