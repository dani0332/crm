<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\APiFetchUrl;
use App\Services\ApiService;
use App\Strategy\AllocationFactory;
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

            $allocationType = $request['quoteTypeId'];

            $allocationId = $request['quoteUUID'];

            info('API is hit for quote uuid : '.$allocationId.' with quote type id : '.$allocationType);

            $allocationStrategy = AllocationFactory::createStrategy($allocationType, $allocationId);

            $allocationStrategy->executeSteps();
        } else {
            return response('Required Parameter missing', 403);
        }
    }
}
