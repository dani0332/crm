<?php

namespace App\Http\Controllers\API;

use App\Enums\quoteBusinessTypeCode;
use App\Enums\quoteTypeCode;
use App\Events\PaymentNotifications;
use App\Http\Controllers\Controller;
use App\Http\Requests\APiFetchUrl;
use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\EvaluateTierRequest;
use App\Http\Requests\SICWorkflowRequest;
use App\Services\ApiService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    use GenericQueriesAllLobs;

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

    public function quotePaymentStatusUpdated(Request $request)
    {
        if (is_numeric($request->quoteType)) {
            return response()->json(['message' => 'Quote Type Not Valid'], 403);
        }
        $model = $this->getModelObject(strtolower($request->quoteType));
        $url = url('/');

        if (is_numeric($request->quoteId)) {
            $model = $model::find($request->quoteId);
        } else {
            $model = $model::where('uuid', $request->quoteId)->first();
        }

        if ($request->quoteType == quoteTypeCode::Business) {
            if ($model->business_type_of_insurance_id == quoteBusinessTypeCode::getId(quoteBusinessTypeCode::groupMedical)) {
                $url .= "/medical/amt/$model->uuid";
            } else {
                $url .= "/quotes/business/$model->uuid";
            }
        } else {
            $url .= '/quotes/'.strtolower($request->quoteType).'/'.$model->uuid;
        }
        if ($model->advisor_id === null) {
            return response()->json(['message' => 'No Advisor Assign to this Lead.'], 403);
        }

        event(new PaymentNotifications($model, $url));

        return response()->json(['message' => 'Payment notification successfully send to advisor!'], 200);
    }

    public function triggerSICWorkflow(SICWorkflowRequest $request)
    {
        return $this->apiService->triggerSICWorkflow($request);
    }

    public function evaluateTier(EvaluateTierRequest $request)
    {
        return $this->apiService->evaluateTier($request);
    }

}
