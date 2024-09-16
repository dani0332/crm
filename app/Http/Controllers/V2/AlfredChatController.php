<?php

namespace App\Http\Controllers\V2;

use App\Enums\InstantChatReportsEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Exports\InstantChatConsolidatedExport;
use App\Exports\InstantChatDetailedExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlfredChatRequest;
use App\Models\AlfredChat;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\QuoteBatches;
use App\Models\QuoteStatus;
use App\Models\TravelQuote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AlfredChatController extends Controller
{
    protected $carQuery;
    protected $healthQuery;
    protected $travelQuery;

    public function __construct()
    {
        $this->middleware('permission:'.PermissionsEnum::INSTANT_ALFRED_CHAT_LOGS, ['only' => ['logs']]);
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
                    ->where('qt.quote_type_id', '=', CarQuote::class);
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
                $join->on('qt.quote_uuid', '=', 'cqr.uuid')
                    ->where('qt.quote_type_id', '=', HealthQuote::class);
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
                $join->on('qt.quote_uuid', '=', 'cqr.uuid')
                    ->where('qt.quote_type_id', '=', TravelQuote::class);
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(AlfredChatRequest $request)
    {
        $chat = AlfredChat::raw(function ($collection) use ($request) {
            return $collection->aggregate([
                [
                    '$match' => [ // $match is a group operator to filter the records just like where clause in SQL
                        'quote_id' => $request->quoteId,
                        'quote_type' => $request->quoteType,
                    ],
                ],
                [
                    '$group' => [
                        '_id' => [ // _id is a group operator to group the records
                            '$dateToString' => [ // $dateToString is an aggregation operator to convert date to string
                                'timezone' => '+04:00',
                                'format' => '%Y-%m-%d', // format of the date
                                'date' => ['$toDate' => '$created_at'], // $toDate is an aggregation operator to convert string to date
                            ],
                        ],
                        'role' => ['$first' => '$role'], //$first is used to add role field of the first occurrence of the group
                        'msg' => ['$first' => '$msg'], //$first is used to add msg field  of the first occurrence of the group
                        'count' => ['$sum' => 1], // $sum is used to count the number of records in the group
                    ],
                ],
                [
                    '$sort' => ['_id' => -1], // Sort by _id (date) in descending order
                ],
            ]);
        });

        if ($chat->isEmpty()) {
            return response()->json(['message' => 'No chat available']);
        }

        return response()->json(['data' => $chat]);
    }

    public function getChatByDate(AlfredChatRequest $request)
    {
        // $dateFrom = Carbon::createFromFormat('Y-m-d', $request->created_at)->startOfDay()->toIso8601String();
        // $dateTo = Carbon::createFromFormat('Y-m-d', $request->created_at)->endOfDay()->toIso8601String();

        $chat = AlfredChat::where('quote_id', $request->quoteId)
            ->where('quote_type', $request->quoteType)
            ->get();

        if ($chat->isEmpty()) {
            return response()->json(['message' => 'No chat available']);
        }

        return response()->json(['data' => $chat]);
    }

    public function logs(Request $request)
    {
        $modelType = $request->quoteType ?? 'Car';
        $nameSpace = 'App\\Models\\';
        $modelType = (in_array(ucwords($modelType), newUi()) && checkPersonalQuotes(ucwords($modelType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($modelType).'Quote';

        $data = $this->processSqlChatFilters($request, $modelType);

        $result = $this->processMongoDBChatFilters($request, $data);

        $perPage = $request->input('per_page', 15);
        $currentPage = $request->input('page', 1);
        $total = count($data);
        $lastPage = ceil($total / $perPage);

        $paginatedData = array_slice($result === false ? $data->toArray() : $data, ($currentPage - 1) * $perPage, $perPage);

        $path = $request->url();

        $nextPageUrl = $currentPage < $lastPage
            ? $path.'?page='.($currentPage + 1).'&per_page='.$perPage
            : null;

        $prevPageUrl = $currentPage > 1
            ? $path.'?page='.($currentPage - 1).'&per_page='.$perPage
            : null;

        $pagination = [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'from' => ($currentPage - 1) * $perPage + 1,
            'to' => min($currentPage * $perPage, $total),
            'next_page_url' => $nextPageUrl,
            'prev_page_url' => $prevPageUrl,
        ];

        return inertia('AlfredChat/Index', ['logs' => $paginatedData, 'pagination' => $pagination,   'leadStatuses' => QuoteStatus::all(), 'batches' => QuoteBatches::all()]);

    }

    public function processMongoDBChatFilters(Request $request, $data)
    {

        if (isset($request->fallback) && $request->fallback != '' || isset($request->channel) && $request->channel != '') {

            $dataArray = json_decode(json_encode($data), true);

            $itemIds = array_column($dataArray, 'uuid');

            $chatPipeline = $this->createPipeline($request, $itemIds, 'chat');

            $mongoResults = AlfredChat::raw(fn ($collection) => $collection->aggregate($chatPipeline))->toArray();

            $refactoredData = array_map(function ($entry) {
                if (isset($entry['communication_channels']) && $entry['communication_channels'] instanceof \MongoDB\Model\BSONArray) {
                    $entry['communication_channels'] = $entry['communication_channels']->getArrayCopy();
                }

                return $entry;
            }, $mongoResults);

            $dataById = [];
            foreach ($dataArray as $item) {
                $dataById[$item['uuid']] = $item;
            }

            $refactoredById = [];
            foreach ($refactoredData as $entry) {
                $refactoredById[$entry['_id']] = $entry;
            }

            $mergedData = array_map(function ($item) use ($refactoredById) {
                $uuid = $item['uuid'];
                if (isset($refactoredById[$uuid])) {
                    return array_merge($item, $refactoredById[$uuid]);
                }

                return $item;
            }, $dataById);

            $mergedData = array_values($mergedData);

            $fallbackFilter = $request->fallback;
            $channelFilter = $request->channel;
            $filteredData = [];

            $filteredData = array_filter($mergedData, function ($item) use ($fallbackFilter, $channelFilter) {
                if ($fallbackFilter) {
                    $hasFallback = isset($item['fallback']) ? $item['fallback'] : null;
                    if ($fallbackFilter === quoteTypeCode::yesText && $hasFallback) {
                        return $item;
                    } elseif ($fallbackFilter === quoteTypeCode::noText && $hasFallback === null) {
                        return $item;
                    }
                }
                if ($channelFilter) {
                    if (in_array($channelFilter, ['whatsapp', 'website', 'e-commerce'])) {
                        return $item;
                    }
                }

            });

            return $filteredData;
        } else {
            return false;
        }
    }

    public function processSqlChatFilters(Request $request, $modelType)
    {
        $modelType = $request->quoteType ?? 'Car';
        $nameSpace = 'App\\Models\\';
        $modelType = (in_array(ucwords($modelType), newUi()) &&
        checkPersonalQuotes(ucwords($modelType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($modelType).'Quote';

        $partialQuery = null;
        $quoteId = null;
        if ($request->has('quoteId') && $request->quoteId != null) {
            if (strpos($request->quoteId, '-') !== false) {
                $quote = explode('-', $request->quoteId);
                $quoteId = $quote[1];
            } else {
                $quoteId = $request->quoteId;
            }
        }

        if ($modelType == CarQuote::class) {
            $partialQuery = $this->carQuery->when(isset($quoteId) && $quoteId != '', function ($query) use ($quoteId) {
                $query->where('cqr.uuid', $quoteId);
            });
        } elseif ($modelType == HealthQuote::class) {
            $partialQuery = $this->healthQuery->when(isset($quoteId) && $quoteId != '', function ($query) use ($quoteId)  {
                $query->where('hqr.uuid', $quoteId);
            });
        } elseif ($modelType == TravelQuote::class) {
            $partialQuery = $this->travelQuery->when(isset($quoteId) && $quoteId != '', function ($query) use ($quoteId)  {
                $query->where('tqr.uuid', $quoteId);
            });
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
        } else {
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
            $partialQuery->where('payment_status_id', $request->payment_status_id);
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

    public function exportChat(Request $request)
    {
        $data = $this->processSqlChatFilters($request, CarQuote::class);

        $itemIds = array_column($data->toArray(), 'uuid');

        $chatPipeline = $this->createPipeline($request, $itemIds, $request->report);
        $mongoResults = AlfredChat::raw(fn ($collection) => $collection->aggregate($chatPipeline))->toArray();

        $fileName = 'alfred_chat_logs_'.Carbon::now()->format('Y-m-d_H-i-s');

        if ($request->report == InstantChatReportsEnum::CONSOLIDATED_REPORT) {
            $mergedData = array_merge((array) $data->first(), (array) $mongoResults[0]);

            return Excel::download(new InstantChatConsolidatedExport($mergedData), $fileName.'.xlsx');
        }

        if ($request->report == InstantChatReportsEnum::DETAILED_REPORT) {
            return Excel::download(new InstantChatDetailedExport($mongoResults), $fileName.'.xlsx');
        }
    }

    public function createPipeline(Request $request, $itemIds, $type)
    {
        $quoteId = $item->uuid ?? null;
        $quoteType = $request->quoteType ?? 'CAR';
        $pipeline = [];

        if ($request->has('quoteId') && $request->quoteId != null) {
            if (strpos($request->quoteId, '-') !== false) {
                $quote = explode('-', $request->quoteId);
                $quoteId = $quote[1];
            } else {
                $quoteId = $request->quoteId;
            }
        }

        if ($request->get('quoteType')) {
            $quoteType = strtoupper($request->quoteType);
        }

        $pipeline[] = [
            '$match' => [
                'quote_id' => ['$in' => $itemIds],
            ],
        ];

        if (isset($quoteType) && $quoteType != null) {
            $pipeline[] = ['$match' => ['quote_type' => $quoteType]];
        }

        if (isset($quoteId) && $quoteId != null) {
            $pipeline[] = ['$match' => ['quote_id' => $quoteId]];
        }

        if ($request->has('start_date') && $request->start_date != null && $request->has('end_date') && $request->end_date != null) {
            $start_date = Carbon::createFromFormat('Y-m-d', Carbon::parse($request->start_date)->format('Y-m-d'))->startOfDay()->toIso8601String();
            $end_date = Carbon::createFromFormat('Y-m-d', Carbon::parse($request->end_date)->format('Y-m-d'))->endOfDay()->toIso8601String();
            $pipeline[] = ['$match' => ['created_at' => ['$gte' => $start_date, '$lte' => $end_date]]];
        }

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
                    'fallbacks' => ['$sum' => ['$cond' => [['$ifNull' => ['$fallback', false]], 1, 0,
                    ],
                    ],
                    ],
                ],
            ];
        }

        return $pipeline;
    }

}
