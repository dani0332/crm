<?php

namespace App\Http\Controllers;

use App\Models\InsurerRequestResponse;
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
        $auditableType = $request->get('auditableType');

        $quoteUuid = $request->auditableType::where('id', $request->auditableId)->value('uuid');

        if ($auditableType == TravelQuote::class) {
            $query = TravelInsurerRequestResponses::with('insuranceProvider');
            $query->whereNotIn('call_type', ['oAuth', 'login']);
        } else {
            $query = InsurerRequestResponse::with('insuranceProvider');
        }
        $query->select('*')->where('quote_uuid', $quoteUuid)
            ->orderByDesc('created_at');

        if ($request->insurance_provider) {
            $query->where('insurer_request_response.provider_id', $request->insurance_provider);
        }

        return $query->get();
    }

    /**
     * @return mixed
     */
    public function getQuoteAudits(Request $request)
    {
        $audits = AuditRepository::getQuoteAudits();

        return ($request->jsonData) ? response()->json($audits) : $audits;
    }

}
