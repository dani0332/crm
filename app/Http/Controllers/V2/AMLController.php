<?php

namespace App\Http\Controllers\V2;

use App\Enums\CustomerTypeEnum;
use App\Enums\LookupsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\AMLCheckRequest;
use App\Http\Requests\AMLRequest;
use App\Models\AML;
use App\Models\ApplicationStorage;
use App\Models\BusinessCoverType;
use App\Models\BusinessQuoteType;
use App\Models\CommunicationMode;
use App\Models\Customer;
use App\Models\Emirate;
use App\Models\Entity;
use App\Models\HealthMemberDetail;
use App\Models\PersonalQuote;
use App\Models\QuoteRequestEntityMapping;
use App\Models\QuoteStatus;
use App\Models\QuoteType;
use App\Models\SanctionListDownloads;
use App\Models\TravelMemberDetail;
use App\Models\UAEAMLListUploads;
use App\Repositories\LookupRepository;
use App\Repositories\NationalityRepository;
use App\Repositories\QuoteMemberDetailsRepository;
use App\Repositories\QuoteTypeRepository;
use App\Services\BridgerInsightService;
use App\Services\CheckAmlService;
use App\Services\AMLService;
use App\Services\QuoteStatusService;
use App\Services\SanctionListService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AMLController extends Controller
{
    protected $checkAmlService;
    protected $quoteStatusService;
    protected $sanctionListService;
    use GenericQueriesAllLobs;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(CheckAmlService $checkAmlService, QuoteStatusService $quoteStatusService, SanctionListService $sanctionListService)
    {
        $this->middleware('permission:aml-list', ['only' => ['index']]);
        $this->checkAmlService = $checkAmlService;
        $this->quoteStatusService = $quoteStatusService;
        $this->sanctionListService = $sanctionListService;
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

            if (isset($request->quoteType) && !empty($request->quoteType)) {
                $quoteTypeId = $quoteTypes->where('code', $request->quoteType)->first()?->id;
                $quoteRequestTable = strtolower($request->quoteType) . '_quote_request';

                if (in_array($quoteTypeId, [
                    QuoteTypes::BIKE->id(),
                    QuoteTypes::YACHT->id(),
                    QuoteTypes::PET->id(),
                    QuoteTypes::CYCLE
                ])) {
                    if (isset($request->amlCreatedStartDate) && !empty($request->amlCreatedStartDate)) {
                        $quoteRequestTable = AMLService::isDataMigrated($quoteTypeId, '', $request->amlCreatedStartDate) ? 'personal_quotes' : $quoteRequestTable;
                    } else {
                        if (isset($request->searchType) && in_array($request->searchType, ['cdbId', 'customerEmail', 'id'])) {
                            $searchType = match ($request->searchType) {
                                'cdbId' => 'code',
                                'customerEmail' => 'email',
                                'id' => 'id'
                            };

                            $createdDate =
                                $request->searchType == 'id' ? AML::where($searchType, $request->searchField)->firstOrFail()->created_at :
                                    PersonalQuote::where($searchType, $request->searchField)->firstOrFail()->created_at;

                            $quoteRequestTable = AMLService::isDataMigrated($quoteTypeId, '', $createdDate) ? 'personal_quotes' : strtolower($request->quoteType) . '_quote_request';
                        }
                    }
                }

                $dataAml = AML::select('kyc_logs.*', 'quote_type.text as quote_type_text', $quoteRequestTable.'.code as cdb_id')
                    ->leftjoin('quote_type', 'quote_type.id', 'kyc_logs.quote_type_id')
                    ->leftjoin($quoteRequestTable, $quoteRequestTable.'.id', 'kyc_logs.quote_request_id')
                    ->where('kyc_logs.quote_type_id', $quoteTypeId)
                    ->orderBy('kyc_logs.created_at', 'desc');

                if (
                    isset($request->searchType) && ! empty($request->searchType) &&
                    isset($request->searchField) && ! empty($request->searchField)
                ) {
                    if ($request->searchType == 'cdbId') {
                        $dataAml->where($quoteRequestTable.'.code', $request->searchField);
                    }
                    if ($request->searchType == 'id') {
                        $dataAml->where('kyc_logs.id', $request->searchField);
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
                    $amlCreatedDate = date(config('constants.DATE_FORMAT_ONLY'), strtotime($request->amlCreatedStartDate));
                    $amlEndDate = date(config('constants.DATE_FORMAT_ONLY'), strtotime($request->amlCreatedEndDate));
                    $dataAml->whereRaw('DATE(kyc_logs.created_at) BETWEEN "'.$amlCreatedDate.'" AND "'.$amlEndDate.'"');
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

    /**
     * Display the specified resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(AML $aml)
    {
        $amlResults = json_decode($aml->results);

        $aml->quote_type_text = $aml->quotetype->text;

        return inertia('Aml/Show', [
            'amlResults' => $amlResults,
            'aml' => $aml,
        ]);
    }

    public function amlQuoteDetails($quoteTypeId, $quoteRequestId)
    {
        $quoteStatusCode = '';
        $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
        $isCompanySearchEnabled = ApplicationStorage::where('key_name', '=', 'IS_AML_ENTITY_SEARCH_ENABLED')->value('value');
        $amlRecordFetch = AML::where([ 'quote_request_id' => $quoteRequestId, 'quote_type_id' => $quoteTypeId ]);
        $kycLogs = $amlRecordFetch->orderBy('created_at', 'desc')->get();

        $quoteRequest = AMLService::getQuoteDetails($quoteTypeId, $quoteRequestId);
        $entityDetails = QuoteRequestEntityMapping::with('entity')->where(['quote_type_id' => $quoteTypeId, 'quote_request_id' => $quoteRequestId])->first() ?? [];

        if ($quoteTypeId == QuoteTypeId::Health) {
            $membersDetail = HealthMemberDetail::with(['relation', 'nationality'])->where('health_quote_request_id', $quoteRequest->id)->get();
        } elseif ($quoteTypeId == QuoteTypeId::Travel) {
            $membersDetail = TravelMemberDetail::with(['relation', 'nationality'])->where('travel_quote_request_id', $quoteRequest->id)->get();;
        } else {
            $membersDetail = QuoteMemberDetailsRepository::getBy('quote_request_id', $quoteRequest->id, $quoteTypeId);
        }

        $uboDetails = QuoteMemberDetailsRepository::getBy('quote_request_id', $quoteRequest->id, $quoteTypeId, CustomerTypeEnum::Entity);
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $uboRelations = LookupRepository::where('key', LookupsEnum::UBO_RELATION)->get();
        $nationalities =  NationalityRepository::withActive()->get();
        $emirates = Emirate::where('is_active', 1)->orderBy('sort_order')->get();
        $industryType = LookupRepository::where('key', LookupsEnum::COMPANY_TYPE)->get();
        $isCurrentUserFromCompliance = auth()->user()->hasRole(RolesEnum::COMPLIANCE) ? 1 : 0;
        $isCurrentUserFromPaAml = auth()->user()->hasAnyRole([RolesEnum::PA, RolesEnum::AML]) ? 1 : 0;

        $nationalityList = $this->sanctionListService->fetchNationality();
        $yearsList = $this->sanctionListService->years();
        $firstAmlLogResults = $amlRecordFetch->first()->results_found ?? 0;
        $latestAmlLogResults = $amlRecordFetch->latest()->first()->results_found ?? 0;
        $getAMLNumRows = $amlRecordFetch->count();

        if ($quoteRequest && $quoteRequest->quote_status_id && $quoteRequest->quote_status_id != '') {
            $quoteStatus = QuoteStatus::where('id', '=', $quoteRequest->quote_status_id)->get(['code']);
            $quoteStatusCode = $quoteStatus[0]->code;
        }

        $data = [
            'quoteType' => $quoteType,
            'quoteRequest' => $quoteRequest,
            'entityDetails' => $entityDetails,
            'membersDetails' => $membersDetail,
            'uboDetails' => $uboDetails,
            'memberRelations' => $memberRelations,
            'uboRelations' => $uboRelations,
            'nationalities' => $nationalities,
            'emirates' => $emirates,
            'industryType' => $industryType,
            'customerTypeEnum' => CustomerTypeEnum::asArray(),
            'kycLogs' => $kycLogs,
            'quoteStatusCode' => $quoteStatusCode,
            'isCurrentUserFromCompliance' => $isCurrentUserFromCompliance,
            'isCurrentUserFromPaAml' => $isCurrentUserFromPaAml,
            'firstAmlLogResults' => $firstAmlLogResults,
            'latestAmlLogResults' => $latestAmlLogResults,
            'getAMLNumRows' => $getAMLNumRows,
            'nationalityList' => $nationalityList,
            'yearsList' => $yearsList,
            'isCompanySearchEnabled' => $isCompanySearchEnabled,
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
        $updateQuoteStatusResp = $this->quoteStatusService->updateQuoteStatus($quoteTypeId, $quoteRequestId, $quoteStatusType);

        if ($updateQuoteStatusResp == 'false') {
            return redirect()->back()->with('message', 'Quote Status is not updated');
        } else {
            $quoteStatusText = $updateQuoteStatusResp[0];
            $quoteCdbId = $updateQuoteStatusResp[1];
            $quoteTypeText = $updateQuoteStatusResp[2];
            $quotePaID = $updateQuoteStatusResp[3];
            $clientFullName = $updateQuoteStatusResp[4];
            if (auth()->user()->hasRole(RolesEnum::COMPLIANCE)) {
                $this->checkAmlService->sendAMLQuoteStatusChangeNotification($quoteTypeId, $quoteRequestId, $quoteStatusText, $quoteCdbId, $quoteTypeText, $quotePaID, $clientFullName);
            }

            return redirect()->back()->with('success', 'Quote Status is set to '.$quoteStatusText.'');
        }
    }

    public function quoteUpdate(AMLCheckRequest $AMLCheckRequest, $quoteTypeId, $quoteRequestId)
    {
        $quoteId = $quoteRequestId;
        $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();

        if (checkPersonalQuotes($quoteType->code) && (!AMLService::isDataMigrated($quoteTypeId, $quoteRequestId))) {
            $quoteId = AMLService::getPersonalQuoteId($quoteTypeId, $quoteId);
        }

        $updateQuote = $this->getQuoteObject($quoteType->code, $quoteId);
        $getMemberOrUBODetails = AMLService::getMemberOrUBODetails($AMLCheckRequest, $quoteType, $quoteId);

        if($updateQuote) {

            if (auth()->user()->hasAnyRole([RolesEnum::AML, RolesEnum::PA])) {
                if (checkPersonalQuotes($quoteType->code)) {
                    AMLService::updatePaIdForPersonalQuotes($quoteTypeId, $quoteRequestId, AMLService::isDataMigrated($quoteTypeId, $quoteId));
                } else {
                    $updateQuote->pa_id = auth()->user()->id;
                    $updateQuote->save();
                }
            }

            if($AMLCheckRequest->customer_type == CustomerTypeEnum::Individual) {
                $customer = Customer::with('nationality')->findOrFail($AMLCheckRequest->customer_id);
                $customer->update($AMLCheckRequest->validated());

                $getMemberOrUBODetails[] = [
                    'first_name' => $customer->insured_first_name,
                    'last_name' => $customer->insured_last_name,
                    'dob' => Carbon::parse($customer->dob)->format(config('constants.DATE_FORMAT_ONLY')),
                    'nationality' => $customer->nationality->toArray(),
                    'code' => CustomerTypeEnum::IndividualShort . '-'. $customer->id,
                ];
            }

            if ($AMLCheckRequest->customer_type == CustomerTypeEnum::Entity) {

                $entity = Entity::updateOrCreate(['trade_license_no' => $AMLCheckRequest->trade_license_no],[
                    'company_name' => $AMLCheckRequest->company_name,
                    'company_address' => $AMLCheckRequest->company_address,
                    'entity_type_code' => $AMLCheckRequest->entity_type_code,
                    'industry_type_code' => $AMLCheckRequest->industry_type_code,
                    'emirate_of_registration_id' => $AMLCheckRequest->emirate_of_registration_id
                ]);
                $entityId = $entity->id;
                $entity->update(['code' => CustomerTypeEnum::EntityShort . '-'. $entityId]);

                QuoteRequestEntityMapping::updateOrCreate([
                    'quote_type_id' => $quoteType->id,
                    'quote_request_id' => $quoteRequestId
                ],['entity_id' => $entityId]);

                $getMemberOrUBODetails[] = [
                    'company_name' => $entity->company_name,
                    'code' => CustomerTypeEnum::EntityShort . '-'. $entityId,
                ];
            }

            dd($getMemberOrUBODetails->toArray());
            $bridgerInsightService = new BridgerInsightService();
            foreach ($getMemberOrUBODetails as $value):
                Log::info('Bridger API call for AML Check with customer ID '.$value->code.' And data pass for Bridger API are : '.json_encode($value));
                $bridgerInsightService->searchAMLResult($value, $quoteRequestId, $quoteTypeId, $AMLCheckRequest->customer_type);
//                AMLService::amlCheck($value, $quoteRequestId, $quoteTypeId);
                sleep(5);
            endforeach;
            dd("final");

            return redirect()->back()->with('success', 'Quote is updated');
        }

        return redirect()->back()->with('error', 'Something went wrong');

    }

    public function sanctionListHistory(Request $request, SanctionListDownloads $sanctionListDownloads, Datatables $datatables)
    {
        $url = env('AZURE_RYU_STORAGE_URL').env('AZURE_AML_HISTORY');

        $sanctionListDownloads = $sanctionListDownloads->newQuery();

        if ($request->file_name != '') {
            $sanctionListDownloads = $sanctionListDownloads->where('file_name', 'like', '%'.$request->file_name.'%');
        }
        if ($request->is_processed == '0' || $request->is_processed == '1') {
            $value = $request->is_processed == '1';
            $sanctionListDownloads = $sanctionListDownloads->where('is_processed', $value);
        }
        $orderBy = $request->sortBy == '' ? 'created_at' : $request->sortBy;
        $sortType = $request->sortType == '' ? 'DESC' : $request->sortType;

        $sanctionListDownloads = $sanctionListDownloads->orderBy($orderBy, $sortType)->paginate(10);

        return inertia('Aml/History', [
            'sanctionListDownloads' => $sanctionListDownloads,
            'url' => $url,
        ]);

        // return view('aml.history', compact('url'));
    }

    public function uaeSanctionListUpload(Request $request)
    {
        $this->validate($request, [
            'file_name' => 'required|mimetypes:application/vnd.ms-excel,text/anytext,application/octet-stream,application/txt,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet|max:2048',
        ]);

        $getUAEUploadRecord = UAEAMLListUploads::where('id', '=', 1)->get()->first();

        if ($getUAEUploadRecord == null) {
            $newUAEUploadRecord = new UAEAMLListUploads([
                'id' => 1,
                'file_name' => '16-11-2021_UAESanctionlist.xls',
                'is_updated' => false,
            ]);
            $newUAEUploadRecord->save();
        }

        $fileNameOriginal = $request->file_name->getClientOriginalName();
        $fileNameAzure = date('d-m-Y').'_'.$fileNameOriginal;
        $request->file('file_name')->storeAs('/', $fileNameAzure, 'azureForRyu');

        $newUpload = UAEAMLListUploads::where('id', '=', 1)->get()->first();
        $newUpload->file_name = $fileNameAzure;
        $newUpload->is_updated = true;
        $newUpload->save();

        return redirect('/kyc/aml/upload/uae')->with('success', 'UAE Sanction list uploaded successfully');
    }

    public function uploadUaeSanctionList()
    {
        return inertia('Aml/UploadUae');
        // return view('aml.upload');
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
        QuoteRequestEntityMapping::updateOrCreate([
            'quote_type_id' => $request->quote_type_id,
            'quote_request_id' => $request->quote_request_id
        ],['entity_id' => $request->entity_id]);

//        $quoteRequestMapping = QuoteRequestEntityMapping::where([
//            'quote_type_id' => $request->quote_type_id,
//            'quote_request_id' => $request->quote_request_id
//        ])->first() ?? [];
//
//        if(!empty($quoteRequestMapping)){
//            $quoteRequestMapping->update(['entity_id' => $request->entity_id]);
//        } else {
//            QuoteRequestEntityMapping::create([
//                'quote_type_id' => $request->quote_type_id,
//                'quote_request_id' => $request->quote_request_id,
//                'entity_id' => $request->entity_id
//            ]);
//        }

        $entity = Entity::where('id', $request->entity_id)->first();

        return response()->json(['status' => true, 'response' => $entity, 'message' => 'Entity Linked Successfully']);
    }
}
