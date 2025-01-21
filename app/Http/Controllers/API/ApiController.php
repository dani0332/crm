<?php

namespace App\Http\Controllers\API;

use App\Enums\QuoteTypes;
use App\Facades\Ken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\QuoteUpdatedRequest;
use App\Http\Requests\Api\UpdateLeadStatusRequest;
use App\Http\Requests\APiFetchUrl;
use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\BirdStopWorkFlowRequest;
use App\Http\Requests\BirdWebhookRequest;
use App\Http\Requests\EmailEventsRequest;
use App\Http\Requests\EvaluateTierRequest;
use App\Http\Requests\HandleZeroPlansRequest;
use App\Http\Requests\PaymentNotificationRequest;
use App\Http\Requests\SendHealthApplyNowEmailRequest;
use App\Http\Requests\SICWorkflowRequest;
use App\Jobs\FixQuoteStatusDate;
use App\Models\HealthQuote;
use App\Models\HealthQuotePlan;
use App\Models\QuoteFlowDetails;
use App\Services\ApiService;
use App\Services\BirdService;
use App\Services\EmailStatusService;
use App\Services\InboundEmailsHookService;
use App\Services\NotificationService;
use App\Services\QuoteStatusService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    use GenericQueriesAllLobs;

    public $apiService;
    public $inboundEmailsHookService;
    protected $emailStatusService;

    public function __construct(ApiService $apiService, InboundEmailsHookService $inboundEmailsHookService, EmailStatusService $emailStatusService)
    {
        $this->apiService = $apiService;
        $this->inboundEmailsHookService = $inboundEmailsHookService;
        $this->emailStatusService = $emailStatusService;
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

    public function quotePaymentStatusUpdated(PaymentNotificationRequest $request)
    {
        return app(NotificationService::class)->paymentStatusUpdate($request->quoteType, $request->quoteId);
    }

    public function triggerSICWorkflow(SICWorkflowRequest $request)
    {
        return $this->apiService->triggerSICWorkflow($request);
    }

    public function evaluateTier(EvaluateTierRequest $request)
    {
        return $this->apiService->evaluateTier($request);
    }

    public function inboundEmailsHook()
    {
        return $this->inboundEmailsHookService->process();
    }

    public function handleZeroPlansEmail(HandleZeroPlansRequest $request)
    {
        return $this->apiService->handleZeroPlansEmail($request);
    }

    public function birdInboundEmailsHook(BirdWebhookRequest $request)
    {
        return $this->inboundEmailsHookService->handleBirdWebhook($request);
    }

    public function logFollowUpEvent(EmailEventsRequest $request)
    {
        $response = app(EmailStatusService::class)->addBirdEmailStatus($request);

        return apiResponse([], Response::HTTP_OK, $response->message);
    }

    public function stopFollowUpEvent(BirdStopWorkFlowRequest $request)
    {
        $flowType = $request->flowType;
        $quoteUID = $request->uuid;
        $flowId = $request->flowId ?? null;
        info("getting request to stopFollowUpEvent Ref-ID: {$quoteUID} | FlowType: {$flowType} Time:".now());
        $workflow = QuoteFlowDetails::where('quote_uuid', $quoteUID)
            ->where('flow_type', $flowType)
            ->first();
        if (! $workflow) {
            info("lead not found for uuid: {$quoteUID} | FlowType: {$flowType} | Time: ".now());

            return apiResponse([], Response::HTTP_NOT_FOUND, 'Lead not found');
        }
        $response = app(BirdService::class)->stopWorkFlow($workflow, $flowId);

        return apiResponse(['response_body' => $response->body ?? null], Response::HTTP_OK, 'Email event stopped successfully');
    }

    // Temporary Endpoint - Will be Removed after fixing Quote Status Dates for all LOBs
    public function fixQuoteStatusDate()
    {
        $quoteType = QuoteTypes::getName(request()->quoteTypeId);

        if ($quoteType) {
            if (request('process')) {
                FixQuoteStatusDate::dispatch($quoteType, request('statuses'), request('chunkSize', 200));

                return apiResponse(null, Response::HTTP_OK, 'Fix Quote Status Date Job dispatched');
            } else {
                $records = $quoteType->model()->whereIn('quote_status_id', request('statuses'))->count();

                return apiResponse(null, Response::HTTP_OK, "Total Records are: {$records}");
            }
        }

        return apiResponse(null, Response::HTTP_OK, 'Invalid Quote Type');
    }

    // Temporary Endpoint - Will be Removed after analysing health data
    public function analyseHealthData()
    {
        $leads = HealthQuote::with('advisor')->whereBetween('created_at', [Carbon::parse(request('start')), Carbon::parse(request('end'))])->latest('id')->get();

        $getMaxPricePlan = function ($lead) {
            $healthQuotePlan = HealthQuotePlan::where('health_quote_request_id', $lead->id)->first();
            if ($healthQuotePlan) {
                $payload = $healthQuotePlan->plan_payload ? json_decode($healthQuotePlan->plan_payload) : null;
                if ($payload && property_exists($payload, 'plans')) {
                    return collect($payload->plans)->map(function ($plan) {
                        $premium = collect($plan->ratesPerCopay)->max('premium');

                        return [
                            'id' => property_exists($plan, 'id') ? $plan->id : null,
                            'planCode' => property_exists($plan, 'planCode') ? $plan->planCode : null,
                            'name' => property_exists($plan, 'name') ? $plan->name : null,
                            'premium' => $premium,
                        ];
                    })->sortByDesc('premium')->first();
                }
            }

            return null;
        };

        $data = collect([]);
        foreach ($leads as $lead) {
            $maxPricePlan = $getMaxPricePlan($lead);

            if ($maxPricePlan) {
                $data->push([
                    'id' => $lead->id,
                    'uuid' => $lead->uuid,
                    'health_team_type' => $lead->health_team_type,
                    'price_starting_from' => $lead->price_starting_from,
                    'premium' => $lead->premium,
                    'advisor_id' => $lead->advisor_id,
                    'advisor_name' => $lead->advisor?->name,
                    'plan_id' => $maxPricePlan['id'],
                    'plan_code' => $maxPricePlan['planCode'],
                    'plan_name' => $maxPricePlan['name'],
                    'max_premium' => $maxPricePlan['premium'],
                    'created_at' => $lead->created_at,
                ]);
            }
        }

        return response()->json($data);
    }

    public function sendHealthApplyNowEmail(SendHealthApplyNowEmailRequest $request)
    {
        return $this->apiService->sendHealthApplyNowEmail($request);
    }

    public function quoteUpdated(QuoteUpdatedRequest $request)
    {
        return $this->apiService->quoteUpdated($request->validated());
    }

    public function updateQuoteStatus(UpdateLeadStatusRequest $request)
    {
        $quoteTypeId = QuoteTypes::getIdFromValue($request->quote_type);
        app(QuoteStatusService::class)->markQuoteAsStale($quoteTypeId, $request->quote_uuid);

        return response()->json(['success' => true, 'message' => 'Lead status updated successfully']);
    }

    public function Ken2Connectivity()
    {
        return Ken::renewalRequest('/get-connectivity-check', 'get');
    }
}
