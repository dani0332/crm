<?php

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Factories\AllocationFactory;
use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\EvaluateTierRequest;
use App\Http\Requests\HandleZeroPlansRequest;
use App\Http\Requests\SICWorkflowRequest;
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

            $newMyAlFredUser = new MyAlFredUser();
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
        $allocationStrategy = AllocationFactory::createStrategy($allocationType, $allocationId);
        $overrideAdvisorId = true;
        $responsePayload = $allocationStrategy->executeSteps($overrideAdvisorId);
        $status = $responsePayload['status'];
        $rest = array_diff_key($responsePayload, array_flip(['status', 'message']));
        $message = $rest['message'];
        if ($rest['advisorId'] == 0) {
            $message = 'Allocation failed: '.$rest['message'];
        }

        info('------ Lead allocation request completed to assign advisor only for '.$allocationId.' ------');

        return apiResponse($rest, $status, $message);
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

    private function performLeadAllocation($allocationType, $allocationId, $teamId)
    {
        info('------ Lead allocation started for lead : '.$allocationId.' ------');
        $allocationStrategy = AllocationFactory::createStrategy($allocationType, $allocationId, $teamId);
        if (is_null($allocationStrategy)) {
            info('-- Exception against - allocationType: '.$allocationId.' and allocationId: '.$allocationId.' --');
            throw new InvalidArgumentException("Allocation strategy for type '$allocationType -- $allocationId' not found.");
        }
        $responsePayload = $allocationStrategy->executeSteps();
        $status = $responsePayload['status'];
        $rest = array_diff_key($responsePayload, array_flip(['status', 'message']));
        $message = $rest['message'];
        if ($rest['advisorId'] == 0 || $rest['tierId'] == 0) {
            $message = 'Allocation failed: '.$rest['message'];
        }

        info('------ Lead allocation ended for lead '.$allocationId.' ------');

        return apiResponse($rest, $status, $message);
    }

    public function triggerSICWorkflow(SICWorkflowRequest $request)
    {
        $quoteTypeId = QuoteTypeId::Car;
        if ($request->has('quoteTypeId')) {
            $quoteTypeId = $request->quoteTypeId;
        }
        $quoteType = QuoteTypes::getName($quoteTypeId);
        if (! $quoteType) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Invalid Quote Type!');
        }

        if (! $quoteType?->model()->where('uuid', $request->quoteUuid)->exists()) {
            return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Lead not found!');
        }

        $ocbEmailJob = $quoteType?->ocbEmailJob();
        if ($ocbEmailJob) {
            info("------ SIC workflow trigger request received for lead : {$request->quoteUuid} ------");
            dispatch(new $ocbEmailJob($request->quoteUuid, null, true));
            info("------ SIC workflow trigger request completed for lead : {$request->quoteUuid} ------");

            return apiResponse(null, Response::HTTP_OK, 'SIC workflow triggered successfully!');
        }

        return apiResponse(null, Response::HTTP_NOT_FOUND, 'OCB Email not found!');
    }

    public function evaluateTier(EvaluateTierRequest $request)
    {
        $allocationType = $request->input('quoteTypeId');
        $allocationId = $request->input('quoteUUID');

        info('------ Lead allocation request received to evaluate tier only for '.$allocationId.' ------');
        $allocationStrategy = AllocationFactory::createStrategy($allocationType, $allocationId);
        $responsePayload = $allocationStrategy->executeSteps(false, false, true);
        $status = $responsePayload['status'];
        $rest = array_diff_key($responsePayload, array_flip(['status', 'message']));
        $message = $rest['message'];
        if ($rest['tierId'] == 0) {
            $message = 'Tier failed: '.$rest['message'];
        }

        info('------ Lead allocation request completed to evaluate tier only for '.$rest['tierId'].' ------');

        return apiResponse($rest, $status, $message);
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
}
