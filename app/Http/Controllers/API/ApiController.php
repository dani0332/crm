<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityApiRequest;
use App\Http\Requests\APiFetchUrl;
use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\EvaluateTierRequest;
use App\Http\Requests\HandleZeroPlansRequest;
use App\Http\Requests\SICWorkflowRequest;
use App\Services\ApiService;
use App\Services\InboundEmailsHookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    public $apiService;
    public $inboundEmailsHookService;

    public function __construct(ApiService $apiService, InboundEmailsHookService $inboundEmailsHookService)
    {
        $this->apiService = $apiService;
        $this->inboundEmailsHookService = $inboundEmailsHookService;
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
            info(self::class.'assignLeads: request params as : '.json_encode($request->all()));

            // Check if lead allocation endpoint is disabled
            if ($this->apiService->isLeadAllocationEndpointDisabled()) {
                return apiResponse(null, Response::HTTP_SERVICE_UNAVAILABLE, 'Lead allocation endpoint disabled');
            }

            return $this->apiService->processAssignLead($request);
        } catch (\Exception $e) {
            info('------ Lead allocation ended for lead with An error occurred ------');

            return apiResponse($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (ValidationException $e) {
            info('------ Lead allocation ended for lead with Required parameters missing ------');

            return apiResponse($e, Response::HTTP_BAD_REQUEST);
        }
    }

    public function triggerSICWorkflow(SICWorkflowRequest $request)
    {
        return $this->apiService->triggerSICWorkflow($request);
    }

    public function evaluateTier(EvaluateTierRequest $request)
    {
        return $this->apiService->evaluateTier($request);
    }

    public function createActivity(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            ActivityApiRequest::rules(),
            ActivityApiRequest::messages()
        );

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }
        return $this->apiService->createActivity($request);
    }
    public function getActivity(Request $request)
    {
        return $this->apiService->getActivity($request);
    }

    public function inboundEmailsHook()
    {
        return $this->inboundEmailsHookService->process();
    }

    public function handleZeroPlansEmail(HandleZeroPlansRequest $request)
    {
        return $this->apiService->handleZeroPlansEmail($request);
    }
}
