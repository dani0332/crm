<?php

namespace App\Http\Controllers\API;

use App\Enums\quoteTypeCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\APiFetchUrl;
use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\EvaluateTierRequest;
use App\Http\Requests\SICWorkflowRequest;
use App\Services\ActivitiesService;
use App\Services\ApiService;
use App\Services\CRUDService;
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
        if ($request->input('title') === null || $request->input('title') === '') {
            return response()->json(['message' => 'Activity Title Required'], 422);
        }
        if ($request->input('description') === null || $request->input('description') === '') {
            return response()->json(['message' => 'Activity Description Required'], 422);
        }
        if ($request->input('due_date') === null || $request->input('due_date') === '') {
            return response()->json(['message' => 'Activity Due Date Required'], 422);
        }
        if ($request->input('assignee_id') === null || $request->input('assignee_id') === '') {
            return response()->json(['message' => 'Activity Assignee ID Required'], 422);
        }
        if ($request->input('entityUId') === null || $request->input('entityUId') === '') {
            return response()->json(['message' => 'Entity UUID Required'], 422);
        }
        if ($request->input('entityId') === null || $request->input('entityId') === '') {
            return response()->json(['message' => 'Entity ID Required'], 422);
        }
        if ($request->input('modelType') === null || $request->input('modelType') === '') {
            return response()->json(['message' => 'Model Type Required'], 422);
        }
        $record = '';
        if (isset($request->entityId)) {
            $record = app(CRUDService::class)->getEntity($request->modelType, $request->entityUId);
        }
        app(ActivitiesService::class)->createActivity($request, $record);

        return response()->json(['message' => 'Activity has been Created'], 200);
    }
}
