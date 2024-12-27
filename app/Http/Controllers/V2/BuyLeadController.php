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
        $data['isMaxCapReached'] = $data['maxCapacity'] === 0;
        $data['requestAlreadySubmitted'] = ! $data['isMaxCapReached'] && $this->buyLeadService->isRequestAlreadySubmitted($request->getQuoteType());
        $data['maxCapacity'] = $data['maxCapacity'] === 'DISABLED' ? 0 : $data['maxCapacity'];

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
        $data['lobs'] = collect(QuoteTypes::withLabels())->filter(fn ($type) => in_array($type['value'], [QuoteTypes::CAR->value, QuoteTypes::HEALTH->value]))->values()->toArray();
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
        $data['lobs'] = collect(QuoteTypes::withLabels())->filter(fn ($type) => in_array($type['value'], [QuoteTypes::CAR->value, QuoteTypes::HEALTH->value]))->values()->toArray();
        [$startDate, $endDate] = request('date');

        if ($quoteType && $startDate && $endDate) {
            if (request()->has('export')) {
                return $this->buyLeadService->exportTrackingReportPDF($quoteType, Carbon::parse($startDate), Carbon::parse($endDate));
            } else {
                $data['list'] = $this->buyLeadService->getTrackingData($quoteType, Carbon::parse($startDate), Carbon::parse($endDate));
            }
        }

        return inertia('BuyLeads/BuyLeadsTracking', $data);
    }
}
