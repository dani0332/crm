<?php

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Factories\AllocationFactory;
use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\EvaluateTierRequest;
use App\Http\Requests\SICWorkflowRequest;
use App\Jobs\SendOCBIntroEmailJob;
use App\Models\Customer;
use App\Models\HealthQuote;
use App\Models\MyAlFredUser;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

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
            return $this->triggerOCBOnly($allocationId);
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
        $assignedAdvisorId = $allocationStrategy->executeSteps($overrideAdvisorId);
        $responseData = ['assignedAdvisorId' => $assignedAdvisorId];
        info('------ Lead allocation request completed to assign advisor only for '.$allocationId.' ------');

        return apiResponse($responseData, Response::HTTP_OK, 'Advisor assigned successfully!');

    }

    private function triggerOCBOnly($allocationId)
    {
        info('------ Lead allocation request received to send OCB only for '.$allocationId.' ------');
        SendOCBIntroEmailJob::dispatch($allocationId, null, false);
        info('------ Lead allocation request completed to send OCB only for '.$allocationId.' ------');

        return apiResponse(null, Response::HTTP_OK, 'OCB email triggered successfully!');
    }

    private function performLeadAllocation($allocationType, $allocationId, $teamId)
    {
        info('------ Lead allocation started for lead : '.$allocationId.' ------');
        $allocationStrategy = AllocationFactory::createStrategy($allocationType, $allocationId, $teamId);
        $assignedAdvisorId = $allocationStrategy->executeSteps();
        info('------ Lead allocation ended for lead : '.$allocationId.' ------');
        $responseData = ['assignedAdvisorId' => $assignedAdvisorId];

        return apiResponse($responseData, Response::HTTP_OK, 'Lead allocated successfully!');
    }

    public function triggerSICWorkflow(SICWorkflowRequest $request)
    {
        if($request->quoteType == QuoteTypes::HEALTH) {
            info('------ SIC workflow trigger request received for lead : '.$request->quoteUuid.' ------');
        }
        else {
        info('------ SIC workflow trigger request received for lead : '.$request->quoteUuid.' ------');
        SendOCBIntroEmailJob::dispatch($request->quoteUuid, null, true);
        info('------ SIC workflow trigger request completed for lead : '.$request->quoteUuid.' ------');
        }

        return apiResponse(null, Response::HTTP_OK, 'SIC workflow triggered successfully!');
    }

    public function evaluateTier(EvaluateTierRequest $request)
    {
        $allocationType = $request->input('quoteTypeId');
        $allocationId = $request->input('quoteUUID');

        info('------ Lead allocation request received to evaluate tier only for '.$allocationId.' ------');
        $allocationStrategy = AllocationFactory::createStrategy($allocationType, $allocationId);
        $tierId = $allocationStrategy->executeSteps(false, false, true);
        $responseData = ['assignedTierId' => $tierId];
        info('------ Lead allocation request completed to evaluate tier only for '.$allocationId.' ------');

        return apiResponse($responseData, Response::HTTP_OK, 'Tier assigned successfully!');
    }
}
