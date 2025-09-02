<?php

namespace App\Http\Controllers;

use App\Http\Requests\OcrLogsRequest;
use App\Models\HomeInsurerRequestResponses;
use App\Models\HomeQuote;
use App\Models\InsurerRequestResponse;
use App\Models\LifeInsurerRequestResponses;
use App\Models\LifeQuote;
use App\Models\OcrLog;
use App\Models\TravelInsurerRequestResponses;
use App\Models\TravelQuote;
use App\Repositories\AuditRepository;
use App\Services\BaseService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AuditableController extends Controller
{
    use GenericQueriesAllLobs;

    public function loadAuditableComponent(Request $request)
    {
        $auditableType = $request->auditableType;
        $auditableId = $request->auditableId;

        if ($request->jsonData) {
            $service = app()->make(BaseService::class);

            return response()->json($service->audits($auditableId, $auditableType));
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

        $quoteId = $request->get('quote_id');
        $quoteType = $request->get('quote_type');
        $insuranceProviderId = $request->get('insurance_provider_id');

        $extraLogs = [
            'quote_id' => $quoteId,
            'quote_type' => $quoteType,
            'insurance_provider_id' => $insuranceProviderId
        ];

        try {
            $request->validate([
                'quote_id' => 'required|integer',
                'quote_type' => 'required|string',
                'insurance_provider_id' => 'required|integer',
            ]);

            LoggerService::info('Loading policy issuance API logs', extra: $extraLogs);

            // Get the table name dynamically based on quote type
            $quoteModel = new $quoteType;
            $quoteTableName = $quoteModel->getTable();

            $policyIssuanceLogs = DB::table($quoteTableName . ' as q')
                ->select([
                    'q.id as quote_id',
                    'q.uuid as quote_uuid',
                    'pi.id as policy_issuance_id',
                    'pi.model_id',
                    'pi.model_type',
                    'pi.insurance_provider_id',
                    'pi.completed_step',
                    'ip.id as insurance_provider_id',
                    'ip.code as insurance_provider_code',
                    'ip.text as insurance_provider_text',
                    'pi_logs.id',
                    'pi_logs.step',
                    'pi_logs.status',
                    'pi_logs.created_at',
                    'pi_logs.policy_issuance_id',
                    'pi_logs.endPoint',
                    'pi_logs.payload',
                    'pi_logs.response'
                ])
                ->leftJoin('policy_issuance as pi', function ($join) use ($quoteType) {
                    $join->on('pi.model_id', '=', 'q.id')
                        ->where('pi.model_type', '=', $quoteType);
                })
                ->leftJoin('insurance_provider as ip', 'pi.insurance_provider_id', '=', 'ip.id')
                ->leftJoin('policy_issuance_logs as pi_logs', 'pi_logs.policy_issuance_id', '=', 'pi.id')
                ->where('q.id', $quoteId);

            if ($insuranceProviderId) {
                $policyIssuanceLogs = $policyIssuanceLogs->where('pi.insurance_provider_id', $insuranceProviderId);
            }

            $policyIssuanceLogs = $policyIssuanceLogs
                ->orderBy('pi_logs.created_at', 'desc')
                ->get();

            return $policyIssuanceLogs;
        } catch (\Exception $e) {
            LoggerService::info('Error loading policy issuance API logs', 
                extra: [...$extraLogs, 'error' => $e->getMessage()]
            );

            return response()->json([
                'error' => 'An error occurred while loading policy issuance API logs.',
                'message' => $e->getMessage(),
            ], 500);
        }
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

            $query->where('quote_uuid', $quoteUID)
                ->orderByDesc('created_at');

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
            default:
                return InsurerRequestResponse::with('insuranceProvider');
        }
    }

    public function loadOcrLogs(OcrLogsRequest $request)
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
                        'provider_name' => $log->provider?->name ?? 'N/A',
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
}
