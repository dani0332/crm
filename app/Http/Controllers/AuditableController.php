<?php

namespace App\Http\Controllers;

use App\Enums\QuoteTypes;
use App\Models\BikeQuote;
use App\Models\CycleQuote;
use App\Models\JetskiQuote;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\YachtQuote;
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

    public function loadAuditLogs(Request $request)
    {
        $audits = DB::table('audits')
       ->select('audits.*', 'users.name')
       ->join('users', 'audits.user_id', 'users.id')
       ->where('auditable_id', $request->auditableId)
       ->where('auditable_type', $request->auditableType)
       ->orderBy('created_at', 'desc')
       ->get();

        return $audits;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getQuoteAudits(Request $request)
    {
        return AuditRepository::getQuoteAudits();
    }
}
