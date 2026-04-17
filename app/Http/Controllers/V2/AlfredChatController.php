<?php

namespace App\Http\Controllers\V2;

use App\Enums\ApplicationStorageEnums;
use App\Enums\InstantChatReportsEnum;
use App\Enums\PermissionsEnum;
use App\Enums\TransactionTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlfredChatRequest;
use App\Models\AlfredChat;
use App\Models\ApplicationStorage;
use App\Models\Lookup;
use App\Models\QuoteBatches;
use App\Models\QuoteStatus;
use App\Models\RenewalBatch;
use App\Services\BirdService;
use App\Services\InstantAlfredExportService;
use App\Services\InstantAlfredService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AlfredChatController extends Controller
{
    private $instantAlfredService;

    public function __construct(InstantAlfredService $instantAlfredService)
    {
        $this->instantAlfredService = $instantAlfredService;

        $this->middleware('permission:'.PermissionsEnum::INSTANT_ALFRED_CHAT_LOGS, ['only' => ['logs']]);

        $this->middleware('readonly_db');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
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

    public function generateExportUrl(Request $request)
    {
        $request->validate([
            'report' => 'required|string|in:'.InstantChatReportsEnum::DETAILED_REPORT.','.InstantChatReportsEnum::CONSOLIDATED_REPORT,
            'recipientEmail' => 'required|email',
            'recipientName' => 'nullable|string|max:255',
        ]);

        try {
            $service = app(InstantAlfredExportService::class);

            $params = $this->prepareExportParams($request);

            $result = $service->generateCsvAndGetUrl($params);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('API endpoint: Export URL generation failed', [
                'error' => $e->getMessage(),
                'recipient' => $request->recipientEmail,
                'report' => $request->report,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate export URL.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function prepareExportParams(Request $request): array
    {
        if ($request->has('filters') && is_array($request->filters)) {
            $params = $request->filters;

            $params['report'] = $request->report;
            $params['recipientEmail'] = $request->recipientEmail ?? Auth::user()?->email ?? 'system@example.com';
            $params['recipientName'] = $request->recipientName ?? Auth::user()?->name ?? 'User';

            if ($request->has('user_id')) {
                $params['user_id'] = $request->user_id;
            }
        } else {
            $params = $request->all();

            $params['recipientEmail'] = $request->recipientEmail ?? Auth::user()?->email ?? 'system@example.com';
            $params['recipientName'] = $request->recipientName ?? Auth::user()?->name ?? 'User';

            if ($request->has('chat_initiated_at') && is_array($request->chat_initiated_at)) {
                $params['chat_initiated_at'] = $request->chat_initiated_at;
            }
        }

        if (! isset($params['report'])) {
            $params['report'] = $request->report ?? InstantChatReportsEnum::DETAILED_REPORT;
        }

        unset($params['page'], $params['per_page']);

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
                'error' => 'Export workflow URL not configured.',
                'message' => 'Please contact administrator to configure the export workflow URL.',
            ], 500);
        }

        try {

            $birdPayload = [
                'report' => $request->report,
                'recipientEmail' => $request->recipientEmail ?? Auth::user()?->email,
                'recipientName' => Auth::user()?->name ?? 'User',
                'filters' => $request->except(['report', 'recipientEmail']),
                'user_id' => Auth::id(),
                'exportApiUrl' => route('api.instant-alfred.generate-url'),
            ];

            $birdService = app(BirdService::class);
            $response = $birdService->triggerWebHookRequest($workflowUrl->value, $birdPayload, 'post', false);

            if (in_array($response->status_code, [200, 201])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Your export has been queued. You will receive an email with the download link shortly.',
                    'report_type' => $request->report,
                ]);
            }

            Log::error('Export workflow failed for instant alfred export', [
                'report' => $request->report,
                'recipient' => $birdPayload['recipientEmail'],
                'status' => $response->status_code,
                'response' => $response->body,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to trigger export workflow.',
                'message' => 'The export workflow could not be initiated. Please try again.',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Export workflow exception for instant alfred export', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'report' => $request->report,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to trigger export workflow.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
