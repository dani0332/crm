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
use App\Jobs\InstantAlfredExportJob;
use App\Models\AlfredChat;
use App\Models\Lookup;
use App\Models\QuoteBatches;
use App\Models\QuoteStatus;
use App\Models\RenewalBatch;
use App\Enums\ApplicationStorageEnums;
use App\Models\ApplicationStorage;
use App\Services\BirdService;
use App\Services\InstantAlfredExportService;
use App\Services\InstantAlfredService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

        return inertia('AlfredChat/Index', ['logs' => $data->simplePaginate(15)->withQueryString(),  'leadStatuses' => QuoteStatus::all(), 'batches' => QuoteBatches::all(), 'transactionTypes' => $transactionTypes,
            'renewalBatches' => RenewalBatch::getAllBatches(true)]);

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
        $fileName = $request->report;
        switch ($request->report) {
            case InstantChatReportsEnum::CONSOLIDATED_REPORT:
                return (new InstantChatConsolidatedExport)->download($fileName);

            case InstantChatReportsEnum::DETAILED_REPORT:
                return (new InstantChatDetailedExport)->download($fileName);

            default:
                abort(400, 'Invalid report type requested.');
        }
    }

    public function exportChatToEmail(Request $request)
    {
        // Validate request parameters
        $request->validate([
            'report' => 'required|string',
            'recipientEmail' => 'sometimes|email',
            'subject' => 'sometimes|string',
            'ccRecipients' => 'sometimes|array',
            'ccRecipients.*' => 'email',
        ]);

        if (! in_array($request->report, [InstantChatReportsEnum::CONSOLIDATED_REPORT, InstantChatReportsEnum::DETAILED_REPORT])) {
            return response()->json([
                'error' => 'Invalid report type requested.',
                'available_reports' => [InstantChatReportsEnum::CONSOLIDATED_REPORT, InstantChatReportsEnum::DETAILED_REPORT],
            ], 400);
        }

        // Set default recipient email to current user if not provided
        $recipientEmail = $request->recipientEmail ?? (Auth::check() ? Auth::user()->email : null);

        if (! $recipientEmail) {
            return response()->json([
                'error' => 'Recipient email is required.',
                'message' => 'Please provide a recipient email or ensure you are authenticated.',
            ], 400);
        }

        $fileName = $request->report.' '.Carbon::now()->format('Y-m-d_H-i-s');
        $subject = $request->subject ?? "Chat Report: {$request->report}";

        try {
            InstantAlfredExportJob::dispatch([
                ...$request->all(),
                'recipientEmail' => $recipientEmail,
                'recipientName' => Auth::user()?->name ?? 'User',
                'subject' => $subject,
                'fileName' => $fileName,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Your export is being processed. You will receive an email with a download link shortly.',
                'report_type' => $request->report,
                'recipient' => $recipientEmail,
                'subject' => $subject,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to initiate export.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateExportUrl(Request $request)
    {
        $apiStartTime = microtime(true);

        $request->validate([
            'report' => 'required|string|in:'.InstantChatReportsEnum::DETAILED_REPORT.','.InstantChatReportsEnum::CONSOLIDATED_REPORT,
            'created_at_start' => 'sometimes|date',
            'created_at_end' => 'sometimes|date|after_or_equal:created_at_start',
            'chat_initiated_at' => 'sometimes|array|size:2',
            'chat_initiated_at.0' => 'required_with:chat_initiated_at|date',
            'chat_initiated_at.1' => 'required_with:chat_initiated_at|date',
        ]);

        try {
            $service = app(InstantAlfredExportService::class);
            $params = $this->prepareExportParams($request);

            $result = $service->generateCsvAndGetUrl($params);

            $totalApiTime = round(microtime(true) - $apiStartTime, 3);

            Log::info('API endpoint: Export URL generated', [
                'total_response_time' => $totalApiTime,
                'records' => $result['records'],
                'report' => $result['report_type'],
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            $totalApiTime = round(microtime(true) - $apiStartTime, 3);
            Log::error('API endpoint: Export URL generation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'time_before_failure' => $totalApiTime,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate export URL.',
                'message' => $e->getMessage(),
                'time_before_failure' => $totalApiTime,
            ], 500);
        }
    }

    private function prepareExportParams(Request $request): array
    {
        $params = $request->all();

        $params['recipientEmail'] = $request->recipientEmail ?? Auth::user()?->email ?? 'system@example.com';
        $params['recipientName'] = Auth::user()?->name ?? 'User';

        if ($request->has('created_at_start') && $request->has('created_at_end')) {
            $params['chat_initiated_at'] = [
                $request->created_at_start,
                $request->created_at_end,
            ];
        }

        if ($request->has('chat_initiated_at') && is_array($request->chat_initiated_at)) {
            $params['chat_initiated_at'] = $request->chat_initiated_at;
        }

        return $params;
    }

    public function exportChatViaBird(Request $request)
    {
        $request->validate([
            'report' => 'required|string|in:'.InstantChatReportsEnum::DETAILED_REPORT.','.InstantChatReportsEnum::CONSOLIDATED_REPORT,
            'recipientEmail' => 'sometimes|email',
        ]);

        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_INSTANT_ALFRED_EXPORT_WORKFLOW)->first();

        if (! $workflowUrl || empty($workflowUrl->value)) {
            return response()->json([
                'success' => false,
                'error' => 'Bird workflow URL not configured.',
                'message' => 'Please contact administrator to configure the Bird workflow URL.',
            ], 500);
        }

        try {
            $recipientName = Auth::user()?->name;
            if (empty($recipientName)) {
                $recipientName = 'User';
            }

            $birdPayload = [
                'report' => $request->report,
                'recipientEmail' => $request->recipientEmail ?? Auth::user()?->email ?? null,
                'recipientName' => $recipientName,
                'filters' => $request->except(['report', 'recipientEmail']),
                'user_id' => Auth::id(),
                'exportApiUrl' => route('api.instant-alfred.generate-url'),
            ];

            Log::info('Bird workflow payload prepared', [
                'payload' => $birdPayload,
                'recipientName' => $birdPayload['recipientName'],
            ]);

            $birdService = app(BirdService::class);
            $response = $birdService->triggerWebHookRequest($workflowUrl->value, $birdPayload, 'post', false);

            if (in_array($response->status_code, [200, 201])) {
                Log::info('Bird workflow triggered for instant alfred export', [
                    'report' => $request->report,
                    'recipient' => $birdPayload['recipientEmail'],
                    'workflow_url' => $workflowUrl->value,
                    'status' => $response->status_code,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Export workflow has been triggered. You will receive an email with the download link shortly.',
                    'report_type' => $request->report,
                ]);
            }

            Log::error('Bird workflow failed for instant alfred export', [
                'report' => $request->report,
                'recipient' => $birdPayload['recipientEmail'],
                'status' => $response->status_code,
                'response' => $response->body,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to trigger Bird workflow.',
                'message' => 'The workflow could not be initiated. Please try again.',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Bird workflow exception for instant alfred export', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'report' => $request->report,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to trigger Bird workflow.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
