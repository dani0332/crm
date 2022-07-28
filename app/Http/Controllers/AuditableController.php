<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuditableController extends Controller
{
    public function loadAuditableComponent(Request $request)
    {
        $auditableType = $request->auditableType;
        $auditableId = $request->auditableId;

        return view('auditable', compact('auditableId', 'auditableType'));
    }
}
