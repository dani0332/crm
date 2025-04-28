<?php

namespace App\Http\Controllers\V2\Admin;

use App\Enums\AssignmentTypeEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Models\Audit\AllocationAudit;
use Illuminate\Http\Request;

class AllocationAuditController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:'.RolesEnum::Engineering);
    }

    public function index(Request $request)
    {
        $audits = AllocationAudit::query()
            ->byQuoteType()
            ->byUuid()
            ->orderBy('created_at', 'desc')
            ->get();

        return inertia('Admin/AllocationAudit/Index', [
            'audits' => $audits,
            'quoteTypes' => QuoteTypes::withLabels(),
            'assignmentTypes' => AssignmentTypeEnum::withLabels(),
        ]);
    }
}
