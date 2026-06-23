<?php

namespace App\Http\Controllers\V2;

use App\Enums\AmlAutomationStatus;
use App\Enums\AMLDecisionStatusEnum;
use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\CarRegistrationType;
use App\Enums\CustomerTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\EmirateUpdateSourceEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\Kyc;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TravelQuoteEnum;
use App\Enums\UserNameEnum;
use App\Enums\WorkflowTypeEnum;
use App\Exports\AmlCftReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\AMLCheckRequest;
use App\Http\Requests\AMLRequest;
use App\Http\Requests\AutomateQuoteAmlScreeningRequest;
use App\Http\Requests\InsuredKycRequest;
use App\Http\Requests\RetriggerTravelAmlScreeningRequest;
use App\Http\Requests\SkipBridgerScreeningRequest;
use App\Http\Requests\TogglePolicyIssuanceAutomationRequest;
use App\Http\Requests\UpdateAdditionalVehicleDriverDetailsRequest;
use App\Jobs\AmlScreeningAutomationJob;
use App\Jobs\BridgerAMLJob;
use App\Jobs\ExportCsvAndSendEmailJob;
use App\Jobs\InsurerAMLScreeningJob;
use App\Models\AML;
use App\Models\AmlAutomation;
use App\Models\BikeQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\KycLog;
use App\Models\PersonalQuoteDetail;
use App\Models\QuoteStatus;
use App\Models\QuoteType;
use App\Models\TravelQuote;
use App\Models\User;
use App\Repositories\QuoteTypeRepository;
use App\Services\AML\AMLDisplayService;
use App\Services\AML\AMLEntityService;
use App\Services\AML\AMLExportService;
use App\Services\AML\AMLInsuredService;
use App\Services\AML\AMLInsurerService;
use App\Services\AML\AMLQueryService;
use App\Services\AML\AMLQuoteDetailsService;
use App\Services\AMLService;
use App\Services\BridgerInsightService;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\QuoteDocumentService;
use App\Services\SIBService;
use App\Services\TravelQuoteService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Inertia\Response;
use Inertia\ResponseFactory;

class AMLController extends Controller
{
    use GenericQueriesAllLobs;

    public function __construct()
    {
        // Reminder:: move middleware to routes file (route specific middleware)
        $this->middleware('permission:'.PermissionsEnum::DATA_EXTRACTION, ['only' => ['export']]);
        $this->middleware('permission:'.PermissionsEnum::CAR_LEGACY_KYC_SKIP_INSURER_API, ['only' => ['togglePolicyIssuanceAutomation']]);
    }

    public function index(AMLRequest $request, AMLQueryService $amlQueryService)
    {
        $quoteTypes = QuoteTypeRepository::getQuoteTypesByLob();
        $quoteStatuses = QuoteStatus::withActive()->orderBy('sort_order')->get();
        $quotes = $amlQueryService->getAMLQuotes($request);

        return inertia('Aml/Index', [
            'quoteTypes' => $quoteTypes,
            'quoteStatuses' => $quoteStatuses,
            'aml' => $quotes,
        ]);
    }

    public function amlQuoteDetails($quoteTypeId, $quoteRequestId, AMLQuoteDetailsService $amlQuoteDetailsService)
    {
        $resolvedId = $amlQuoteDetailsService->resolveQuoteRequestId($quoteTypeId, $quoteRequestId);
        abort_if($resolvedId === 0, 404);

        $data = $amlQuoteDetailsService->prepareQuoteDetailsData(
            (int) $quoteTypeId,
            $resolvedId
        );

        return inertia('Aml/DetailPage', $data);
    }

    /**
     * Display the specified resource.
     *
     * @return Response|ResponseFactory
     */
    public function show(AML $aml, AMLDisplayService $amlDisplayService, $insuredId = null, $customerId = null)
    {
        $data = $amlDisplayService->prepareShowData(
            $aml,
            $insuredId,
            $customerId
        );

        return inertia('Aml/Show', $data);
    }

    public function getInsuredDetails(Request $request, AMLInsuredService $amlInsuredService): JsonResponse
    {
        $result = $amlInsuredService->getInsuredDetails(
            $request->customer_type,
            $request->id_type,
            $request->id_number
        );

        return response()->json($result);
    }

    public function fetchEntity(Request $request, AMLEntityService $entityService)
    {
        $entity = $entityService->fetchEntityByTradeLicense($request->trade_license);

        if ($entity) {
            return response()->json([
                'status' => true,
                'response' => $entity,
                'message' => 'Entity found with the entered Trade License number',
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'No Entity found with the entered Trade License number',
        ]);
    }

    public function linkEntityDetails(Request $request, AMLEntityService $entityService)
    {
        $result = $entityService->linkEntityToQuote(
            $request->quote_type_id,
            $request->quote_request_id,
            $request->entity_id,
            $request->triggeredFrom
        );

        return response()->json($result);
    }

    public function export(Request $request, AMLExportService $amlExportService)
    {
        return $amlExportService->exportAMLLogs($request);
    }

    public function quoteStatusUpdate($quoteTypeId, $quoteRequestId, $quoteStatusType)
    {
        $quoteType = QuoteType::where('id', $quoteTypeId)->first();
        $quoteObject = $this->getQuoteObject($quoteType->code, $quoteRequestId);

        LoggerService::startQuoteLogging($quoteObject, LoggerFeatureEnum::AML_SCREENING);
        LoggerService::info(self::class.' fn: '.__FUNCTION__);

        if (isset(request()->decisonsForUpdatePortal)) {
            request()->merge(['ref_id' => $quoteObject->code]);
            $response = AMLService::updateAMLDecisionLexisNexis(request());
            if ($response['status'] == 'success') {
                $clientFullName = $quoteObject->first_name.' '.$quoteObject->last_name;
                $updatedAMLStatus = app(AMLService::class)->updateAMLStatusAgainstDecision(request()->toArray(), $quoteObject);
                $responseMessage = ['status' => 'success', 'message' => 'AML Status Updated'];

                if (
                    auth()->user()->hasRole(RolesEnum::ComplianceSuperUser) ||
                    (auth()->user()->hasRole(RolesEnum::COMPLIANCE) && request()->aml_decision == AMLDecisionStatusEnum::FALSE_POSITIVE)
                ) {
                    app(AMLService::class)->sendAMLQuoteStatusChangeNotification($quoteTypeId, $quoteObject->id, $updatedAMLStatus, $quoteObject->code, $quoteType->text, $quoteObject->pa_id, $clientFullName);
                    if (! empty(request()->complianceComponent)) {
                        app(AMLService::class)->saveKYCComplianceQuestions(request()->complianceComponent);
                    }
                }

                $response = ['status' => $response['status'], 'message' => $response['message'].' and '.$responseMessage['message']];
            } else {
                $response = ['status' => $response['status'], 'message' => $response['message']];
            }
        }

        return response()->json($response);
    }

    public function checkMissingTravelAmlRequirement(Request $request)
    {
        LoggerService::info(self::class.' fn: '.__FUNCTION__);

        $travelQuoteService = app(TravelQuoteService::class);
        $customerTravelInfo = (array) $travelQuoteService->getCustomerTravelInfo($request->quoteRequestId, $request->quoteType);

        if (empty($customerTravelInfo['id'])) {
            return response()->json(['status' => false, 'message' => 'Record not found'], 404);
        }

        return response()->json($travelQuoteService->checkCustomerTravelInfoIsComplete($customerTravelInfo), 200);
    }

    public function quoteUpdate(AMLCheckRequest $AMLCheckRequest, $quoteTypeId, $quoteRequestId)
    {
        $quoteId = $quoteRequestId;
        $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
        $updateQuote = $this->getQuoteObject($quoteType->code, $quoteId);

        LoggerService::startQuoteLogging($updateQuote, LoggerFeatureEnum::AML_SCREENING);
        LoggerService::info('IM AML Screening Process Started');

        $systemUser = User::where('name', UserNameEnum::System)->first();
        $isAutomation = $AMLCheckRequest->isTrustedInternalAutomation();
        $processbyUser = $isAutomation ? $systemUser : FacadesAuth::user();

        if ($updateQuote) {
            [$status, $message, $getMemberOrUBODetails, $getLastScreening] = app(AMLService::class)->prepareScreeningData($AMLCheckRequest, $quoteType, $updateQuote);

            if (! $status) {
                LoggerService::info('Failed to prepare screening data');

                return app(AMLService::class)->handleResponse($status, $message, $isAutomation);
            }

            Context::add('emirate_update_source', EmirateUpdateSourceEnum::AML_SCREEN->value);

            try {
                [$shouldApplicableForScreening, $insured, $entityId] = app(AMLService::class)->processInsuredDataForScreening($AMLCheckRequest, $quoteType->id, $updateQuote, $getLastScreening);
                app(AMLService::class)->updatePAId([
                    'isAutomation' => $isAutomation,
                    'systemUser' => $systemUser,
                    'processbyUser' => $processbyUser,
                    'quoteType' => $quoteType,
                    'quoteRequestId' => $quoteRequestId,
                ], $updateQuote);

                LoggerService::info('Completed execution of processInsuredDataForScreening in quoteUpdate');
            } catch (QueryException $e) {
                if ($e->getCode() == '40001' || str_contains($e->getMessage(), 'Lock wait timeout')) {
                    LoggerService::warning('Lock timeout during AML screening process', [
                        'quote_id' => $quoteRequestId,
                        'quote_type_id' => $quoteType->id,
                        'error' => $e->getMessage(),
                    ]);

                    return app(AMLService::class)->handleResponse(false, 'System is busy, please try again', $isAutomation);
                }

                throw $e;
            }

            session()->put('amlResponseCheck', []);
            $isEntity = $AMLCheckRequest->customer_type == CustomerTypeEnum::Entity;
            if ($shouldApplicableForScreening) {
                if ($isEntity) {
                    $getEntityDetailsForScreening = [
                        'company_name' => $insured->company_name,
                        'code' => CustomerTypeEnum::EntityShort.'-'.$entityId, // TODO:: code should be updated with insured id (Required FR for this)
                    ];

                    $bridgerInsightService = new BridgerInsightService;
                    $bridgerAPIToken = $bridgerInsightService->getJWTToken();

                    LoggerService::info('Dispatching AML Screening Job against Entity for Screening', extra: [
                        'payload' => $getEntityDetailsForScreening,
                        'insuredId' => $insured->id,
                    ]);
                    BridgerAMLJob::dispatchSync($bridgerAPIToken, $getEntityDetailsForScreening, $updateQuote, $quoteTypeId, CustomerTypeEnum::Entity, auth()->user()->email);

                    if (isset($AMLCheckRequest->company_name) && in_array($quoteTypeId, [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Yacht, QuoteTypeId::Car])) {
                        $updateQuote->company_name = $AMLCheckRequest->company_name;
                        $updateQuote->company_address = $AMLCheckRequest->company_address;
                        $updateQuote->save();
                    }
                } else {
                    // Handle individual screening
                    $individualDetails = [
                        'first_name' => $insured->first_name,
                        'last_name' => $insured->last_name,
                        'dob' => $insured->dob ? Carbon::parse($insured->dob)->format(config('constants.DATE_FORMAT_ONLY')) : null,
                        'nationality' => $insured?->nationality->toArray() ?? [],
                        'code' => CustomerTypeEnum::IndividualShort.'-'.$AMLCheckRequest->customer_id, // TODO:: code should be updated with insured id (Required FR for this)
                    ];
                    $getMemberOrUBODetails[] = $individualDetails;

                    LoggerService::info('Individual Screening payload', extra: $individualDetails);
                }
            }

            if (in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Home])) {
                $this->updateChassisNumber($quoteTypeId, $AMLCheckRequest, $quoteRequestId, $updateQuote);
            }

            // Process members (UBO or regular members)
            if (empty($getMemberOrUBODetails->toArray()) && ! $shouldApplicableForScreening) {
                LoggerService::info('No Member Found for Screening, AML Screening status set to Cleared');

                return app(AMLService::class)->handleResponse(true, 'AML Screening Completed', $isAutomation);
            }

            $bridgerInsightService = new BridgerInsightService;
            $bridgerAPIToken = $bridgerInsightService->getJWTToken();

            // Job dispatch for all members including customer
            LoggerService::info('Dispatching AML Screening Job for Members including primary insured', extra: [
                'membersDetails' => $getMemberOrUBODetails->toArray() ?? [],
            ]);
            $this->AMLJobDispatchForMembers($updateQuote, $getMemberOrUBODetails, $bridgerAPIToken, $quoteRequestId, $quoteTypeId, CustomerTypeEnum::Individual, $processbyUser, isAutomation: $isAutomation);

            return app(AMLService::class)->handleResponse(true, 'Quote is updated', $isAutomation);
        }

        LoggerService::info('Something went wrong in AML Screening process');

        return app(AMLService::class)->handleResponse(false, 'Something went wrong', $isAutomation);
    }

    private function InsurerScreening($quoteTypeId, $AMLCheckRequest, $updateQuote)
    {
        LoggerService::startQuoteLogging($updateQuote, LoggerFeatureEnum::INSURER_AML_SCREENING_WITH_KYC_DOCUMENT);
        $insurerAMLScreeningResponse = [];

        if (! isTapEnabled()) {
            LoggerService::info('Tap integration is disabled. Skipping Insurer AML Screening process');

            return $insurerAMLScreeningResponse;
        }

        LoggerService::info('Tap integration is enabled. Insurer AML Screening process started');
        $enableInsurerScreening = [
            QuoteTypes::CAR->id(),
            QuoteTypes::HOME->id(),
            QuoteTypes::TRAVEL->id(),
            QuoteTypes::BIKE->id(),
        ];
        if (in_array($quoteTypeId, $enableInsurerScreening)) {
            session()->put('insurerAMLScreeningResponse');
            InsurerAMLScreeningJob::dispatchSync($quoteTypeId, $updateQuote, CustomerTypeEnum::Individual, $AMLCheckRequest->toArray());
            $getInsurerScreeningResponse = collect(session()->get('insurerAMLScreeningResponse', []))->first();
            if (! empty($getInsurerScreeningResponse)) {
                $insurerAMLScreeningResponse = [
                    'status' => $getInsurerScreeningResponse['status'],
                    'message' => $getInsurerScreeningResponse['message'],
                    'isEmailMismatched' => $getInsurerScreeningResponse['isEmailMismatched'] ?? false,
                    'isRenewalLead' => $getInsurerScreeningResponse['isRenewalLead'] ?? false,
                    'is_previous_policy_expired' => $getInsurerScreeningResponse['is_previous_policy_expired'] ?? false,
                    'is_get_quote_api_failed' => $getInsurerScreeningResponse['is_get_quote_api_failed'] ?? false,
                ];

                if (isset($getInsurerScreeningResponse['autoCaptureStatus'])) {
                    $insurerAMLScreeningResponse['autoCaptureStatus'] = $getInsurerScreeningResponse['autoCaptureStatus'];
                    $insurerAMLScreeningResponse['autoCaptureMessage'] = $getInsurerScreeningResponse['autoCaptureMessage'];
                }
            }
            session()->forget('insurerAMLScreeningResponse');
        }
        LoggerService::info('Insurer AML Screening process completed');

        return $insurerAMLScreeningResponse;
    }

    private function updateChassisNumber($quoteTypeId, $AMLCheckRequest, $quoteRequestId, $updateQuote)
    {
        LoggerService::info('Chassis Number Update Started');

        if ($quoteTypeId == QuoteTypes::CAR->id()) {
            $carQuoteRequestDetails = CarQuoteRequestDetail::where('car_quote_request_id', $quoteRequestId)->first();
            $carQuoteRequestDetails->chassis_number = $AMLCheckRequest->chassis_number;
            $carQuoteRequestDetails->insurer_quote_email = $AMLCheckRequest->get_quote_email_gig;
            if ($carQuoteRequestDetails->isDirty()) {
                LoggerService::info('AML Screening Bridger - Chassis number and Insurer Quote Email updated for QuoteTypeId: '.$quoteTypeId);
                $carQuoteRequestDetails->save();
            }
        }

        if ($quoteTypeId == QuoteTypes::BIKE->id()) {
            $bikeQuoteRequest = BikeQuote::where('personal_quote_id', $quoteRequestId)->first();
            $personalQuoteDetailBikeRequest = PersonalQuoteDetail::where('personal_quote_id', $quoteRequestId)->first();

            $bikeQuoteRequest->chassis_number = $AMLCheckRequest->chassis_number;
            $personalQuoteDetailBikeRequest->insurer_quote_email = $AMLCheckRequest->get_quote_email_gig;

            if ($bikeQuoteRequest->isDirty()) {
                LoggerService::info('AML Screening Bridger - Chassis number and Insurer Quote Email updated for QuoteTypeId: '.$quoteTypeId);
                $bikeQuoteRequest->save();
            }

            if ($personalQuoteDetailBikeRequest->isDirty()) {
                $personalQuoteDetailBikeRequest->save();
            }
        }

        if ($quoteTypeId == QuoteTypes::HOME->id()) {
            $personalQuoteDetailHomeRequest = PersonalQuoteDetail::where('personal_quote_id', $quoteRequestId)->first();
            $personalQuoteDetailHomeRequest->insurer_quote_email = $AMLCheckRequest->get_quote_email_gig;
            if ($personalQuoteDetailHomeRequest->isDirty()) {
                LoggerService::info('AML Screening Bridger - Insurer Quote Email updated for QuoteTypeId: '.$quoteTypeId);
                $personalQuoteDetailHomeRequest->save();
            }
        }

        LoggerService::info('Chassis Number Update Completed');
    }

    private function AMLJobDispatchForMembers($quoteDetails, $membersDetails, $bridgerAPIToken, $quoteRequestId, $quoteTypeId, $customerType, $processByUser = null, $isAutomation = false)
    {
        foreach ($membersDetails as $memberDetail) {
            BridgerAMLJob::dispatchSync($bridgerAPIToken, $memberDetail, $quoteDetails, $quoteTypeId, $customerType, $processByUser?->email ?? auth()->user()?->email, isAutomation: $isAutomation);
        }

        if (! in_array(true, session()->get('amlResponseCheck')) && ! AMLService::checkAMLStatusFailed($quoteTypeId, $quoteRequestId)) {
            $quoteTypeIds = [QuoteTypeId::Health, QuoteTypeId::Home, QuoteTypeId::Cycle, QuoteTypeId::Pet, QuoteTypeId::Yacht, QuoteTypeId::Corpline];
            if (in_array($quoteTypeId, $quoteTypeIds)) {
                $quoteDetails->stale_at = null;
            }

            $quoteDetails->aml_status = AMLStatusCode::AMLScreeningCleared;
            ($isAutomation && ! Config::get('audit.console', true)) && app(AMLService::class)->saveManualAuditLog($quoteDetails, $processByUser);
            $quoteDetails->save();
            // this event only working for travel lob
            if (QuoteTypes::TRAVEL->id() == $quoteTypeId) {
                $this->stopHapexReminder($quoteDetails);
            }
            // info('AML Screening Bridger - Potential Matches not Found, Quote Status changed to AML Screening Cleared - Ref-ID: '.$quoteDetails->code);
        } else {
            $quoteDetails->aml_status = AMLStatusCode::AMLScreeningFailed;
            ($isAutomation && ! Config::get('audit.console', true)) && app(AMLService::class)->saveManualAuditLog($quoteDetails, $processByUser);
            $quoteDetails->save();
            if (QuoteTypes::TRAVEL->id() == $quoteTypeId) {
                $isPassportDocumentExist = app(QuoteDocumentService::class)->isDocumentExists(quoteTypeCode::Travel, $quoteDetails->id, DocumentTypeCode::TRVLPAS);
                if (isset($quoteDetails->is_documents_valid) && ! $quoteDetails->is_documents_valid && ! $isPassportDocumentExist) {
                    $this->sendHapexReminder($quoteDetails);
                } else {
                    LoggerService::info(self::class.' - Hapex reminder not sent as passport document exists', extra: [
                        'quote_uuid' => $quoteDetails->uuid,
                        'isPassportDocumentExist' => $isPassportDocumentExist,
                        'is_documents_valid' => $quoteDetails->is_documents_valid,
                    ]);
                }
            }
            LoggerService::info('AML Screening Bridger - Potential Matches Found, Quote Status changed to AML Screening Failed');
        }

        session()->forget('amlResponseCheck');
    }

    private function saveManualAuditLog($quoteDetails, User $processByUser)
    {
        if (! $quoteDetails instanceof TravelQuote) {
            return false;
        }

        $dirty = $quoteDetails->getDirty();

        if (empty($dirty)) {
            return false;
        }

        $changes = [];
        foreach ($dirty as $attribute => $value) {
            $changes['old_values'][$attribute] = $quoteDetails->getOriginal($attribute);
            $changes['new_values'][$attribute] = $value;
        }

        $quoteDetails->audits()->create([
            'user_type' => get_class($processByUser),
            'user_id' => $processByUser->id ?? null,
            'event' => 'updated',
            'old_values' => $changes['old_values'],
            'new_values' => $changes['new_values'],
        ]);

        return true;
    }

    public function sendBridgerResponse(Request $request)
    {
        LoggerService::startQuoteLogging($request['quote_ref_id'], LoggerFeatureEnum::AML_SCREENING);
        LoggerService::info(self::class.' fn: '.__FUNCTION__, extra: [
            'bridger_response' => $request['bridger_response'],
        ]);
        $response = [];
        $kycLog = KycLog::withTrashed()->where('id', $request->aml_id)->first();

        if (! $kycLog) {
            return response()->json(['status' => 'error', 'message' => 'KYC log not found']);
        }

        $oldDecision = $kycLog->decision;

        if (checkModifiedRecord($kycLog->updated_at, $request->last_updated_at)) {
            return response()->json(['status' => 'error', 'message' => 'Record already modified please refresh the page']);
        }

        if (empty($kycLog->results)) {
            return response()->json(['status' => 'error', 'message' => 'KYC log results are empty']);
        }

        $bridgerResponse = json_decode($kycLog->results);
        $manualStatusIM = isset($bridgerResponse[0]->ManualStatusUpdateIM) ? (array) $bridgerResponse[0]->ManualStatusUpdateIM : [];

        if ($request->bridger_decision_type == AMLDecisionStatusEnum::TRUE_MATCH && auth()->user()->hasRole(RolesEnum::COMPLIANCE)) {
            $bridgerResponse[0]->ManualStatusUpdateIM = [$request->bridger_match_id => $request->bridger_decision_type];
            $kycLog->decision = AMLDecisionStatusEnum::SENT_FOR_REVIEW;
            $response['result_state'] = AMLDecisionStatusEnum::SENT_FOR_REVIEW;
        } else {
            if ((collect($manualStatusIM)->has($request->bridger_match_id) && $manualStatusIM[$request->bridger_match_id] == AMLDecisionStatusEnum::TRUE_MATCH) &&
                $request->bridger_decision_type == AMLDecisionStatusEnum::FALSE_POSITIVE
            ) {
                $kycLog->decision = AMLDecisionStatusEnum::ESCALATED;
                $response = ['status' => 'success', 'message' => 'Result update successfully'];
            }

            if (! empty($manualStatusIM)) {
                unset($bridgerResponse[0]->ManualStatusUpdateIM->{$request->bridger_match_id});
            }
        }

        $newDecision = $kycLog->decision;
        $kycLog->results = json_encode($bridgerResponse);
        $kycLog->save();

        $infoLog = 'AML Screening Bridger - AML Logs decision changed. Old Decision: '.$oldDecision.' - New Decision: '.$newDecision;

        if ($request->bridger_decision_type == AMLDecisionStatusEnum::TRUE_MATCH) {
            $infoLog .= ' - Email triggered to Compliance Super User';
            AMLService::sendAMLMatchedEmailtoComplianceTeam(
                config('constants.APP_URL').$request['aml_quote_url'],
                $request['quote_ref_id'],
                $request['bridger_response'],
                $request['customer_entity_name'],
                $request['quote_type_text'],
                auth()->user()->email,
                true
            );
            $response['status'] = 'success';
            $response['message'] = 'Email Triggered to Compliance Super User';
        }
        LoggerService::info($infoLog, extra: [
            'triggeredBy' => auth()->user()->email,
        ]);

        return response()->json($response);
    }

    public function updateQuoteComment(Request $request)
    {
        $request->validate([
            'compliance_comments' => 'required|string',
            'modelType' => 'required',
            'quote_id' => 'required',
        ]);

        $model = '\\App\\Models\\'.ucwords($request->modelType).'Quote';
        if (checkPersonalQuotes(ucwords($request->modelType))) {
            $model = '\\App\\Models\\PersonalQuote';
        }
        $quoteModel = $model::where('id', $request->quote_id)->first();
        $quoteModel->update([
            'compliance_comments' => $request->compliance_comments,
        ]);

        return response()->json(['message' => 'Comment added successfully', 'data' => $quoteModel]);
    }

    public function insuredKycDetailsUpdate(InsuredKycRequest $insuredKycRequest)
    {
        LoggerService::info(self::class.' fn: '.__FUNCTION__);
        $quoteType = QuoteTypes::getName($insuredKycRequest->quote_type_id)->value;
        $quote = $this->getQuoteObjectBy($quoteType, $insuredKycRequest->quote_uuid, 'uuid');
        LoggerService::startQuoteLogging($quote);
        $payment = $quote->payments()->mainLeadPayment()->first();
        $insuranceProvider = getInsuranceProvider($payment, $quoteType);

        $response = ['success' => false];
        $insurerAMLScreeningResponse = [];

        $isPolicyIssuanceAutomationEnabled = true;
        $isCarQuote = $insuredKycRequest->quote_type_id == QuoteTypeId::Car;
        if ($isCarQuote) {
            $isPolicyIssuanceAutomationEnabled = $quote->isQuotePolicyIssuanceAutomationEnabled();
        }

        LoggerService::info('Check Quote Policy Issuance Enabled condition', extra: [
            'isCarQuote' => $isCarQuote,
            'isPolicyIssuanceAutomationEnabled' => $isPolicyIssuanceAutomationEnabled,
        ]);
        // Insurer AML Screening is required if policy issuance automation is enabled
        if (
            $insuredKycRequest->customer_type == CustomerTypeEnum::Individual &&
            $isPolicyIssuanceAutomationEnabled &&
            ! (
                $insuranceProvider?->code == InsuranceProvidersEnum::RSA &&
                $quote?->registration_type != CarRegistrationType::PERSONAL
            )
        ) {
            $insurerAMLScreeningResponse = $this->InsurerScreening($insuredKycRequest->quote_type_id, $insuredKycRequest, $quote);

            if (! empty($insurerAMLScreeningResponse)) {
                $response['insurer_screening'] = [
                    'status' => $insurerAMLScreeningResponse['status'],
                    'message' => $insurerAMLScreeningResponse['message'],
                    'isEmailMismatched' => $insurerAMLScreeningResponse['isEmailMismatched'] ?? false,
                    'autoCaptureStatus' => $insurerAMLScreeningResponse['autoCaptureStatus'] ?? null,
                    'autoCaptureMessage' => $insurerAMLScreeningResponse['autoCaptureMessage'] ?? null,
                    'isPolicyExpired' => $insurerAMLScreeningResponse['is_previous_policy_expired'] ?? false,
                    'isGetQuoteAPIFailed' => $insurerAMLScreeningResponse['is_get_quote_api_failed'] ?? false,
                ];
            }
        }

        if (empty($insurerAMLScreeningResponse) || $insurerAMLScreeningResponse['status'] == AMLStatusCode::AMLScreeningCleared || $insurerAMLScreeningResponse['is_previous_policy_expired']) {
            $preparedFormData = app(AMLService::class)->prepareInsuredKycFormData($insuredKycRequest, $quote, $quoteType);
            $response['success'] = $preparedFormData;

            if (! $isPolicyIssuanceAutomationEnabled) {
                $response['message'] = 'Please Capture and Issue Policy Manually.';
            }

            $quote = $quote->refresh();

            $isHealthQuote = $insuredKycRequest->quote_type_id == QuoteTypeId::Health;

            $isHealthAndSTPCase = $isHealthQuote && $quote?->isSTPCase();
            $isAmlAndKycCleared = $quote?->aml_status == AMLStatusCode::AMLScreeningCleared && $quote?->kyc_decision == Kyc::COMPLETE;
            $policyAutomation = (new PolicyIssuanceService)->init($quoteType, $insuranceProvider?->code);
            $isPolicyAutomationEnabled = $policyAutomation?->isPolicyIssuanceAutomationEnabled() ?? false;
            LoggerService::info('Policy Automation AutoCapture Checks', extra: [
                'QuoteType' => $quoteType,
                'STP Case' => $isHealthQuote ? $quote?->isSTPCase() : false,
                'AML Status' => $quote?->aml_status,
                'KYC Status' => $quote?->kyc_decision,
                'carQuotePolicyIssuanceToggle' => $isCarQuote ? $isPolicyIssuanceAutomationEnabled : null,
                'insurerPolicyAutomationEnabled' => $isPolicyAutomationEnabled,
            ]);
            if ($isHealthAndSTPCase && $isAmlAndKycCleared && $isPolicyAutomationEnabled) {
                $isAutoCaptureStarted = app(CentralService::class)->autoCapturePaymentProcess($insuredKycRequest->quote_type_id, $quote);
                $response['autoCaptureStatus'] = $isAutoCaptureStarted['autoCaptureStatus'];
                $response['autoCaptureMessage'] = $isAutoCaptureStarted['autoCaptureMessage'];
            }
        }

        return response()->json($response);
    }

    public function stopHapexReminder($quote)
    {
        SIBService::createWorkflowEvent(WorkflowTypeEnum::TRAVEL_HAPEX_STOP_EMAIL_REMINDER, $quote, null, $quote);
        LoggerService::info(self::class.'- stopHapexReminder Hapex reminder stopped', extra: [
            'quoteUUID' => $quote->uuid,
            'time' => now(),
        ]);

        return true;
    }

    public function mapHapexMailPayload($quote)
    {
        LoggerService::info('fn:mapHapexMailPayload - AMLController');

        $directionCode = $quote['direction_code'] == TravelQuoteEnum::TRAVEL_UAE_INBOUND ? TravelQuoteEnum::IN_BOUND : TravelQuoteEnum::OUT_BOUND;

        return [
            'carQuoteId' => $quote->code,
            'customerName' => "{$quote->first_name} {$quote->last_name}",
            'direction_code' => $quote->direction_code,
            'advisor' => ! empty($quote->advisor) ? (object) [
                'name' => $quote->advisor->name,
                'email' => $quote->advisor->email,
                'phone' => $quote->advisor->mobile_no,
                'directLine' => $quote->advisor->landline_no,
                'whatsapp' => $quote->advisor->mobile_no,
            ] : [],
            'uploadDocsPage' => config('constants.ECOM_TRAVEL_INSURANCE_QUOTE_URL').$quote->uuid.'/thankyou/'.$directionCode,
        ];
    }

    public function mapHapexPlans($plans)
    {
        return collect($plans)->map(function ($plan) {
            return [
                'id' => $plan->id,
                'planName' => $plan->name,
                'repairType' => $plan->travelType,
                'vat' => $plan->vat,
                'actualPremium' => $plan->actualPremium,
                'discountPremium' => $plan->discountPremium,
                'benefits' => collect($plan->benefits->exclusion)->map(function ($benefit) {
                    return (object) [
                        'value' => $benefit->text,
                        'code' => $benefit->code,
                    ];
                }),
            ];
        });
    }

    public function sendHapexReminder($quote)
    {
        SIBService::createWorkflowEvent(WorkflowTypeEnum::TRAVEL_HAPEX_EMAIL_REMINDER, $quote, null, $this->mapHapexMailPayload($quote));
        LoggerService::info(self::class.'- sendHapexReminder Hapex reminder sent', extra: [
            'quoteUUID' => $quote->uuid,
            'time' => now(),
        ]);

        return true;
    }

    public function tempSkipBridgerAML(SkipBridgerScreeningRequest $skipBridgerScreeningRequest)
    {
        LoggerService::info(self::class.' fn: '.__FUNCTION__);
        $skipBrigerAMLResponse = app(AMLService::class)->tempSkipBridgerAML($skipBridgerScreeningRequest);

        return response()->json(['response' => $skipBrigerAMLResponse['status'], 'message' => $skipBrigerAMLResponse['response']]);
    }

    public function amlCtfReportExport(Request $request)
    {

        // Validate request parameters
        $request->validate([
            'recipientEmail' => 'sometimes|email',
            'subject' => 'sometimes|string',
            'ccRecipients' => 'sometimes|array',
            'ccRecipients.*' => 'email',
            'amlCreatedStartDate' => 'sometimes|date',
            'amlCreatedEndDate' => 'sometimes|date',
            'searchType' => 'sometimes|string|in:customerEmail,cdbId',
            'searchField' => 'sometimes|string',
            'quoteType' => 'sometimes|string',
        ]);

        // Set default recipient email to current user if not provided
        $recipientEmail = $request->recipientEmail ?? (FacadesAuth::check() ? FacadesAuth::user()->email : null);

        if (! $recipientEmail) {
            return response()->json([
                'error' => 'Recipient email is required.',
                'message' => 'Please provide a recipient email or ensure you are authenticated.',
            ], 400);
        }

        $reportYear = Carbon::now()->format('Y');
        $fileName = "AML CTF Report {$reportYear} ".Carbon::now()->format('Y-m-d_H-i-s');
        $subject = $request->subject ?? "AML/CFT Customer Risk Profile Report {$reportYear}";

        $requestParams = array_merge($request->all(), [
            'recipientEmail' => $recipientEmail,
            'subject' => $subject,
            'fileName' => $fileName,
            'ccRecipients' => $request->ccRecipients ?? [],
            'exportTitle' => 'AML/CFT Customer Risk Profile Report',
            'includeCustomFormatting' => true, // Enable custom CSV formatting
        ]);

        // Log the export request
        LoggerService::info(self::class.' fn: '.__FUNCTION__, extra: [
            'recipient' => $recipientEmail,
            'date_range' => [
                'start' => $request->amlCreatedStartDate,
                'end' => $request->amlCreatedEndDate,
            ],
            'filters' => [
                'searchType' => $request->searchType,
                'searchField' => $request->searchField,
                'quoteType' => $request->quoteType,
            ],
        ]);

        try {
            // Use the export class's emailCSV method for consistency
            ExportCsvAndSendEmailJob::dispatch(
                AmlCftReportExport::class,
                $recipientEmail,
                $requestParams
            );

            return response()->json([
                'message' => 'Your export is being processed. You will receive an email with the CSV file shortly.',
                'report_type' => $request->report,
                'recipient' => $recipientEmail,
                'subject' => $subject,
            ]);

        } catch (\Exception $e) {
            LoggerService::error(self::class.' fn: '.__FUNCTION__.' - Export failed', extra: [
                'error' => $e->getMessage(),
                'recipient' => $recipientEmail,
            ]);

            return response()->json([
                'error' => 'Failed to initiate AML/CFT report export.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateAddtionalVehicleDriverDetails(UpdateAdditionalVehicleDriverDetailsRequest $updateAdditionalVehicleDriverDetailsRequest)
    {
        $quoteType = QuoteTypes::getName($updateAdditionalVehicleDriverDetailsRequest->quote_type_id)->value;
        $quote = $this->getQuoteObjectBy($quoteType, $updateAdditionalVehicleDriverDetailsRequest->quote_uuid, 'uuid');

        LoggerService::startQuoteLogging($quote);

        $response = app(AMLService::class)->saveAdditionalVehicleAndDriverDetails($updateAdditionalVehicleDriverDetailsRequest, $quote);

        return response()->json([
            'success' => $response['status'],
            'message' => $response['message'],
            'is_insured_driver_same' => $response['is_insured_driver_same'] ?? null,
        ]);
    }

    public function getQuoteDetailsFromInsurer(Request $request)
    {
        $result = app(AMLInsurerService::class)->getQuoteDetailsFromInsurer($request->quoteTypeId, $request->quoteUID);

        return response()->json($result);
    }

    /**
     * Toggle policy issuance automation enabled status for a car quote
     *
     * @return JsonResponse
     */
    public function togglePolicyIssuanceAutomation(TogglePolicyIssuanceAutomationRequest $request)
    {
        $requestData = $request->safe();
        LoggerService::startQuoteLogging($requestData->quote_uuid, LoggerFeatureEnum::DISABLE_POLICY_ISSUANCE_AUTOMATION);

        try {
            $policyIssuanceService = app(PolicyIssuanceService::class);
            $result = $policyIssuanceService->togglePolicyIssuanceAutomation(
                $requestData,
                $requestData->quote_type_id,
                $requestData->enabled
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null,
            ], $result['status_code']);
        } catch (\Exception $e) {
            LoggerService::error('Error toggling policy issuance automation for quote', extra: [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to toggle policy issuance automation, Please try again later.',
            ], 500);
        }
    }

    /**
     * IMCRM: bulk-retrigger AML screening automation for Travel quotes in a given date range.
     *
     * Finds Travel quotes with PolicyBooked status and AML_PENDING, then queues
     * AmlScreeningAutomationJob for each in chunks of 50.
     */
    public function retriggerTravelAmlScreening(RetriggerTravelAmlScreeningRequest $request): JsonResponse
    {
        getAppStorageValueByKey(ApplicationStorageEnums::TRAVEL_AML_RETRIGGER_ENABLED) || abort(403, 'Retriggering AML screening is disabled.');

        $validated = $request->validated();

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        $dispatched = 0;

        TravelQuote::query()
            ->where('quote_status_id', QuoteStatusEnum::PolicyBooked)
            ->where(function ($query): void {
                $query->whereNull('aml_status')
                    ->orWhere('aml_status', AMLStatusCode::AMLPending);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(['id', 'code', 'uuid'])
            ->chunk(50, function ($quotes) use (&$dispatched): void {
                foreach ($quotes as $quote) {
                    AmlAutomation::updateOrCreate(
                        ['code' => $quote->code],
                        ['status' => AmlAutomationStatus::Queue->value],
                    );

                    AmlScreeningAutomationJob::dispatch(QuoteTypes::TRAVEL, $quote);
                    $dispatched++;
                }
            });

        return response()->json([
            'success' => true,
            'message' => "Retrigger AML screening dispatched for {$dispatched} Travel quote(s).",
            'data' => ['dispatched_count' => $dispatched],
        ]);
    }

    /**
     * IMCRM: trigger AML screening automation for an allowed LOB by quote UUID and explicit {@see QuoteTypes} value.
     */
    public function automateQuoteAmlScreening(AutomateQuoteAmlScreeningRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = app(AMLService::class)->initiateAutomatedAmlByQuoteUuid(
            $validated['quoteUuid'],
            $request->validatedQuoteType(),
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
        ], $result['http_status']);
    }
}
