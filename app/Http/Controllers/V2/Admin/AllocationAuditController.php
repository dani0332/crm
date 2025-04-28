<?php

namespace App\Http\Controllers\V2\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit\AllocationAudit;
use App\Enums\QuoteTypes;
use App\Enums\AssignmentTypeEnum;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AllocationAuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AllocationAudit::query();


        // Apply filters
        if ($request->has('quote_type')) {
            $quoteType = QuoteTypes::tryFrom(request('quote_type'));
            $query->where('quote_type_id', $quoteType?->id());
        }

        if ($request->has('uuid')) {
            $query->where('uuid', $request->uuid);
        }

        $audits = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return inertia('Admin/AllocationAudit/Index', [
            'audits' => $audits,
            'quoteTypes' => QuoteTypes::withLabels(),
            'assignmentTypes' => AssignmentTypeEnum::withLabels(),
        ]);
    }
}
