<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuyLeads\BuyLeadsRateFetchRequest;
use App\Http\Requests\BuyLeads\RequestBuyLeadsRequest;
use App\Models\BuyLeadConfiguration;
use App\Models\BuyLeadRequest;
use App\Models\LeadAllocation;
use App\Services\BuyLeads\BuyLeadService;
use Illuminate\Support\Facades\Auth;

class BuyLeadController extends Controller
{
    public function __construct(public BuyLeadService $buyLeadService)
    {
        $this->middleware('permission:'.PermissionsEnum::BUY_LEADS, ['only' => ['show']]);
    }

    public function fetchRate(BuyLeadsRateFetchRequest $request)
    {
        $config = BuyLeadConfiguration::where([
            'quote_type_id' => $request->getQuoteTypeId(),
            'department_id' => $request->getDepartmentId(),
        ])->first();

        $data['value'] = $config?->value ?? 0;
        $data['volume'] = $config?->volume ?? 0;

        $leadAllocation = LeadAllocation::where('user_id', Auth::id())->where('quote_type_id', $request->getQuoteTypeId())->first();
        $data['maxCapacity'] = $leadAllocation?->buy_lead_max_capacity ?? 0;

        return response()->json($data);
    }

    public function show()
    {
        $data['lobs'] = QuoteTypes::withLabels();
        $data['requests'] = BuyLeadRequest::with('quoteType:id,code')->where('user_id', Auth::id())->latest()->simplePaginate(20)->withQueryString();

        return inertia('BuyLeads/BuyLeadsRequest', $data);
    }

    public function submit(RequestBuyLeadsRequest $request)
    {
        return $this->buyLeadService->requestBuyLeads($request);
    }
}
