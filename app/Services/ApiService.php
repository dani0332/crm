<?php

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\EvaluateTierRequest;
use App\Http\Requests\HandleZeroPlansRequest;
use App\Http\Requests\SendHealthApplyNowEmailRequest;
use App\Http\Requests\SICWorkflowRequest;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Jobs\SendHealthOCBIntroEmailJob;
use App\Models\Customer;
use App\Models\HealthQuote;
use App\Models\MyAlFredUser;
use App\Models\TravelQuote;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ApiService
{
    public function fetchSignupUrl($request)
    {
        try {
            return $this->checkmyAlredLink($request->email, $request);
        } catch (Exception $e) {
            Log::error($e->getLine().' '.$e->getMessage().' '.$e->getFile());

            return response()->json(['message' => 'Something went wrong. Please try again later.'], 500);
        }
    }

    private function checkmyAlredLink($email, $request)
    {
        $customer = CustomerService::getCustomerByEmail($email);
        if ($customer) {
            $data = MyAlFredUser::select('signup_url', 'code')->where('customer_id', $customer->id)->latest()->first();
            if ($data) {
                return response()->json([
                    'message' => isset($data->signup_url) ? $data->signup_url : $data->code,
                ], 200);
            } else {
                if (! $customer->is_we_sent) {
                    return $this->generateSignupUrl($customer, $request);
                } else {
                    return response()->json(['message' => 'Signup url does not exists against the Customer'], 404);
                }
            }
        }

        return response()->json(['message' => 'Customer does not exists'], 404);
    }

    private function generateSignupUrl($customer)
    {
        $WEGenerateUrlResponse = BerlinService::getCustomerWeUrl();
        if (gettype($WEGenerateUrlResponse) == 'string') {
            Customer::where('id', $customer->id)->update(['is_we_sent' => true]);

            $newMyAlFredUser = new MyAlFredUser;
            $newMyAlFredUser->signup_url = $WEGenerateUrlResponse;
            $newMyAlFredUser->customer_id = $customer->id;
            $newMyAlFredUser->code = substr($WEGenerateUrlResponse, strpos($WEGenerateUrlResponse, 'signup/') + 7); // code;
            $newMyAlFredUser->source = 'IMCRM';
            $newMyAlFredUser->save();

            return response()->json(['message' => $newMyAlFredUser->signup_url], 500);
        } else {
            return response()->json(['message' => $WEGenerateUrlResponse], 500);
        }
    }

    public function sibHealthQuoteCallBack($code)
    {
        HealthQuote::where('code', $code)->update([
            'quote_status_id' => QuoteStatusEnum::InNegotiation,
            'quote_status_date' => now(),
        ]);
    }

    public function isLeadAllocationEndpointDisabled()
    {
        return config('services.lead_allocation.disabled');
    }

    public function processAssignLead(AssignLeadRequest $request)
    {
        // Extract request parameters
        $allocationType = $request->input('quoteTypeId');
        $allocationId = $request->input('quoteUUID');
        $assignAdvisor = $request->input('reAssignAdvisor', false);
        $triggerOCB = $request->input('triggerOCB', false);
        $teamId = $request->input('teamId', false);

        // Handle different scenarios based on request parameters
        if ($assignAdvisor && ! $triggerOCB) {
            return $this->assignAdvisorOnly($allocationType, $allocationId);
        }

        if (! $assignAdvisor && $triggerOCB) {
            return $this->triggerOCBOnly($allocationId, $allocationType);
        }

        if (! $assignAdvisor && ! $triggerOCB) {
            return $this->performLeadAllocation($allocationType, $allocationId, $teamId);
        }

        return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Invalid request');
    }

    private function assignAdvisorOnly($allocationType, $allocationId)
    {
        info('------ Lead allocation request received to assign advisor only for '.$allocationId.' ------');
        $responsePayload = $this->executeAllocation($allocationType, $allocationId, false, false, true);
        info('------ Lead allocation request completed to assign advisor only for '.$allocationId.' ------');

        return apiResponse($responsePayload['data'], Response::HTTP_OK, $responsePayload['message']);
    }

    private function triggerOCBOnly($quoteUUID, $quoteTypeId = QuoteTypeId::Car)
    {
        if (! $quoteTypeId) {
            $quoteTypeId = QuoteTypeId::Car;
        }
        $quoteType = QuoteTypes::getName($quoteTypeId);
        if (! $quoteType) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Invalid Quote Type!');
        }

        $ocbEmailJob = $quoteType?->ocbEmailJob();
        if ($ocbEmailJob) {
            info("------ Lead allocation request received to send OCB only for {$quoteUUID} ------");
            dispatch(new $ocbEmailJob($quoteUUID, null, false));
            info("------ Lead allocation request completed to send OCB only for {$quoteUUID} ------");
        }

        return apiResponse(null, Response::HTTP_OK, 'OCB email triggered successfully!');
    }

    private function performLeadAllocation($allocationType, $leadId, $teamId)
    {
        info('------ Lead allocation started for lead : '.$leadId.' ------');
        $responsePayload = $this->executeAllocation($allocationType, $leadId, $teamId);
        info('------ Lead allocation ended for lead '.$leadId.' ------');

        return apiResponse($responsePayload['data'], Response::HTTP_OK, $responsePayload['message']);
    }

    public function triggerSICWorkflow(SICWorkflowRequest $request)
    {
        if (isset($request->quoteTypeId) && $request->quoteTypeId == QuoteTypes::HEALTH->id()) {

            info('------ Health SIC workflow trigger request received for  lead : '.($request->quoteUuid ?? '').' ------');
            SendHealthOCBIntroEmailJob::dispatch($request->quoteUuid, null, true);
            info('------ Health SIC workflow trigger request completed for lead : '.$request->quoteUuid.' ------');

            return apiResponse(null, Response::HTTP_OK, 'SIC workflow triggered successfully!');
        } else {
            info('------ SIC workflow trigger request received for  lead : '.($request->quoteUuid ?? '').' ------');

            $quoteTypeId = QuoteTypeId::Car;
            if ($request->has('quoteTypeId')) {
                $quoteTypeId = $request->quoteTypeId;
            }
            $quoteType = QuoteTypes::getName($quoteTypeId);
            if (! $quoteType) {
                info("Invalid Quote Type ID {$quoteTypeId} for uuid : {$request->quoteUuid}");

                return apiResponse(null, Response::HTTP_NOT_FOUND, 'Invalid Quote Type!');
            }

            $lead = $quoteType?->model()->where('uuid', $request->quoteUuid)->first();

            if (! $lead) {
                info("Lead not found: {$request->quoteUuid} for quoteTypeId: {$quoteTypeId}");

                return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Lead not found');
            }

            if ($lead->sic_flow_enabled) {
                info("SIC workflow is enabled on this lead already for uuid: {$lead->uuid}");

                return apiResponse(null, Response::HTTP_OK, 'SIC workflow already enabled!');
            } else {
                $lead->sic_flow_enabled = true;
                $lead->save();

                $ocbEmailJob = $quoteType?->ocbEmailJob();
                if ($ocbEmailJob) {
                    info("------ Going to Trigger Workflow for lead : {$request->quoteUuid} ------");
                    dispatch(new $ocbEmailJob($request->quoteUuid, null, true, forceSicWorkflow: true));
                    info("------ SIC workflow trigger request completed for lead : {$request->quoteUuid} ------");

                    return apiResponse(null, Response::HTTP_OK, 'SIC workflow triggered successfully!');
                }
            }
        }

        return apiResponse(null, Response::HTTP_NOT_FOUND, 'OCB Email not found!');
    }

    public function evaluateTier(EvaluateTierRequest $request)
    {
        $allocationType = $request->input('quoteTypeId');
        $allocationId = $request->input('quoteUUID');

        info('------ Lead allocation request received to evaluate tier only for '.$allocationId.' ------');
        $responsePayload = $this->executeAllocation($allocationType, $allocationId, false, true);
        info('------ Lead allocation request completed to evaluate tier only for '.$responsePayload['tierId'].' ------');

        return apiResponse($responsePayload['data'], Response::HTTP_OK, $responsePayload['message']);
    }
    /**
     * This function use to allocate the lead to advisor on the basis of lead type Bike, Car, Health, Travel
     *
     * @param  string  $allocationType
     * @param  string  $allocationId
     * @param  bool  $teamId
     * @return void
     */
    private function executeAllocation($allocationType, $allocationId, $teamId = false, $tierOnly = false, $overrideAdvisorId = false)
    {
        $responsePayload = QuoteTypes::getName($allocationType)->allocate(uuid: $allocationId, teamId: $teamId, overrideAdvisorId: $overrideAdvisorId, tierOnly: $tierOnly);
        if (is_null($responsePayload)) {
            info('-- Exception against - allocationType: '.$allocationId.' and allocationId: '.$allocationId.' --');
            throw new InvalidArgumentException("Allocation strategy for type '$allocationType -- $allocationId' not found.");
        }

        $status = $responsePayload['status'];
        $rest = array_diff_key($responsePayload, array_flip(['status', 'message']));
        $message = $responsePayload['message'];
        if ((isset($rest['advisorId']) && $rest['advisorId'] == 0) || (isset($rest['tierId']) && $rest['tierId'] == 0)) {
            $message = (isset($rest['tierId']) && $rest['tierId'] == 0) ? 'Tier failed: '.$responsePayload['message'] : 'Allocation failed: '.$responsePayload['message'];
        }

        return [
            'data' => [
                'tierId' => $responsePayload['tierId'] ?? 0,
                'assignedAdvisorId' => $responsePayload['advisorId'] ?? 0,
                'status' => $status,
            ],
            'message' => $message,
        ];
    }

    public function handleZeroPlansEmail(HandleZeroPlansRequest $request)
    {
        $quoteType = QuoteTypes::getName($request->quoteTypeId);
        if (! $quoteType) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Invalid Quote Type!');
        }

        $lead = $quoteType?->model()->where('uuid', $request->quoteUuid)->first();

        if (! $lead) {
            return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Lead not found!');
        }

        if ($lead instanceof TravelQuote && $lead->isMultiTrip()) {
            info(self::class." - handleZeroPlansEmail: First OCB Email Skipped because it is a Multi Trip Lead uuid: {$lead->uuid}");

            return apiResponse(null, Response::HTTP_OK, 'First OCB Email Skipped because it is a Multi Trip Lead!');
        }

        $ocbEmailJob = $quoteType?->ocbEmailJob();

        if (! $ocbEmailJob) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'OCB Email not found!');
        }

        info("------ Handling First OCB email when 0 Plans : {$request->quoteUuid} ------");
        dispatch(new $ocbEmailJob($request->quoteUuid, null, handleZeroPlans: true));
        info("------ Triggered Job for First OCB email when 0 Plans : {$request->quoteUuid} ------");

        return apiResponse(null, Response::HTTP_OK, 'Email triggered successfully!');
    }

    public function sendHealthApplyNowEmail(SendHealthApplyNowEmailRequest $request)
    {
        $lead = HealthQuote::where('uuid', $request->quoteUuid)->first();

        if (! $lead) {
            return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Lead not found!');
        }

        if (! $lead->isApplyNowEmailSent()) {
            app(HealthEmailService::class)->initiateApplyNowEmail($lead);

            return apiResponse(null, Response::HTTP_OK, 'Email Sent');
        }

        return apiResponse(null, Response::HTTP_OK, 'Email Already Sent!');
    }

    public function quoteUpdated($data)
    {
        $quoteType = QuoteTypes::getName($data['quoteTypeId']);
        if (! $quoteType) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Invalid Quote Type!');
        }
        $model = $quoteType?->model();

        $quote = $model::where('uuid', $data['quoteUUID'])->first();
        if (! $quote) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Quote not found!');
        }

        // Sync Courier Quote with MACRM if Policy Issued
        SyncCourierQuoteWithMacrm::dispatch($quote, $quoteType?->id());

        return apiResponse(null, message: 'ok');
    }
}
