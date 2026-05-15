<?php

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Requests\LogsRequest;
use App\Models\CyberInsurerRequestResponses;
use App\Models\CyberQuote;
use App\Models\DeviceInsurerRequestResponses;
use App\Models\DeviceQuote;
use App\Models\EpLog;
use App\Models\HealthInsurerRequestResponse;
use App\Models\HealthQuote;
use App\Models\HealthRoutingLog;
use App\Models\HomeInsurerRequestResponses;
use App\Models\HomeQuote;
use App\Models\InsurerRequestResponse;
use App\Models\LifeInsurerRequestResponses;
use App\Models\LifeQuote;
use App\Models\OcrLog;
use App\Models\TravelInsurerRequestResponses;
use App\Models\TravelQuote;
use App\Models\User;
use App\Repositories\AuditRepository;
use App\Services\BaseService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AuditableController extends Controller
{
    use GenericQueriesAllLobs;

    public function __construct(
        private BaseService $baseService,
        private PolicyIssuanceService $policyIssuanceService,
    ) {
        $this->middleware('permission:'.PermissionsEnum::ILA_CONFIG_ALL_LOB)->only(['loadAuditLogs', 'loadAuditableComponent']);
    }

    public function loadAuditableComponent(Request $request)
    {
        $auditableType = $request->auditableType;
        $auditableId = $request->auditableId;

        if ($request->jsonData) {
            return response()->json($this->baseService->audits($auditableId, $auditableType));
        }

        return view('auditable', compact('auditableId', 'auditableType'));
    }

    public function apiLogsComponent(Request $request)
    {
        $auditableType = $request->auditableType;
        $auditableId = $request->auditableId;

        return view('apilogs', compact('auditableId', 'auditableType'));
    }

    public function loadAuditLogs(Request $request)
    {
        $code = isset($request->code) ? $request->code : '';

        $documentIds = [];
        if ($request->auditableType === 'App\Models\SendUpdateLog') {
            $code = $this->getSendUpdatePaymentCode($request->auditableId);
            $documentIds = $this->getSendUpdateDocumentIds($request->auditableId);
        }

        $auditableTypes = ['App\Models\Payment', 'App\Models\PaymentSplits'];
        $query = DB::table('audits')
            ->select('audits.*', 'users.name')
            ->leftJoin('users', 'audits.user_id', 'users.id')
            ->where('auditable_id', $request->auditableId)
            ->where('auditable_type', $request->auditableType);

        // if ($code != '') {
        //     $query->orWhere(function ($query) use ($code, $auditableTypes) {
        //         $query->where('old_values', 'like', '%"code":"'.$code.'"%')
        //             ->whereIn('auditable_type', $auditableTypes);
        //     });
        // }

        if (! empty($documentIds)) {
            $query->orWhere(function ($query) use ($documentIds) {
                $query->where('auditable_type', 'App\Models\QuoteDocument')
                    ->whereIn('auditable_id', $documentIds);
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function loadPolicyIssuanceApiLogs(Request $request)
    {
        $quoteType = QuoteTypes::getName($request->quoteTypeId)->value ?? '';
        $quote = $this->getQuoteObject($quoteType, $request->quoteId);

        $allowedQuoteTypes = [QuoteTypes::CAR->value, QuoteTypes::HEALTH->value, QuoteTypes::TRAVEL->value, QuoteTypes::CYBER->value, QuoteTypes::DEVICE->value];

        if (empty($quote) || empty($quoteType) || ! in_array($quoteType, $allowedQuoteTypes)) {
            return response()->json([
                'success' => false,
                'message' => empty($quote) ? 'Quote not found' : 'Quote type not supported',
            ]);
        }

        $policyIssuanceLogs = $quote->policyIssuance?->policyIssuanceLogs()
            ->with(['policyIssuance.insuranceProvider:id,code,text', 'policyIssuance.model:id,uuid'])
            ->get()
            ->sortByDesc('created_at')
            ->values();

        $policyIssuance = $quote->policyIssuance;
        $reTriggerPolicyAutomationEligible = false;
        if ($policyIssuance) {
            $policyIssuance->loadMissing('insuranceProvider');
            $reTriggerPolicyAutomationEligible = $this->policyIssuanceService
                ->shouldOfferReTriggerPolicyAutomation($policyIssuance);
        }

        return response()->json([
            'success' => true,
            'message' => 'Policy issuance API logs retrieved successfully',
            'data' => $policyIssuanceLogs,
            'policyIssuance' => $policyIssuance ?? null,
            'reTriggerPolicyAutomationEligible' => $reTriggerPolicyAutomationEligible,
        ]);
    }

    public function loadApiLogs(Request $request)
    {
        try {
            $request->validate([
                'auditableType' => 'required|string',
                'auditableId' => 'required|integer',
            ]);

            $auditableType = $request->get('auditableType');
            $auditableId = $request->get('auditableId');
            $insuranceProvider = $request->get('insurance_provider');

            info('Loading API logs', [
                'auditableType' => $auditableType,
                'auditableId' => $auditableId,
            ]);

            $quoteUID = $auditableType::where('id', $auditableId)->value('uuid');

            if (! $quoteUID) {
                info('Quote UUID not found', [
                    'auditableType' => $auditableType,
                    'auditableId' => $auditableId,
                ]);

                return response()->json(['error' => 'Quote UID not found.'], 404);
            }

            $query = $this->getQueryBuilderForAuditableType($auditableType);

            // Use string direction so MongoDB\Laravel\Query\Builder maps to -1/1; orderByDesc() passes
            // SortDirection enum which cannot be BSON-serialized for Mongo find sort options.
            $query->where('quote_uuid', $quoteUID)
                ->orderBy('created_at', 'desc');

            if ($insuranceProvider) {
                $query->where('provider_id', $insuranceProvider);
            }

            $logs = $query->get();

            info('API logs retrieved successfully', [
                'auditableType' => $auditableType,
                'auditableId' => $auditableId,
                'logsCount' => $logs->count(),
            ]);

            return $logs;
        } catch (\Exception $e) {
            info('Error loading API logs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'An error occurred while loading API logs.'], 500);
        }
    }

    /**
     * @return mixed
     */
    public function getQuoteAudits(Request $request)
    {
        $user = $request->user();
        $quoteType = $request->input('quote_type');
        $quoteTypeString = is_string($quoteType) && $quoteType !== '' ? $quoteType : null;

        $primaryAuditableType = null;
        if ($quoteTypeString !== null) {
            try {
                $primaryAuditableType = AuditRepository::primaryAuditableTypeForQuoteType($quoteTypeString);
            } catch (\Throwable) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view audit logs.',
                ], Response::HTTP_FORBIDDEN);
            }

            if ($primaryAuditableType === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view audit logs.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $requiresSelfAuditableId = $primaryAuditableType === User::class;

        $forbidden = $requiresSelfAuditableId
            ? (
                ! $user->hasAnyRole([RolesEnum::Admin, RolesEnum::Engineering])
                && (
                    (int) $user->id !== (int) $request->input('auditable_id')
                    || ! $user->hasAnyPermission([PermissionsEnum::Auditable])
                )
            )
            : ! $user->hasAnyPermission([PermissionsEnum::Auditable]);

        if ($forbidden) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view audit logs.',
            ], Response::HTTP_FORBIDDEN);
        }

        $audits = AuditRepository::getQuoteAudits();

        return ($request->jsonData) ? response()->json($audits) : $audits;
    }

    protected function getQueryBuilderForAuditableType(string $auditableType)
    {
        switch ($auditableType) {
            case TravelQuote::class:
                return TravelInsurerRequestResponses::with('insuranceProvider')
                    ->whereNotIn('call_type', ['oAuth', 'login']);
            case HomeQuote::class:
                return HomeInsurerRequestResponses::with('insuranceProvider')
                    ->whereNotIn('call_type', ['oAuth', 'login']);
            case LifeQuote::class:
                return LifeInsurerRequestResponses::with('insuranceProvider')
                    ->whereNotIn('call_type', ['oAuth', 'login']);
            case CyberQuote::class:
                return CyberInsurerRequestResponses::with('insuranceProvider');
            case DeviceQuote::class:
                return DeviceInsurerRequestResponses::with('insuranceProvider')
                    ->whereNotIn('call_type', ['oAuth', 'login']);
            case HealthQuote::class:
                return HealthInsurerRequestResponse::with('insuranceProvider')->whereNotIn('call_type', ['oAuth', 'login']);
            default:
                return InsurerRequestResponse::with('insuranceProvider');
        }
    }

    public function loadOcrLogs(LogsRequest $request)
    {
        try {
            $auditableType = $request->input('type');
            $auditableId = $request->input('id');

            LoggerService::info('Loading OCR logs');

            $logs = OcrLog::where('ocr_loggable_type', $auditableType)
                ->where('ocr_loggable_id', $auditableId)
                ->with(['provider', 'user'])
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'ref_id' => $log->request_data['ref_id'] ?? 'N/A',
                        'document_type_name' => $log->document_type_name,
                        'status' => $log->status,
                        'formatted_execution_time' => $log->formatted_execution_time,
                        'provider_name' => $log->provider?->text ?? 'N/A',
                        'uploaded_through' => $log->uploaded_through,
                        'user_name' => $log->user?->name ?? null,
                        'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                        'request_data' => $log->request_data,
                        'response_data' => $log->response_data,
                        'error_message' => $log->error_message,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Failed to load OCR logs - ', exception: $e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load OCR logs',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function loadHealthRoutingLogs(Request $request)
    {
        try {
            $logs = HealthRoutingLog::with('user')
                ->where('type', $request->type)
                ->when($request->team_category, function ($query) use ($request) {
                    $query->where('team_category', $request->team_category);
                })
                ->when($request->quote_request_id, function ($query) use ($request) {
                    $query->where('quote_request_id', $request->quote_request_id);
                })
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Failed to load Health Routing Logs - ', exception: $e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load Health Routing Logs',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function loadEpLogs(LogsRequest $request)
    {
        try {
            $auditableType = $request->input('type');
            $auditableId = $request->input('id');

            $logs = EpLog::where('loggable_type', $auditableType)
                ->where('loggable_id', $auditableId)
                ->with('embeddedTransaction', 'embeddedTransaction.payment')
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'event' => $log->event,
                        'product_type' => $log->embeddedTransaction?->code ? substr($log->embeddedTransaction->code, 0, 3) : null,
                        'captured_at' => $log->embeddedTransaction?->payment?->getRawOriginal('captured_at') ?? null,
                        'values' => $log->values ?? null,
                        'created_at' => $log->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        } catch (\Exception $e) {

            LoggerService::error('Failed to load EP logs - ', exception: $e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load EP logs',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
