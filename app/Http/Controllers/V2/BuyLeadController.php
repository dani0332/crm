<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuyLeads\BuyLeadsRateFetchRequest;
use App\Http\Requests\BuyLeads\RequestBuyLeadsRequest;
use App\Models\BuyLeadConfiguration;
use App\Services\BuyLeads\BuyLeadService;
use Carbon\Carbon;

class BuyLeadController extends Controller
{
    public function __construct(public BuyLeadService $buyLeadService)
    {
        $this->middleware('permission:'.PermissionsEnum::BUY_LEADS, ['only' => ['show', 'tracking']]);
    }

    public function fetchRate(BuyLeadsRateFetchRequest $request)
    {
        $config = BuyLeadConfiguration::where([
            'quote_type_id' => $request->getQuoteTypeId(),
            'department_id' => $request->getDepartmentId(),
        ])->first();

        $data['value'] = $config?->value ?? 0;
        $data['volume'] = $config?->volume ?? 0;
        $data['maxCapacity'] = $this->buyLeadService->getBlLeadRemainingLimit($request->getQuoteType());

        return response()->json($data);
    }

    public function show()
    {
        $data['lobs'] = QuoteTypes::withLabels();
        $data['requests'] = $this->buyLeadService->getTodaysRequests();

        return inertia('BuyLeads/BuyLeadsRequest', $data);
    }

    public function submit(RequestBuyLeadsRequest $request)
    {
        return $this->buyLeadService->requestBuyLeads($request);
    }

    public function tracking()
    {
        $quoteType = QuoteTypes::tryFrom(request()->get('quote_type'));
        $data['lobs'] = QuoteTypes::withLabels();
        $date = request('date');

        $data['list'] = null;

        if ($quoteType && $date) {
            $data['list'] = $this->buyLeadService->getTrackingData($quoteType, Carbon::parse($date));
        }

        return inertia('BuyLeads/BuyLeadsTracking', $data);
    }
}
