<?php

namespace App\Http\Controllers\V2;

use App\Enums\InstantChatReportsEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\TransactionTypeEnum;
use App\Exports\InstantChatConsolidatedExport;
use App\Exports\InstantChatDetailedExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlfredChatRequest;
use App\Models\AlfredChat;
use App\Models\Lookup;
use App\Models\QuoteBatches;
use App\Models\QuoteStatus;
use App\Services\InstantAlfredService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AlfredChatController extends Controller
{
    private $instantAlfredService;

    public function __construct(InstantAlfredService $instantAlfredService)
    {
        $this->instantAlfredService = $instantAlfredService;

        $this->middleware('permission:'.PermissionsEnum::INSTANT_ALFRED_CHAT_LOGS, ['only' => ['logs']]);

        $this->middleware('permission:'.PermissionsEnum::DATA_EXTRACTION, ['only' => ['exportChat']]);

        $this->middleware('readonly_db');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function chats(AlfredChatRequest $request)
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

    public function index(Request $request)
    {
        $data = $this->instantAlfredService->processSqlChatFilters($request);

        $transactionTypes = Lookup::select('id', 'text')->whereIn('text', [TransactionTypeEnum::EXISTING_CUSTOMER_NEW_BUSINESS, TransactionTypeEnum::NEW_BUSINESS, TransactionTypeEnum::EXISTING_CUSTOMER_RENEWAL])->get();

        return inertia('AlfredChat/Index', ['logs' => $data->simplePaginate(15)->withQueryString(),  'leadStatuses' => QuoteStatus::all(), 'batches' => QuoteBatches::all(), 'transactionTypes' => $transactionTypes]);

    }

    public function processMongoDBChatFilters(Request $request, $data)
    {
        if (isset($request->fallback) && $request->fallback != '' || isset($request->channel) && $request->channel != '') {

            $dataArray = json_decode(json_encode($data), true);

            $itemIds = array_column($dataArray, 'uuid');

            $chatPipeline = $this->instantAlfredService->createPipeline($request, $itemIds, 'chat');

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
