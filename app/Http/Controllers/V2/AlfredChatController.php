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
    protected $query;

    public function __construct()
    {
        $this->middleware('permission:'.PermissionsEnum::INSTANT_ALFRED_CHAT_LOGS, ['only' => ['logs']]);
        $this->query = DB::table('car_quote_request as cqr')
            ->select(
                'cqr.uuid',
                'cqr.id',
                'cqr.email',
                'cqr.code',
                'cqr.payment_status_id',
                'ps.text AS payment_status_id_text',
                'ps.created_at AS payment_status_id_created_at',
                'cqr.plan_id',
                'cp.text AS plan_id_text',
                'cp.provider_id AS car_plan_provider_id',
                'cpip.text AS car_plan_provider_id_text',
                'cqr.quote_status_id',
                'qs.text AS quote_status_id_text',
                'cqr.quote_batch_id',
                'lu.text as transaction_type_text',
                'qb.name as quote_batch_id_text',
                'cpip.code as plan_provider_code',
                'cpip.code as plan_provider_code',
                'cqr.insurance_provider_id',
                'cqrd.chat_initiated_at'
            )
            ->leftJoin('payments as py', function ($join) {
                $join->on('py.paymentable_id', '=', 'cqr.id')
                    ->where('py.paymentable_type', '=', CarQuote::class);
            })
            ->leftJoin('car_quote_request_detail as cqrd', 'cqrd.car_quote_request_id', '=', 'cqr.id')
            ->leftJoin('lookups as lu', 'lu.id', '=', 'cqr.transaction_type_id')
            ->leftJoin('car_plan as cp', 'cp.id', '=', 'cqr.plan_id')
            ->leftJoin('insurance_provider as cpip', 'cpip.id', '=', 'cp.provider_id')
            ->leftJoin('insurance_provider as cpdip', 'cpdip.id', '=', 'cqr.insurance_provider_id')
            ->leftJoin('payment_status as ps', 'ps.id', '=', 'cqr.payment_status_id')
            ->leftJoin('quote_status as qs', 'qs.id', '=', 'cqr.quote_status_id')
            ->leftJoin('quote_batches as qb', 'qb.id', '=', 'cqr.quote_batch_id');
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

    public function logs(Request $request, $exportChat = false)
    {
        $modelType = $request->quoteType ?? 'Car';
        $nameSpace = 'App\\Models\\';
        $modelType = (in_array(ucwords($modelType), newUi()) && checkPersonalQuotes(ucwords($modelType))) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($modelType).'Quote';

        $data = [];
        if ($modelType == CarQuote::class) {
            $data = $this->query->where(function ($query) use ($request, $modelType) {
                $this->processChatFilters($request, $query, $modelType);
            })->get();
        } elseif ($modelType == HealthQuote::class) {
            $data = HealthQuote::with(['healthQuoteRequestDetail' => function ($query) {
                $query->select('id', 'health_quote_request_id', 'chat_initiated_at'); // specify keys from healthQuoteRequestDetail
            }])
                ->select('id', 'uuid', 'code') // specify keys from HealthQuote
                ->whereHas('healthQuoteRequestDetail', function ($query) {
                    $query->whereNotNull('chat_initiated_at');
                });

        } elseif ($modelType == TravelQuote::class) {
            $data = TravelQuote::with(['travelQuoteRequestDetail' => function ($query) {
                $query->select('id', 'travel_quote_request_id', 'chat_initiated_at'); // specify keys from travelQuoteRequestDetail
            }])
                ->select('id', 'uuid', 'code') // specify keys from TravelQuote
                ->whereHas('travelQuoteRequestDetail', function ($query) {
                    $query->whereNotNull('chat_initiated_at');
                });
        }

        // if (isset($request->channel) && $request->channel != '') {

        // }

        if (isset($request->fallback) && $request->fallback != '') {
            $data = $this->processMongoDBChatFilters($request, $data);
        }

        // Set up pagination parameters
        $perPage = $request->input('per_page', 15);  // Default to 15 items per page
        $currentPage = $request->input('page', 1);   // Current page from the request
        $total = count($data);                       // Total items in the dataset
        $lastPage = ceil($total / $perPage);

        // Slice the data array based on current page and per page count
        $paginatedData = array_slice($data->toArray(), ($currentPage - 1) * $perPage, $perPage);

        $path = $request->url();  // Get the current URL

        $nextPageUrl = $currentPage < $lastPage
            ? $path.'?page='.($currentPage + 1).'&per_page='.$perPage
            : null;

        $prevPageUrl = $currentPage > 1
            ? $path.'?page='.($currentPage - 1).'&per_page='.$perPage
            : null;

        // Prepare pagination meta information
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

        if ($exportChat) {
            return $data;
        } else {
            return inertia('AlfredChat/Index', ['logs' => $paginatedData, 'pagination' => $pagination,   'leadStatuses' => QuoteStatus::all(), 'batches' => QuoteBatches::all()]);
        }
    }

    public function processMongoDBChatFilters(Request $request, $data)
    {
        foreach ($data as $item) {
            $chatPipeline = $this->createPipeline($request, $item, 'chat');
            $mongoResult = AlfredChat::raw(fn ($collection) => $collection->aggregate($chatPipeline))->toArray();

            if (empty($mongoResult)) {
                $item->chat = [];
            } else {
                $item->chat = $mongoResult;
            }
        }

        $fallbackFilter = $request->fallback;

        $filteredData = collect($data)->map(function ($item) use ($fallbackFilter) {
            // Filter the 'chat' array based on fallback value
            $item->chat = collect($item->chat)->filter(function ($chat) use ($fallbackFilter) {
                if ($fallbackFilter === quoteTypeCode::yesText) {
                    return ! is_null($chat['fallback']);  // Keep entries with a non-null fallback
                } elseif ($fallbackFilter === quoteTypeCode::noText) {
                    return is_null($chat['fallback']);   // Keep entries with a null fallback
                }

                return true;  // If no valid filter, return all chats
            })->values()->toArray(); // Re-index the array

            return $item;
        })->reject(function ($item) {
            // Optionally, remove the entire item if there are no valid chats left
            return empty($item->chat);
        })->values(); // Re-index the collection

        return $filteredData;
    }

    public function processChatFilters(Request $request, $partialQuery, $modelType)
    {

        if (isset($request->quoteId) && $request->quoteId != '') {
            $partialQuery->where('cqr.uuid', $request->quoteId);
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

        return $partialQuery;
    }

    public function exportChat(Request $request)
    {
        $result = $this->processChatFilters($request, $this->query, CarQuote::class);
        $data = $result->get();

        $itemIds = array_column($data->toArray(), 'uuid');

        $chatPipeline = $this->createPipeline($request, $itemIds, $request->report);
        $mongoResults = AlfredChat::raw(fn ($collection) => $collection->aggregate($chatPipeline))->toArray();

        $fileName = 'alfred_chat_logs_'.Carbon::now()->format('Y-m-d_H-i-s');

       
        if ($request->report == InstantChatReportsEnum::CONSOLIDATED_REPORT) {
            $mergedData = array_merge((array) $data->first(), (array)$mongoResults[0]);
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
                'quote_id' => ['$in' => $itemIds], // assuming 'quote_id' corresponds to the item's identifier
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
                    '_id' => ['quote_id' => '$quote_id', ['$dateToString' => ['timezone' => '+04:00', 'format' => '%Y-%m-%d', 'date' => ['$toDate' => '$created_at']]]],
                    'created_at' => ['$first' => '$created_at'],
                    'role' => ['$first' => '$role'],
                    'msg' => ['$first' => '$msg'],
                    'quote_id' => ['$first' => '$quote_id'],
                    'quote_type' => ['$first' => '$quote_type'],
                    'email' => ['$first' => '$who_chatted.email'],
                    'communication_channel' => ['$first' => '$channel'],
                    'fallback' => ['$first' => '$fallback'],
                ],
            ];
        } elseif ($type === 'total') {
            $pipeline[] = [
                '$group' => [
                    '_id' => ['quote_id' => '$quote_id', ['$dateToString' => ['timezone' => '+04:00', 'format' => '%Y-%m-%d', 'date' => ['$toDate' => '$created_at']]]],
                    'quote_type' => ['$first' => '$quote_type'],
                    'quote_id' => ['$first' => '$quote_id'],
                ],
            ];
            $pipeline[] = ['$count' => 'total'];
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
                            '$$REMOVE'  
                        ]
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
                    'fallbacks' => ['$sum' => ['$cond' => [['$ifNull' => ['$fallback', false]], 1,0
        ]
     ]
    ],
                ],
            ];
        }

        return $pipeline;
    }

}
