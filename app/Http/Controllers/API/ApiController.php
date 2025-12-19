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
use App\Http\Requests\UpdateCustomerRepliedRequest;
use App\Jobs\FixQuoteStatusDate;
use App\Jobs\HomeSyncSALJob;
use App\Jobs\LifeSyncHealthQuestionnaireJob;
use App\Jobs\RunCQFJobs;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\OCRSourceEnum;
use App\Models\CarQuote;
use App\Models\DocumentType;
use App\Models\QuoteDocument;
use App\Models\HealthQuote;
use App\Models\HealthQuotePlan;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\QuoteFlowDetails;
use App\Models\LeadOcrDataComparison;
use App\Scripts\DeDuplicateQuoteDetailScript;
use App\Services\ApiService;
use App\Services\BirdService;
use App\Services\Cache\CacheManager;
use App\Services\CQF\CarCQFFileExportService;
use App\Services\EmailServices\HomeEmailService;
use App\Services\EmailStatusService;
use App\Services\InboundEmailsHookService;
use App\Services\Logger\LoggerService;
use App\Services\QuoteDocumentService;
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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use App\Models\Nationality;
use App\Services\LookupService;
class ApiController extends Controller
{
    use GenericQueriesAllLobs, PrivateClient;

    private const REQUIRED_STRING = 'required|string';

    public $apiService;
    public $inboundEmailsHookService;
    public $outboundEmailsHookService;
    protected $emailStatusService;
    protected $quoteDocumentService;
    protected $ocrReponseStructure;

    public function __construct(ApiService $apiService, InboundEmailsHookService $inboundEmailsHookService, EmailStatusService $emailStatusService, OutboundEmailsHookService $outboundEmailsHookService, QuoteDocumentService $quoteDocumentService)
    {
        $this->apiService = $apiService;
        $this->inboundEmailsHookService = $inboundEmailsHookService;
        $this->emailStatusService = $emailStatusService;
        $this->outboundEmailsHookService = $outboundEmailsHookService;
        $this->quoteDocumentService = $quoteDocumentService;
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCustomerRepliedStatus(UpdateCustomerRepliedRequest $request)
    {
        try {
            $emailStatusService = app(EmailStatusService::class);
            $result = $emailStatusService->updateCustomerRepliedStatus(
                $request->quote_uuid,
                $request->quote_type_id,
                $request->email_subject
            );

            if ($result->success) {
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

    public function getCarDocuments()
    {
        $uuid = request('uuid');
        $startDate = Carbon::parse('2025-11-01')->startOfMonth();
        $endDate = Carbon::parse('2025-11-30')->endOfMonth();

        $documentTypeCodes = DocumentType::query()
            ->active()
            ->byQuoteTypeId(QuoteTypes::CAR->id())
            ->get()
            ->filter(fn (DocumentType $documentType) => OCRDocumentTypeEnum::getDocumentType($documentType) !== null)
            ->pluck('code')
            ->values()
            ->all();

        $carQuotes = CarQuote::query()
            ->select([
                'id',
                'uuid',
                'code',
                'quote_status_id',
                'policy_booking_date',
                'insurance_provider_id',
                'price_with_vat',
                'price_vat_applicable',
                'vat',
                'policy_issuance_date',
            ])
           /* ->where('quote_status_id', QuoteStatusEnum::PolicyBooked)
            ->when($uuid, function ($q) use ($uuid) {
                $q->where('uuid', $uuid);
            }, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('transaction_approved_at', [$startDate, $endDate]);
            })*/
            ->whereHas('documents', function ($q) use ($documentTypeCodes) {
                $q->whereIn('document_type_code', $documentTypeCodes);
            })
            ->with([
                'documents' => function ($q) use ($documentTypeCodes) {
                    $q->whereIn('document_type_code', $documentTypeCodes)
                        ->select('id', 'quote_documentable_id', 'doc_name', 'doc_url', 'doc_mime_type', 'document_type_code', 'is_ocr_processed');
                },
                'payments' => function ($q) {
                    $q->latest('created_at')
                        ->take(1)
                        ->select('id', 'paymentable_id', 'paymentable_type', 'insurance_provider_id', 'created_at')
                        ->with(['insuranceProvider:id,code']);
                },
                'insuranceProvider:id,code',
            ])
            ->where('uuid', '7M9V8B6Y')
            ->take(1)
            ->get();

        $carQuoteIds = $carQuotes->filter(fn ($quote) => $quote->documents->isNotEmpty())->pluck('id');

        LoggerService::info('getCarDocuments - Car quotes with OCR documents fetched', extra: [
            'uuid_filter' => $uuid,
            'document_type_codes' => $documentTypeCodes,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_car_quotes' => $carQuotes->count(),
            'car_quotes_with_documents' => $carQuoteIds->count(),
            'car_quote_ids' => $carQuoteIds->toArray(),
        ]);

        $this->processOcrDocumentsForLeads($carQuotes, $documentTypeCodes);

        return apiResponse(null, Response::HTTP_OK, 'OCR documents reprocessing job has been completed');
    }

    private function processOcrDocumentsForLeads($carQuotes, array $documentTypeCodes): void
    {
        LoggerService::info(self::class.'::processOcrDocumentsForLeads - Starting to process OCR documents', extra: [
            'total_quotes' => $carQuotes->count(),
            'document_type_codes' => $documentTypeCodes,
        ]);

        foreach ($carQuotes as $quote) {
            LoggerService::info(self::class.'::processOcrDocumentsForLeads - Processing quote', extra: [
                'quote_id' => $quote->id,
                'quote_uuid' => $quote->uuid,
                'quote_code' => $quote->code,
                'documents_count' => $quote->documents->count(),
            ]);

            $emiratesIdDocumentsFound = 0;
            $emiratesIdDocumentsProcessed = 0;
            $emiratesIdDocumentsFailed = 0;
            $leadDataStructure = [];
            $ocrDataStructure = [];

            foreach ($quote->documents as $document) {
                LoggerService::info(self::class.'::processOcrDocumentsForLeads - Checking document', extra: [
                    'quote_id' => $quote->id,
                    'document_id' => $document->id,
                    'document_type_code' => $document->document_type_code,
                    'doc_name' => $document->doc_name,
                    'has_doc_url' => (bool) $document->doc_url,
                    'has_doc_mime_type' => (bool) $document->doc_mime_type,
                ]);

                $documentType = DocumentType::where('code', $document->document_type_code)
                    ->where('quote_type_id', QuoteTypes::CAR->id())
                    ->first();

                if ($documentType) {
                    // Check if the document type is enabled for OCR
                    $isDocOCREnabled = OCRDocumentTypeEnum::isOCREnabled($documentType, QuoteTypes::CAR);

                    // Skip if nto enabled for OCR
                    if (!$isDocOCREnabled) {
                       LoggerService::info(self::class.'::processOcrDocumentsForLeads - Document type is not enabled for OCR', extra: [
                        'quote_id' => $quote->id,
                        'quote_uuid' => $quote->uuid,
                        'document_id' => $document->id,
                        'document_type_code' => $document->document_type_code,
                        'document_type_name' => $documentType->name,
                       ]);

                        continue;
                    }
                    
                    // Get the OCR document type
                    $ocrDocType = OCRDocumentTypeEnum::getDocumentType($documentType);

                    LoggerService::info(self::class. "::processOcrDocumentsForLeads - Found {$documentType->name} document", extra: [
                        'quote_id' => $quote->id,
                        'quote_uuid' => $quote->uuid,
                        'document_id' => $document->id,
                        'document_type_code' => $document->document_type_code,
                        'total_emirates_id_found' => $emiratesIdDocumentsFound,
                    ]);

                    $this->processOcrDocument($quote, $document, $ocrDocType->value, $leadDataStructure, $ocrDataStructure);
                  
                } else {
                    LoggerService::warning(self::class.'::processOcrDocumentsForLeads - DocumentType not found', extra: [
                        'quote_id' => $quote->id,
                        'document_id' => $document->id,
                        'document_type_code' => $document->document_type_code,
                    ]);
                }
            }

            // Save data in database
            $this->saveleadOCRComparisonData($quote->id, $quote->uuid, $leadDataStructure, $ocrDataStructure);
            
            LoggerService::info(self::class.'::processOcrDocumentsForLeads - Quote processing summary', extra: [
                'quote_id' => $quote->id,
                'quote_uuid' => $quote->uuid,
                'total_documents' => $quote->documents->count(),
                'emirates_id_documents_found' => $emiratesIdDocumentsFound,
                'emirates_id_documents_processed' => $emiratesIdDocumentsProcessed,
                'emirates_id_documents_failed' => $emiratesIdDocumentsFailed,
            ]);
        }

        LoggerService::info(self::class.'::processOcrDocumentsForLeads - Completed processing all documents', extra: [
            'total_quotes_processed' => $carQuotes->count(),
        ]);
    }

    private function saveleadOCRComparisonData($quoteId, $quoteUuid, $leadDataStructure, $ocrDataStructure): void
    {
        // Save data in database
        LeadOcrDataComparison::updateOrCreate([
            'quoteable_id' => $quoteId,
            'quoteable_type' => QuoteTypes::CAR->modelClass(),
            'uuid' => $quoteUuid,
        ], [
            'lead_data' => json_encode($leadDataStructure),
            'ocr_data' => json_encode($ocrDataStructure),
            'ocr_responses' => json_encode($this->ocrReponseStructure),
        ]);
    }

    private function processOcrDocument(CarQuote $quote, QuoteDocument $document, string $ocrDocType,
        array &$leadDataStructure, array &$ocrDataStructure): bool
    {
        // Get lead data structure for the document type
        $leadDataStructure[$ocrDocType] = $this->getLeadDataStructure($ocrDocType, $quote);

        LoggerService::info(self::class.'::processOcrDocument - Processing OCR document', extra: [
            'quote_id' => $quote->id,
            'quote_uuid' => $quote->uuid,
            'quote_code' => $quote->code,
            'document_id' => $document->id,
            'document_type_code' => $document->document_type_code,
            'ocr_doc_type' => $ocrDocType,
            'doc_name' => $document->doc_name,
            'doc_url' => $document->doc_url,
        ]);

        if (!$document->doc_url || !$document->doc_mime_type) {
            LoggerService::warning(self::class.'::processOcrDocument - Missing document payload', extra: [
                'quote_id' => $quote->id,
                'document_id' => $document->id,
                'has_doc_url' => (bool) $document->doc_url,
                'has_doc_mime_type' => (bool) $document->doc_mime_type,
            ]);

            return false;
        }

        $ocrData = $this->callOcrApi($quote, $document, $ocrDocType);
    
        if ($ocrData) {
            LoggerService::info(self::class.'::processOcrDocument - OCR API call successful', extra: [
                'quote_id' => $quote->id,
                'document_id' => $document->id,
                'has_data' => !empty($ocrData),
            ]);

            // Add ocr data structure
            $ocrDataStructure[$ocrDocType] = $this->getOcrDataStructure($ocrDocType, $ocrData, $quote);

            return true;
        }

        LoggerService::warning(self::class.'::processOcrDocument - OCR API call failed', extra: [
            'quote_id' => $quote->id,
            'document_id' => $document->id,
        ]);

        return false;
    }

    // Lead data structures
    private function getLeadDataStructure(string $ocrDocType,  $quote)
    {
        switch ($ocrDocType) {
            case OCRDocumentTypeEnum::ID_CARD->value:
                return $this->getEmiratesIdLeadDataStructure($quote);
                break;
            case OCRDocumentTypeEnum::DRIVING_LICENSE->value:
                return $this->getDrivingLicenseLeadDataStructure($quote);
                break;
            case OCRDocumentTypeEnum::REGISTRATION_CERTIFICATE->value:
                return $this->getVehicleRegistrationCertificateLeadDataStructure($quote);
                break;
            case OCRDocumentTypeEnum::TAX_INVOICE->value:
                return $this->getTaxInvoiceLeadDataStructure($quote);
                break;
            case OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER->value:
                return $this->getTaxInvoiceRaisedByBuyerLeadDataStructure($quote);
                break;
            case OCRDocumentTypeEnum::MOTOR_INSURANCE_POLICY_SCHEDULE->value:
                return $this->getMotorInsurancePolicyScheduleLeadDataStructure($quote);
                break;
            case OCRDocumentTypeEnum::CERTIFICATE_OF_ISSUANCE->value:
                return $this->getCertificateOfIssuanceLeadDataStructure($quote);
                break;
            default:
                break;
        }
    }

    // --------- OCR data structures ---------
    private function getOcrDataStructure(string $ocrDocType, object $ocrData, $quote)
    {
        switch ($ocrDocType) {
            case OCRDocumentTypeEnum::ID_CARD->value:
                return $this->getEmiratesIdOCRDataStructure($ocrData);
                break;
            case OCRDocumentTypeEnum::DRIVING_LICENSE->value:
                return $this->getDrivingLicenseOCRDataStructure($ocrData);
                break;
            case OCRDocumentTypeEnum::REGISTRATION_CERTIFICATE->value:
                return $this->getVehicleRegistrationCertificateOCRDataStructure($ocrData, $quote->insurance_provider_id);
                break;
            case OCRDocumentTypeEnum::TAX_INVOICE->value:
                return $this->getTaxInvoiceOCRDataStructure($ocrData, $quote);
                break;
            case OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER->value:
                return $this->getTaxInvoiceRaisedByBuyerOCRDataStructure($ocrData, $quote);
                break;
            case OCRDocumentTypeEnum::MOTOR_INSURANCE_POLICY_SCHEDULE->value:
                return $this->getMotorInsurancePolicyScheduleOCRDataStructure($ocrData);
                break;
            case OCRDocumentTypeEnum::CERTIFICATE_OF_ISSUANCE->value:
                return $this->getCertificateOfIssuanceOCRDataStructure($ocrData);
                break;
            default:
                break;
        }
    }

    private function getEmiratesIdOCRDataStructure(object $ocrData): array
    {
        return [
            'eid_number' => $ocrData->idNumber,
            'first_name' => $this->extractFirstName($ocrData->name),
            'last_name' => $this->extractLastName($ocrData->name),
            'date_of_birth' => $ocrData->dateOfBirth,
            'nationality_id' => $this->getNationalityId($ocrData->nationality),
            'gender' => $this->formatGender($ocrData->sex),
            'id_issuance_date' => $this->formatDate($ocrData->issuingDate),
            'id_expiry_date' => $this->formatDate($ocrData->expiryDate),
            'place_of_birth' => $this->getNationalityId($ocrData->nationality),
            'country_of_residence' => $this->getNationalityId($ocrData->country), 
            'residential_address' =>  $ocrData->issuingPlace.', UAE',
            'employer_company_name' => $ocrData->sponsor,
            'job_title' => $ocrData->occupation,
            'issuing_place' => $ocrData->issuingPlace,
        ];
    }

    private function getDrivingLicenseOCRDataStructure(object $ocrData): array
    {
        return [
            'driver_license_number' => $ocrData->licenseNumber,
            'driver_license_issue_date' => $this->formatDate($ocrData->issueDate),
            'driver_license_expiry_date' => $this->formatDate($ocrData->expiryDate),
            'driver_license_issue_place' => $this->getIssuancePlaceCode($ocrData->placeOfIssue),
            'traffic_code_number' => $ocrData->trafficCodeNumber,
            'driver_first_name' => $this->extractFirstName($ocrData->personalInformation['fullName']),
            'driver_last_name' => $this->extractLastName($ocrData->personalInformation['fullName']),
            'driver_dob' => $this->formatDate($ocrData->personalInformation['dateOfBirth']),
            'driver_gender' => $this->formatGender($ocrData->personalInformation['sex']),
            'nationality_id' => $this->getNationalityId($ocrData->personalInformation['nationality']),
        ];
    }

    private function getVehicleRegistrationCertificateOCRDataStructure(object $ocrData, int $providerId): array
    {
        $plateInfo = $this->extractPlateCodeNumber($ocrData->trafficPlateNumber ?? null);
        $bankName = $this->getBankCode($ocrData->mortageBy ?? null, QuoteTypeId::Car, $providerId);

        return [
            'vehicle_plate_code' => $plateInfo['plate_code'] ?? null,
            'vehicle_plate_number' => $plateInfo['plate_number'] ?? null,
            'first_registration_date' => $this->formatDate($ocrData->registrationDate ?? null),
            'vehicle_color' => $this->getVehicleColorCode($ocrData->vehicalColor ?? null, QuoteTypeId::Car, $providerId),
            'vehicle_engine_number' => $ocrData->engineNumber ?? null,
            'bank_name' => $bankName ?? null,
            'bank_loan' => $bankName ? 1 : 0,
            'place_of_issue' => $ocrData->placeOfIssue,
            'expiry_date' => $this->formatDate($ocrData->expiryDate),
            'owner' => $ocrData->owner ?? null,
            'nationality_id' => $this->getNationalityId($ocrData->nationality),
            'mortgage_by' => $ocrData->mortageBy,
            'notes' => $ocrData->notes,
            'insured_with' => $ocrData->insuredWith,
            'insurance_type' => $ocrData->insuranceType,
            'model' => $ocrData->vehicalModel,
            'vehicle_class' => $ocrData->vehicalClass,
            'vehicle_type' => $ocrData->vehicalType,
            'vehicle_make' => $ocrData->vehicleMake,
            'vehicle_make_model' => $ocrData->vehicleMakeModel,
            'origin' => $ocrData->origin,
            'empty_weight' => $ocrData->emptyWeight,
            'gross_vehicle_weight' => $ocrData->grossVehicleWeight,
            'number_of_passengers' => isset($ocrData->numberOfPassengers) ? (int) $ocrData->numberOfPassengers : null,
            'traffic_code_number' => $ocrData->trafficCodeNumber ?? null,
        ];
    }

    private function getTaxInvoiceOCRDataStructure(object $ocrData, $quote): array
    {
        $priceVatApplicable = $ocrData->price?->baseAmount ?? $quote->price_vat_applicable;
        $priceWithVat = $ocrData->price?->totalAmount ?? $quote->price_with_vat;

        $vatPercentage = app(\App\Services\ApplicationStorageService::class)->getValueByKey(\App\Enums\ApplicationStorageEnums::VAT_VALUE);
        $vatAmount = $priceVatApplicable * $vatPercentage / 100;

        return [
            'price_with_vat' => $priceWithVat,
            'price_vat_applicable' => $priceVatApplicable,
            'vat' => $vatAmount,
            'policy_issuance_date' => $ocrData->issuanceDate ?? $quote->policy_issuance_date,
            'insurer_invoice_date' => $ocrData->invoiceDate,
            'tax_invoice_number' => $ocrData->taxInvoiceNumber,
        ];
    }

    private function getTaxInvoiceRaisedByBuyerOCRDataStructure(object $ocrData, $quote): array
    {
        $commissionVat = $ocrData->commission['VAT'] ?? ($quote->payment?->comission_vat ?: 0);
        $commissionTotal = $ocrData->commission['totalAmount'] ?? $quote->payment?->comission;

        $commissionVatApplicable = $ocrData->commission['baseAmount'] ?? $quote->payment?->commission_vat_applicable;
        $commissionPercentageDivisor = 1 + ($commissionVat > 0 ? .05 : 0);
        $commissionWithoutVat = $commissionTotal - $commissionVat;
        $premiumWithoutVat = $quote->payment->total_price / $commissionPercentageDivisor;
        $commissionPercentage = roundNumber((($commissionWithoutVat / $premiumWithoutVat) * 100)) ?? $this->quote->payment?->comission_percentage;

        return [
            'commission_vat' => $commissionVat,
            'commission' => $commissionTotal,
            'commmission_percentage' => $commissionPercentage,
            'insurer_commmission_invoice_number' => $ocrData->taxInvoiceNumber ?? $quote->payment?->insurer_commmission_invoice_number,
            'commission_vat_applicable' => $commissionVatApplicable
        ];
    }

    private function getMotorInsurancePolicyScheduleOCRDataStructure(object $ocrData): array
    {
        return [
            'policy_number' => $ocrData->policyNumber,
            'policy_start_date' => $ocrData->policyStartDate ?? null,
            'policy_expiry_date' => $ocrData->policyExpiryDate ?? null,
        ];
    }

    private function getCertificateOfIssuanceOCRDataStructure(object $ocrData): array
    {
        return [
            'policy_number' => $ocrData->policyNumber,
            'policy_start_date' => $ocrData->policyStartDate ?? null,
            'policy_expiry_date' => $ocrData->policyExpiryDate ?? null,
            'policy_issuance_date' => $ocrData->policyIssuanceDate ?? null,
        ];
    }

    // --------- Lead data structures ---------
    private function getEmiratesIdLeadDataStructure($quote): array
    {
        $result = [];
        $insured = $quote->insured;
        $insuredKyc = $insured?->insuredKyc;

        if ($insured) {
            $result = [
                'eid_number' => $insured->id_number,
                'first_name' => $insured->first_name,
                'lastname' => $insured->last_name,
                'dob' => $insured->dob,
                'nationality_id' => $insured->nationality_id,
                'gender' => $insured->gender,
            ];
        }

        if ($insuredKyc) {
            $result = array_merge($result, [
                'id_issuance_date' => $insuredKyc->id_issuance_date,
                'id_expiry_date' => $insuredKyc->id_expiry_date,
                'place_of_birth' => $insuredKyc->place_of_birth,
                'country_of_residence' => $insuredKyc->country_of_residence,
                'residential_address' => $insuredKyc->residential_address,
                'employer_company_name' => $insuredKyc->employer_company_name,
                'job_title' => $insuredKyc->job_title,
            ]);
        }

        return $result;
    }

    private function getDrivingLicenseLeadDataStructure($quote): array
    {
        $result = [];
        $vehicleDriverDetail = $quote->vehicleDriverDetail;

        if ($vehicleDriverDetail) {
            $result = [
                'driver_license_number' => $vehicleDriverDetail->driver_license_number,
                'dtiver_gender' => $vehicleDriverDetail->driver_gender,
                'driver_license_issue_date' => $vehicleDriverDetail->driver_license_issue_date,
                'driver_license_expiry_date' => $vehicleDriverDetail->driver_license_expiry_date,
                'driver_license_issue_place' => $vehicleDriverDetail->driver_license_issue_place,
                'traffic_code_number' => $vehicleDriverDetail->traffic_code_number,
                'driver_first_name' => $vehicleDriverDetail->driver_first_name,
                'driver_last_name' => $vehicleDriverDetail->driver_last_name,
                'driver_dob' => $vehicleDriverDetail->driver_dob,
                'nationality_id' => $vehicleDriverDetail->nationality_id,
            ];
        }

        return $result;
    }

    private function getVehicleRegistrationCertificateLeadDataStructure($quote): array
    {
        $result = [];
        $vehicleDriverDetail = $quote->vehicleDriverDetail;
        $registrationCertificate = $quote->registrationCertificate;

        if ($vehicleDriverDetail) {
            $result = [
                'vehicle_plate_number' => $vehicleDriverDetail->vehicle_plate_number,
                'first_registration_date' => $vehicleDriverDetail->first_registration_date,
                'vehicle_color' => $vehicleDriverDetail->vehicle_color,
                'vehicle_engine_number' => $vehicleDriverDetail->vehicle_engine_number,
                'traffic_code_number' => $vehicleDriverDetail->traffic_code_number,
            ];
        } 

        If ($registrationCertificate) {
            $result = array_merge($result, [
                'place_of_issue' => $registrationCertificate->place_of_issue,
                'expiry_date' => $registrationCertificate->expiry_date->format('Y-m-d'),
                'owner' => $registrationCertificate->owner,
                'nationality_id' => $registrationCertificate->nationality_id,
                'mortgage_by' => $registrationCertificate->mortgage_by,
                'model' => $registrationCertificate->model,
                'vehicle_type' => $registrationCertificate->vehicle_type,
                'origin' => $registrationCertificate->origin,
                'traffic_code_number' => $registrationCertificate->traffic_code_number,
            ]);
        }

        return $result;
    }

    private function getTaxInvoiceLeadDataStructure($quote): array
    {
        $result = [];

        $result = [
            'price_with_vat' => $quote->price_with_vat,
            'price_vat_applicable' => $quote->price_vat_applicable,
            'vat' => $quote->vat,
            'policy_issuance_date' => $quote->policy_issuance_date,
        ];

        $payment = $quote->payment;

        if ($payment) {
            $result = array_merge($result, [
                'insurer_invoice_date' => $payment->insurer_invoice_date,
                'insurer_tax_number' => $payment->insurer_tax_number,
                'tax_invoice_number' => $payment->tax_invoice_number,
            ]);
        }

        return $result;
    }

    private function getTaxInvoiceRaisedByBuyerLeadDataStructure($quote): array
    {
        $result = [];
        $payment = $quote->payment;

        if ($payment) {
            $result = [
                'commission_vat' => $payment->commission_vat,
                'commission' => $payment->commission,
                'commmission_percentage' => $payment->commmission_percentage,
                'insurer_commmission_invoice_number' => $payment->insurer_commmission_invoice_number,
                'commission_vat_applicable' => $payment->commission_vat_applicable,
            ];
        }

        return $result;
    }

    private function getMotorInsurancePolicyScheduleLeadDataStructure($quote): array
    {
        return [
            'policy_number' => $quote->personalQuote->policy_number,
            'policy_start_date' => $quote->personalQuote->policy_start_date,
            'policy_expiry_date' => $quote->personalQuote->policy_expiry_date,
        ];
    }

    private function getCertificateOfIssuanceLeadDataStructure($quote): array
    {
        return [
            'policy_number' => $quote->personalQuote->policy_number,
            'policy_start_date' => $quote->personalQuote->policy_start_date,
            'policy_expiry_date' => $quote->personalQuote->policy_expiry_date,
            'policy_issuance_date' => $quote->personalQuote->policy_issuance_date,
        ];
    }

    private function callOcrApi(CarQuote $quote, QuoteDocument $document, string $docType): ?object
    {
        $providerCode = $this->extractProviderCode($quote);
        $refId = $this->getRefId($quote);
        $isEcom = false;

        //$docUrl = $this->quoteDocumentService->getDocumentUrl($document->doc_url);
        //$docUrl = "https://azstorinsurancemarketstg.blob.core.windows.net/imcrmdev/{$document->doc_url}";
        $encodedFileName = urlencode($document->doc_url);
        $docUrl = Storage::disk('azureIM')->temporaryUrl($encodedFileName, now()->addMinutes(20));

        if (!$docUrl) {
            LoggerService::warning(self::class.'::callOcrApi - Failed to get document URL', extra: [
                'quote_uuid' => $quote->uuid,
                'document_id' => $document->id,
                'doc_url' => $document->doc_url,
            ]);

            // Add to ocr response structure
            $this->ocrReponseStructure[$docType] = [
                'status' => 'false',
                'doc_url' => $document->doc_url,
                'doc_type' => $docType,
                'reason' => 'Failed to get document URL',
            ];

            return null;
        }

        $requestData = [
            'ref_id' => $refId,
            'uuid' => $quote->uuid,
            'quote_type_id' => QuoteTypes::CAR->id(),
            'doc_url' => $docUrl,
            'doc_type' => $docType,
            'provider_code' => $providerCode,
            'image' => false,
        ];

        LoggerService::info(self::class.'::callOcrApi - OCR API Request Details', extra: [
            'request_data' => $requestData,
        ]);

        try {
            $response = Http::baseUrl(config('constants.OCR_API_ENDPOINT'))
                ->withHeader('Referer', trim(config('constants.APP_URL'), '/'))
                ->withHeader('x-api-key', config('constants.OCR_API_KEY'))
                ->withHeader('source', $isEcom ? OCRSourceEnum::ECOM->value : OCRSourceEnum::IMCRM->value)
                ->timeout(config('constants.OCR_API_TIMEOUT'))
                ->post('/process-document', $requestData);

            $responseData = $response->json();
            $responseBody = $response->body();

            if ($response->successful()) {
                LoggerService::info(self::class.'::callOcrApi - OCR API Response Success', extra: [
                    'quote_uuid' => $quote->uuid,
                    'document_id' => $document->id,
                    'has_data' => !empty($responseData),
                    'response_body' => $responseBody,
                    'response_data' => $responseData,
                ]);

                // Add to ocr response structure
                $this->ocrReponseStructure[$docType] = [
                    'status' => 'true',
                    'response' => $responseBody,
                    'reason' => 'Success',
                ];

                return (object) $responseData;
            }

            LoggerService::warning(self::class.'::callOcrApi - OCR API Response Failed', extra: [
                'quote_uuid' => $quote->uuid,
                'document_id' => $document->id,
                'response_status' => $response->status(),
                'response_headers' => $response->headers(),
                'response_body' => $responseBody,
                'response_data' => $responseData,
                'response_message' => $responseData['message'] ?? ($responseData['error'] ?? 'Unknown error'),
            ]);

            // Add to ocr response structure
            $this->ocrReponseStructure[$docType] = [
                'status' => 'false',
                'response' => $responseData,
                'reason' => 'Failed',
            ];

            return null;
        } catch (\Exception $e) {
            LoggerService::error(self::class.'::callOcrApi - Exception occurred during API call', exception: $e);

            return null;
        }
    
    }
    
    private function getNationalityId(?string $nationality): ?int
    {
        if (empty($nationality)) {
            return null;
        }

        $nationalityRecord = Nationality::where('text', $nationality)
            ->orWhere('code', $nationality)
            ->orWhere('country_name', $nationality)
            ->first();

        return $nationalityRecord?->id;
    }

    private function getNationalityName(?int $nationalityId): ?string
    {
        if (empty($nationalityId)) {
            return null;
        }

        $nationalityRecord = Nationality::find($nationalityId);

        // Return country_name if available, otherwise fall back to text
        return $nationalityRecord?->country_name ?? $nationalityRecord?->text ?? null;
    }

    private function extractFirstName(string $fullName): string
    {
        $nameParts = explode(' ', trim($fullName));

        return $nameParts[0] ?? '';
    }

    private function extractLastName(string $fullName): string
    {
        $nameParts = explode(' ', trim($fullName));
        if (count($nameParts) > 1) {
            array_shift($nameParts); // Remove first name

            return implode(' ', $nameParts);
        }

        return '';
    }

    private function formatGender(?string $gender): ?string
    {
        if (empty($gender)) {
            return null;
        }

        return match (strtoupper(trim($gender))) {
            'M', 'MALE' => 'Male',
            'F', 'FEMALE' => 'Female',
            default => $gender
        };
    }
    
    public function formatDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            LoggerService::error('Failed to format date', exception: $e);

            return null;
        }
    }

    public function getIssuancePlaceCode(?string $issuancePlace): ?string
    {
        if (empty($issuancePlace)) {
            return null;
        }

        $issuancePlaces = app(LookupService::class)->getIssuancePlaces();

        return $issuancePlaces->first(function ($place) use ($issuancePlace) {
            return strtolower($place->text) === strtolower($issuancePlace);
        })?->code ?? null;
    }

    public function getVehicleColorCode(?string $vehicleColor, int $quoteTypeId, ?int $providerId): ?string
    {
        LoggerService::info('OCR Utils - getVehicleColorCode called', extra: [
            'vehicleColor' => $vehicleColor,
            'quoteTypeId' => $quoteTypeId,
            'providerId' => $providerId,
        ]);

        if (empty($vehicleColor)) {
            LoggerService::info('OCR Utils - Vehicle color is empty, returning null');

            return null;
        }

        if (! $providerId) {
            LoggerService::warning('OCR Utils - No valid provider id for vehicle color code', extra: [
                'vehicleColor' => $vehicleColor,
                'quoteTypeId' => $quoteTypeId,
                'providerId' => $providerId,
            ]);

            return null;
        }

        $vehicleColors = app(LookupService::class)->getVehicleColors($quoteTypeId, $providerId);

        $matchedColor = $vehicleColors->first(function ($color) use ($vehicleColor) {
            return strtolower($color->text) === strtolower($vehicleColor);
        });

        LoggerService::info('OCR Utils - Vehicle color lookup result', extra: [
            'vehicleColor' => $vehicleColor,
            'providerId' => $providerId,
            'availableColors' => $vehicleColors->pluck('text')->toArray(),
            'matchedColor' => $matchedColor?->code,
            'matchedColorText' => $matchedColor?->text,
        ]);

        return $matchedColor?->code ?? null;
    }

    public function getBankCode(?string $bankName, int $quoteTypeId, ?int $providerId): ?string
    {
        if (empty($bankName)) {
            return null;
        }

        if (! $providerId) {
            LoggerService::warning('OCR Utils - No valid provider id for bank code');

            return null;
        }

        $banks = app(LookupService::class)->getBankNames($quoteTypeId, $providerId);

        return $banks->first(function ($bank) use ($bankName) {
            return strtolower($bank->text) === strtolower($bankName);
        })?->code ?? null;
    }


    public function extractPlateCodeNumber(?string $plateNumber): ?array
    {
        if (empty($plateNumber)) {
            return null;
        }

        $cleaned = trim($plateNumber);

        // $parts = explode('/', $cleaned, 2);
        if (preg_match('/^([A-Z0-9]+)[\/:\-\s\']*(\d+)$/i', $cleaned, $matches)) {
            LoggerService::info('OCR Utils - extractPlateCodeNumber - Plate code and number extracted', extra: [
                'plate_number' => $plateNumber,
                'matches' => $matches,
            ]);

            return [
                'plate_code' => $matches[1],
                'plate_number' => $matches[2],
            ];
        }

        return [
            'plate_code' => null,
            'plate_number' => null,
        ];
    }

    //----------------- End Ocr Util ------------------------------

    private function extractProviderCode(CarQuote $quote): ?string
    {
        if ($quote->payments && $quote->payments->isNotEmpty()) {
            $latestPayment = $quote->payments->first();
            if ($latestPayment && $latestPayment->insuranceProvider) {
                return $latestPayment->insuranceProvider->code;
            }
        }

        if ($quote->insuranceProvider) {
            return $quote->insuranceProvider->code;
        }

        return null;
    }

    private function getRefId(CarQuote $quote): string
    {
        return $quote->code;
    }

    public function checkLeadDocuments()
    {
        $uuid = request('uuid', 'GWNRK7CH');

        $carQuote = CarQuote::where('uuid', $uuid)
            ->with([
                'documents' => function ($q) {
                    $q->select('id', 'quote_documentable_id', 'doc_name', 'doc_url', 'document_type_code', 'created_at')
                        ->orderBy('created_at', 'desc');
                },
            ])
            ->first();

        if (!$carQuote) {
            return apiResponse(null, Response::HTTP_NOT_FOUND, 'Car quote not found');
        }

        $emiratesIdCodes = ['CEID', 'EID_CAR', 'IDC'];
        $allDocuments = $carQuote->documents;
        $emiratesIdDocuments = $allDocuments->filter(function ($doc) use ($emiratesIdCodes) {
            return in_array($doc->document_type_code, $emiratesIdCodes);
        });

        $documentTypeCodes = DocumentType::query()
            ->active()
            ->byQuoteTypeId(QuoteTypes::CAR->id())
            ->get()
            ->filter(fn (DocumentType $documentType) => OCRDocumentTypeEnum::getDocumentType($documentType) !== null)
            ->pluck('code')
            ->values()
            ->all();

        $ocrEligibleDocuments = $allDocuments->filter(function ($doc) use ($documentTypeCodes) {
            return in_array($doc->document_type_code, $documentTypeCodes);
        });

        LoggerService::info(self::class.'::checkLeadDocuments - Lead documents checked', extra: [
            'quote_id' => $carQuote->id,
            'quote_uuid' => $carQuote->uuid,
            'quote_code' => $carQuote->code,
            'total_documents' => $allDocuments->count(),
            'emirates_id_documents_count' => $emiratesIdDocuments->count(),
            'ocr_eligible_documents_count' => $ocrEligibleDocuments->count(),
            'emirates_id_documents' => $emiratesIdDocuments->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'doc_name' => $doc->doc_name,
                    'document_type_code' => $doc->document_type_code,
                    'doc_url' => $doc->doc_url,
                    'created_at' => $doc->created_at,
                ];
            })->values()->all(),
            'all_documents' => $allDocuments->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'doc_name' => $doc->doc_name,
                    'document_type_code' => $doc->document_type_code,
                    'created_at' => $doc->created_at,
                ];
            })->values()->all(),
        ]);

        return apiResponse([
            'quote_id' => $carQuote->id,
            'quote_uuid' => $carQuote->uuid,
            'quote_code' => $carQuote->code,
            'total_documents' => $allDocuments->count(),
            'emirates_id_documents_count' => $emiratesIdDocuments->count(),
            'ocr_eligible_documents_count' => $ocrEligibleDocuments->count(),
            'emirates_id_documents' => $emiratesIdDocuments->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'doc_name' => $doc->doc_name,
                    'document_type_code' => $doc->document_type_code,
                    'doc_url' => $doc->doc_url,
                    'created_at' => $doc->created_at instanceof \Carbon\Carbon ? $doc->created_at->toDateTimeString() : $doc->created_at,
                ];
            })->values()->all(),
            'all_documents' => $allDocuments->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'doc_name' => $doc->doc_name,
                    'document_type_code' => $doc->document_type_code,
                    'created_at' => $doc->created_at instanceof \Carbon\Carbon ? $doc->created_at->toDateTimeString() : $doc->created_at,
                ];
            })->values()->all(),
        ], Response::HTTP_OK, 'Lead documents retrieved successfully');
    }
}
