<?php

namespace App\Http\Controllers;

use App\Models\HomeInsurerRequestResponses;
use App\Models\HomeQuote;
use App\Models\InsurerRequestResponse;
use App\Models\LifeInsurerRequestResponses;
use App\Models\LifeQuote;
use App\Models\TravelInsurerRequestResponses;
use App\Models\TravelQuote;
use App\Repositories\AuditRepository;
use App\Services\BaseService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
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
}
