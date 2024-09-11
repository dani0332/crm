<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\APiFetchUrl;
use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\EmailEventsRequest;
use App\Http\Requests\EvaluateTierRequest;
use App\Http\Requests\HandleZeroPlansRequest;
use App\Http\Requests\SICWorkflowRequest;
use App\Services\ApiService;
use App\Services\InboundEmailsHookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use App\Services\EmailStatusService;
use App\Models\HealthQuote;
use App\Enums\ProcessStatusCode;
use App\Models\EmailStatus;
use App\Services\BirdService;
use App\Models\QuoteFlowDetails;

class ApiController extends Controller
{
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

    public function birdInboundEmailsHook()
    {

        return $this->inboundEmailsHookService->handleBirdWebhook();
    }

    public function logFollowUpEvent(EmailEventsRequest $request)
    {

        $quote = HealthQuote::where('uuid', $request->uuid)->first();
        if(! $quote) {
            info("lead not found for uuid: {$request->uuid} time: ".now());
            return apiResponse([], Response::HTTP_NOT_FOUND, 'Lead not found');
        }
        if(!EmailStatus::where('email_status', ProcessStatusCode::SENT)
        ->where('msg_id', $request->message_id)
        ->where('quote_id', $quote->id)->exists()) {
            $request->quoteId = $quote->id;
            $request->customerEmail = $request->customer_email;
            $this->emailStatusService->addEmailStatus($request, $request->message_id, $request->subject, ProcessStatusCode::SENT);
            return apiResponse([], Response::HTTP_OK, 'Email event logged successfully');
        }
        else {
            return apiResponse([], Response::HTTP_OK, 'Email event already logged');
        }

    }

    public function stopFollowUpEvent(){
        $flowType = request('flowType');
        $quoteUID = request('uuid');

        info("getting request to stopFollowUpEvent Ref-ID: {$quoteUID} | FlowType: {$flowType} Time:" .now());
        $workflow = QuoteFlowDetails::where('quote_uuid', $quoteUID)
                                      ->where('flow_type', $flowType)
                                      ->first();
        if(! $workflow) {
            info("lead not found for uuid: {$quoteUID} | FlowType: {$flowType} | Time: ".now());
            return apiResponse([], Response::HTTP_NOT_FOUND, 'Lead not found');
        }
        $response =app(BirdService::class)->stopWorkFlow($workflow);
        return apiResponse([$response], Response::HTTP_OK, 'Email event stopped successfully');
    }
}
