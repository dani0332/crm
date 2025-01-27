<?php

namespace App\Http\Controllers\V2;

use App\Enums\AMLDecisionStatusEnum;
use App\Enums\AMLStatusCode;
use App\Enums\CustomerTypeEnum;
use App\Enums\DatabaseColumnsString;
use App\Enums\LookupsEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TravelQuoteEnum;
use App\Enums\WorkflowTypeEnum;
use App\Exports\KycLogs;
use App\Http\Controllers\Controller;
use App\Http\Requests\AMLCheckRequest;
use App\Http\Requests\AMLRequest;
use App\Http\Requests\UpdateAMLCustomerDetailRequest;
use App\Http\Requests\UpdateAMLEntityDetailRequest;
use App\Jobs\BridgerAMLJob;
use App\Models\AML;
use App\Models\BusinessCoverType;
use App\Models\BusinessQuoteType;
use App\Models\CommunicationMode;
use App\Models\Customer;
use App\Models\Emirate;
use App\Models\Entity;
use App\Models\KycLog;
use App\Models\Lookup;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\QuoteRequestEntityMapping;
use App\Models\QuoteStatus;
use App\Models\QuoteStatusLog;
use App\Models\QuoteType;
use App\Repositories\CustomerMembersRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\EntityRepository;
use App\Repositories\NationalityRepository;
use App\Repositories\QuoteTypeRepository;
use App\Services\AMLService;
use App\Services\BridgerInsightService;
use App\Services\QuoteStatusService;
use App\Services\SIBService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $query = AML::select([
            'id',
            'quote_request_id',
            'quote_type_id',
            'input',
            'search_type',
            'match_found',
            'results_found',
            'created_at',
            'decision',
        ])
            ->where('decision', '!=', AMLDecisionStatusEnum::RYU)
            ->whereBetween('created_at', dateQueryFilter($request->amlCreatedStartDate, $request->amlCreatedEndDate));

        $data = collect();

        $query->chunk(1000, function ($chunk) use (&$data) {
            $quoteTypeGroup = $chunk->groupBy('quote_type_id');
            foreach ($quoteTypeGroup as $quoteTypeId => $quoteTypeData) {
                $quoteType = QuoteTypes::getName($quoteTypeId);
                $nameSpace = '\\App\\Models\\';
                $model = checkPersonalQuotes(ucwords($quoteType->value)) ? $nameSpace.'PersonalQuote' : $nameSpace.ucwords($quoteType->value).'Quote';

                $distinctQuoteTypeIds = $quoteTypeData->pluck('quote_request_id')->unique();
                $quoteRequestData = $model::whereIn('id', $distinctQuoteTypeIds)->select(['id', 'uuid', 'aml_status'])->get();
                foreach ($quoteRequestData as $quoteRequest) {
                    $amlData = $chunk->where('quote_type_id', $quoteTypeId)->where('quote_request_id', $quoteRequest->id);
                    foreach ($amlData as $index => $value) {
                        $chunk[$index]['uuid'] = $quoteType->shortCode().$quoteRequest->uuid;
                        $chunk[$index]['aml_status'] = $quoteRequest->aml_status;
                    }
                }
            }
            $data = $data->merge($chunk);
        });

        $reportDateRange = Carbon::parse($request->amlCreatedStartDate)->toDateString().' - '.Carbon::parse($request->amlCreatedEndDate)->toDateString();

        return (new KycLogs($data))->download("AML Logs {$reportDateRange}");
    }

    /**
     * Display the specified resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(AML $aml)
    {
        $amlResults = collect(json_decode($aml->results))->first() ?? [];
        $manualStatusUpdateIM = collect($amlResults->ManualStatusUpdateIM ?? []);
        $aml->quote_type_text = $aml->quotetype->text;
        $quoteType = QuoteType::where('id', $aml->quote_type_id)->first();
        $quoteObject = $this->getQuoteObject($quoteType->code, $aml->quote_request_id);

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
        ]);
    }

    public function amlQuoteDetails($quoteTypeId, $quoteRequestId)
    {
        $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
        $amlRecordFetch = AML::with('quotetype')->where(['quote_request_id' => $quoteRequestId, 'quote_type_id' => $quoteTypeId])
            ->where(function ($aml) {
                $aml->whereNotIn('decision', [AMLDecisionStatusEnum::RYU]);
                $aml->orWhereNull('decision');
            })->whereNull('screenshot');
        $kycLogs = $amlRecordFetch->orderBy('created_at', 'desc')->get();
        $quoteRequest = AMLService::getQuoteDetails($quoteTypeId, $quoteRequestId);

        $isPersonalQuote = checkPersonalQuotes($quoteType->code);

        if ($isPersonalQuote) {
            $quoteRequest->quote_link = '/personal-quotes/'.strtolower($quoteType->code).'/'.$quoteRequest->uuid;
        } else {
            $quoteRequest->quote_link = '/quotes/'.strtolower($quoteType->code).'/'.$quoteRequest->uuid;
        }

        $customerDetails = Customer::where('id', $quoteRequest->customer_id)->with('detail')->firstOrFail();
        $entityDetails = QuoteRequestEntityMapping::with(['entity', 'entity.quoteMember'])
            ->where(['quote_type_id' => $quoteTypeId, 'quote_request_id' => $quoteRequestId])
            ->first() ?? [];
        $membersDetail = CustomerMembersRepository::getBy($quoteRequest->id, $quoteType->code);

        $uboDetails = CustomerMembersRepository::getBy($quoteRequest->id, $quoteType->code, CustomerTypeEnum::Entity);
        $nationalities = NationalityRepository::withActive()->get();
        $emirates = Emirate::where('is_active', 1)->orderBy('sort_order')->get();

        $lookups = Lookup::whereIn('key', [
            LookupsEnum::RESIDENT_STATUS,
            LookupsEnum::DOCUMENT_ID_TYPE,
            LookupsEnum::ENTITY_DOCUMENT_TYPE,
            LookupsEnum::MODE_OF_CONTACT,
            LookupsEnum::MODE_OF_DELIVERY,
            LookupsEnum::EMPLOYMENT_SECTOR,
            LookupsEnum::LEGAL_STRUCTURE,
            LookupsEnum::ISSUANCE_PLACE,
            LookupsEnum::ISSUING_AUTHORITY,
            LookupsEnum::COMPANY_POSITION,
            LookupsEnum::PROFESSIONAL_TITLE,
            LookupsEnum::UBO_RELATION,
            LookupsEnum::COMPANY_TYPE,
            LookupsEnum::MEMBER_RELATION,
        ])->get()->groupBy('key');

        // lookups , loop through each key, replace - with _ and update key
        $lookups = $lookups->mapWithKeys(function ($item, $key) {
            return [str_replace('-', '_', $key) => $item];
        });

        $checkScreeningStatus = [AMLStatusCode::AMLScreeningCleared => 2, AMLStatusCode::AMLScreeningFailed => 1];
        $kycStatus = AMLService::getKycType($quoteTypeId, $quoteRequestId);

        $payment = Payment::where('code', $quoteRequest->code)
            ->with(['getCustomerPaymentInstrument' => function ($query) {
                $query->whereNotNull('card_holder_name');
            }])
            ->first();
        $cardHolderName = '';
        if (isset($payment->getCustomerPaymentInstrument->card_holder_name)) {
            $cardHolderName = $payment->getCustomerPaymentInstrument;
        }
        $amlStatusName = AMLStatusCode::getName($quoteRequest->aml_status);
        $data = [
            'quoteType' => $quoteType,
            'quoteRequest' => $quoteRequest,
            'amlStatusName' => $amlStatusName,
            'entityDetails' => $entityDetails,
            'membersDetails' => $membersDetail,
            'uboDetails' => $uboDetails,
            'nationalities' => $nationalities,
            'emirates' => $emirates,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'kycLogs' => $kycLogs,
            'kycStatus' => $kycStatus,
            'customerDetails' => $customerDetails,
            'amlDecisionStatusEnum' => AMLDecisionStatusEnum::asArray(),
            'lookups' => $lookups,
            'quoteAmlStatus' => $checkScreeningStatus[$quoteRequest->aml_status] ?? null,
            'cardHolderName' => $cardHolderName,
        ];

        if ($quoteType->code == quoteTypeCode::Business) {
            $data['businessTypeCode'] = BusinessQuoteType::where('id', $quoteRequest->business_type_of_insurance_id)->value('code');
            $data['businessCoverTypeText'] = BusinessCoverType::where('id', $quoteRequest->business_cover_type_id)->value('text');
            $data['businessCommuModeText'] = CommunicationMode::where('id', $quoteRequest->business_communication_mode_id)->value('text');
        }

        return inertia('Aml/Details', $data);
    }

    public function quoteStatusUpdate($quoteTypeId, $quoteRequestId, $quoteStatusType)
    {
        $quoteType = QuoteType::where('id', $quoteTypeId)->first();
        $quoteObject = $this->getQuoteObject($quoteType->code, $quoteRequestId);
        if (isset(request()->decisonsForUpdatePortal)) {
            request()->merge(['ref_id' => $quoteObject->code]);
            $response = AMLService::updateAMLDecisionLexisNexis(request());
            if ($response['status'] == 'success') {
                $updateQuoteStatusResp = app(QuoteStatusService::class)->updateQuoteStatus($quoteTypeId, $quoteRequestId, $quoteStatusType, \request()->toArray());

                $responseMessage = ['status' => 'success', 'message' => 'Quote Status Updated'];
                $quoteStatusText = $updateQuoteStatusResp['quote_status_text'];
                $quoteCdbId = $updateQuoteStatusResp['quote_ref_id'];
                $quoteTypeText = $updateQuoteStatusResp['quote_type_text'];
                $quotePaID = $updateQuoteStatusResp['pa_id'];
                $clientFullName = $updateQuoteStatusResp['client_name'];

                if (auth()->user()->hasRole(RolesEnum::ComplianceSuperUser) ||
                    (auth()->user()->hasRole(RolesEnum::COMPLIANCE) && request()->aml_decision == AMLDecisionStatusEnum::FALSE_POSITIVE)) {
                    app(AMLService::class)->sendAMLQuoteStatusChangeNotification($quoteTypeId, $quoteRequestId, $quoteStatusText, $quoteCdbId, $quoteTypeText, $quotePaID, $clientFullName);
                }

                $response = ['status' => $response['status'], 'message' => $response['message'].' and '.$responseMessage['message']];
            } else {
                $response = ['status' => $response['status'], 'message' => $response['message']];
            }
        }

        return response()->json($response);
    }

    public function updateCustomerDetails(UpdateAMLCustomerDetailRequest $request)
    {
        $customer = CustomerRepository::updateCustomerDetails($request->customer_id, $request->safe());

        return response()->json(['success' => true]);
    }

    public function updateEntityDetails(UpdateAMLEntityDetailRequest $request)
    {
        $entity = EntityRepository::updateEntityDetail($request->safe());

        return response()->json(['success' => true]);
    }

    public function quoteUpdate(AMLCheckRequest $AMLCheckRequest, $quoteTypeId, $quoteRequestId)
    {
        $quoteId = $quoteRequestId;
        $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
        $updateQuote = $this->getQuoteObject($quoteType->code, $quoteId);
        $getMemberOrUBODetails = AMLService::getMemberOrUBODetails($AMLCheckRequest, $quoteType, $quoteId);

        $getLastScreening = KycLog::withTrashed()->where([
            'quote_type_id' => $quoteTypeId,
            'quote_request_id' => $quoteRequestId,
        ])->where(function ($ryuFilter) {
            $ryuFilter->whereNotIn('decision', [AMLDecisionStatusEnum::RYU]);
            $ryuFilter->orWhereNull('decision');
        })->whereNull('screenshot')->get()->last() ?? [];

        if ($getMemberOrUBODetails) {
            $memberValidateCheck = collect($getMemberOrUBODetails)->pluck('first_name')->toArray();
            if (in_array(null, $memberValidateCheck)) {
                return redirect()->back()->with('error', 'First Name missing');
            }

            $getMemberOrUBODetails = collect($getMemberOrUBODetails)->filter(function ($value) use ($getLastScreening) {
                return $value->updated_at >= ($getLastScreening->created_at ?? '');
            });
        }

        if ($updateQuote) {
            if (auth()->user()->hasAnyRole([RolesEnum::AML, RolesEnum::PA])) {
                if (checkPersonalQuotes($quoteType->code)) {
                    AMLService::updatePaIdForPersonalQuotes($quoteTypeId, $quoteRequestId, true);
                } else {
                    $updateQuote->pa_id = auth()->user()->id;
                    $updateQuote->save();
                }
            }

            session()->put('amlResponseCheck', []);

            if ($AMLCheckRequest->customer_type == CustomerTypeEnum::Individual) {
                $customer = Customer::with('nationality')->findOrFail($AMLCheckRequest->customer_id);

                $customer->nationality_id = $AMLCheckRequest->nationality_id;
                $customer->dob = $AMLCheckRequest->dob;
                $customer->insured_first_name = $AMLCheckRequest->insured_first_name;
                $customer->insured_last_name = $AMLCheckRequest->insured_last_name;
                if ($customer->isDirty() || ! isset($getLastScreening->created_at) || Carbon::parse($customer->updated_at) >= Carbon::parse($getLastScreening->created_at ?? '')) {
                    $customer->save();
                    $customer->refresh();

                    $getMemberOrUBODetails[] = [
                        'first_name' => $customer->insured_first_name,
                        'last_name' => $customer->insured_last_name,
                        'dob' => Carbon::parse($customer->dob)->format(config('constants.DATE_FORMAT_ONLY')),
                        'nationality' => $customer->nationality->toArray() ?? [],
                        'code' => CustomerTypeEnum::IndividualShort.'-'.$customer->id,
                    ];
                }

                if (empty($getMemberOrUBODetails->toArray())) {
                    return redirect()->back()->with('success', 'AML Screening Completed');
                }

                $bridgerInsightService = new BridgerInsightService;
                $bridgerAPIToken = $bridgerInsightService->getJWTToken();

                // Job dispatch for all members including customer
                $this->AMLJobDispatchForMembers($updateQuote, $getMemberOrUBODetails, $bridgerAPIToken, $quoteRequestId, $quoteTypeId, CustomerTypeEnum::Individual);
            }

            if ($AMLCheckRequest->customer_type == CustomerTypeEnum::Entity) {
                $entityDetailsForApi = [];
                $bridgerInsightService = new BridgerInsightService;
                $bridgerAPIToken = $bridgerInsightService->getJWTToken();
                $fetchEntity = Entity::where(['trade_license_no' => $AMLCheckRequest->trade_license_no])->first();
                if (! $fetchEntity) {
                    $entity = Entity::create([
                        'trade_license_no' => $AMLCheckRequest->trade_license_no,
                        'company_name' => $AMLCheckRequest->company_name,
                        'company_address' => $AMLCheckRequest->company_address,
                        'industry_type_code' => $AMLCheckRequest->industry_type_code,
                        'emirate_of_registration_id' => $AMLCheckRequest->emirate_of_registration_id,
                    ]);
                    $entity->refresh();
                    $entityId = $entity->id;
                    $entity->update(['code' => CustomerTypeEnum::EntityShort.'-'.$entityId]);

                    QuoteRequestEntityMapping::updateOrCreate([
                        'quote_type_id' => $quoteType->id,
                        'quote_request_id' => $quoteRequestId,
                    ], ['entity_id' => $entityId, 'entity_type_code' => $AMLCheckRequest->entity_type_code]);

                    $entityDetailsForApi = ['company_name' => $entity->company_name, 'code' => CustomerTypeEnum::EntityShort.'-'.$entity->id];
                    BridgerAMLJob::dispatchSync($bridgerAPIToken, $entityDetailsForApi, $updateQuote, $quoteTypeId, CustomerTypeEnum::Entity, auth()->user()->email);
                } else {
                    $fetchEntity->trade_license_no = $AMLCheckRequest->trade_license_no;
                    $fetchEntity->company_name = $AMLCheckRequest->company_name;
                    $fetchEntity->company_address = $AMLCheckRequest->company_address;
                    $fetchEntity->industry_type_code = $AMLCheckRequest->industry_type_code;
                    $fetchEntity->emirate_of_registration_id = $AMLCheckRequest->emirate_of_registration_id;
                    $isEntityDetailUpdated = $fetchEntity->isDirty();
                    $kycExist = KycLog::withTrashed()->where(['quote_request_id' => $quoteRequestId, 'quote_type_id' => $quoteTypeId, 'input' => $fetchEntity->company_name])->first();
                    if ($isEntityDetailUpdated || ! isset($kycExist->id)) {
                        $fetchEntity->save();
                        $fetchEntity->refresh();

                        $entityDetailsForApi = ['company_name' => $fetchEntity->company_name, 'code' => $fetchEntity->code];
                        BridgerAMLJob::dispatchSync($bridgerAPIToken, $entityDetailsForApi, $updateQuote, $quoteTypeId, CustomerTypeEnum::Entity, auth()->user()->email);
                    }
                    QuoteRequestEntityMapping::updateOrCreate([
                        'quote_type_id' => $quoteType->id,
                        'quote_request_id' => $quoteRequestId,
                    ], ['entity_id' => $fetchEntity->id, 'entity_type_code' => $AMLCheckRequest->entity_type_code]);
                }

                if (isset($AMLCheckRequest->company_name) && in_array($quoteTypeId, [QuoteTypeId::Business, QuoteTypeId::Home, QuoteTypeId::Yacht, QuoteTypeId::Car])) {
                    $updateQuote->company_name = $AMLCheckRequest->company_name;
                    $updateQuote->company_address = $AMLCheckRequest->company_address;
                    $updateQuote->save();
                }

                if (empty($entityDetailsForApi) && empty($getMemberOrUBODetails->toArray())) {
                    return redirect()->back()->with('success', 'AML Screening Completed');
                }

                // Job dispatch for all UBO members
                $this->AMLJobDispatchForMembers($updateQuote, $getMemberOrUBODetails, $bridgerAPIToken, $quoteRequestId, $quoteTypeId, CustomerTypeEnum::Individual);
            }

            return redirect()->back();
        }

        return redirect()->back()->with('error', 'Something went wrong');
    }

    public function fetchEntity(Request $request)
    {
        $entity = Entity::where('trade_license_no', $request->trade_license)->first();

        if ($entity) {
            return response()->json(['status' => true, 'response' => $entity, 'message' => 'Entity found with the entered Trade License number']);
        }

        return response()->json(['status' => false, 'message' => 'No Entity found with the entered Trade License number']);
    }

    public function linkEntityDetails(Request $request)
    {
        $updateFields = ['entity_id' => $request->entity_id, 'entity_type_code' => LookupsEnum::PARENT_ENTITY];
        if ($request->triggeredFrom) {
            $updateFields['entity_type_code'] = LookupsEnum::SUB_ENTITY;
        }

        QuoteRequestEntityMapping::updateOrCreate(['quote_type_id' => $request->quote_type_id, 'quote_request_id' => $request->quote_request_id], $updateFields);
        $entity = Entity::with(
            [
                'quoteRequestEntityMapping' => function ($mappedEntity) use ($request) {
                    $mappedEntity->where(['quote_type_id' => $request->quote_type_id, 'quote_request_id' => $request->quote_request_id]);
                },
                'quoteMember']
        )->where('id', $request->entity_id)->first();

        return response()->json(['status' => true, 'response' => $entity, 'message' => 'Entity Linked Successfully']);
    }

    public function sendBridgerResponse(Request $request)
    {
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
                $request->bridger_decision_type == AMLDecisionStatusEnum::FALSE_POSITIVE) {
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

        $infoLog = 'AML Screening Bridger - AML Logs decision changed. Quote Ref-ID: '.$request['quote_ref_id'].' - Old Decision: '.$oldDecision.' - New Decision: '.$newDecision;

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
        info($infoLog.' - Triggered By: '.auth()->user()->email);

        return response()->json($response);
    }

    private function AMLJobDispatchForMembers($quoteDetails, $membersDetails, $bridgerAPIToken, $quoteRequestId, $quoteTypeId, $customerType)
    {
        foreach ($membersDetails as $memberDetail) {
            BridgerAMLJob::dispatchSync(
                $bridgerAPIToken,
                $memberDetail,
                $quoteDetails,
                $quoteTypeId,
                $customerType,
                auth()->user()->email
            );
        }
        if (! in_array(true, session()->get('amlResponseCheck')) && ! AMLService::checkAMLStatusFailed($quoteTypeId, $quoteRequestId)) {
            QuoteStatusLog::create([
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $quoteRequestId,
                'current_quote_status_id' => QuoteStatusEnum::AMLScreeningCleared,
                'previous_quote_status_id' => $quoteDetails->quote_status_id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            if ($quoteTypeId == QuoteTypeId::Health || $quoteTypeId == QuoteTypeId::Home || $quoteTypeId == QuoteTypeId::Cycle || $quoteTypeId == QuoteTypeId::Pet || $quoteTypeId == QuoteTypeId::Yacht || $quoteTypeId == QuoteTypeId::Corpline) {
                $quoteDetails->stale_at = null;
            }

            $quoteDetails->aml_status = AMLStatusCode::AMLScreeningCleared;
            $quoteDetails->save();
            // this event only working for travel lob
            if (QuoteTypes::TRAVEL->id() == $quoteTypeId) {
                $this->stopHapexReminder($quoteDetails);
            }
            info('AML Screening Bridger - Potential Matche(s) not Found, Quote Status changed to AML Screening Cleared');
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
            $quoteDetails->save();
            if (QuoteTypes::TRAVEL->id() == $quoteTypeId) {
                if (isset($quoteDetails->is_documents_valid) && ! $quoteDetails->is_documents_valid) {
                    $this->sendHapexReminder($quoteDetails);
                }
            }
            info('AML Screening Bridger - Potential Matche(s) Found, Quote Status changed to AML Screening Failed');
        }
        session()->forget('amlResponseCheck');
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

    public function stopHapexReminder($quote)
    {
        SIBService::createWorkflowEvent(WorkflowTypeEnum::TRAVEL_HAPEX_STOP_EMAIL_REMINDER, $quote, null, $quote);

        return true;
    }

    public function mapHapexMailPayload($quote)
    {
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

        return true;
    }
}
