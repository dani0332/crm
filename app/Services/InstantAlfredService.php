<?php

namespace App\Services;

use App\Enums\InstantChatReportsEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteSegmentEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Models\AlfredChat;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\TravelQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstantAlfredService extends BaseService
{
    private $carQuery;
    private $healthQuery;
    private $travelQuery;

    private function buildQueryByModel($modelType)
    {
        $aliases = [];
        if ($modelType == CarQuote::class) {
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
                    // 'qt.name as segment',
                    'ps.text AS payment_status_id_text',
                    'cpip.text as provider_name',
                    'cti.text as plan_type',
                    'cp.text as plan_name',
                    'cqr.price_with_vat as total_price',
                    // 'ps.created_at AS payment_created_at',
                    DB::raw('DATE_FORMAT(cqr.paid_at, "%d-%m-%Y %H:%i:%s") as paid_at'),
                    DB::raw('DATE_FORMAT(cqr.payment_paid_at, "%d-%m-%Y %H:%i:%s") as payment_paid_at'),
                    // 'ep.display_name',
                )
                ->leftJoin('payments as py', function ($join) {
                    $join->on('py.paymentable_id', '=', 'cqr.id')
                        ->where('py.paymentable_type', '=', CarQuote::class);
                })
                ->leftJoin('car_quote_request_detail as cqrd', 'cqrd.car_quote_request_id', '=', 'cqr.id')
                ->leftJoin('quote_tags as qt', function ($join) {
                    $join->on('qt.quote_uuid', '=', 'cqr.uuid')
                        // ->where(function ($query) {
                        //     $query->where('qt.name', QuoteSegmentEnum::SIC->tag())
                        //         ->orWhere('qt.name', QuoteSegmentEnum::SIC_REVIVAL->tag())
                        //         ->orWhere('qt.name', QuoteSegmentEnum::NON_SIC->tag());
                        // })
                        ->where('qt.quote_type_id', '=', QuoteTypeId::Car);
                })
                ->leftJoin('car_type_insurance as cti', 'cti.id', '=', 'cqr.car_type_insurance_id')
                ->leftJoin('lookups as lu', 'lu.id', '=', 'cqr.transaction_type_id')
                ->leftJoin('car_plan as cp', 'cp.id', '=', 'cqr.plan_id')
                ->leftJoin('insurance_provider as cpip', 'cpip.id', '=', 'cp.provider_id')
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
                ->addSelect([
                    DB::raw("
                        CASE 
                        WHEN qt.name = '" . QuoteSegmentEnum::SIC->tag() . "' THEN 'SIC'
                        WHEN qt.name = '" . QuoteSegmentEnum::SIC->tag() . "' 
                            AND cqr.source IN ('" . LeadSourceEnum::REVIVAL . "', '" . LeadSourceEnum::REVIVAL_REPLIED . "', '" . LeadSourceEnum::REVIVAL_PAID . "') 
                            THEN 'SIC REVIVAL'
                        WHEN qt.name != '" . QuoteSegmentEnum::SIC->tag() . "' THEN 'NON SIC'
                        ELSE 'N/A'
                        END as segment
                    ")
                ])
            // ->leftJoin('embedded_product_options as po', 'po.id', '=', 'e.product_id')
            // ->leftJoin('embedded_products as ep', 'ep.id', '=', 'po.embedded_product_id')
                ->groupBy('cqr.id');

            $aliases = [CarQuote::class => ['query' => $this->carQuery, 'alias' => 'cqr']];

        } elseif ($modelType == HealthQuote::class) {
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
                // 'qt.name as segment',
                'ps.text AS payment_status',
                'ihp.text as provider_name',
                'hpt.text as plan_type',
                'hp.text as plan_name',
                'py.total_price',
                // 'ps.created_at AS payment_created_at',
                DB::raw('DATE_FORMAT(hqr.paid_at, "%d-%m-%Y %H:%i:%s") as paid_at'),
                DB::raw('DATE_FORMAT(hqr.payment_paid_at, "%d-%m-%Y %H:%i:%s") as payment_paid_at'),
                // 'ep.display_name',

            )
                ->leftJoin('payments as py', function ($join) {
                    $join->on('py.paymentable_id', '=', 'hqr.id')
                        ->where('py.paymentable_type', '=', HealthQuote::class);
                })
                ->leftJoin('quote_tags as qt', function ($join) {
                    $join->on('qt.quote_uuid', '=', 'hqr.uuid')
                        // ->where(function ($query) {
                        //     $query->where('qt.name', QuoteSegmentEnum::SIC->tag())
                        //         ->orWhere('qt.name', QuoteSegmentEnum::SIC_REVIVAL->tag())
                        //         ->orWhere('qt.name', QuoteSegmentEnum::NON_SIC->tag());
                        // })
                        ->where('qt.quote_type_id', '=', QuoteTypeId::Health);
                })
                ->leftJoin('health_quote_request_detail as hqrd', 'hqrd.health_quote_request_id', '=', 'hqr.id')
                ->leftJoin('lookups as lu', 'lu.id', '=', 'hqr.transaction_type_id')
                ->leftJoin('quote_status as qs', 'qs.id', '=', 'hqr.quote_status_id')
                ->leftJoin('quote_batches as qb', 'qb.id', '=', 'hqr.quote_batch_id')
                ->leftJoin('health_plan as hp', 'hp.id', '=', 'hqr.plan_id')
                ->leftJoin('health_plan_type as hpt', 'hpt.id', '=', 'hp.plan_type_id')
                ->leftJoin('insurance_provider as ihp', 'ihp.id', '=', 'hp.provider_id')
                // ->leftJoin('insurance_provider as ins_provider', 'ins_provider.id', '=', 'hqr.currently_insured_with_id')
                ->leftJoin('payment_status as ps', 'ps.id', '=', 'hqr.payment_status_id')
                ->leftJoin('embedded_transactions as e', function ($join) {
                    $join->on('hqr.id', '=', 'e.quote_request_id')
                        ->where('e.quote_request_type', '=', HealthQuote::class);
                })
                ->addSelect([
                    DB::raw("
                        CASE 
                        WHEN qt.name = '" . QuoteSegmentEnum::SIC->tag() . "' THEN 'SIC'
                        WHEN qt.name = '" . QuoteSegmentEnum::SIC->tag() . "' 
                            AND hqr.source IN ('" . LeadSourceEnum::REVIVAL . "', '" . LeadSourceEnum::REVIVAL_REPLIED . "', '" . LeadSourceEnum::REVIVAL_PAID . "') 
                            THEN 'SIC REVIVAL'
                        WHEN qt.name != '" . QuoteSegmentEnum::SIC->tag() . "' THEN 'NON SIC'
                        ELSE 'N/A'
                        END as segment
                    ")
                ])
                // ->leftJoin('embedded_product_options as po', 'po.id', '=', 'e.product_id')
                // ->leftJoin('embedded_products as ep', 'ep.id', '=', 'po.embedded_product_id')
                ->groupBy('hqr.id');

            $aliases = [HealthQuote::class => ['query' => $this->healthQuery, 'alias' => 'hqr']];

        } elseif ($modelType == TravelQuote::class) {
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
                // 'qt.name as segment',
                'ps.text AS payment_status',
                'tqpd.provider_name',
                // missing plan_type
                'tqpd.plan_name',
                'py.total_price',
                DB::raw('DATE_FORMAT(tqr.paid_at, "%d-%m-%Y %H:%i:%s") as paid_at'),
                DB::raw('DATE_FORMAT(tqr.payment_paid_at, "%d-%m-%Y %H:%i:%s") as payment_paid_at'),
                // 'ps.created_at AS payment_created_at',
                // 'ep.display_name',

            )
                ->leftJoin('payments as py', function ($join) {
                    $join->on('py.paymentable_id', '=', 'tqr.id')
                        ->where('py.paymentable_type', '=', TravelQuote::class);
                })
                ->leftJoin('quote_tags as qt', function ($join) {
                    $join->on('qt.quote_uuid', '=', 'tqr.uuid')
                        // ->where(function ($query) {
                        //     $query->where('qt.name', QuoteSegmentEnum::SIC->tag())
                        //         ->orWhere('qt.name', QuoteSegmentEnum::SIC_REVIVAL->tag())
                        //         ->orWhere('qt.name', QuoteSegmentEnum::NON_SIC->tag());
                        // })
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
                ->addSelect([
                    DB::raw("
                        CASE 
                        WHEN qt.name = '" . QuoteSegmentEnum::SIC->tag() . "' THEN 'SIC'
                        WHEN qt.name = '" . QuoteSegmentEnum::SIC->tag() . "' 
                            AND tqr.source IN ('" . LeadSourceEnum::REVIVAL . "', '" . LeadSourceEnum::REVIVAL_REPLIED . "', '" . LeadSourceEnum::REVIVAL_PAID . "') 
                            THEN 'SIC REVIVAL'
                        WHEN qt.name != '" . QuoteSegmentEnum::SIC->tag() . "' THEN 'NON SIC'
                        ELSE 'N/A'
                        END as segment
                    ")
                ])
                // ->leftJoin('embedded_product_options as po', 'po.id', '=', 'e.product_id')
                // ->leftJoin('embedded_products as ep', 'ep.id', '=', 'po.embedded_product_id')
                ->groupBy('tqr.id');

            $aliases = [TravelQuote::class => ['query' => $this->travelQuery, 'alias' => 'tqr']];
        }

        return $aliases;
    }

    public function processSqlChatFilters(Request $request)
    {   

        $modelType = $request->quoteType ?? 'Car';
        $nameSpace = 'App\\Models\\';
        $modelType = (in_array(ucwords($modelType), newUi()) && checkPersonalQuotes(ucwords($modelType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($modelType).'Quote';

        $aliases = $this->buildQueryByModel($modelType);

        $modelData = $aliases[$modelType] ?? $aliases[CarQuote::class];

        $alias = $modelData['alias'];

        $partialQuery = $modelData['query'];

        $partialQuery->whereNotNull('chat_initiated_at');

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

        if (! empty($request->chat_initiated_at) && $request->email == null && $request->mobile_no == null && $quoteId == null) {
            $dateFrom = date('Y-m-d 00:00:00', strtotime($request->chat_initiated_at[0]));
            $dateTo = date('Y-m-d 23:59:59', strtotime($request->chat_initiated_at[1]));

            $partialQuery->whereBetween('chat_initiated_at', [$dateFrom, $dateTo]);
        }

        if ($request->email == null && $request->mobile_no == null && $quoteId == null && empty($request->chat_initiated_at)) {
            // Default to last 30 days if no dates are provided
            $dateFrom = now()->startOfDay();
            $dateTo = now()->endOfDay();

            $partialQuery->whereBetween('chat_initiated_at', [$dateFrom, $dateTo]);
        }

        if (isset($request->transaction_type_id) && $request->transaction_type_id != '') {
            $partialQuery->whereIn('transaction_type_id', $request->transaction_type_id);
        }

        if (isset($request->quote_batch_id) && ! empty($request->quote_batch_id)) {
            $partialQuery->whereIn('quote_batch_id', $request->quote_batch_id);
        }

        if (isset($request->quote_status_id) && is_array($request->quote_status_id) && count($request->quote_status_id) > 0) {
            $partialQuery->whereIn('quote_status_id', $request->quote_status_id);
        }

        if (isset($request->payment_status_id) && $request->payment_status_id != '') {
            $partialQuery->whereIn("{$alias}.payment_status_id", $request->payment_status_id);
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

        return $partialQuery;
    }

    public function generateChatConsolidateReport()
    {

        $request = request();

        $data = app(InstantAlfredService::class)->processSqlChatFilters($request)->get();

        $data->chunk(1000)->each(function ($sqlBatch) use ($request) {
            $uuids = $sqlBatch->pluck('uuid')->toArray();

            $mongoPipeline = $this->createPipeline($request, $uuids, $request->report);

            $mongoResults = AlfredChat::raw(fn ($collection) => $collection->aggregate($mongoPipeline))->toArray();

            $mongoResultsCollection = collect($mongoResults);

            foreach ($sqlBatch as $sqlRecord) {

                $relatedMongoRecord = $mongoResultsCollection->firstWhere('_id', $sqlRecord->uuid);
                if ($relatedMongoRecord) {
                    $sqlRecord->quote_type = $relatedMongoRecord['quote_type'];
                    $sqlRecord->communication_channels = $relatedMongoRecord['communication_channels'];
                    $sqlRecord->customer_interactions = $relatedMongoRecord['customer_interactions'];
                    $sqlRecord->ai_interactions = $relatedMongoRecord['ai_interactions'];
                    $sqlRecord->total_ai_interactions = $relatedMongoRecord['total_ai_interactions'];
                    $sqlRecord->fallbacks = $relatedMongoRecord['fallbacks'];
                    $sqlRecord->date_of_first_interaction = $relatedMongoRecord['date_of_first_interaction'];
                }
            }

        });

        return $data;
    }

    public function generateChatDetailedReport()
    {
        $request = request();

        $data = app(InstantAlfredService::class)->processSqlChatFilters($request)->get();

        $uuids = array_column($data->toArray(), 'uuid');

        $chunkSize = 1000;

        $uuidChunks = array_chunk($uuids, $chunkSize);

        $mongoResults = collect();

        foreach ($uuidChunks as $chunk) {

            $mongoPipeline = $this->createPipeline($request, $chunk, $request->report);

            $chunkResults = AlfredChat::raw(fn ($collection) => $collection->aggregate($mongoPipeline));

            $mongoResults = $mongoResults->merge(collect($chunkResults));
        }

        return $mongoResults;
    }

    public function createPipeline(Request $request, $itemIds, $type)
    {
        $pipeline[] = [
            '$match' => [
                'quote_id' => ['$in' => $itemIds],
            ],
        ];

        if ($type === 'chat') {
            $pipeline[] = [
                '$group' => [
                    '_id' => '$quote_id',
                    'created_at' => ['$first' => '$created_at'],
                    'communication_channels' => ['$addToSet' => [
                        '$cond' => [
                            ['$ifNull' => ['$channel', false]],
                            '$channel',
                            '$$REMOVE',
                        ],
                    ]],
                    'fallback' => ['$first' => '$fallback'],
                ],
            ];
        } elseif ($request->report == InstantChatReportsEnum::DETAILED_REPORT) {
            $pipeline[] = [
                '$project' => [
                    'created_at' => 1,
                    'role' => 1,
                    'msg' => 1,
                    'quote_id' => 1,
                    'quote_type' => 1,
                    'employee_flag' => '$who_chatted.is_employee',
                    'email' => '$who_chatted.email',
                    'user_system' => '$who_chatted.user_agent',
                    'user_ip_address' => '$who_chatted.ip',
                    'communication_channel' => '$channel',
                    'input_tokens_usage' => '$response.usage.prompt_tokens',
                    'completion_tokens' => '$response.usage.completion_tokens',
                    'total_tokens' => '$response.usage.total_tokens',
                ],
            ];
        } elseif ($request->report == InstantChatReportsEnum::CONSOLIDATED_REPORT) {
            $pipeline[] = [
                '$group' => [
                    '_id' => '$quote_id',
                    'quote_type' => ['$last' => '$quote_type'],
                    'date_of_first_interaction' => ['$min' => '$created_at'],
                    'communication_channels' => ['$addToSet' => [
                        '$cond' => [
                            ['$ifNull' => ['$channel', false]],
                            '$channel',
                            '$$REMOVE',
                        ],
                    ]],
                    'customer_interactions' => [
                        '$sum' => [
                            '$cond' => [
                                ['$eq' => ['$role', 'USER']],
                                1,
                                0,
                            ],
                        ],
                    ],
                    'ai_interactions' => [
                        '$sum' => [
                            '$cond' => [
                                ['$eq' => ['$role', 'AI']],
                                1,
                                0,
                            ],
                        ],
                    ],
                    'total_ai_interactions' => [
                        '$sum' => [
                            '$cond' => [
                                ['$in' => ['$role', ['AI', 'USER']]],
                                1,
                                0,
                            ],
                        ],
                    ],
                    'fallbacks' => [
                        '$sum' => ['$cond' => [['$ifNull' => ['$fallback', false]], 1, 0]],
                    ],
                ],
            ];
        }

        return $pipeline;
    }
}
