<?php

namespace App\Http\Controllers\V2;

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\AMLStatusCode;
use App\Enums\CustomerTypeEnum;
use App\Enums\DatabaseColumnsString;
use App\Enums\DocumentTypeCode;
use App\Enums\GenericModelTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\InsuranceProvidersEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\LookupsEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TravelQuoteEnum;
use App\Enums\UserNameEnum;
use App\Enums\WorkflowTypeEnum;
use App\Exports\AmlCftReportExport;
use App\Exports\KycLogsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\AMLCheckRequest;
use App\Http\Requests\AMLRequest;
use App\Http\Requests\InsuredKycRequest;
use App\Http\Requests\SkipBridgerScreeningRequest;
use App\Http\Requests\UpdateAdditionalVehicleDriverDetailsRequest;
use App\Jobs\BridgerAMLJob;
use App\Jobs\ExportCsvAndSendEmailJob;
use App\Jobs\InsurerAMLScreeningJob;
use App\Models\AML;
use App\Models\BikeQuote;
use App\Models\BusinessCoverType;
use App\Models\BusinessQuoteType;
use App\Models\CarQuoteRequestDetail;
use App\Models\CommunicationMode;
use App\Models\Customer;
use App\Models\CustomerInsured;
use App\Models\Emirate;
use App\Models\Entity;
use App\Models\Insured;
use App\Models\KycLog;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\PersonalQuoteDetail;
use App\Models\PolicyIssuance;
use App\Models\QuoteRequestEntityMapping;
use App\Models\QuoteStatus;
use App\Models\QuoteStatusLog;
use App\Models\QuoteType;
use App\Models\TravelQuote;
use App\Models\User;
use App\Repositories\CarQuoteRepository;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\NationalityRepository;
use App\Repositories\QuoteTypeRepository;
use App\Services\AMLService;
use App\Services\BridgerInsightService;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\Car\LivaInsuranceService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use App\Services\QuoteDocumentService;
use App\Services\SIBService;
use App\Services\TravelQuoteService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class AMLController extends Controller
{
    use GenericQueriesAllLobs;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('permission:aml-list', ['only' => ['index']]);
        $this->middleware('permission:'.PermissionsEnum::DATA_EXTRACTION, ['only' => ['export']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(AMLRequest $request)
    {
        $quoteTypes = QuoteTypeRepository::allowedQuoteForAml();
        $quoteStatuses = QuoteStatus::withActive()->orderBy('sort_order')->get();
        $quotes = [];

        if ($request->ajax()) {
            if (isset($request->quoteType) && ! empty($request->quoteType)) {
                $quoteTypeId = $quoteTypes->where('code', $request->quoteType)->first()?->id;
                $quoteRequestTable = strtolower($request->quoteType).'_quote_request';

                if (in_array($quoteTypeId, [
                    QuoteTypes::BIKE->id(),
                    QuoteTypes::YACHT->id(),
                    QuoteTypes::PET->id(),
                    QuoteTypes::CYCLE->id(),
                    QuoteTypes::JETSKI->id(),
                    QuoteTypes::LIFE->id(),
                    QuoteTypes::SAVINGS->id(),
                    QuoteTypes::HOME->id(),
                ])) {
                    if (isset($request->amlCreatedStartDate) && ! empty($request->amlCreatedStartDate)) {
                        $quoteRequestTable = AMLService::isDataMigrated($quoteTypeId, '', $request->amlCreatedStartDate) ? 'personal_quotes' : $quoteRequestTable;
                    } else {
                        if (isset($request->searchType) && in_array($request->searchType, ['cdbId', 'customerEmail'])) {
                            $searchType = match ($request->searchType) {
                                'cdbId' => DatabaseColumnsString::CODE,
                                'customerEmail' => DatabaseColumnsString::EMAIL,
                            };

                            $createdDate =
                                $request->searchType == 'id' ? AML::where($searchType, $request->searchField)->firstOrFail()->created_at :
                                PersonalQuote::where($searchType, $request->searchField)->firstOrFail()->created_at;

                            $quoteRequestTable = AMLService::isDataMigrated($quoteTypeId, '', $createdDate) ? 'personal_quotes' : strtolower($request->quoteType).'_quote_request';
                        }
                    }
                }

                $dataAml = DB::table($quoteRequestTable);
                if ($quoteRequestTable == strtolower(quoteTypeCode::Pet).'_quote_request') {
                    $dataAml = $dataAml->select($quoteRequestTable.'.*', $quoteRequestTable.'.personal_quote_id as id', DB::raw('"'.$request->quoteType.' Insurance" as quote_type_text, "'.$quoteTypeId.'" as quote_type_id'), $quoteRequestTable.'.code as cdb_id');
                } else {
                    $dataAml = $dataAml->select($quoteRequestTable.'.*', DB::raw('"'.$request->quoteType.' Insurance" as quote_type_text, "'.$quoteTypeId.'" as quote_type_id'), $quoteRequestTable.'.code as cdb_id');
                }

                $dataAml = $dataAml->orderBy($quoteRequestTable.'.created_at', 'desc');
                if ($quoteRequestTable == 'personal_quotes') {
                    $dataAml->where($quoteRequestTable.'.quote_type_id', $quoteTypeId);
                }
                if (
                    isset($request->searchType) && ! empty($request->searchType) &&
                    isset($request->searchField) && ! empty($request->searchField)
                ) {
                    if ($request->searchType == 'cdbId') {
                        $dataAml->where($quoteRequestTable.'.code', $request->searchField);
                    }

                    if ($request->searchType == 'customerEmail') {
                        $dataAml->where($quoteRequestTable.'.email', $request->searchField);
                    }
                }
                if (isset($request->matchFound)) {
                    if ($request->matchFound == 'False') {
                        $dataAml->where('kyc_logs.results_found', '=', '0');
                    }
                    if ($request->matchFound == 'True') {
                        $dataAml->where('kyc_logs.results_found', '>', '0');
                    }
                }
                if (
                    isset($request->amlCreatedStartDate) && ! empty($request->amlCreatedStartDate) &&
                    isset($request->amlCreatedEndDate) && ! empty($request->amlCreatedEndDate)
                ) {
                    $dataAml->whereBetween($quoteRequestTable.'.created_at', dateQueryFilter($request->amlCreatedStartDate, $request->amlCreatedEndDate));
                }

                $quotes = $dataAml->simplePaginate(10)->withQueryString();
            }
        }

        return inertia('Aml/Index', [
            'quoteTypes' => $quoteTypes,
            'quoteStatuses' => $quoteStatuses,
            'aml' => $quotes,
        ]);
    }

    public function export(Request $request)
    {
        $reportDateRange = Carbon::parse($request->amlCreatedStartDate)->toDateString().' - '.Carbon::parse($request->amlCreatedEndDate)->toDateString();

        $request->merge([
            'exportTitle' => 'AML',
            'created_at_start' => $request->amlCreatedStartDate,
            'created_at_end' => $request->amlCreatedEndDate,
        ]);

        if ($request->exportType == 'email') {
            return app(KycLogsExport::class)->emailCSV("AML Logs {$reportDateRange}", $request->all());
        }

        return app(KycLogsExport::class)->download("AML Logs {$reportDateRange}");
    }

    /**
     * Display the specified resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(AML $aml, $insuredId = null, $customerId = null)
    {
        $amlResults = collect(json_decode($aml->results))->first() ?? [];
        $manualStatusUpdateIM = collect($amlResults->ManualStatusUpdateIM ?? []);
        $aml->quote_type_text = $aml->quotetype->text;
        $quoteType = QuoteType::where('id', $aml->quote_type_id)->first();
        $quoteObject = $this->getQuoteObject($quoteType->code, $aml->quote_request_id);
        $insured = Insured::where('id', $insuredId)->with('insuredKyc')->first() ?? null;

        if (isset($amlResults->Watchlist)) {
            $amlResults = collect($amlResults->Watchlist->Matches)->filter(function ($value) use ($manualStatusUpdateIM) {
                $value->decision = (! $value->FalsePositive && ! $value->TrueMatch) ?
                    ($manualStatusUpdateIM->has($value->ID) ? $manualStatusUpdateIM->get($value->ID) : AMLDecisionStatusEnum::UNKNOWN) :
                    AMLDecisionStatusEnum::TRUE_MATCH;

                return $value->FalsePositive == false;
            })->values();
        }

        return inertia('Aml/Show', [
            'aml' => $aml,
            'amlResults' => $amlResults,
            'quoteStatusCode' => quoteStatusCode::asArray(),
            'amlDecisionStatusCode' => AMLDecisionStatusEnum::asArray(),
            'quoteObject' => $quoteObject,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'insured' => $insured ?? null,
            'customerId' => $customerId ?? null,
        ]);
    }

    public function amlQuoteDetails($quoteTypeId, $quoteRequestId)
    {
        $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
        $quoteRequest = AMLService::getQuoteDetails($quoteTypeId, $quoteRequestId);

        LoggerService::startQuoteLogging($quoteRequest, LoggerFeatureEnum::AML_SCREENING);
        LoggerService::info(self::class.' fn: '.__FUNCTION__);

        $quoteRequest->quote_link = checkPersonalQuotes($quoteType->code) ?
            '/personal-quotes/'.strtolower($quoteType->code).'/'.$quoteRequest->uuid :
            '/quotes/'.strtolower($quoteType->code).'/'.$quoteRequest->uuid;

        $kycLogs = app(AMLService::class)->getKYCLogs($quoteTypeId, $quoteRequestId);
        $isAnyEscalated = $kycLogs->isNotEmpty() ? count($kycLogs->filter(function ($log) {
            return $log['decision'] == AMLDecisionStatusEnum::ESCALATED;
        })) : 0;

        $lookups = app(AMLService::class)->getAMLLookups();
        if ($quoteType->code == quoteTypeCode::Car || $quoteRequest->plan?->insuranceProvider?->code == InsuranceProvidersEnum::RSA) {
            $additionalLookups = app(AMLService::class)->getAMLLookups($quoteRequest?->plan?->provider_id, [
                LookupsEnum::RTA_TRANSACTION_TYPE,
                LookupsEnum::RTA_PLATE_CATEGORY,
                LookupsEnum::VEHICLE_COLOR,
                LookupsEnum::BANK_NAME,
                LookupsEnum::ANNUAL_MILEAGE_ESTIMATE,
                LookupsEnum::PLATE_CODE,
            ]);

            $lookups = array_merge($lookups->toArray(), $additionalLookups->toArray());
        }

        $insuredDetails = app(AMLService::class)->getInsuredDetails($quoteRequest->customer_id, $quoteTypeId, $quoteRequestId);
        $entityDetails = app(AMLService::class)->getEntityDetails($quoteTypeId, $quoteRequestId); // TODO:: this will only for customer member mapping, this will remove when customer member mapping updated with insured
        $nationalities = NationalityRepository::withActive()->get();
        $emirates = Emirate::where('is_active', 1)->orderBy('sort_order')->get();
        $membersDetail = CustomerMembersRepository::getBy($quoteRequest->id, $quoteType->code);
        $uboDetails = CustomerMembersRepository::getBy($quoteRequest->id, $quoteType->code, CustomerTypeEnum::Entity);
        $payment = Payment::where('code', $quoteRequest->code)
            ->with(['getCustomerPaymentInstrument' => fn ($query) => $query->whereNotNull('card_holder_name')])->first();
        $cardHolderName = $payment->getCustomerPaymentInstrument?->card_holder_name ?? '';
        // $customerDetails = Customer::with('detail')->where('id', $quoteRequest->customer_id)->first();

        $checkScreeningStatus = [AMLStatusCode::AMLScreeningCleared => 2, AMLStatusCode::AMLScreeningFailed => 1];
        $amlStatusName = AMLStatusCode::getName($quoteRequest->aml_status);
        $screeningType = AMLService::getKycType($quoteTypeId, $quoteRequestId);

        if ($quoteType->code == quoteTypeCode::Business) {
            LoggerService::info('Business lead found aginst AML Screening');
            $businessPayload['businessTypeCode'] = BusinessQuoteType::where('id', $quoteRequest->business_type_of_insurance_id)->value('code');
            $businessPayload['businessCoverTypeText'] = BusinessCoverType::where('id', $quoteRequest->business_cover_type_id)->value('text');
            $businessPayload['businessCommuModeText'] = CommunicationMode::where('id', $quoteRequest->business_communication_mode_id)->value('text');
        }

        return inertia('Aml/DetailPage', array_merge([
            'quoteType' => $quoteType,
            'quoteRequest' => $quoteRequest,
            'amlStatusName' => $amlStatusName,
            'amlStatusCode' => AMLStatusCode::asArray(),
            'kycLogs' => $kycLogs,
            'lookups' => $lookups,
            'nationalities' => $nationalities,
            'emirates' => $emirates,
            'insuredDetails' => $insuredDetails,
            'entityDetails' => $entityDetails,
            'amlDecisionStatusEnum' => AMLDecisionStatusEnum::asArray(),
            'quoteTypeIdEnum' => QuoteTypeId::asArray(),
            'quoteStatusEnums' => QuoteStatusEnum::asArray(),
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'amlStatusEvaluation' => AMLDecisionStatusEnum::amlStatusEvaluation(),
            'membersDetails' => $membersDetail,
            'uboDetails' => $uboDetails,
            'cardHolderName' => $cardHolderName,
            // 'customerDetails' => $customerDetails,
            'quoteAmlStatus' => $checkScreeningStatus[$quoteRequest->aml_status] ?? null,
            'defaultNationality' => GenericRequestEnum::DEFAULT_NATIONALITY,
            'screeningType' => $screeningType,
            'gigInsurerDefaultEmail' => GenericModelTypeEnum::GIG_INSURER_SCREENIN_DEFAULT_EMAIL,
            'isAnyEscalated' => $isAnyEscalated,
        ], $businessPayload ?? []));
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
                    app(AMLService::class)->sendAMLQuoteStatusChangeNotification($quoteTypeId, $quoteRequestId, $updatedAMLStatus, $quoteObject->code, $quoteType->text, $quoteObject->pa_id, $clientFullName);
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

    // public function updateCustomerDetails(UpdateAMLCustomerDetailRequest $request)
    // {
    //     $customer = CustomerRepository::updateCustomerDetails($request->customer_id, $request->safe());

    //     return response()->json(['success' => true]);
    // }

    // public function updateEntityDetails(UpdateAMLEntityDetailRequest $request)
    // {
    //     $entity = EntityRepository::updateEntityDetail($request->safe());

    //     return response()->json(['success' => true]);
    // }

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
        LoggerService::info(self::class.' fn: '.__FUNCTION__.' - AML Screening Bridger - Process Started');

        $systemUser = User::where('name', UserNameEnum::System)->first();
        $isAutomation = $AMLCheckRequest->is_automation ?? false;
        $processbyUser = $isAutomation ? $systemUser : FacadesAuth::user();

        if ($updateQuote) {
            [$status, $message, $getMemberOrUBODetails, $getLastScreening] = app(AMLService::class)->prepareScreeningData($AMLCheckRequest, $quoteType, $updateQuote);

            if (! $status) {
                return app(AMLService::class)->handleResponse($status, $message, $isAutomation);
            }

            // Wrap insured processing and related operations in a single transaction
            [$shouldApplicableForScreening, $insured, $entityId] = DB::transaction(function () use ($AMLCheckRequest, $quoteType, $updateQuote, $getLastScreening, $processbyUser, $isAutomation, $systemUser, $quoteRequestId) {
                [$shouldApplicableForScreening, $insured, $entityId] = app(AMLService::class)->processInsuredDataForScreening($AMLCheckRequest, $quoteType->id, $updateQuote, $getLastScreening);
                app(AMLService::class)->updatePAId([
                    'isAutomation' => $isAutomation,
                    'systemUser' => $systemUser,
                    'processbyUser' => $processbyUser,
                    'quoteType' => $quoteType,
                    'quoteRequestId' => $quoteRequestId,
                ], $updateQuote);

                return [$shouldApplicableForScreening, $insured, $entityId];
            });

            session()->put('amlResponseCheck', []);
            $insurerAMLScreeningResponse = [];
            $isEntity = $AMLCheckRequest->customer_type == CustomerTypeEnum::Entity;

            if ($shouldApplicableForScreening) {
                if ($isEntity) {
                    $getEntityDetailsForScreening = [
                        'company_name' => $insured->company_name,
                        'code' => CustomerTypeEnum::EntityShort.'-'.$entityId, // TODO:: code should be updated with insured id (Required FR for this)
                    ];

                    $bridgerInsightService = new BridgerInsightService;
                    $bridgerAPIToken = $bridgerInsightService->getJWTToken();

                    LoggerService::info('AML Screening Bridger - AML Screening Job Dispatched against Entity');
                    BridgerAMLJob::dispatchSync($bridgerAPIToken, $getEntityDetailsForScreening, $updateQuote, $quoteTypeId, CustomerTypeEnum::Entity, auth()->user()->email);

                    if (isset($AMLCheckRequest->company_name) && in_array($quoteTypeId, [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Yacht, QuoteTypeId::Car])) {
                        $updateQuote->company_name = $AMLCheckRequest->company_name;
                        $updateQuote->company_address = $AMLCheckRequest->company_address;
                        $updateQuote->save();
                    }
                } else {
                    // Handle individual screening
                    $getMemberOrUBODetails[] = [
                        'first_name' => $insured->first_name,
                        'last_name' => $insured->last_name,
                        'dob' => Carbon::parse($insured->dob)->format(config('constants.DATE_FORMAT_ONLY')),
                        'nationality' => $insured?->nationality->toArray() ?? [],
                        'code' => CustomerTypeEnum::IndividualShort.'-'.$AMLCheckRequest->customer_id, // TODO:: code should be updated with insured id (Required FR for this)
                    ];
                }
            }

            if (in_array($quoteTypeId, [QuoteTypeId::Car, QuoteTypeId::Bike, QuoteTypeId::Home])) {
                $this->updateChassisNumber($quoteTypeId, $AMLCheckRequest, $quoteRequestId, $updateQuote);
            }

            // Process members (UBO or regular members)
            if (empty($getMemberOrUBODetails->toArray()) && ! $shouldApplicableForScreening) {
                LoggerService::info('AML Screening Bridger - No Member Found for Screening, AML Screening Cleared');
                $response = app(AMLService::class)->handleResponse(true, 'AML Screening Completed', $isAutomation);
                if (! empty($insurerAMLScreeningResponse) && ! $isAutomation) {
                    $response->with('info', ['message' => $insurerAMLScreeningResponse['message']]);
                }

                return $response;
            }

            $bridgerInsightService = new BridgerInsightService;
            $bridgerAPIToken = $bridgerInsightService->getJWTToken();

            // Job dispatch for all members including customer
            LoggerService::info('AML Screening Bridger - AML Screening Job Dispatched for Members');
            $this->AMLJobDispatchForMembers($updateQuote, $getMemberOrUBODetails, $bridgerAPIToken, $quoteRequestId, $quoteTypeId, CustomerTypeEnum::Individual, $processbyUser, isAutomation: $isAutomation);

            $response = app(AMLService::class)->handleResponse(true, 'Quote is updated', $isAutomation);
            if (! empty($insurerAMLScreeningResponse) && ! $isAutomation) {
                $response = $response->with('info', ['message' => $insurerAMLScreeningResponse['message'], 'isEmailMismatched' => $insurerAMLScreeningResponse['isEmailMismatched']]);
            }

            return $response;
        }

        return app(AMLService::class)->handleResponse(false, 'Something went wrong', $isAutomation);
    }

    private function InsurerScreening($quoteTypeId, $AMLCheckRequest, $updateQuote)
    {
        LoggerService::info(self::class.' fn: '.__FUNCTION__);
        $insurerAMLScreeningResponse = [];

        if (isTapEnabled()) {
            LoggerService::info('AML Screening Bridger - Tap Enabled - Insurer AML Screening process start');
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
                    ];

                    if (isset($getInsurerScreeningResponse['autoCaptureStatus'])) {
                        $insurerAMLScreeningResponse['autoCaptureStatus'] = $getInsurerScreeningResponse['autoCaptureStatus'];
                        $insurerAMLScreeningResponse['autoCaptureMessage'] = $getInsurerScreeningResponse['autoCaptureMessage'];
                    }
                }
                session()->forget('insurerAMLScreeningResponse');
            }
            LoggerService::info('AML Screening Bridger - Tap Enabled - Insurer AML Screening process completed');
        }

        return $insurerAMLScreeningResponse;
    }

    private function updateChassisNumber($quoteTypeId, $AMLCheckRequest, $quoteRequestId, $updateQuote)
    {
        LoggerService::info(self::class.' fn: '.__FUNCTION__);

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
    }

    private function AMLJobDispatchForMembers($quoteDetails, $membersDetails, $bridgerAPIToken, $quoteRequestId, $quoteTypeId, $customerType, $processByUser = null, $isAutomation = false)
    {
        foreach ($membersDetails as $memberDetail) {
            BridgerAMLJob::dispatchSync($bridgerAPIToken, $memberDetail, $quoteDetails, $quoteTypeId, $customerType, $processByUser?->email ?? auth()->user()?->email, isAutomation: $isAutomation);
        }

        if (! in_array(true, session()->get('amlResponseCheck')) && ! AMLService::checkAMLStatusFailed($quoteTypeId, $quoteRequestId)) {
            $quoteTypeIds = [QuoteTypeId::Health, QuoteTypeId::Home, QuoteTypeId::Cycle, QuoteTypeId::Pet, QuoteTypeId::Yacht, QuoteTypeId::Corpline];
            QuoteStatusLog::create([
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quoteRequestId,
                'current_quote_status_id' => QuoteStatusEnum::AMLScreeningCleared,
                'previous_quote_status_id' => $quoteDetails->quote_status_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

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
            QuoteStatusLog::create([
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quoteRequestId,
                'current_quote_status_id' => QuoteStatusEnum::AMLScreeningFailed,
                'previous_quote_status_id' => $quoteDetails->quote_status_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

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

    public function fetchEntity(Request $request)
    {
        $entity = Insured::where([
            'customer_type' => CustomerTypeEnum::Entity,
            'trade_license_no' => $request->trade_license,
        ])->first();

        if ($entity) {
            return response()->json(['status' => true, 'response' => $entity, 'message' => 'Entity found with the entered Trade License number']);
        }

        return response()->json(['status' => false, 'message' => 'No Entity found with the entered Trade License number']);
    }

    public function linkEntityDetails(Request $request)
    {
        // Reminder:: This patch add because data should be updated in new structure
        $quoteType = QuoteType::where('id', $request->quote_type_id)->first();
        $quoteObject = $this->getQuoteObject($quoteType->code, $request->quote_request_id);

        LoggerService::startQuoteLogging($quoteObject, LoggerFeatureEnum::AML_SCREENING);
        LoggerService::info(self::class.' fn: '.__FUNCTION__);

        // Reminder:: Entity id is Insured ID which we get from fetchEntity() this function
        $insured = Insured::where('id', $request->entity_id)->first();
        app(AMLService::class)->updateInsuredInPersonalQuote($request->quote_type_id, $quoteObject, $insured);

        $customerInsured = CustomerInsured::where('customer_id', $quoteObject->customer_id)
            ->where('insured_id', $insured->id)
            ->whereNull('quote_type_id')
            ->whereNull('quote_request_id')
            ->first();

        if ($customerInsured) {
            $customerInsured->update([
                'quote_type_id' => $request->quote_type_id,
                'quote_request_id' => $request->quote_request_id,
                'updated_at' => now(),
            ]);
        } else {
            CustomerInsured::updateOrCreate([
                'quote_type_id' => $request->quote_type_id,
                'quote_request_id' => $request->quote_request_id,
            ], [
                'customer_id' => $quoteObject->customer_id,
                'insured_id' => $insured->id,
                'updated_at' => now(),
            ]);
        }

        // Reminder:: This code should be remove when new structure will be completly mapped
        $oldStructureEntity = Entity::where('trade_license_no', $insured->trade_license_no)->first();
        $existingEntityMapping = QuoteRequestEntityMapping::where(['quote_type_id' => $request->quote_type_id, 'quote_request_id' => $request->quote_request_id])->first();

        $updateFields = ['entity_id' => $oldStructureEntity->id, 'entity_type_code' => LookupsEnum::PARENT_ENTITY];
        if ($request->triggeredFrom) {
            $updateFields['entity_type_code'] = LookupsEnum::SUB_ENTITY;
        }

        QuoteRequestEntityMapping::updateOrCreate(['quote_type_id' => $request->quote_type_id, 'quote_request_id' => $request->quote_request_id], $updateFields);
        $entity = Entity::with(
            [
                'quoteRequestEntityMapping' => function ($mappedEntity) use ($request) {
                    $mappedEntity->where(['quote_type_id' => $request->quote_type_id, 'quote_request_id' => $request->quote_request_id]);
                },
                'quoteMember',
            ]
        )->where('id', $oldStructureEntity->id)->first();

        if ($existingEntityMapping) {
            $previousEntity = $existingEntityMapping->entity;
            $entityMappingCount = QuoteRequestEntityMapping::where(['entity_id' => $previousEntity->id ?? null])->count();
            // Reminder:: This is Jawad change for car commercial quote
            if ($entityMappingCount === 0 && empty($previousEntity->trade_license_no)) {
                $previousEntity->delete();
            }
        }

        if ($request->quote_type_id == QuoteTypeId::Car) {
            CarQuoteRepository::where('id', $request->quote_request_id)->update([
                'company_name' => $entity->company_name,
                'company_address' => $entity->company_address,
            ]);
        }

        return response()->json(['status' => true, 'response' => $entity, 'message' => 'Entity Linked Successfully']);
    }

    public function getInsuredDetails(Request $request): \Illuminate\Http\JsonResponse
    {
        LoggerService::startQuoteLogging($request->code, LoggerFeatureEnum::AML_SCREENING);
        LoggerService::info(self::class.' fn: '.__FUNCTION__, extra: [
            'customer_type' => $request->customer_type,
            'id_type' => $request->id_type,
            'id_number' => $request->id_number,
            'trade_license' => $request->trade_license,
        ]);

        // TODO:: this condition should be move to AMLService class
        $isEntity = $request->customer_type == CustomerTypeEnum::Entity;
        // TODO:: this condition should be updated later
        if (empty($request->customer_type) || is_null($request->customer_type) || $request->customer_type == 'null') {
            $isEntity = ! empty($request->trade_license);
        }

        $whereClause = $isEntity
            ? ['trade_license_no' => $request->trade_license, 'customer_type' => CustomerTypeEnum::Entity]
            : [
                'customer_type' => CustomerTypeEnum::Individual,
                'id_type' => $request->id_type,
                'id_number' => $request->id_number,
            ];

        $insuredDetails = Insured::with('insuredKyc')->where($whereClause)->first();

        $customerType = $isEntity ? CustomerTypeEnum::Entity : CustomerTypeEnum::Individual;
        $status = (bool) $insuredDetails;
        $messageType = $status ? 'found' : 'not_found';

        $messages = [
            CustomerTypeEnum::Individual => [
                'found' => 'Customer found with the entered ID number',
                'not_found' => 'No Customer found with the entered ID number',
            ],
            CustomerTypeEnum::Entity => [
                'found' => 'Entity found with the entered Trade License number',
                'not_found' => 'No Entity found with the entered Trade License number',
            ],
        ];

        return response()->json([
            'status' => $status,
            'response' => $insuredDetails,
            'message' => $messages[$customerType][$messageType],
        ]);
    }

    public function sendBridgerResponse(Request $request)
    {
        LoggerService::startQuoteLogging($request['quote_ref_id'], LoggerFeatureEnum::AML_SCREENING);
        LoggerService::info(self::class.' fn: '.__FUNCTION__, extra: [
            'bridger_response' => $request['bridger_response'],
        ]);
        $response = [];
        $kycLog = KycLog::withTrashed()->where('id', $request->aml_id)->first();
        $oldDecision = $kycLog->decision;

        if (checkModifiedRecord($kycLog->updated_at, $request->last_updated_at)) {
            return response()->json(['status' => 'error', 'message' => 'Record already modified please refresh the page']);
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

        $response = ['success' => false];
        $insurerAMLScreeningResponse = [];

        if ($insuredKycRequest->customer_type == CustomerTypeEnum::Individual) {
            $insurerAMLScreeningResponse = $this->InsurerScreening($insuredKycRequest->quote_type_id, $insuredKycRequest, $quote);

            if (! empty($insurerAMLScreeningResponse)) {
                $response['insurer_screening'] = [
                    'status' => $insurerAMLScreeningResponse['status'],
                    'message' => $insurerAMLScreeningResponse['message'],
                    'isEmailMismatched' => $insurerAMLScreeningResponse['isEmailMismatched'] ?? false,
                    'autoCaptureStatus' => $insurerAMLScreeningResponse['autoCaptureStatus'] ?? null,
                    'autoCaptureMessage' => $insurerAMLScreeningResponse['autoCaptureMessage'] ?? null,
                ];
            }
        }

        if (empty($insurerAMLScreeningResponse) || $insurerAMLScreeningResponse['status'] == AMLStatusCode::AMLScreeningCleared) {
            $preparedFormData = app(AMLService::class)->prepareInsuredKycFormData($insuredKycRequest, $quote, $quoteType);
            $response['success'] = $preparedFormData;
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

        return response()->json(['success' => $response['status'], 'message' => $response['message']]);
    }

    public function getQuoteDetailsFromInsurer(Request $request)
    {
        $quoteType = QuoteTypes::getName($request->quoteTypeId)->value;
        $quoteDetails = $this->getQuoteObjectBy($quoteType, $request->quoteUID, 'uuid');
        $insurerCode = getInsuranceProvider($quoteDetails->payments()->mainLeadPayment()->first(), $quoteType);

        return match (ucfirst($quoteType)) {
            QuoteTypes::CAR->value => match ($insurerCode->code) {
                InsuranceProvidersEnum::RSA => app(LivaInsuranceService::class)->getQuoteDetailsFromInsurer($request->quoteTypeId, $quoteDetails),

                default => response()->json([
                    'success' => false,
                    'message' => 'Quote type not supported'
                ]),
            },
            default => response()->json([
                'success' => false,
                'message' => 'Quote type not supported'
            ]),
        };
    }
}
