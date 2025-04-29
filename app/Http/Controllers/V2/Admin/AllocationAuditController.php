<?php

namespace App\Http\Controllers\V2\Admin;

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
        if ($request->filled('uuid') && $request->filled('quote_type')) {

            $audits = AllocationAudit::query()
                ->byQuoteType()
                ->byUuid()
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $audits = [];
        }

        $quoteTypes = collect(QuoteTypes::withLabels())->filter(fn ($item) => in_array($item['value'], [
            QuoteTypes::CAR->value,
            QuoteTypes::HOME->value,
            QuoteTypes::HEALTH->value,
            QuoteTypes::LIFE->value,
            QuoteTypes::BUSINESS->value,
            QuoteTypes::BIKE->value,
            QuoteTypes::YACHT->value,
            QuoteTypes::TRAVEL->value,
            QuoteTypes::PET->value,
            QuoteTypes::CYCLE->value,
            QuoteTypes::JETSKI->value,
        ]))->values();

        return inertia('Admin/AllocationAudit/Index', [
            'audits' => $audits,
            'quoteTypes' => $quoteTypes,
        ]);
    }
}
