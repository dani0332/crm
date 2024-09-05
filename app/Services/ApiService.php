<?php

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Factories\AllocationFactory;
use App\Http\Requests\ActivityApiRequest;
use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\EvaluateTierRequest;
use App\Http\Requests\HandleZeroPlansRequest;
use App\Http\Requests\SICWorkflowRequest;
use App\Models\Activities;
use App\Models\Customer;
use App\Models\HealthQuote;
use App\Models\MyAlFredUser;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Models\TravelQuote;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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
        $allocationStrategy = AllocationFactory::createStrategy($allocationType, $allocationId, $teamId);
        if (is_null($allocationStrategy)) {
            info('-- Exception against - allocationType: '.$allocationId.' and allocationId: '.$allocationId.' --');
            throw new InvalidArgumentException("Allocation strategy for type '$allocationType -- $allocationId' not found.");
        }
        $responsePayload = $allocationStrategy->executeSteps($overrideAdvisorId, $teamId, $tierOnly);
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

    public function createActivity($request)
    {
        $validator = Validator::make(
            $request->all(),
            ActivityApiRequest::rules(),
            ActivityApiRequest::messages()
        );

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        $existingActivity = Activities::where('quote_uuid', $request->entityUId)
            ->Where('status', 0)
            ->Where('source', LeadSourceEnum::INSTANT_ALFRED)
            ->first();
        if ($existingActivity) {
            return response()->json(['message' => 'An existing activity was found. Please Mark Done the current activity before creating a new one.'], 409);
        }
        $modelType = $request->quoteTypeId
            ? QuoteType::select('code')->find($request->quoteTypeId)
            : null;
        $record = '';
        if (isset($request->entityUId) && $modelType && ! checkPersonalQuotes($modelType->code)) {
            $record = app(CRUDService::class)->getEntity($modelType->code, $request->entityUId);
        } else {
            $record = PersonalQuote::where('uuid', $request->entityUId)->first();
        }
        if (is_null($record) || is_null($record->advisor_id)) {
            return response()->json(['message' => 'No advisor has been assigned to this lead.'], 404);
        }
        app(ActivitiesService::class)->createApiActivity($request, $record, $modelType);

        return response()->json(['message' => 'Activity has been Created'], 200);

    }

    public function getActivity($request)
    {
        $uuid = $request->entityUId;

        if (empty($uuid)) {
            return response()->json(['message' => 'Entity UUID Not Found'], 404);
        }

        $activity = Activities::where('quote_uuid', $request->entityUId)
            ->Where('source', LeadSourceEnum::INSTANT_ALFRED)
            ->latest()->first();

        if (! $activity) {
            return response()->json(['message' => 'Activity Not Found'], 404);
        }

        return response()->json([
            'message' => 'Activity Found',
            'activity' => $activity,
        ], 200);
    }

}
