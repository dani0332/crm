<?php

namespace App\Http\Controllers\API;

use App\Enums\InsuranceProvidersEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Exports\EmailStatusExport;
use App\Facades\Ken;
use App\Http\Controllers\Controller;
use App\Http\Requests\AIGWorkflowRequest;
use App\Http\Requests\Api\ClearCacheRequest;
use App\Http\Requests\Api\QuoteUpdatedRequest;
use App\Http\Requests\Api\UpdateLeadStatusRequest;
use App\Http\Requests\APiFetchUrl;
use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\BirdOutBoundWebhookRequest;
use App\Http\Requests\BirdStopWorkFlowRequest;
use App\Http\Requests\BirdWebhookRequest;
use App\Http\Requests\DocumentNotificationRequest;
use App\Http\Requests\EmailEventsRequest;
use App\Http\Requests\EvaluateTierRequest;
use App\Http\Requests\HandleZeroPlansRequest;
use App\Http\Requests\LifeSyncHealthQuestionnaireRequest;
use App\Http\Requests\PaymentNotificationRequest;
use App\Http\Requests\SendHealthApplyNowEmailRequest;
use App\Http\Requests\SICWhatsappRequest;
use App\Http\Requests\SICWorkflowRequest;
use App\Http\Requests\TravelAIGWorkflowRequest;
use App\Jobs\FixQuoteStatusDate;
use App\Jobs\HomeSyncSALJob;
use App\Jobs\LifeSyncHealthQuestionnaireJob;
use App\Models\HealthQuote;
use App\Models\HealthQuotePlan;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\QuoteFlowDetails;
use App\Scripts\DeDuplicateQuoteDetailScript;
use App\Services\ApiService;
use App\Services\BirdService;
use App\Services\Cache\CacheManager;
use App\Services\CQF\CarCQFFileExportService;
use App\Services\EmailServices\HomeEmailService;
use App\Services\EmailStatusService;
use App\Services\InboundEmailsHookService;
use App\Services\Logger\LoggerService;
use App\Services\MetLife\MetLifeApiService;
use App\Services\NotificationService;
use App\Services\OutboundEmailsHookService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\QuoteStatusService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PrivateClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    use GenericQueriesAllLobs, PrivateClient;

    private const REQUIRED_STRING = 'required|string';

    public $apiService;
    public $inboundEmailsHookService;
    public $outboundEmailsHookService;
    protected $emailStatusService;

    public function __construct(ApiService $apiService, InboundEmailsHookService $inboundEmailsHookService, EmailStatusService $emailStatusService, OutboundEmailsHookService $outboundEmailsHookService)
    {
        $this->apiService = $apiService;
        $this->inboundEmailsHookService = $inboundEmailsHookService;
        $this->emailStatusService = $emailStatusService;
        $this->outboundEmailsHookService = $outboundEmailsHookService;
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
            LoggerService::info(self::class.': Processing assign leads request', extra: $request->all());

            // Check if lead allocation endpoint is disabled
            if ($this->apiService->isLeadAllocationEndpointDisabled()) {
                return apiResponse(null, Response::HTTP_SERVICE_UNAVAILABLE, 'Lead allocation endpoint disabled');
            }

            return $this->apiService->processAssignLead($request);
        } catch (\Exception $e) {
            LoggerService::error(self::class.': Lead allocation failed with error', exception: $e);

            return apiResponse($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (ValidationException $e) {
            LoggerService::error(self::class.': Lead allocation failed due to validation errors', exception: $e);

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

    public function birdOutboundEmailsHook(BirdOutBoundWebhookRequest $request)
    {
        return $this->outboundEmailsHookService->handleOutboundEmailsHook($request);
    }
    public function duplicateEntries()
    {
        return DeDuplicateQuoteDetailScript::run();
    }

    public function markAutoCaptureFailed($quoteUuid, $quoteType)
    {
        $quoteTypeId = QuoteTypes::getIdFromValue($quoteType);
        if ($quoteTypeId) {
            LoggerService::startQuoteLogging(QuoteTypes::getName($quoteTypeId)->refId($quoteUuid));
        }
        LoggerService::info(self::class.': Marking auto capture as failed', extra: [
            'function' => __FUNCTION__,
            'quote_type' => $quoteType,
        ]);

        $quote = $this->getQuoteObject($quoteType, $quoteUuid);
        $isDuplicateOrCIRLead = ! empty($quote->parent_duplicate_quote_id);
        $payment = Payment::where('code', $quote->code)->mainLeadPayment()->with('paymentSplits')->first();

        if ($isDuplicateOrCIRLead && empty($payment)) {
            $payment = Payment::where([
                'paymentable_id' => $quote->id,
                'paymentable_type' => $quote->getMorphClass(),
            ])->mainLeadPayment()->with('paymentSplits')->first();
        }
        $insuranceProvider = getInsuranceProvider($payment, $quoteType);
        if ($insuranceProvider) {
            LoggerService::info(self::class.': Updating statuses and allocating lead', extra: [
                'function' => __FUNCTION__,
                'quote_type' => $quoteType,
                'insurance_provider' => $insuranceProvider->code,
            ]);
            $insuranceProviderAutomation = (new PolicyIssuanceService)->init($quoteType, $insuranceProvider->code);

            if ($quoteType === QuoteTypes::CAR->value && in_array($insuranceProvider->code, [InsuranceProvidersEnum::AXA])) {
                app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, $quoteType, PolicyIssuanceEnum::AUTO_CAPTURE_FAILED_STATUS_ID, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);
            } else {
                // TODO:: This should be updated with the new function in PolicyIssuanceService
                $insuranceProviderAutomation?->updateQuoteApiIssuanceStatusAndAllocate($quote, PolicyIssuanceEnum::AUTO_CAPTURE_FAILED_STATUS_ID, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID);
            }

            LoggerService::info(self::class.': Statuses updated and allocation triggered', extra: [
                'function' => __FUNCTION__,
                'quote_type' => $quoteType,
                'insurance_provider' => $insuranceProvider->code,
            ]);

            return response()->json(['status' => true, 'message' => 'Insurer and API Issuance statuses updated and Lead allocation is triggered successfully']);
        }

        LoggerService::info(self::class.': Status update and allocation failed', extra: [
            'function' => __FUNCTION__,
            'quote_type' => $quoteType,
            'insurance_provider' => $insuranceProvider?->code,
        ]);

        return response()->json(['success' => false, 'message' => 'Failed to update Insurer and API Issuance statuses and lead allocation!']);
    }

    public function homeSyncSAL(Request $request)
    {
        $request->validate([
            'quoteUID' => self::REQUIRED_STRING, // Ensure quoteUID is present
        ]);

        LoggerService::startQuoteLogging(QuoteTypes::getName(QuoteTypes::HOME->id())->refId($request->quoteUID));
        LoggerService::info(self::class.': Received request to sync SAL data');

        try {
            HomeSyncSALJob::dispatch($request->all());

            LoggerService::info(self::class.': SAL sync job dispatched');

            return response()->json([
                'status' => 'success',
                'message' => 'SAL sync job has been queued.',
                'quoteUID' => $request->quoteUID,
            ], 202);
        } catch (\Exception $e) {
            LoggerService::error(self::class.': SAL sync failed', extra: [
                'request' => $request->all(),
            ], exception: $e);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while syncing SAL data.',
                'error_details' => $e->getMessage(),
                'quoteUID' => $request->quoteUID,
            ], 500);
        }
    }

    public function forgetCache(ClearCacheRequest $request)
    {
        CacheManager::forget($request->getKey());

        return apiResponse(null, Response::HTTP_OK, 'Cache cleared successfully');
    }

    public function triggerAIGWorkflow(AIGWorkflowRequest $request)
    {
        return $this->apiService->triggerAIGWorkflow($request);
    }

    /**
     * Process the one-time exercise to tag customers as Private Clients based on criteria
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tagPrivateClients(Request $request)
    {
        LoggerService::info(self::class.': Private client tag exercise has been initiated');

        $request->validate([
            'batch_size' => 'required|integer|min:1',
            'cursor' => 'nullable|string',
        ]);

        try {

            $batchSize = $request->input('batch_size');
            $cursor = $request->input('cursor');

            $quotes = PersonalQuote::with('customer')->whereNull('pc_qualified')
                ->where('quote_status_id', '!=', QuoteStatusEnum::Cancelled)
                ->whereNotNull('policy_expiry_date')
                ->where('policy_expiry_date', '>', now())
                ->whereIn('quote_type_id', [QuoteTypeId::Car, QuoteTypeId::Health, QuoteTypeId::Home, QuoteTypeId::Life, QuoteTypeId::Yacht]);

            if ($cursor) {
                $quotes->where('id', '>', $cursor);
            }

            $quotes = $quotes->limit($batchSize)->orderBy('created_at', 'asc')->get();

            if ($quotes->isEmpty()) {
                LoggerService::info(self::class.': No quotes found without PCP tag');

                return apiResponse(
                    null,
                    Response::HTTP_OK,
                    'No quotes found without PCP tag.'
                );
            }

            $nextCursor = $quotes->last()->id;
            $hasMore = $quotes->count() === $batchSize;

            $data = [
                'data' => [
                    'next_cursor' => $nextCursor,
                    'has_more' => $hasMore,
                ],
                'message' => 'Private client tagging exercise has been completed.',
                'status' => 'success',
            ];

            foreach ($quotes as $quote) {

                $customerData = [
                    'customer_id' => $quote->customer->id,
                    'customer_name' => $quote->customer->first_name.' '.$quote->customer->last_name,
                    'email' => $quote->customer->email,
                ];

                LoggerService::info(self::class.': Private client tag marking activity started', extra: $customerData);

                LoggerService::startQuoteLogging(QuoteTypes::getName($quote->quote_type_id)->refId($quote->uuid), LoggerFeatureEnum::PCP_CLIENT);
                $this->applyPcpTag($quote->uuid, $quote->quote_type_id);
                LoggerService::endLogging();

                LoggerService::info(self::class.': Private client tag marking activity completed', extra: $customerData);
            }

            return apiResponse($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            LoggerService::error(self::class.': Private client tagging exercise failed', exception: $e);

            return apiResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'An error occurred while completing the private client tagging exercise.'
            );
        }
        LoggerService::info(self::class.': Private client tag exercise has been completed');
    }

    public function triggerTravelAIGWorkflow(TravelAIGWorkflowRequest $request)
    {
        return $this->apiService->triggerTravelAIGWorkflow($request);
    }

    public function triggerSICWhatsapp(SICWhatsappRequest $request)
    {
        // TODO: Implement triggerSICWhatsapp
        return $this->apiService->triggerSICWhatsapp($request);
    }
    public function homeRenewalOCBAttachment(Request $request)
    {
        $request->validate([
            'quoteUID' => self::REQUIRED_STRING,
        ]);

        $publicUrl = app(HomeEmailService::class)->attachHomeOCBPDFToEmail($request->quoteUID);

        return response()->json([
            'public_url' => $publicUrl,
        ]);
    }

    public function downloadValidationFailedFile($id)
    {
        return app(CarCQFFileExportService::class)->downloadValidationFailedFile($id);
    }
    public function documentNotification(DocumentNotificationRequest $request)
    {
        return $this->apiService->documentNotification($request);
    }

    /**
     * Export email status logs as Excel file for a specific quote
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportEmailStatusLogs(int $quoteTypeId, int $quoteId)
    {
        try {
            $export = new EmailStatusExport($quoteId, $quoteTypeId);
            $fileName = "email-status-logs-quote-{$quoteId}-type-{$quoteTypeId}";

            return $export->download($fileName);
        } catch (\Exception $e) {
            Log::error('Failed to export email status logs', [
                'quote_id' => $quoteId,
                'quote_type_id' => $quoteTypeId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export email status logs',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function lifeSyncHealthQuestionnaire(LifeSyncHealthQuestionnaireRequest $request)
    {
        $metLifeApiService = new MetLifeApiService;

        if (! $metLifeApiService->isMetLifeEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'MetLife feature is not enabled right now',
            ], 403);
        }

        LoggerService::startQuoteLogging(QuoteTypes::getName(QuoteTypes::LIFE->id())->refId($request->validated()['quote_uuid']));
        LoggerService::info(self::class.': Received request to sync Health Questionnaire data');

        try {
            LifeSyncHealthQuestionnaireJob::dispatch($request->validated());

            LoggerService::info(self::class.': Health Questionnaire sync job dispatched');

            return response()->json([
                'status' => 'success',
                'message' => 'Health Questionnaire sync job has been queued.',
                'quote_uuid' => $request->validated()['quote_uuid'],
            ], 202);
        } catch (\Exception $e) {
            LoggerService::error(self::class.': Health Questionnaire sync failed', extra: [
                'request' => $request->validated(),
            ], exception: $e);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while syncing Health Questionnaire data.',
                'error_details' => $e->getMessage(),
                'quote_uuid' => $request->validated()['quote_uuid'],
            ], 500);
        }
    }
}
