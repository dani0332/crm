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
use App\Services\InstantAlfredService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AlfredChatController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:'.PermissionsEnum::INSTANT_ALFRED_CHAT_LOGS, ['only' => ['logs']]);
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
        $chat = AlfredChat::where('quote_id', $request->quoteId)
            ->where('quote_type', $request->quoteType)
            ->select('quote_id', 'quote_type', 'role', 'msg', 'created_at', 'channel', 'whatsapp_request')
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

        $data = app(InstantAlfredService::class)->processSqlChatFilters($request, $modelType);

        $result = $this->processMongoDBChatFilters($request, $data);

        $perPage = $request->input('per_page', 15);
        $currentPage = $request->input('page', 1);
        $total = count($data);
        $lastPage = ceil($total / $perPage);

        $paginatedData = array_slice($result === false ? $data->toArray() : $result, ($currentPage - 1) * $perPage, $perPage);

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

                if ($channelFilter && ! empty($item['communication_channels'])) {
                    $channels = array_filter($item['communication_channels'], function ($channel) {
                        return is_string($channel);
                    });
                    if (array_intersect($channels, [$channelFilter])) {
                        return $item;
                    }
                }

            });

            return $filteredData;
        } else {
            return false;
        }
    }

    public function exportChat(Request $request)
    {
        $data = $this->processSqlChatFilters($request, CarQuote::class);

        $itemIds = array_column($data->toArray(), 'uuid');

        $chatPipeline = $this->createPipeline($request, $itemIds, $request->report);
        $mongoResults = AlfredChat::raw(fn ($collection) => $collection->aggregate($chatPipeline))->toArray();

        $fileName = 'alfred_chat_logs_'.Carbon::now()->format('Y-m-d_H-i-s');

        if ($request->report == InstantChatReportsEnum::CONSOLIDATED_REPORT) {

            foreach ($data as $item) {
                $dataById[$item->uuid] = $item;
                foreach ($mongoResults as $mongoResult) {
                    if ($item->uuid == $mongoResult['_id']) {
                        $item->quote_type = $mongoResult['quote_type'];
                        $item->communication_channels = $mongoResult['communication_channels'];
                        $item->customer_interactions = $mongoResult['customer_interactions'];
                        $item->ai_interactions = $mongoResult['ai_interactions'];
                        $item->total_ai_interactions = $mongoResult['total_ai_interactions'];
                        $item->fallbacks = $mongoResult['fallbacks'];
                        $item->date_of_first_interaction = $mongoResult['date_of_first_interaction'];
                    }
                }
            }

            return Excel::download(new InstantChatConsolidatedExport($data->toArray()), $fileName.'.xlsx');
        }

        if ($request->report == InstantChatReportsEnum::DETAILED_REPORT) {
            return Excel::download(new InstantChatDetailedExport($mongoResults), $fileName.'.xlsx');
        }
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
