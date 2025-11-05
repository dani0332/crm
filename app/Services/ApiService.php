<?php

namespace App\Services;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Events\DocumentNotificationEvent;
use App\Http\Requests\AIGWorkflowRequest;
use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\DocumentNotificationRequest;
use App\Http\Requests\EvaluateTierRequest;
use App\Http\Requests\HandleZeroPlansRequest;
use App\Http\Requests\SendHealthApplyNowEmailRequest;
use App\Http\Requests\SICWhatsappRequest;
use App\Http\Requests\SICWorkflowRequest;
use App\Http\Requests\TravelAIGWorkflowRequest;
use App\Jobs\AIGWorkflowJob;
use App\Jobs\MACRM\SyncCourierQuoteWithMacrm;
use App\Jobs\SendHealthOCBIntroEmailJob;
use App\Jobs\SendHealthSICWAFollowupJob;
use App\Models\Customer;
use App\Models\HealthQuote;
use App\Models\MyAlFredUser;
use App\Models\TravelQuote;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use App\Models\CarQuote;
use App\Services\WAServices\CarWAService;
use App\Jobs\CarMissingDocReminderJob;
use App\Enums\DocumentTypeCode;

class ApiService
{
    private const LEAD_NOT_FOUND = 'Lead not found!';
    private const QUOTE_NOT_FOUND = 'Quote not found!';
    public function fetchSignupUrl($request)
    {
        try {
            return $this->checkmyAlredLink($request->email, $request);
        } catch (Exception $e) {
            LoggerService::error($e->getLine().' '.$e->getMessage().' '.$e->getFile());

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
        return config('constants.DISABLE_LEAD_ALLOCATION_ENDPOINT') == '1';
    }

    public function processAssignLead(AssignLeadRequest $request)
    {
        // Extract request parameters
        $allocationType = $request->input('quoteTypeId');
        $allocationId = $request->input('quoteUUID');
        $assignAdvisor = $request->input('reAssignAdvisor', false);
        $triggerOCB = $request->input('triggerOCB', false);
        $teamId = $request->input('teamId', false);
        $sicAdvisorRequested = $request->input('sicAdvisorRequested', false);

        $lead = QuoteTypes::getName($allocationType)?->model()?->where('uuid', $allocationId)?->first();
        if ($lead) {
            LoggerService::startQuoteLogging($lead, LoggerFeatureEnum::ALLOCATION);
        }

        // Handle different scenarios based on request parameters
        if ($assignAdvisor && ! $triggerOCB) {
            return $this->assignAdvisorOnly($allocationType, $allocationId);
        }

        if (! $assignAdvisor && $triggerOCB) {
            return $this->triggerOCBOnly($allocationId, $allocationType);
        }

        if (! $assignAdvisor && ! $triggerOCB) {
            return $this->performLeadAllocation($allocationType, $allocationId, $teamId, $sicAdvisorRequested);
        }

        return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Invalid request');
    }

    private function assignAdvisorOnly($allocationType, $allocationId)
    {
        LoggerService::info('------ Lead allocation request received to assign advisor only for '.$allocationId.' ------');
        $responsePayload = $this->executeAllocation($allocationType, $allocationId, false, false, true);
        LoggerService::info('------ Lead allocation request completed to assign advisor only for '.$allocationId.' ------');

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
            LoggerService::info("------ Lead allocation request received to send OCB only for {$quoteUUID} ------");
            dispatch(new $ocbEmailJob($quoteUUID, null, false));
            LoggerService::info("------ Lead allocation request completed to send OCB only for {$quoteUUID} ------");
        }

        return apiResponse(null, Response::HTTP_OK, 'OCB email triggered successfully!');
    }

    private function performLeadAllocation($allocationType, $leadId, $teamId, $sicAdvisorRequested = false)
    {
        LoggerService::info('------ Lead allocation started for lead : '.$leadId.' ------');
        $responsePayload = $this->executeAllocation($allocationType, $leadId, $teamId, false, false, $sicAdvisorRequested);
        LoggerService::info('------ Lead allocation ended for lead '.$leadId.' ------');

        return apiResponse($responsePayload['data'], Response::HTTP_OK, $responsePayload['message']);
    }

    public function triggerSICWorkflow(SICWorkflowRequest $request)
    {
        if (isset($request->quoteTypeId) && $request->quoteTypeId == QuoteTypes::HEALTH->id()) {

            LoggerService::info('------ Health SIC workflow trigger request received for  lead : '.($request->quoteUuid ?? '').' ------');
            SendHealthOCBIntroEmailJob::dispatch($request->quoteUuid, null, true);
            LoggerService::info('------ Health SIC workflow trigger request completed for lead : '.$request->quoteUuid.' ------');

            return apiResponse(null, Response::HTTP_OK, 'SIC workflow triggered successfully!');
        } else {
            LoggerService::info('------ SIC workflow trigger request received for  lead : '.($request->quoteUuid ?? '').' ------');

            $quoteTypeId = QuoteTypeId::Car;
            if ($request->has('quoteTypeId')) {
                $quoteTypeId = $request->quoteTypeId;
            }
            $quoteType = QuoteTypes::getName($quoteTypeId);
            if (! $quoteType) {
                LoggerService::warning("Invalid Quote Type ID {$quoteTypeId} for uuid : {$request->quoteUuid}");

                return apiResponse(null, Response::HTTP_NOT_FOUND, 'Invalid Quote Type!');
            }

            $lead = $quoteType?->model()->where('uuid', $request->quoteUuid)->first();

            if (! $lead) {
                LoggerService::warning("Lead not found: {$request->quoteUuid} for quoteTypeId: {$quoteTypeId}");

                return apiResponse(null, Response::HTTP_BAD_REQUEST, self::LEAD_NOT_FOUND);
            }

            if ($lead->sic_flow_enabled) {
                LoggerService::info("SIC workflow is enabled on this lead already for uuid: {$lead->uuid}");

                return apiResponse(null, Response::HTTP_OK, 'SIC workflow already enabled!');
            } else {
                $lead->sic_flow_enabled = true;
                $lead->save();

                $ocbEmailJob = $quoteType?->ocbEmailJob();
                if ($ocbEmailJob) {
                    LoggerService::info("------ Going to Trigger Workflow for lead : {$request->quoteUuid} ------");
                    dispatch(new $ocbEmailJob($request->quoteUuid, null, true, forceSicWorkflow: true));
                    LoggerService::info("------ SIC workflow trigger request completed for lead : {$request->quoteUuid} ------");

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

        LoggerService::info('------ Lead allocation request received to evaluate tier only for '.$allocationId.' ------');
        $responsePayload = $this->executeAllocation($allocationType, $allocationId, false, true);
        LoggerService::info('------ Lead allocation request completed to evaluate tier only for '.$responsePayload['data']['tierId'].' ------');

        return apiResponse($responsePayload['data'], Response::HTTP_OK, $responsePayload['message']);
    }
    /**
     * This function use to allocate the lead to advisor on the basis of lead type Bike, Car, Health, Travel
     *
     * @param  string  $allocationType
     * @param  string  $allocationId
     * @param  bool  $teamId
     * @param  bool  $tierOnly
     * @param  bool  $overrideAdvisorId
     * @param  bool  $sicAdvisorRequested
     * @return void
     */
    private function executeAllocation($allocationType, $allocationId, $teamId = false, $tierOnly = false, $overrideAdvisorId = false, $sicAdvisorRequested = false)
    {
        $responsePayload = QuoteTypes::getName($allocationType)->allocate(
            uuid: $allocationId,
            teamId: $teamId,
            overrideAdvisorId: $overrideAdvisorId,
            tierOnly: $tierOnly,
            sicAdvisorRequested: $sicAdvisorRequested
        );
        if (is_null($responsePayload)) {
            LoggerService::error('-- Exception against - allocationType: '.$allocationId.' and allocationId: '.$allocationId.' --');
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
                'tierName' => $responsePayload['tierName'] ?? null,
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
            return apiResponse(null, Response::HTTP_BAD_REQUEST, self::LEAD_NOT_FOUND);
        }

        if ($lead instanceof TravelQuote && $lead->isMultiTrip()) {
            LoggerService::info(self::class." - handleZeroPlansEmail: First OCB Email Skipped because it is a Multi Trip Lead uuid: {$lead->uuid}");

            return apiResponse(null, Response::HTTP_OK, 'First OCB Email Skipped because it is a Multi Trip Lead!');
        }

        $ocbEmailJob = $quoteType?->ocbEmailJob();

        if (! $ocbEmailJob) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'OCB Email not found!');
        }

        LoggerService::info("------ Handling First OCB email when 0 Plans : {$request->quoteUuid} ------");
        dispatch(new $ocbEmailJob($request->quoteUuid, null, handleZeroPlans: true));
        LoggerService::info("------ Triggered Job for First OCB email when 0 Plans : {$request->quoteUuid} ------");

        return apiResponse(null, Response::HTTP_OK, 'Email triggered successfully!');
    }

    public function sendHealthApplyNowEmail(SendHealthApplyNowEmailRequest $request)
    {
        LoggerService::startQuoteLogging($request->quoteUuid);
        LoggerService::info('------ Request received to send Apply Now email for lead ------');
        $lead = HealthQuote::where('uuid', $request->quoteUuid)->first();

        if (! $lead) {
            return apiResponse(null, Response::HTTP_BAD_REQUEST, self::LEAD_NOT_FOUND);
        }

        if ($lead->isAUHLead() || ($lead->isAUHLead(false) && $lead->isLeadSourceRevivalOrInsuranceWallet())) {
            return apiResponse(null, Response::HTTP_OK, 'AUH and Revival/Insurance Wallet Leads are not allowed to send OCA Email!');
        }

        if (! $lead->isApplyNowEmailSent()) {
            app(HealthEmailService::class)->initiateApplyNowEmail($lead);

            return apiResponse(null, Response::HTTP_OK, 'Email Sent');
        }

        LoggerService::info('------ Apply Now email already sent for lead ------');

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
            return apiResponse(null, Response::HTTP_NOT_FOUND, self::QUOTE_NOT_FOUND);
        }

        // Sync Courier Quote with MACRM if Policy Issued
        SyncCourierQuoteWithMacrm::dispatch($quote, $quoteType?->id());

        return apiResponse(null, message: 'ok');
    }

    public function triggerAIGWorkflow(AIGWorkflowRequest $request)
    {
        info('------ AIG workflow trigger request received for lead : '.($request->quoteUuid ?? '').' ------');

        try {
            $quoteTypeId = $request->quoteTypeId;
            $quoteUuid = $request->quoteUuid;

            if (! $quoteUuid) {
                return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Quote UUID is required!');
            }

            // Get the quote type if provided
            if ($quoteTypeId) {
                $quoteType = QuoteTypes::getName($quoteTypeId);
                if (! $quoteType) {
                    info("Invalid Quote Type ID {$quoteTypeId} for uuid : {$quoteUuid}");

                    return apiResponse(null, Response::HTTP_NOT_FOUND, 'Invalid Quote Type!');
                }

                // Verify the quote exists
                $quote = $quoteType->model()->where('uuid', $quoteUuid)->first();
                if (! $quote) {
                    info("Quote not found with uuid: {$quoteUuid} for quoteTypeId: {$quoteTypeId}");

                    return apiResponse(null, Response::HTTP_NOT_FOUND, self::QUOTE_NOT_FOUND);
                }
            }

            // Dispatch the AIG workflow job
            info("------ Dispatching AIG workflow job for lead : {$quoteUuid} ------");
            dispatch(new AIGWorkflowJob($quoteUuid, $quoteTypeId));
            info("------ AIG workflow trigger request completed for lead : {$quoteUuid} ------");

            return apiResponse(null, Response::HTTP_OK, 'AIG workflow triggered successfully!');
        } catch (\Exception $e) {
            info("------ AIG workflow trigger failed: {$e->getMessage()} ------");
            Log::error($e);

            return apiResponse(null, Response::HTTP_INTERNAL_SERVER_ERROR, 'AIG workflow trigger failed!');
        }
    }

    public function triggerTravelAIGWorkflow(TravelAIGWorkflowRequest $request)
    {
        LoggerService::startQuoteLogging(QuoteTypes::TRAVEL->refId($request->quoteUuid));
        LoggerService::info('------ Travel AIG workflow trigger request received ------');

        try {
            $quoteTypeId = $request->quoteTypeId;
            $quoteUuid = $request->quoteUuid;

            if (! $quoteUuid) {
                return apiResponse(null, Response::HTTP_BAD_REQUEST, 'Quote UUID is required!');
            }

            // Get the quote type if provided, or default to Travel
            if ($quoteTypeId) {
                $quoteType = QuoteTypes::getName($quoteTypeId);
            } else {
                $quoteType = QuoteTypes::TRAVEL;
                $quoteTypeId = QuoteTypes::TRAVEL->id();
            }

            if (! $quoteType) {
                LoggerService::info('Invalid Quote Type');

                return apiResponse(null, Response::HTTP_NOT_FOUND, 'Invalid Quote Type!');
            }

            // Get model class for the quote type
            $modelClass = $quoteType->model();

            // Verify the quote exists
            $quote = $modelClass->where('uuid', $quoteUuid)->first();
            if (! $quote) {
                LoggerService::info('Quote not found');

                return apiResponse(null, Response::HTTP_NOT_FOUND, self::QUOTE_NOT_FOUND);
            }

            // Atomic update - only proceeds if travel_aig_flow_executed_at is null
            $updated = $modelClass->where('uuid', $quoteUuid)
                ->whereNull('travel_aig_flow_executed_at')
                ->update(['travel_aig_flow_executed_at' => now()]);

            if ($updated) {
                // Only dispatch the job if we successfully updated the record
                LoggerService::info('------ Dispatching Travel AIG workflow job ------');
                dispatch(new \App\Jobs\TravelAIGWorkflowJob($quoteUuid, $quoteType));
                LoggerService::info('------ Travel AIG workflow trigger request completed ------');

                return apiResponse(null, Response::HTTP_OK, 'Travel AIG workflow triggered successfully!');
            } else {
                // The workflow has already been triggered
                LoggerService::info('------ Travel AIG workflow already triggered for this quote ------');

                return apiResponse(null, Response::HTTP_OK, 'Travel AIG workflow already triggered for this quote');
            }
        } catch (\Exception $e) {
            LoggerService::error('Travel AIG workflow trigger failed', exception: $e);

            return apiResponse(null, Response::HTTP_INTERNAL_SERVER_ERROR, 'Travel AIG workflow trigger failed!');
        }
    }

    public function triggerSICWhatsapp(SICWhatsappRequest $request)
    {
        //  Implement triggerSICWhatsapp
        $quoteType = QuoteTypes::getName($request->quoteTypeId);
        switch ($quoteType) {
            case QuoteTypes::HEALTH:
                $lead = HealthQuote::where('uuid', $request->quoteUuid)->first();
                if (! $lead) {
                    return apiResponse(null, Response::HTTP_NOT_FOUND, self::LEAD_NOT_FOUND);
                }
                if (getWhatsappConsent(QuoteTypes::HEALTH, $lead->uuid)) {
                    if (! app(BirdService::class)->isFollowupExecuted($lead->uuid, QuoteTypes::HEALTH->id(), QuoteFlowType::SIC_HEALTH_FOLLOWUPS_WA->value)) {
                        SendHealthSICWAFollowupJob::dispatch($lead->uuid)->delay(now()->addSeconds(50));
                    } else {
                        LoggerService::info('SIC Health Followups WA already executed');

                        return apiResponse(null, Response::HTTP_OK, 'SIC WhatsApp workflow already executed for this lead!');
                    }
                } else {
                    return apiResponse(null, Response::HTTP_OK, 'WhatsApp consent not given for this lead!');
                }
                break;
            default:
                return apiResponse(null, Response::HTTP_NOT_FOUND, 'Invalid Quote Type!');
        }

        return apiResponse(null, Response::HTTP_OK, 'SIC WhatsApp workflow triggered successfully!');
    }

    public function documentNotification(DocumentNotificationRequest $request)
    {
        try {
            $notificationData = [
                'quoteUID' => $request->quoteUID,
                'status' => $request->status,
            ];

            event(new DocumentNotificationEvent($notificationData));

            return apiResponse(null, Response::HTTP_OK, 'Document notification received!');
        } catch (Exception $e) {
            LoggerService::error('Document notification processing failed', exception: $e);

            return apiResponse(null, Response::HTTP_INTERNAL_SERVER_ERROR, 'Document notification processing failed!');
        }
    }

    public function missingDocsReminder($quoteUuid)
    {
        try {
            LoggerService::info(self::class.': Missing docs reminder has been initiated');
            if(app(BirdService::class)->isFollowupExecuted($quoteUuid, QuoteTypes::CAR->id(), QuoteFlowType::CAR_MISSING_DOC_REMINDER->value)) {
                LoggerService::info(self::class.': Missing docs reminder already executed');
                return ['success' => false, 'message' => 'Missing docs reminder already executed'];
            }
            $quote = CarQuote::where('uuid', $quoteUuid)->first();
            LoggerService::startQuoteLogging($quoteUuid);
            if (! $quote) {
                LoggerService::info(self::class.': Quote not found');
                return ['success' => false, 'message' => 'Quote not found'];
            }
            CarMissingDocReminderJob::dispatch($quoteUuid)->delay(now()->addSeconds(50));
            return ['success' => true, 'message' => 'Missing docs reminder has been sent to the customer'];
        } catch (\Exception $e) {
            LoggerService::error(self::class.': Missing docs reminder failed', exception: $e);
            return ['success' => false, 'message' => 'Missing docs reminder failed: '.$e->getMessage()];
        }
    }
    public function verifyMissingDocs($quoteUuid, $quoteType)
    {
        try {
            LoggerService::info(self::class.': Verify missing docs has been initiated');
            switch ($quoteType) {
                case QuoteTypes::CAR->value:
                    $quote = CarQuote::where('uuid', $quoteUuid)->first();
                    if (! $quote) {
                        LoggerService::info(self::class.': Quote not found');
                        return ['success' => false, 'message' => 'Quote not found','isDocumentMissing' => null];
                    }
                    $requiredDocuments = [
                        DocumentTypeCode::EMIRATES_ID,
                        DocumentTypeCode::REGISTRATION_CARD_MULKIYA,
                        DocumentTypeCode::DRIVING_LICENSE
                    ];
                    $leadDocuments = $quote->documents()
                        ->whereIn('document_type_code', $requiredDocuments)
                        ->get()
                        ->keyBy('document_type_code');
           
                    // Check if all required documents are present and complete
                    $missingOrIncomplete = collect($requiredDocuments)->map(function ($docType) use ($leadDocuments) {
                        $doc = $leadDocuments->get($docType);
                        return ['document_type_code' => $docType, 'is_complete' => $doc ? true : false];
                    })->values();
              
                    if ($missingOrIncomplete->every(function ($item) {
                        return $item['is_complete'];
                    })) {
                        return ['success' => true, 'message' => 'All documents are present and complete','isDocumentMissing' => false,'missingDocuments' => $missingOrIncomplete];
                    } else {
                        return ['success' => false, 'message' => 'Missing documents ','isDocumentMissing' => true,'missingDocuments' => $missingOrIncomplete];
                    }

                    break;
            }
        } catch (\Exception $e) {
            LoggerService::error(self::class.': Verify missing docs failed', exception: $e);
            return ['success' => false, 'message' => 'Verify missing docs failed: '.$e->getMessage()];
        }
    }
}
