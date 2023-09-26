<?php

namespace App\Http\Controllers\V2;

use App\Enums\LookupsEnum;
use App\Enums\quoteTypeCode;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\AMLCheckRequest;
use App\Models\AML;
use App\Models\ApplicationStorage;
use App\Models\BusinessCoverType;
use App\Models\BusinessQuoteType;
use App\Models\CommunicationMode;
use App\Models\Customer;
use App\Models\QuoteStatus;
use App\Models\QuoteType;
use App\Models\SanctionListDownloads;
use App\Models\UAEAMLListUploads;
use App\Repositories\LookupRepository;
use App\Repositories\NationalityRepository;
use App\Repositories\QuoteMemberDetailsRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\QuoteTypeRepository;
use App\Services\CheckAmlService;
use App\Services\AMLService;
use App\Services\QuoteStatusService;
use App\Services\SanctionListService;
use App\Traits\GenericQueriesAllLobs;
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
    public function index(Request $request)
    {
        $quoteTypes = QuoteTypeRepository::getList();
        $quoteStatuses = QuoteStatusRepository::getList();
        $quoteTypeId = $quoteTypes->where('code', $request->quoteType)->first()?->id;

        $quotes = new AML();
        if (isset($request->quoteType) && ! empty($request->quoteType)) {
            $quoteRequestTable = str_replace(' ', '_', strtolower($request->quoteType).'_quote_request');

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
                $dataAml->whereRaw('DATE(kyc_logs.created_at) BETWEEN "'.$request->amlCreatedStartDate.'" AND "'.$request->amlCreatedEndDate.'"');
            }
            $quotes = $dataAml->simplePaginate(10)->withQueryString();
        }

        // return view('aml.view', compact('quoteTypes', 'quoteStatuses'));
        return inertia('Aml/Index', [
            'quoteTypes' => $quoteTypes,
            'quoteStatuses' => $quoteStatuses,
            'aml' => $quotes,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
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
        $quoteType = QuoteType::where('id', $quoteTypeId)->firstOrFail();
        $isCompanySearchEnabled = ApplicationStorage::where('key_name', '=', 'IS_AML_ENTITY_SEARCH_ENABLED')->value('value');

        // Need to update with Limit when new UI going to Live
        $kycLogs = AML::where([
            'quote_request_id' => $quoteRequestId,
            'quote_type_id' => $quoteTypeId
        ])->orderBy('created_at', 'desc')->get();

        $quoteRequest = AMLService::getQuoteDetails($quoteTypeId, $quoteRequestId);
//        dd($quoteRequest->toArray());

//        $quoteRequest = match ($quoteType->code) {
//            quoteTypeCode::Car => QuoteRequestAmlService::getCarQuoteRequest($quoteRequestId),
//            quoteTypeCode::Health => QuoteRequestAmlService::getHealthQuoteRequest($quoteRequestId),
//            quoteTypeCode::Home => QuoteRequestAmlService::getHomeQuoteRequest($quoteRequestId),
//            quoteTypeCode::Travel => QuoteRequestAmlService::getTravelQuoteRequest($quoteRequestId),
//            quoteTypeCode::Life => QuoteRequestAmlService::getLifeQuoteRequest($quoteRequestId),
//            quoteTypeCode::Bike => QuoteRequestAmlService::getBikeQuoteRequest($quoteRequestId),
//            quoteTypeCode::Yacht => QuoteRequestAmlService::getYachtQuoteRequest($quoteRequestId),
//            quoteTypeCode::Business => QuoteRequestAmlService::getBusinessQuoteRequest($quoteRequestId),
//            quoteTypeCode::Pet => QuoteRequestAmlService::getPetQuoteRequest($quoteRequestId),
//            default => '',
//        };

        // 8113
        $membersDetail = QuoteMemberDetailsRepository::getBy('quote_request_id', $quoteRequest->id, $quoteTypeId);
        $memberRelations = LookupRepository::where('key', LookupsEnum::MEMBER_RELATION)->get();
        $nationalities =  NationalityRepository::withActive()->get();


        $quoteStatusCode = '';
        if ($quoteRequest && $quoteRequest->quote_status_id && $quoteRequest->quote_status_id != '') {
            $quoteStatus = QuoteStatus::where('id', '=', $quoteRequest->quote_status_id)->get(['code']);
            $quoteStatusCode = $quoteStatus[0]->code;
        }

        $isCurrentUserFromCompliance = 0;
        if (auth()->user()->hasRole(RolesEnum::COMPLIANCE)) {
            $isCurrentUserFromCompliance = 1;
        }

        $isCurrentUserFromPaAml = 0;
        if (auth()->user()->hasAnyRole([RolesEnum::PA, RolesEnum::AML])) {
            $isCurrentUserFromPaAml = 1;
        }

        $firstAmlLogResults = AML::where('quote_type_id', $quoteTypeId)->where('quote_request_id', $quoteRequestId)->first()->results_found ?? 0;
        $latestAmlLogResults = AML::where('quote_type_id', $quoteTypeId)->where('quote_request_id', $quoteRequestId)->latest()->first()->results_found ?? 0;

        $getAMLNumRows = AML::where('quote_type_id', '=', $quoteTypeId)->where('quote_request_id', $quoteRequestId)->count();

        $nationalityList = $this->sanctionListService->fetchNationality();
        $yearsList = $this->sanctionListService->years();

        $data = [
            'quoteType' => $quoteType,
            'quoteRequest' => $quoteRequest,
            'membersDetails' => $membersDetail,
            'memberRelations' => $memberRelations,
            'nationalities' => $nationalities,

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
            $data['businessTypeCode'] = BusinessQuoteType::where('id', '=', $quoteRequest->business_type_of_insurance_id)->value('code');
            $data['businessCoverTypeText'] = BusinessCoverType::where('id', '=', $quoteRequest->business_cover_type_id)->value('text');
            $data['businessCommuModeText'] = CommunicationMode::where('id', '=', $quoteRequest->business_communication_mode_id)->value('text');
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
        $customerDetails = [
            'insured_first_name' => $AMLCheckRequest->insured_first_name,
            'insured_last_name' => $AMLCheckRequest->insured_last_name,
            'nationality_id' => $AMLCheckRequest->nationality,
            'dob' => $AMLCheckRequest->date_of_birth,
        ];

        if (checkPersonalQuotes($quoteType->code) && (! AMLService::isDataMigrated($quoteTypeId, $quoteRequestId))) {
            $quoteId = AMLService::getPersonalQuoteId($quoteTypeId, $quoteId);
        }

        $updateQuote = $this->getQuoteObject($quoteType->code, $quoteId);
        if($updateQuote) {

            $customer = Customer::where('id', $AMLCheckRequest->customer_id)->get();
            $customer->update($customerDetails);

            if (auth()->user()->hasAnyRole([RolesEnum::AML, RolesEnum::PA])) {
                if (checkPersonalQuotes($quoteType->code)) {
                    AMLService::updatePaIdForPersonalQuotes($quoteTypeId, $quoteRequestId, AMLService::isDataMigrated($quoteTypeId, $quoteId));
                } else {
                    $updateQuote->pa_id = auth()->user()->id;
                    $updateQuote->save();
                }
            }
        }

        // Bridger API call for AML Check
        Log::info('Bridger API call for AML Check with customer ID '.$AMLCheckRequest->customer_id.' And data pass for Bridger API are : '.json_encode($customerDetails));
        AMLService::amlCheck($AMLCheckRequest->validated(), $quoteRequestId, $quoteTypeId);

        return redirect()->back()->with('success', 'Quote is updated');
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
}
