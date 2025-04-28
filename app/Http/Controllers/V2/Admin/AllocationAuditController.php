<?php

namespace App\Http\Controllers\V2\Admin;

use App\Enums\AssignmentTypeEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Models\Audit\AllocationAudit;
use Illuminate\Http\Request;

class AllocationAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AllocationAudit::query();

        // Apply filters
        if ($request->has('quote_type')) {
            $quoteType = QuoteTypes::tryFrom(request('quote_type'));
            $query->where('quote_type_id', $quoteType?->id());
        } else {
            $query->where('quote_type_id', null);
        }

        if ($request->has('uuid')) {
            $query->where('uuid', $request->uuid);
        } else {
            $query->where('uuid', null);
        }

        $audits = $query->orderBy('created_at', 'desc')->get();

        return inertia('Admin/AllocationAudit/Index', [
            'audits' => $audits,
            'quoteTypes' => QuoteTypes::withLabels(),
            'assignmentTypes' => AssignmentTypeEnum::withLabels(),
        ]);
    }
}
