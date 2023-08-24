<?php

namespace App\Http\Controllers\API;

use App\Enums\QuoteTypeId;
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
        info('adding request log : '.json_encode($request->all()));
        if ($request->has('quoteUUID') && $request->has('quoteTypeId')) {
            $quoteId = $request['quoteUUID'];
            $quoteType = $request['quoteTypeId'];
            info('API is hit for quote uuid : '.$quoteId.' with quote type id : '.$quoteType);
            if ($quoteType == QuoteTypeId::Car) {
                dispatch(new CarAllocationJob(app(CarAllocationService::class), $quoteId));
            }
        } else {
            return response('Required Parameter missing', 403);
        }
    }
}
