<?php

namespace App\Http\Controllers\V2;

use App\Enums\InstantChatReportsEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteTypeCode;
use App\Exports\InstantChatConsolidatedExport;
use App\Exports\InstantChatDetailedExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlfredChatRequest;
use App\Models\AlfredChat;
use App\Models\QuoteBatches;
use App\Models\QuoteStatus;
use App\Services\InstantAlfredService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AlfredChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.PermissionsEnum::INSTANT_ALFRED_CHAT_LOGS, ['only' => ['logs']]);

        $this->middleware('permission:'.PermissionsEnum::DATA_EXTRACTION, ['only' => ['exportChat']]);

        $this->middleware('readonly_db');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(AlfredChatRequest $request)
    {
        if (isset($request->created_at) && $request->created_at != '') {
            $chat = AlfredChat::where('quote_id', $request->quoteId)
                ->where('quote_type', $request->quoteType)
                ->select('quote_id', 'quote_type', 'role', 'msg', 'created_at', 'channel', 'whatsapp_request')
                ->get();
        } else {
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
        }

        if ($chat->isEmpty()) {
            return response()->json(['message' => 'No chat available']);
        }

        return response()->json(['data' => $chat]);
    }

    public function logs(Request $request)
    {   
        $data = app(InstantAlfredService::class)->processSqlChatFilters($request);
     
        return inertia('AlfredChat/Index', ['logs' => $data->simplePaginate(15)->withQueryString(),  'leadStatuses' => QuoteStatus::all(), 'batches' => QuoteBatches::all()]);

    }

    public function processMongoDBChatFilters(Request $request, $data)
    {
        if (isset($request->fallback) && $request->fallback != '' || isset($request->channel) && $request->channel != '') {

            $dataArray = json_decode(json_encode($data), true);

            $itemIds = array_column($dataArray, 'uuid');

            $chatPipeline = app(InstantAlfredService::class)->createPipeline($request, $itemIds, 'chat');

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
        $fileName = $request->report.' '.Carbon::now()->format('Y-m-d_H-i-s').'.xlsx';

        switch ($request->report) {
            case InstantChatReportsEnum::CONSOLIDATED_REPORT:
                return (new InstantChatConsolidatedExport)->download($fileName);

            case InstantChatReportsEnum::DETAILED_REPORT:
                return (new InstantChatDetailedExport)->download($fileName);

            default:
                abort(400, 'Invalid report type requested.');
        }

    }
}
