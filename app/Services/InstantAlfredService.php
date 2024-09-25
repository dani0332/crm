<?php

namespace App\Services;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\TravelQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstantAlfredService extends BaseService
{
    protected $carQuery;
    protected $healthQuery;
    protected $travelQuery;

    public function __construct()
    {
        $this->carQuery = DB::table('car_quote_request as cqr')
            ->select(
                'cqr.uuid',
                'cqr.id',
                'cqr.email',
                'cqr.code',
                'cqr.payment_status_id',
                'cqr.plan_id',
                'cp.text AS plan_id_text',
                'cp.provider_id AS car_plan_provider_id',
                'cpip.text AS car_plan_provider_id_text',
                'cqr.quote_status_id',
                'qs.text AS quote_status_id_text',
                'cqr.quote_batch_id',
                'cpip.code as plan_provider_code',
                'cqr.insurance_provider_id',
                'cqrd.chat_initiated_at',
                'qb.name as quote_batch_id_text',
                'lu.text as transaction_type_text',
                'qt.name as segment',
                'ps.text AS payment_status_id_text',
                'cqpd.provider_name',
                'cti.text as plan_type',
                'cqpd.plan_name',
                'cqpd.actual_premium as total_price',
                'ps.created_at AS payment_created_at',
                'cqr.paid_at',
                'cqr.payment_paid_at',
                'ep.display_name',
            )
            ->leftJoin('payments as py', function ($join) {
                $join->on('py.paymentable_id', '=', 'cqr.id')
                    ->where('py.paymentable_type', '=', CarQuote::class);
            })
            ->leftJoin('car_quote_request_detail as cqrd', 'cqrd.car_quote_request_id', '=', 'cqr.id')
            ->leftJoin('quote_tags as qt', function ($join) {
                $join->on('qt.quote_uuid', '=', 'cqr.uuid')
                    ->where(function ($query) {
                        $query->where('qt.name', QuoteSegmentEnum::SIC->tag())
                            ->orWhere('qt.name', QuoteSegmentEnum::SIC_REVIVAL->tag())
                            ->orWhere('qt.name', QuoteSegmentEnum::NON_SIC->tag());
                    })
                    ->where('qt.quote_type_id', '=', QuoteTypeId::Car);
            })
            ->leftJoin('car_type_insurance as cti', 'cti.id', '=', 'cqr.car_type_insurance_id')
            ->leftJoin('lookups as lu', 'lu.id', '=', 'cqr.transaction_type_id')
            ->leftJoin('car_plan as cp', 'cp.id', '=', 'cqr.plan_id')
            ->leftJoin('insurance_provider as cpip', 'cpip.id', '=', 'cp.provider_id')
            ->leftJoin('insurance_provider as cpdip', 'cpdip.id', '=', 'cqr.insurance_provider_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'cqr.payment_status_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'cqr.quote_status_id')
            ->leftJoin('quote_batches as qb', 'qb.id', '=', 'cqr.quote_batch_id')
            ->leftJoin('car_quote_plan_details as cqpd', function ($join) {
                $join->on('cqr.uuid', '=', 'cqpd.quote_uuid')
                    ->whereColumn('cqr.plan_id', '=', 'cqpd.plan_id');
            })
            ->leftJoin('embedded_transactions as e', function ($join) {
                $join->on('e.quote_request_id', '=', 'cqr.id')
                    ->where('e.quote_request_type', '=', CarQuote::class);
            })
            ->leftJoin('embedded_product_options as po', 'po.id', '=', 'e.product_id')
            ->leftJoin('embedded_products as ep', 'ep.id', '=', 'po.embedded_product_id')
            ->groupBy('cqr.id');

        $this->healthQuery = DB::table('health_quote_request as hqr')->select(
            'hqr.id',
            'hqr.uuid',
            'hqr.code',
            'hqr.payment_status_id',
            'hqr.email',
            'hqr.mobile_no',
            'hqr.quote_status_id',
            'hqr.plan_id',
            'hqr.quote_batch_id',
            'hqr.currently_insured_with_id',
            'hqr.health_plan_type_id',
            'hqrd.chat_initiated_at',
            'qs.text as quote_status_id_text',
            'hp.plan_type_id as plan_type_id',
            'qb.name as quote_batch_id_text',
            'lu.text as transaction_type_text',
            'qt.name as segment',
            'ps.text AS payment_status',
            'ihp.text as provider_name',
            'hpt.text as plan_type',
            'hp.text as plan_name',
            'py.total_price',
            // 'ps.created_at AS payment_created_at',
            DB::raw('DATE_FORMAT(hqr.paid_at, "%d-%m-%Y %H:%i:%s") as paid_at'),
            DB::raw('DATE_FORMAT(hqr.payment_paid_at, "%d-%m-%Y %H:%i:%s") as payment_paid_at'),
            'ep.display_name',

        )
            ->leftJoin('payments as py', function ($join) {
                $join->on('py.paymentable_id', '=', 'hqr.id')
                    ->where('py.paymentable_type', '=', HealthQuote::class);
            })
            ->leftJoin('quote_tags as qt', function ($join) {
                $join->on('qt.quote_uuid', '=', 'hqr.uuid')
                    ->where(function ($query) {
                        $query->where('qt.name', QuoteSegmentEnum::SIC->tag())
                            ->orWhere('qt.name', QuoteSegmentEnum::SIC_REVIVAL->tag())
                            ->orWhere('qt.name', QuoteSegmentEnum::NON_SIC->tag());
                    })
                    ->where('qt.quote_type_id', '=', QuoteTypeId::Health);
            })
            ->leftJoin('health_plan_type as hpt', 'hpt.id', '=', 'hqr.health_plan_type_id')
            ->leftJoin('health_quote_request_detail as hqrd', 'hqrd.health_quote_request_id', '=', 'hqr.id')
            ->leftJoin('lookups as lu', 'lu.id', '=', 'hqr.transaction_type_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
            ->leftJoin('quote_batches as qb', 'qb.id', '=', 'hqr.quote_batch_id')
            ->leftJoin('health_plan as hp', 'hp.id', '=', 'hqr.plan_id')
            ->leftJoin('insurance_provider as ihp', 'ihp.id', '=', 'hp.provider_id')
            // ->leftJoin('insurance_provider as ins_provider', 'ins_provider.id', '=', 'hqr.currently_insured_with_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'hqr.payment_status_id')
            ->leftJoin('embedded_transactions as e', function ($join) {
                $join->on('hqr.id', '=', 'e.quote_request_id')
                    ->where('e.quote_request_type', '=', HealthQuote::class);
            })
            ->leftJoin('embedded_product_options as po', 'po.id', '=', 'e.product_id')
            ->leftJoin('embedded_products as ep', 'ep.id', '=', 'po.embedded_product_id')
            ->groupBy('hqr.id');

        $this->travelQuery = TravelQuote::as('tqr')->select(
            'tqr.id',
            'tqr.uuid',
            'tqr.code',
            'tqr.email',
            'tqr.quote_batch_id',
            'tqrd.chat_initiated_at',
            'qs.id as quote_status_id',
            'qs.text as quote_status_id_text',
            'tqr.payment_status_id',
            'tqr.plan_id',
            'tp.text AS plan_id_text',
            'tpip.text AS travel_plan_provider_text',
            'qb.name as quote_batch_id_text',
            'lu.text as transaction_type_text',
            'qt.name as segment',
            'ps.text AS payment_status',
            'tqpd.provider_name',
            // missing plan_type
            'tqpd.plan_name',
            'py.total_price',
            'tqr.paid_at',
            'tqr.payment_paid_at',
            // 'ps.created_at AS payment_created_at',
            'ep.display_name',

        )
            ->leftJoin('payments as py', function ($join) {
                $join->on('py.paymentable_id', '=', 'tqr.id')
                    ->where('py.paymentable_type', '=', TravelQuote::class);
            })
            ->leftJoin('quote_tags as qt', function ($join) {
                $join->on('qt.quote_uuid', '=', 'tqr.uuid')
                    ->where(function ($query) {
                        $query->where('qt.name', QuoteSegmentEnum::SIC->tag())
                            ->orWhere('qt.name', QuoteSegmentEnum::SIC_REVIVAL->tag())
                            ->orWhere('qt.name', QuoteSegmentEnum::NON_SIC->tag());
                    })
                    ->where('qt.quote_type_id', '=', QuoteTypeId::Travel);
            })
            ->leftJoin('travel_quote_request_detail as tqrd', 'tqr.id', '=', 'tqrd.travel_quote_request_id')
            ->leftJoin('lookups as lu', 'lu.id', '=', 'tqr.transaction_type_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'tqr.quote_status_id')
            ->leftJoin('quote_batches as qb', 'qb.id', '=', 'tqr.quote_batch_id')
            ->leftJoin('travel_plan as tp', 'tp.id', '=', 'tqr.plan_id')
            ->leftJoin('insurance_provider as tpip', 'tpip.id', '=', 'tp.provider_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'tqr.payment_status_id')
            ->leftJoin('embedded_transactions as e', function ($join) {
                $join->on('tqr.id', '=', 'e.quote_request_id')
                    ->where('e.quote_request_type', '=', TravelQuote::class);
            })
            ->leftJoin('travel_quote_plan_details as tqpd', function ($join) {
                $join->on('tqr.uuid', '=', 'tqpd.quote_uuid')
                    ->whereColumn('tqr.plan_id', '=', 'tqpd.plan_id');
            })
            ->leftJoin('embedded_product_options as po', 'po.id', '=', 'e.product_id')
            ->leftJoin('embedded_products as ep', 'ep.id', '=', 'po.embedded_product_id')
            ->groupBy('tqr.id');
    }
    
    public function processSqlChatFilters(Request $request, $modelType)
    {
        $modelType = $request->quoteType ?? 'Car';
        $nameSpace = 'App\\Models\\';
        $modelType = (in_array(ucwords($modelType), newUi()) &&
        checkPersonalQuotes(ucwords($modelType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($modelType).'Quote';

        $aliases = [
            CarQuote::class => ['query' => $this->carQuery, 'alias' => 'cqr'],
            HealthQuote::class => ['query' => $this->healthQuery, 'alias' => 'hqr'],
            TravelQuote::class => ['query' => $this->travelQuery, 'alias' => 'tqr'],
        ];

        $modelData = $aliases[$modelType] ?? $aliases[CarQuote::class];
        $alias = $modelData['alias'];

        $partialQuery = $modelData['query'];

        $quoteId = null;
        if ($request->has('quoteId') && $request->quoteId != null) {
            if (strpos($request->quoteId, '-') !== false) {
                $quote = explode('-', $request->quoteId);
                $quoteId = $quote[1];
            } else {
                $quoteId = $request->quoteId;
            }
        }

        if (isset($quoteId) && $quoteId != '') {
            $partialQuery->where("{$alias}.uuid", $quoteId);
        }

        if (isset($request->email) && $request->email != '') {
            $partialQuery->where('email', $request->email);
        }

        if (isset($request->mobile_no) && $request->mobile_no != '') {
            $partialQuery->where('mobile_no', $request->mobile_no);
        }

        if (! empty($request->start_date) && ! empty($request->end_date)) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request['start_date']));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request['end_date']));

            $partialQuery->whereBetween('chat_initiated_at', [$dateFrom, $dateTo]);
        }

        if ($request->email == null && $request->mobile_no == null && $quoteId == null && empty($request->start_date) && empty($request->end_date)) {
            // Default to last 30 days if no dates are provided
            $dateFrom = now()->subDays(30)->startOfDay();
            $dateTo = now()->endOfDay();

            $partialQuery->whereBetween('chat_initiated_at', [$dateFrom, $dateTo]);
        }

        if (isset($request->transaction_type_id) && $request->transaction_type_id != '') {
            $partialQuery->where('transaction_type_id', $request->transaction_type_id);
        }

        if (isset($request->quote_batch_id) && ! empty($request->quote_batch_id)) {
            $partialQuery->whereIn('quote_batch_id', $request->quote_batch_id);
        }

        if (isset($request->quote_status_id) && is_array($request->quote_status_id) && count($request->quote_status_id) > 0) {
            $partialQuery->whereIn('quote_status_id', $request->quote_status_id);
        }

        if (isset($request->payment_status_id) && $request->payment_status_id != '') {
            $partialQuery->where("{$alias}.payment_status_id", $request->payment_status_id);
        }

        if (in_array($modelType, [HealthQuote::class, CarQuote::class]) && isset($request->assigment_type) && $request->assigment_type != '') {
            $partialQuery->where('assignment_type', $request->assigment_type);
        }

        if (isset($request->sale_leads) && $request->sale_leads != '') {
            if ($request->sale_leads == quoteTypeCode::yesText) {
                $partialQuery->whereIn('quote_status_id', [QuoteStatusEnum::TransactionApproved, QuoteStatusEnum::PolicyIssued, QuoteStatusEnum::PolicySentToCustomer, QuoteStatusEnum::PolicyBooked]);
            }
            if ($request->sale_leads == quoteTypeCode::noText) {
                $partialQuery->whereNotNull('quote_status_id');
            }
        }

        if (isset($request->segment_filter) && $request->segment_filter != '') {
            $query = $modelType == HealthQuote::class ? 'hqr' : ($modelType == CarQuote::class ? 'cqr' : 'tqr');
            $quoteTypeId = $modelType == HealthQuote::class ? QuoteTypeId::Health : ($modelType == CarQuote::class ? QuoteTypeId::Car : QuoteTypeId::Travel);

            $modelType::applySegmentFilter($partialQuery, $request->segment_filter, $query, $quoteTypeId);
        }

        return $partialQuery->get();
    }

}