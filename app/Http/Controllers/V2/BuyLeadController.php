<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuyLeads\BuyLeadsRateFetchRequest;
use App\Http\Requests\BuyLeads\RequestBuyLeadsRequest;
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
        $data['maxCapacity'] = $this->buyLeadService->getBlLeadRemainingLimit($request->getQuoteType());

        $config = $this->buyLeadService->findConfigCost($request->getQuoteType());
        if (is_string($config)) {
            $data['cost'] = 0;
        } else {
            $data['cost'] = $config[0];
        }

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
        if ($message = $this->buyLeadService->requestBuyLeads($request)) {
            return response()->json(['message' => $message], 422);
        }

        return response()->json(['message' => 'Buy leads requested successfully']);
    }

    public function tracking()
    {
        $quoteType = QuoteTypes::tryFrom(request()->get('quote_type'));
        $data['lobs'] = QuoteTypes::withLabels();
        $date = request('date');

        $data['list'] = ['data' => []];

        if ($quoteType && $date) {
            $data['list'] = $this->buyLeadService->getTrackingData($quoteType, Carbon::parse($date));
        }

        return inertia('BuyLeads/BuyLeadsTracking', $data);
    }
}
