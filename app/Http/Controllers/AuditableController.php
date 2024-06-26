<?php

namespace App\Http\Controllers;

use App\Models\CarQuote;
use App\Models\InsurerRequestResponse;
use App\Models\SageApiLog;
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
        $auditableTypes = ['App\Models\Payment', 'App\Models\PaymentSplits'];
        $query = DB::table('audits')
            ->select('audits.*', 'users.name')
            ->leftJoin('users', 'audits.user_id', 'users.id')
            ->where('auditable_id', $request->auditableId)
            ->where('auditable_type', $request->auditableType);

        if ($code != '') {
            $query->orWhere(function ($query) use ($code, $auditableTypes) {
                $query->where('old_values', 'like', '%"code":"'.$code.'"%')
                    ->whereIn('auditable_type', $auditableTypes);
            });
        }

        return $query->orderBy('created_at', 'desc')->get();

    }

    public function loadApiLogs(Request $request)
    {
        if ($request->auditableType == CarQuote::class) {
            $query = InsurerRequestResponse::with('insuranceProvider')
                ->select('*')
                ->where('insurer_request_response.quote_uuid', CarQuote::where('id', $request->auditableId)->value('uuid'))
                ->orderByDesc('insurer_request_response.created_at');

            if ($request->insurance_provider) {
                $query->where('insurer_request_response.provider_id', $request->insurance_provider);
            }

            return $query->get();
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

    public function sageApiLogs(Request $request, $sectionId)
    {
        $sageApiLogs = SageApiLog::where(['section_type' => $request->modelClass, 'section_id' => $sectionId])->get();

        return response()->json(['success' => true, 'sageApiLogs' => $sageApiLogs]);
    }
}
