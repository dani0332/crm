<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CarQoute;
use DataTables;
use Spatie\Permission\Models\Role;
use DB;

class AuditableController extends Controller
{
    public function loadAuditableComponent(Request $request){
        $auditableType = $request->auditableType;
        $auditableId = $request->auditableId;
        return view('auditable',compact('auditableId','auditableType'));
    }
}
