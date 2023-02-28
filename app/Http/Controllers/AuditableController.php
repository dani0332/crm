<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditableController extends Controller
{
    public function loadAuditableComponent(Request $request)
    {
        $auditableType = $request->auditableType;
        $auditableId = $request->auditableId;

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
}
