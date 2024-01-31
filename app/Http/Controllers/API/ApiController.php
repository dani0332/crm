<?php

namespace App\Http\Controllers\API;

use App\Factories\AllocationFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\APiFetchUrl;
use App\Http\Requests\AssignLeadRequest;
use App\Jobs\SendOCBEmailJob;
use App\Services\ApiService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

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

    public function assignLeads(AssignLeadRequest $request)
    {
        try {
            // Log the incoming request parameters
            info('API assignLeads called with request params as : '.json_encode($request->all()));

            // Check if lead allocation endpoint is disabled
            if ($this->isLeadAllocationEndpointDisabled()) {
                return response()->json(['error' => 'Lead allocation endpoint disabled'], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            // Validate the request
            $request->validated();

            // Extract request parameters
            $allocationType = $request->input('quoteTypeId');
            $allocationId = $request->input('quoteUUID');
            $assignAdvisor = $request->input('assignAdvisor', false);
            $triggerOCB = $request->input('triggerOCB', false);

            // Handle different scenarios based on request parameters
            if ($assignAdvisor && ! $triggerOCB) {
                return $this->assignAdvisorOnly($allocationType, $allocationId);
            }

            if (! $assignAdvisor && $triggerOCB) {
                return $this->triggerOCBOnly($allocationId);
            }

            if (! $assignAdvisor && ! $triggerOCB) {
                return $this->performLeadAllocation($allocationType, $allocationId);
            }

            info('------ Lead allocation ended for lead with Invalid request ------');

            return response()->json(['error' => 'Invalid request'], Response::HTTP_NOT_ACCEPTABLE);
        } catch (\Exception $e) {
            info('------ Lead allocation ended for lead with An error occurred ------');

            return apiResponse($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (ValidationException $e) {
            info('------ Lead allocation ended for lead with Required parameters missing ------');

            return apiResponse($e, Response::HTTP_BAD_REQUEST);
        }
    }

    // Helper methods

    private function isLeadAllocationEndpointDisabled()
    {
        return config('constants.DISABLE_LEAD_ALLOCATION_ENDPOINT') == 1;
    }

    private function assignAdvisorOnly($allocationType, $allocationId)
    {
        info('------ Lead allocation request received to assign advisor only for '.$allocationId.' ------');
        $allocationStrategy = AllocationFactory::createStrategy($allocationType, $allocationId);
        $overrideAdvisorId = true;
        $allocationStrategy->executeSteps($overrideAdvisorId);
        info('------ Lead allocation request completed to assign advisor only for '.$allocationId.' ------');

        return response()->json(['message' => 'Advisor ReAssigned successfully!'], Response::HTTP_OK);
    }

    private function triggerOCBOnly($allocationId)
    {
        info('------ Lead allocation request received to send OCB only for '.$allocationId.' ------');
        SendOCBEmailJob::dispatch($allocationId);
        info('------ Lead allocation request completed to send OCB only for '.$allocationId.' ------');

        return response()->json(['message' => 'OCB email triggered successfully!'], Response::HTTP_OK);
    }

    private function performLeadAllocation($allocationType, $allocationId)
    {
        info('------ Lead allocation started for lead : '.$allocationId.' ------');
        $allocationStrategy = AllocationFactory::createStrategy($allocationType, $allocationId);
        $allocationStrategy->executeSteps();
        info('------ Lead allocation ended for lead : '.$allocationId.' ------');

        return response()->json(['message' => 'Quote allocation completed successfully!'], Response::HTTP_OK);
    }
}
