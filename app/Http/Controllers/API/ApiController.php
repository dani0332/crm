<?php

namespace App\Http\Controllers\API;

use App\Console\Commands\ReportsConversionOptimizationScheduledExportCommand;
use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
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
use App\Http\Requests\BirdWhatsappWebhookRequest;
use App\Http\Requests\CheckDocumentUploadAfterPaymentRequest;
use App\Http\Requests\ClaimAssignmentRequest;
use App\Http\Requests\DocumentNotificationRequest;
use App\Http\Requests\EligibleForRevivalFollowupsRequest;
use App\Http\Requests\EvaluateTierRequest;
use App\Http\Requests\HandleZeroPlansRequest;
use App\Http\Requests\LifeSyncHealthQuestionnaireRequest;
use App\Http\Requests\LogEpEmailStatusesRequest;
use App\Http\Requests\LogFollowUpEventRequest;
use App\Http\Requests\PaymentNotificationRequest;
use App\Http\Requests\ReTriggerLifeRevivalRequest;
use App\Http\Requests\RewatermarkQuoteDocumentsRequest;
use App\Http\Requests\SendHealthApplyNowEmailRequest;
use App\Http\Requests\SendZeroPlanEmailRequest;
use App\Http\Requests\SICWhatsappRequest;
use App\Http\Requests\SICWorkflowRequest;
use App\Http\Requests\STPAdvisorNotificationRequest;
use App\Http\Requests\TravelAIGWorkflowRequest;
use App\Http\Requests\UpdateCustomerRepliedRequest;
use App\Http\Requests\UpdateRevivalLeadSourceRequest;
use App\Http\Resources\GenericDocumentResource;
use App\Jobs\CheckDocumentUploadAfterPaymentJob;
use App\Jobs\FixQuoteStatusDate;
use App\Jobs\HomeSyncSALJob;
use App\Jobs\LifeSyncHealthQuestionnaireJob;
use App\Jobs\ProcessLeadOCRDataComparison;
use App\Jobs\ProcessPaymentStatusUpdateJob;
use App\Jobs\RemovePcQualifiedJob;
use App\Jobs\Revival\CarRevivalFollowUpEmailJob;
use App\Jobs\Revival\LifeRevivalLeadsCreationJob;
use App\Jobs\RunCQFJobs;
use App\Jobs\TagPcpCustomerJob;
use App\Jobs\TagPCQualifiedJob;
use App\Models\CarQuote;
use App\Models\DttRevival;
use App\Models\HealthQuote;
use App\Models\HealthQuotePlan;
use App\Models\Payment;
use App\Models\QuoteFlowDetails;
use App\Scripts\DeDuplicateQuoteDetailScript;
use App\Services\Allocation\AllocationCreationService;
use App\Services\ApiService;
use App\Services\ApplicationStorageService;
use App\Services\BirdService;
use App\Services\Cache\CacheManager;
use App\Services\CarRevivalService;
use App\Services\CQF\CarCQFFileExportService;
use App\Services\EmailServices\CarEmailService;
use App\Services\EmailServices\FailedILAEmailService;
use App\Services\EmailServices\HomeEmailService;
use App\Services\EmailStatusService;
use App\Services\InboundEmailsHookService;
use App\Services\LifeRevivalService;
use App\Services\Logger\LoggerService;
use App\Services\MetLife\MetLifeApiService;
use App\Services\OutboundEmailsHookService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\QuoteDocumentService;
use App\Services\QuoteStatusService;
use App\Services\Reports\ConversionOptimizationScheduledExportService;
use App\Services\RewatermarkQuoteDocumentsService;
use App\Services\UserService;
use App\Services\WhatsAppHookService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\PrivateClient;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiController extends Controller
{
    private const OCR_UTIL_FEAT = 'OCR UTIL FEATURE';
    private const LIFE_REVIVAL_JOB_DELAY_SECONDS = 30;

    use GenericQueriesAllLobs, PrivateClient;

    private const REQUIRED_STRING = 'required|string';

    public $apiService;
    public $inboundEmailsHookService;
    public $outboundEmailsHookService;
    protected $emailStatusService;
    protected $quoteDocumentService;
    protected $ocrReponseStructure;
    protected WhatsAppHookService $whatsAppHookService;

    public function __construct(ApiService $apiService, InboundEmailsHookService $inboundEmailsHookService, EmailStatusService $emailStatusService, OutboundEmailsHookService $outboundEmailsHookService, QuoteDocumentService $quoteDocumentService, WhatsAppHookService $whatsAppHookService)
    {
        $this->apiService = $apiService;
        $this->inboundEmailsHookService = $inboundEmailsHookService;
        $this->emailStatusService = $emailStatusService;
        $this->outboundEmailsHookService = $outboundEmailsHookService;
        $this->quoteDocumentService = $quoteDocumentService;
        $this->whatsAppHookService = $whatsAppHookService;
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
        LoggerService::startFeatureLogging(LoggerFeatureEnum::PAYMENT_STATUS_UPDATE, $request->quoteId);

        // Basic validation: quote type must not be numeric
        if (is_numeric($request->quoteType)) {
            LoggerService::info('Payment Status Update API - Quote Type Not Valid', extra: [
                'quote_type' => $request->quoteType,
                'quote_id' => $request->quoteId,
                'reason' => 'Quote type must be a string, not numeric',
            ]);

            return response()->json(['message' => 'Quote type not valid'], 422);
        }

        // Dispatch job to process payment status update in background
        ProcessPaymentStatusUpdateJob::dispatch($request->quoteType, $request->quoteId);

        LoggerService::info('Payment Status Update API - Job dispatched', extra: [
            'quote_type' => $request->quoteType,
            'quote_id' => $request->quoteId,
        ]);

        return response()->json(['message' => 'Payment notification successfully queued']);
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

    public function logFollowUpEvent(LogFollowUpEventRequest $request)
    {
        $response = app(EmailStatusService::class)->addBirdEmailStatus($request);

        $success = ($response->status ?? false) === true;
        $statusCode = $success ? Response::HTTP_OK : Response::HTTP_NOT_FOUND;

        return apiResponse([], $statusCode, $response->message);
    }

    public function stopFollowUpEvent(BirdStopWorkFlowRequest $request)
    {
        $flowType = $request->flowType;
        $quoteUID = $request->uuid;
        $workflowId = $request->workflowId;
        LoggerService::info("getting request to stopFollowUpEvent Ref-ID: {$quoteUID} | FlowType: {$flowType} Time:".now());
        $workflow = QuoteFlowDetails::where('quote_uuid', $quoteUID)
            ->where('flow_type', $flowType)
            ->whereNull('ended_at')
            ->latest('id')
            ->first();

        if (! $workflow) {
            $alreadyEnded = QuoteFlowDetails::where('quote_uuid', $quoteUID)
                ->where('flow_type', $flowType)
                ->whereNotNull('ended_at')
                ->exists();

            if ($alreadyEnded) {
                return apiResponse([], Response::HTTP_OK, 'Workflow already stopped');
            }

            LoggerService::info("lead not found for uuid: {$quoteUID} | FlowType: {$flowType} | Time: ".now());

            return apiResponse([], Response::HTTP_NOT_FOUND, 'Lead not found');
        }
        $birdResponse = app(BirdService::class)->stopWorkFlow($workflow, $workflowId);
        if ($birdResponse === false || (int) data_get($birdResponse, 'status_code', 200) >= 300) {
            return apiResponse([], Response::HTTP_SERVICE_UNAVAILABLE, 'Unable to stop workflow');
        }

        $bodyString = (string) data_get($birdResponse, 'body', '');

        $decoded = json_decode($bodyString, true) ?? [];
        $result = $decoded['result'] ?? [];
        if (is_array($result) && array_is_list($result)) {
            $result = collect($result)->mapWithKeys(static function (mixed $row): array {
                if (! is_array($row)) {
                    return [];
                }
                $id = (string) ($row['id'] ?? $row['run_id'] ?? $row['runId'] ?? '');

                return $id !== '' ? [$id => $row['status'] ?? $row['state'] ?? ''] : [];
            })->all();
        }
        $c = collect($result);
        $confirmed = $c->contains(fn ($s, $id) => (string) $id === (string) $workflow->flow_id && strtolower((string) $s) === 'cancelled');
        $data = ['action' => $decoded['action'] ?? 'cancel', 'runs' => $c->map(fn ($s, $id) => ['run_id' => $id, 'status' => $s])->values()->all()];

        if (! $confirmed) {
            LoggerService::warning('stopFollowUpEvent: Bird did not confirm workflow run as cancelled; skipping DB update', [
                'quote_uuid' => $quoteUID,
                'flow_type' => $flowType,
                'flow_id' => $workflow->flow_id,
                'parsed_response' => $data,
            ]);

            return apiResponse(
                $data,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Cancellation was not confirmed for this workflow run'
            );
        }

        $workflow->ended_at = now();
        $workflow->stopped_source = $request->input('stop_source') ?? 'api';
        $workflow->save();

        return apiResponse($data, Response::HTTP_OK, 'Email event stopped successfully');
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

    public function birdWhatsappInboundHook(BirdWhatsappWebhookRequest $request)
    {
        return $this->whatsAppHookService->handleInbound($request);
    }

    public function birdWhatsappOutboundHook(BirdWhatsappWebhookRequest $request)
    {
        return $this->whatsAppHookService->handleOutbound($request);
    }

    public function birdWhatsappInteractionHook(BirdWhatsappWebhookRequest $request)
    {
        return $this->whatsAppHookService->handleInteraction($request);
    }
    public function duplicateEntries()
    {
        return DeDuplicateQuoteDetailScript::run();
    }

    public function markAutoCaptureFailed($quoteUuid, $quoteType)
    {
        $quoteType = ucfirst(strtolower($quoteType));
        $quoteTypeId = QuoteTypes::getIdFromValue($quoteType);
        if ($quoteTypeId) {
            LoggerService::startQuoteLogging(QuoteTypes::getName($quoteTypeId)->refId($quoteUuid));
        }
        LoggerService::info(self::class.': Marking auto capture as failed', extra: [
            'function' => __FUNCTION__,
            'quote_type' => $quoteType,
        ]);

        $quote = $this->getQuoteObject($quoteType, $quoteUuid);

        if (! $quote) {
            return response()->json(['success' => false, 'message' => 'Quote not found']);
        }

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

            $shouldUpdateAPIIssuanceAndInsurerStatus = (new PolicyIssuanceService)->shouldUpdateAPIIssuanceAndInsurerStatus($quoteType, $insuranceProvider);
            if (($quoteType === QuoteTypes::CAR->value && in_array($insuranceProvider->code, [InsuranceProvidersEnum::AXA])) || $shouldUpdateAPIIssuanceAndInsurerStatus) {
                // reason for adding this check on process involved is because we not triggering the payment capture failure for car AXA
                $processInvolved = $shouldUpdateAPIIssuanceAndInsurerStatus ? PolicyIssuanceEnum::PROCESS_INVOLVED_PAYMENT_CAPTURE : null;
                app(PolicyIssuanceService::class)->updateAPIIssuanceAndInsurerStatus($quote, $quoteType, PolicyIssuanceEnum::AUTO_CAPTURE_FAILED_STATUS_ID, PolicyIssuanceEnum::POLICY_ISSUANCE_API_STATUS_NO_ID, $processInvolved);
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

    public function tagPrivateClients(Request $request)
    {
        $request->validate([
            'uuids' => 'required|array|min:1',
            'uuids.*' => 'required',
        ]);

        LoggerService::info(self::class.': Private client tag exercise has been initiated');

        dispatch(new TagPCQualifiedJob($request->input('uuids')));

        return apiResponse(null, Response::HTTP_OK, 'Private client tagging job has been dispatched!');
    }

    public function tagPcpCustomers(Request $request)
    {
        $request->validate([
            'uuids' => 'required|array|min:1',
            'uuids.*' => 'required',
        ]);

        LoggerService::info(self::class.': PC customer tag exercise has been initiated');

        dispatch(new TagPcpCustomerJob($request->input('uuids')));

        return apiResponse(null, Response::HTTP_OK, 'Private client tagging job has been dispatched!');
    }

    public function removePcQualified(Request $request)
    {
        $request->validate([
            'uuids' => 'required|array|min:1',
            'uuids.*' => 'required',
        ]);

        LoggerService::info(self::class.': PC qualified removal exercise has been initiated');

        dispatch(new RemovePcQualifiedJob($request->input('uuids')));

        return apiResponse(null, Response::HTTP_OK, 'PC qualified removal job has been dispatched!');
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
    public function assignClaim(ClaimAssignmentRequest $request)
    {
        return $this->apiService->processClaimAssignment($request);
    }
    /**
     * Export email status logs as Excel file for a specific quote
     *
     * @return StreamedResponse
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

    public function runCQFJobs(Request $request)
    {
        try {
            LoggerService::info(self::class.': Running CQF jobs');

            // Validate the date parameter - make it optional since the service can handle null
            $request->validate([
                'date' => 'nullable|date',
            ]);

            $startDate = null;
            if ($request->has('date') && ! empty($request->date)) {
                $startDate = Carbon::parse($request->date);
            }

            RunCQFJobs::dispatch($startDate);

            LoggerService::info(self::class.': CQF jobs have been completed');

            return apiResponse(null, Response::HTTP_OK, 'car cqf renewals process has been completed');
        } catch (\Exception $e) {
            LoggerService::error(self::class.': CQF jobs failed', exception: $e);

            return apiResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, 'Failed to run CQF jobs');
        }

    }

    /**
     * Manually run the same flow as {@see ReportsConversionOptimizationScheduledExportCommand}
     * (reads recipient JSON from {@see ApplicationStorageEnums::CONVERSION_OPTIMIZATION_SCHEDULED_EXPORT_PARAMS}
     * or an optional `application_storage_key`).
     */
    public function triggerConversionOptimizationScheduledExport(
        Request $request,
        ConversionOptimizationScheduledExportService $scheduledExportService
    ): JsonResponse {
        try {
            $validated = $request->validate([
                'application_storage_key' => ['nullable', 'string', 'max:255'],
            ]);

            $storageKey = isset($validated['application_storage_key']) && trim($validated['application_storage_key']) !== ''
                ? trim($validated['application_storage_key'])
                : ApplicationStorageEnums::CONVERSION_OPTIMIZATION_SCHEDULED_EXPORT_PARAMS;

            LoggerService::info(self::class.': Manual conversion optimization scheduled export trigger', [
                'application_storage_key' => $storageKey,
            ]);

            if ($scheduledExportService->dispatchScheduledExport($storageKey)) {
                return apiResponse(
                    ['application_storage_key' => $storageKey],
                    Response::HTTP_OK,
                    'Conversion optimization scheduled export dispatched.'
                );
            }

            return apiResponse(
                ['application_storage_key' => $storageKey],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Conversion optimization scheduled export was not dispatched; see logs.'
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            LoggerService::error(self::class.': Conversion optimization scheduled export trigger failed', exception: $e);

            return apiResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Failed to trigger conversion optimization scheduled export.'
            );
        }
    }

    public function getGenericDocuments(Request $request)
    {
        return GenericDocumentResource::collection($this->apiService->getGenericDocuments($request));
    }

    public function missingDocsReminder($quoteUuid)
    {
        try {
            LoggerService::info(self::class.': Missing docs reminder has been initiated');
            $response = app(ApiService::class)->missingDocsReminder($quoteUuid);

            if ($response['success']) {
                LoggerService::info(self::class.': Missing docs reminder has been completed');

                return response()->json([
                    'success' => true,
                    'message' => $response['message'],
                ], Response::HTTP_OK);
            } else {
                LoggerService::error(self::class.': Missing docs reminder failed', extra: [
                    'quote_uuid' => $quoteUuid,
                    'message' => $response['message'],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $response['message'],
                ], Response::HTTP_OK);
            }
        } catch (\Exception $e) {
            LoggerService::error(self::class.': Missing docs reminder failed', extra: [
                'quote_uuid' => $quoteUuid,
                'message' => $e->getMessage(),
                'exception' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function verifyMissingDocs($quoteUuid, $quoteType)
    {
        $response = app(ApiService::class)->verifyMissingDocs($quoteUuid, $quoteType);
        if ($response['success']) {
            return response()->json([
                'success' => true,
                'message' => $response['message'],
                'missingDocuments' => $response['missingDocuments'] ?? null,
                'isDocumentMissing' => $response['isDocumentMissing'] ?? null,
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => $response['message'],
                'missingDocuments' => $response['missingDocuments'] ?? null,
                'isDocumentMissing' => $response['isDocumentMissing'] ?? null,
            ], Response::HTTP_OK);
        }
    }
    /**
     * Update customer replied status in email_status table
     *
     * @return JsonResponse
     */
    public function updateCustomerRepliedStatus(UpdateCustomerRepliedRequest $request)
    {
        LoggerService::info(self::class.' - update customer replied status request received',
            ['quote_uuid' => $request->quote_uuid, 'quote_type_id' => $request->quote_type_id, 'email_subject' => $request->email_subject]);

        try {
            $quoteType = QuoteTypes::getName($request->quote_type_id);

            $result = DB::transaction(function () use ($request, $quoteType) {

                $emailStatusService = app(EmailStatusService::class);

                $result = $emailStatusService->updateCustomerRepliedStatus(
                    $request->quote_uuid,
                    $request->quote_type_id,
                    $request->email_subject
                );

                if (! $result->success) {
                    LoggerService::warning(self::class.' - error updating customer replied status',
                        ['quote_uuid' => $request->quote_uuid, 'quote_type_id' => $request->quote_type_id, 'email_subject' => $request->email_subject, 'error' => $result->message]);

                    return $result;
                }

                LoggerService::info(self::class.' - updating source for revival leads after customer replied',
                    ['quote_uuid' => $request->quote_uuid, 'quote_type_id' => $request->quote_type_id, 'email_subject' => $request->email_subject, 'quote_type' => $quoteType]);

                match ($quoteType) {
                    QuoteTypes::CAR => app(CarRevivalService::class)->updateSource($request->quote_uuid, LeadSourceEnum::REVIVAL_REPLIED),
                    QuoteTypes::LIFE => app(LifeRevivalService::class)
                        ->updateSource($request->quote_uuid, LeadSourceEnum::REVIVAL_REPLIED),
                    default => null,
                };

                return $result;
            });

            if ($result->success) {
                if ($quoteType === QuoteTypes::LIFE) {
                    try {
                        QuoteTypes::LIFE->allocate(uuid: $request->quote_uuid);

                        LoggerService::info(self::class.' - triggered allocation for life revival lead - Quote UUID: ',
                            ['quote_uuid' => $request->quote_uuid]);
                    } catch (\Throwable $exception) {
                        LoggerService::warning(self::class.' - failed to trigger allocation for life revival lead', [
                            'quote_uuid' => $request->quote_uuid,
                            'quote_type_id' => $request->quote_type_id,
                            'error' => $exception->getMessage(),
                        ], $exception);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => $result->message,
                    'data' => $result->data ?? null,
                ], Response::HTTP_OK);
            }

            return response()->json([
                'success' => false,
                'message' => $result->message,
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            LoggerService::error(self::class.': Error updating customer replied status', [
                'quote_uuid' => $request->quote_uuid ?? null,
                'error' => $e->getMessage(),
            ], $e);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating customer replied status',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Dispatch job to check document upload after 24 hours of payment authorization.
     * Prevents duplicate job dispatch for the same payment code.
     */
    public function checkDocumentUploadAfterPayment(CheckDocumentUploadAfterPaymentRequest $request)
    {
        try {
            $validated = $request->validated();
            $paymentCode = $validated['payment_code'];

            LoggerService::info("CheckDocumentUploadAfterPayment: Starting job execution for payment code: {$paymentCode}");

            // Dispatch job with 24 hours delay
            CheckDocumentUploadAfterPaymentJob::dispatch($paymentCode)
                ->delay(now()->addHours(24));

            return response()->json([
                'success' => true,
                'message' => 'Job dispatched successfully. Will check document upload after 24 hours.',
                'payment_code' => $paymentCode,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            $paymentCodeForError = $request->input('payment_code', 'unknown');

            LoggerService::error("CheckDocumentUploadAfterPayment: Failed to dispatch job for payment code: {$paymentCodeForError}", exception: $e);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while dispatching the job',
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

        $validatedData = $request->validated();

        LoggerService::startQuoteLogging(QuoteTypes::LIFE->refId($validatedData['quote_uuid']));
        LoggerService::info(self::class.': Received request to sync Health Questionnaire data');

        try {
            LifeSyncHealthQuestionnaireJob::dispatch($validatedData);

            LoggerService::info(self::class.': Health Questionnaire sync job dispatched');

            return response()->json([
                'status' => 'success',
                'message' => 'Health Questionnaire sync job has been queued.',
                'quote_uuid' => $validatedData['quote_uuid'],
            ], 202);
        } catch (\Exception $e) {
            LoggerService::error(self::class.': Health Questionnaire sync failed', extra: [
                'request' => $validatedData,
            ], exception: $e);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while syncing Health Questionnaire data.',
                'error_details' => $e->getMessage(),
                'quote_uuid' => $validatedData['quote_uuid'],
            ], 500);
        }
    }

    public function stpAdvisorNotification(STPAdvisorNotificationRequest $request)
    {
        try {
            $response = app(ApiService::class)->stpAdvisorNotification($request);

            return response()->json([
                'success' => $response['success'],
                'message' => $response['message'],
            ]);
        } catch (\Exception $e) {
            LoggerService::error(self::class.': STP advisor notification failed', [
                'error' => $e->getMessage(),
                'exception' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending STP advisor notification: '.$e->getMessage(),
            ], 500);
        }
    }
    public function exportFailedIlaLeads($quoteType)
    {
        try {
            $response = app(FailedILAEmailService::class)->exportFailedIlaLeads($quoteType);

            $fileResponse = $response['file'];
            // Add custom header for total leads count
            $fileResponse->headers->set('X-Total-Leads', $response['total_leads'] ?? 0);

            return $fileResponse;
        } catch (\Exception $e) {
            LoggerService::warning(self::class.': Failed to export failed ILA leads', exception: $e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export failed ILA leads',
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function rewatermarkQuoteDocuments(RewatermarkQuoteDocumentsRequest $request, RewatermarkQuoteDocumentsService $service)
    {
        $result = $service->handle($request->validated());

        return apiResponse($result, Response::HTTP_OK, 'Watermark jobs dispatched');
    }

    public function sendZeroPlansEmail(SendZeroPlanEmailRequest $request)
    {
        $response = app(ApiService::class)->sendZeroPlansEmail($request);
        if ($response['success']) {
            return response()->json([
                'success' => true,
                'message' => $response['message'],
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'success' => false,
                'message' => $response['message'],
            ], Response::HTTP_BAD_REQUEST);
        }
    }
    public function getLeadOCRComparison(Request $request)
    {
        $request->validate(
            [
                'uuid' => 'required_without_all:start_date,end_date|string',
                'start_date' => 'required_without:uuid|date_format:Y-m-d',
                'end_date' => 'required_without:uuid|date_format:Y-m-d',
                'recalculate_comparison' => 'sometimes|boolean',
                'limit' => 'sometimes|integer|min:1|max:15',
            ],
            [
                'uuid.required_without_all' => 'UUID is required when start date and end date are not provided',
                'start_date.required_without' => 'Start date is required when UUID is not provided',
                'start_date.date_format' => 'Start date must be in YYYY-MM-DD format',
                'end_date.required_without' => 'End date is required when UUID is not provided',
                'end_date.date_format' => 'End date must be in YYYY-MM-DD format',
                'recalculate_comparison.boolean' => 'Recalculate comparison must be true or false',
                'limit.integer' => 'Limit must be an integer',
                'limit.min' => 'Limit must be at least 1',
                'limit.max' => 'Limit cannot exceed 15',
            ]
        );

        $startDate = $request->filled('start_date')
        ? Carbon::createFromFormat('Y-m-d', $request->start_date)
        : null;

        $endDate = $request->filled('end_date')
            ? Carbon::createFromFormat('Y-m-d', $request->end_date)
            : null;

        $recalculateComparison = $request->boolean('recalculate_comparison', false);
        $limit = $request->integer('limit', 15);

        if (getAppStorageValueByKey(ApplicationStorageEnums::OCR_UTIL_ENABLED) != '1') {
            return apiResponse(null, Response::HTTP_OK, 'OCR util processing is disabled');
        }

        // Atomically set cache lock - returns false if key already exists
        if (! Cache::add('lead_ocr_data_comparison', true, now()->addMinutes(10))) {
            return apiResponse(null, Response::HTTP_OK, 'Lead vs OCR data comparison job is already running');
        }

        LoggerService::info(self::OCR_UTIL_FEAT.' - '.self::class.': Lead vs OCR data comparison is going to be initiated', extra: [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'uuid' => $request->uuid,
            'recalculate_comparison' => $recalculateComparison,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'limit' => $limit,
        ]);

        ProcessLeadOCRDataComparison::dispatch($request->uuid, $startDate, $endDate, $recalculateComparison, $limit)
            ->onConnection('redis')
            ->onQueue('lead_ocr_data_comparison');

        return apiResponse(null, Response::HTTP_OK, 'Lead vs OCR data comparison job has been initiated');
    }

    public function logEpEmailStatuses(LogEpEmailStatusesRequest $request)
    {
        $this->emailStatusService->logEpEmailStatuses($request->validated());

        return apiResponse(null, Response::HTTP_OK, 'Email statuses logged successfully');
    }

    public function eligibleForRevivalFollowups(EligibleForRevivalFollowupsRequest $request): JsonResponse
    {
        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            LoggerService::info(self::class.': Dtt is not enabled from cms', extra: [
                'quoteUID' => $request->quoteUID,
            ]);

            return apiResponse(false, Response::HTTP_OK, 'Dtt is not enabled from cms');
        }

        LoggerService::info(self::class.': Eligible for revival followups request received', extra: [
            'quoteUID' => $request->quoteUID,
        ]);

        $carQuote = CarQuote::query()
            ->select(['id', 'uuid', 'source', 'advisor_id', 'quote_status_id', 'payment_status_id', 'created_at'])
            ->with(['carQuoteRequestDetail:id,car_quote_request_id,engagement_level'])
            ->where('uuid', $request->quoteUID)
            ->first();

        if (! $carQuote || ! $carQuote->carQuoteRequestDetail) {
            LoggerService::info(self::class.': Car quote not found', extra: [
                'quoteUID' => $request->quoteUID,
            ]);

            return apiResponse(false, Response::HTTP_NOT_FOUND, 'Car quote not found');
        }

        // if car create date lies in between 29APril 00:00:00 and 29APril 23:59:59 then return true
        if (Carbon::parse($carQuote->created_at)->between(Carbon::parse('2026-04-29 00:00:00'), Carbon::parse('2026-04-29 23:59:59'))) {
            LoggerService::info(self::class.': Car create date lies in between 29APril 00:00:00 and 29APril 23:59:59', extra: [
                'quoteUID' => $request->quoteUID,
                'created_at' => $carQuote->created_at,
            ]);

            return apiResponse(true, Response::HTTP_OK, 'Revival followups already triggered today');
        }

        $isEligible = $this->apiService->isEligibleForRevivalFollowups($carQuote);

        LoggerService::info(self::class.': Eligible for revival followups request processed', extra: [
            'quoteUID' => $request->quoteUID,
            'isEligible' => $isEligible,
        ]);

        return apiResponse($isEligible, Response::HTTP_OK, 'Eligible for revival followups');
    }

    public function updateRevivalLeadSource(UpdateRevivalLeadSourceRequest $request): JsonResponse
    {
        LoggerService::info(self::class.': Update revival lead source request received', extra: [
            'quote_uuid' => $request->quote_uuid,
            'quoteTypeId' => $request->quoteTypeId,
            'channel' => $request->channel,
            'CTA' => $request->cta,
            'medium' => $request->medium,
        ]);

        $quoteType = QuoteTypes::getName($request->quoteTypeId);

        $quote = $this->getQuoteObject($quoteType->value, $request->quote_uuid);

        if (! $quote) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Quote not found');
        }

        if ($quote->source !== LeadSourceEnum::REVIVAL) {
            return apiResponse(null, Response::HTTP_UNPROCESSABLE_ENTITY, 'Lead source is not revival');
        }

        $quote->update([
            'source' => LeadSourceEnum::REVIVAL_REPLIED,
        ]);

        LoggerService::info(self::class.': Revival lead source updated successfully', extra: [
            'quote_uuid' => $request->quote_uuid,
            'quoteTypeId' => $request->quoteTypeId,
            'channel' => $request->channel,
            'CTA' => $request->cta,
        ]);

        return apiResponse(null, Response::HTTP_OK, 'Lead source updated successfully');
    }

    public function reTriggerRevivalFollowups(Request $request)
    {
        Log::withContext(['feature' => 're-trigger-revival-followups-request-received']);

        $request->validate([
            'dttRevivalIds' => 'sometimes|array',
            'all' => 'required|boolean',
            'debug' => 'required|boolean',
            'getData' => 'required|boolean',
            'checkCount' => 'required|boolean',
            'checkCountValue' => 'required|integer',
        ]);

        if ($request->filled('debug') && $request->debug) {
            $dttRevivalRecords = DttRevival::query()
                ->when($request->filled('checkCount') && $request->checkCount, function ($query) use ($request) {
                    return $query->where('follow_up_email_count', $request->checkCountValue);
                })
                ->where('created_at', '>=', Carbon::parse('2026-04-29 00:00:00'))
                ->where('created_at', '<=', Carbon::parse('2026-04-29 23:59:59'))
                ->where('quote_type_id', QuoteTypes::CAR->id())
                ->where('reply_received', 0)
                ->when($request->filled('getData') && $request->getData, function ($query) {
                    return $query->get();
                }, function ($query) {
                    return $query->count();
                });

            return apiResponse($dttRevivalRecords, Response::HTTP_OK, 'Dtt revival records');
        }

        if ($request->filled('all') && $request->all) {
            DttRevival::query()
                ->when($request->filled('checkCount') && $request->checkCount, function ($query) use ($request) {
                    return $query->where('follow_up_email_count', $request->checkCountValue);
                })
                ->where('created_at', '>=', Carbon::parse('2026-04-29 00:00:00'))
                ->where('created_at', '<=', Carbon::parse('2026-04-29 23:59:59'))
                ->where('quote_type_id', QuoteTypes::CAR->id())
                ->where('reply_received', 0)
                ->chunk(100, function ($dttRevivalRecords) {
                    foreach ($dttRevivalRecords as $dttRevivalRecord) {
                        $this->reTriggerRevivalFollowupsForQuote($dttRevivalRecord);
                    }
                });
        } elseif ($request->filled('dttRevivalIds') && $request->dttRevivalIds) {
            $dttRevivalRecords = DttRevival::query()
                ->when($request->filled('checkCount') && $request->checkCount, function ($query) use ($request) {
                    return $query->where('follow_up_email_count', $request->checkCountValue);
                })
                ->where('reply_received', 0)
                ->whereIn('id', $request->dttRevivalIds)
                ->get();
            foreach ($dttRevivalRecords as $dttRevivalRecord) {
                $this->reTriggerRevivalFollowupsForQuote($dttRevivalRecord);
            }
        } else {
            return apiResponse(false, Response::HTTP_BAD_REQUEST, 'Invalid request');
        }

        return apiResponse(true, Response::HTTP_OK, 'Revival followups re-triggered successfully');
    }

    private function reTriggerRevivalFollowupsForQuote($dttRevivalRecord)
    {
        $carQuote = CarQuote::query()->where('uuid', $dttRevivalRecord->uuid)->first();

        if (! $carQuote) {
            LoggerService::info(self::class.': Car quote not found', extra: [
                'id' => $dttRevivalRecord->id,
                'uuid' => $dttRevivalRecord->uuid,
            ]);

            return;
        }

        $previousAdvisor = null;
        if (! empty($carQuote->previous_advisor_id)) {
            $previousAdvisor = app(UserService::class)->getUserById($carQuote->previous_advisor_id);
        }

        $emailData = app(CarEmailService::class)->buildDttRevivalBirdEmailPayload($carQuote, $previousAdvisor);
        $emailData->workflowType = WorkflowTypeEnum::MOTOR_REVIVAL_FOLLOWUP;

        CarRevivalFollowUpEmailJob::dispatch($dttRevivalRecord->id, $emailData);

        LoggerService::info(self::class.': CarRevivalFollowUpEmailJob dispatched for revival re-trigger', extra: [
            'id' => $dttRevivalRecord->id,
            'uuid' => $dttRevivalRecord->uuid,
        ]);
    }

    public function reTriggerRevivalFollowupsWithDate(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|string|date_format:Y-m-d',
            'debug' => 'required|boolean',
        ]);

        if ($request->filled('debug') && $request->debug) {
            $records = $this->getDttRevivalRecords($validated['date']);

            return apiResponse($records, Response::HTTP_OK, 'Dtt revival records');
        }

        Artisan::call('Dtt:followup', [
            '--date' => $validated['date'],
        ]);

        return apiResponse(true, Response::HTTP_OK, 'Dtt follow-up command executed');
    }

    private function getDttRevivalRecords($dateOption)
    {

        $carbon = filled($dateOption)
            ? Carbon::parse((string) $dateOption)->startOfDay()
            : Carbon::now();

        $followUpAnchorDate = filled($dateOption)
            ? Carbon::parse((string) $dateOption)->toDateString()
            : null;

        $twoDaysBefore = $carbon->copy()->subDays(2)->toDateString();
        $sevenDaysBefore = $carbon->copy()->subDays(7)->toDateString();
        $thirteenDaysBefore = $carbon->copy()->subDays(13)->toDateString();
        $twentyDaysBefore = $carbon->copy()->subDays(20)->toDateString();
        $twentyEightDaysBefore = $carbon->copy()->subDays(28)->toDateString();

        $logPrefix = 'carRevivalFollowUpEmailJob -';

        $jobs = [];
        $delayCounter = 0;

        // Use chunking to avoid memory issues with large datasets
        $records = DttRevival::where(function ($q) use ($twoDaysBefore, $sevenDaysBefore, $thirteenDaysBefore, $twentyDaysBefore, $twentyEightDaysBefore) {
            $q->whereDate('created_at', '=', $twoDaysBefore);
            $q->orWhereDate('created_at', '=', $sevenDaysBefore);
            $q->orWhereDate('created_at', '=', $thirteenDaysBefore);
            $q->orWhereDate('created_at', '=', $twentyDaysBefore);
            $q->orWhereDate('created_at', '=', $twentyEightDaysBefore);
        })
            ->whereDate('created_at', '<=', Carbon::parse('2026-04-29 23:59:59')->toDateString())
            ->where('reply_received', 0)
            ->select('id', 'uuid') // Only select needed fields to reduce memory usage
            ->count();
        dd($records);

    }

    public function reTriggerLifeRevival(ReTriggerLifeRevivalRequest $request): JsonResponse
    {
        Log::withContext(['feature' => 're-trigger-life-revival']);

        $validated = $request->validated();

        $allocationCreationService = app(AllocationCreationService::class);

        if ($request->boolean('debug')) {
            $leads = $allocationCreationService->executeLifeRevivalAllocation();
            if (! empty($validated['limit'])) {
                $leads = $leads->take((int) $validated['limit']);
            }

            return apiResponse([
                'count' => $leads->count(),
                'leads' => $leads->values(),
            ], Response::HTTP_OK, 'Life revival leads (debug)');
        }

        $isDttEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::DTT_LIFE_ENABLED);
        if ($isDttEnabled == false || $isDttEnabled == 0) {
            LoggerService::info(self::class.' - reTriggerLifeRevival - DTT Life Revival is not enabled from cms');

            return apiResponse(false, Response::HTTP_OK, 'DTT Life Revival is not enabled from cms');
        }

        if (! $request->boolean('all')) {
            return apiResponse(false, Response::HTTP_BAD_REQUEST, 'Invalid request');
        }

        $leads = $allocationCreationService->executeLifeRevivalAllocation();

        if (! empty($validated['limit'])) {
            $leads = $leads->take((int) $validated['limit']);
        }

        if ($leads->isEmpty()) {
            LoggerService::info(self::class.' - reTriggerLifeRevival - no life revival leads to process');

            return apiResponse(true, Response::HTTP_OK, 'No life revival leads to process');
        }

        $this->dispatchLifeRevivalLeadJobs($leads);

        LoggerService::info(self::class.' - reTriggerLifeRevival - life revival batch dispatched', [
            'lead_count' => $leads->count(),
        ]);

        return apiResponse(true, Response::HTTP_OK, 'Life revival jobs dispatched successfully');
    }

    private function dispatchLifeRevivalLeadJobs($leads): void
    {
        $leadsList = $leads->values()->all();

        if ($leadsList === [] || count($leadsList) === 0) {
            return;
        }

        $logPrefix = self::class.' - dispatchLifeRevivalLeadJobs - ';
        $jobs = [];
        $delayCounter = 0;

        foreach ($leadsList as $lead) {
            $jobs[] = (new LifeRevivalLeadsCreationJob($lead->id))->delay(now()->addSeconds(self::LIFE_REVIVAL_JOB_DELAY_SECONDS + $delayCounter));
            $delayCounter += self::LIFE_REVIVAL_JOB_DELAY_SECONDS;
        }

        Bus::batch($jobs)
            ->then(function () use ($logPrefix) {
                LoggerService::info("{$logPrefix} all life revival batch jobs completed successfully");
            })
            ->catch(function () use ($logPrefix) {
                LoggerService::warning("{$logPrefix} one of life revival batch jobs failed.");
            })
            ->finally(function () use ($logPrefix) {
                LoggerService::info("{$logPrefix} life revival batch finished");
            })
            ->allowFailures()
            ->name('Life DTT Batch Jobs (API)')
            ->dispatch();

        LoggerService::info("{$logPrefix} All Life Revival Leads Jobs dispatched");
    }
}
