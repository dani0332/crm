<?php

namespace App\Http\Controllers\API;

use App\Factories\AllocationFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\APiFetchUrl;
use App\Services\ApiService;
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
        try {

            info('API assignLeads called with request params as : '.json_encode($request->all()));

            if ($request->has('quoteUUID') && $request->has('quoteTypeId')) {

                $allocationType = $request->input('quoteTypeId');
                $allocationId = $request->input('quoteUUID');

                info('------ Lead allocation started for lead : '.$allocationId.' ------');

                info('API endpoint is called for quote uuid: '.$allocationId.' with quote type id: '.$allocationType);

                $allocationStrategy = AllocationFactory::createStrategy($allocationType, $allocationId);

                $allocationStrategy->executeSteps();

                info('------ Lead allocation ended for lead : '.$allocationId.' ------');

                return response()->json(['message' => 'Quote allocation completed successfully!'], 200);
            } else {
                info('------ Lead allocation ended for lead with Required parameters missing ------');

                return response()->json(['error' => 'Required parameters missing'], 400);
            }
        } catch (\Exception $e) {
            info('------ Lead allocation ended for lead with An error occurred ------');

            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage(), 'stackTrace' => $e->getTraceAsString()], 500);
        }
    }

}
