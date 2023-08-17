<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\APiFetchUrl;
use App\Jobs\CarAllocationJob;
use App\Services\ApiService;
use App\Services\CarAllocationService;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    private $apiService;

    public function __construct(ApiService $service)
    {
        $this->apiService = $service;
    }

    public function fetchSignupUrl(APiFetchUrl $request)
    {
        return $this->apiService->fetchSignupUrl($request);
    }

    public function sibHealthQuoteCallBack(Request $request)
    {
        if ($request->has('attributes') && isset($request['attributes']['CDBID'])) {
            return $this->apiService->sibHealthQuoteCallBack($request['attributes']['CDBID']);
        }
    }

    public function assignLeads(Request $request)
    {
        $quoteType = $request['attributes']['quoteType'];
        $quoteId = $request['attributes']['CDBID'];
        if ($request->has('attributes') && isset($quoteType) && isset($quoteId)) {
            if ($quoteType == CarQuote::class) {
                dispatch(new CarAllocationJob(app(CarAllocationService::class), $quoteId));
            }

            return $this->apiService->sibHealthQuoteCallBack($request['attributes']['CDBID']);
        }
    }
}
