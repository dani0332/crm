<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuyLeads\RequestBuyLeadsRequest;
use App\Services\BuyLeads\BuyLeadService;

class BuyLeadController extends Controller
{
    public function __construct(public BuyLeadService $buyLeadService) {}

    public function requestBuyLeads(RequestBuyLeadsRequest $request)
    {
        return $this->buyLeadService->requestBuyLeads($request);
    }
}
