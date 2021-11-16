<?php

namespace App\Http\Controllers;

use App\Models\AML;
use App\Models\QuoteType;
use Illuminate\Http\Request;
use DataTables;
use Auth;
use App\Enums\quoteTypeCode;
use App\Services\CheckAmlService;
use App\Services\QuoteStatusService;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\BusinessQuote;
use App\Models\BikeQuote;
use App\Models\YachtQuote;
use App\Models\TravelQuote;
use App\Models\BusinessQuoteType;
use App\Models\BusinessCoverType;
use App\Models\CommunicationMode;
use App\Models\QuoteStatus;
use App\Models\SanctionListDownloads;
use App\Models\UAEAMLListUploads;

class AMLController extends Controller
{
    protected $checkAmlService, $quoteStatusService;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct(CheckAmlService $checkAmlService, QuoteStatusService $quoteStatusService)
    {
        $this->middleware('permission:aml-list', ['only' => ['index']]);
        $this->checkAmlService = $checkAmlService;
        $this->quoteStatusService = $quoteStatusService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $quoteTypes = QuoteType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $quoteStatuses = QuoteStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();

        if ($request->ajax()) {
            $dataAml = [];

            if (isset($request->quoteType) && !empty($request->quoteType)) {

                $quoteTypeCode = QuoteType::where('id', '=', $request->quoteType)->value('code');
                if($quoteTypeCode == quoteTypeCode::Car) { $quoteRequestTable = 'car_quote_request'; }
                if($quoteTypeCode == quoteTypeCode::Home) { $quoteRequestTable = 'home_quote_request'; }
                if($quoteTypeCode == quoteTypeCode::Health) { $quoteRequestTable = 'health_quote_request'; }
                if($quoteTypeCode == quoteTypeCode::Life) { $quoteRequestTable = 'life_quote_request'; }
                if($quoteTypeCode == quoteTypeCode::Business) { $quoteRequestTable = 'business_quote_request'; }
                if($quoteTypeCode == quoteTypeCode::Bike) { $quoteRequestTable = 'bike_quote_request'; }
                if($quoteTypeCode == quoteTypeCode::Yacht) { $quoteRequestTable = 'yacht_quote_request'; }
                if($quoteTypeCode == quoteTypeCode::Travel) { $quoteRequestTable = 'travel_quote_request'; }

                $dataAml = AML::select('kyc_logs.*', 'quote_type.text as quote_type_text', $quoteRequestTable.'.code as cdb_id')
                ->leftjoin('quote_type', 'quote_type.id', 'kyc_logs.quote_type_id')
                ->leftjoin($quoteRequestTable, $quoteRequestTable.'.id', 'kyc_logs.quote_request_id')
                ->where('kyc_logs.quote_type_id', $request->quoteType)
                ->orderBy('kyc_logs.created_at','desc');

                if (isset($request->searchType) && !empty($request->searchType) &&
                    isset($request->searchField) && !empty($request->searchField)) {
                    if($request->searchType == 'cdbId') {
                        $dataAml->where($quoteRequestTable.'.code',$request->searchField);
                    }
                    if($request->searchType == 'id') {
                        $dataAml->where('kyc_logs.id',$request->searchField);
                    }
                    if($request->searchType == 'customerEmail') {
                        $dataAml->where($quoteRequestTable.'.email',$request->searchField);
                    }
                }
                if (isset($request->matchFound)) {
                    if($request->matchFound == "False") {
                        $dataAml->where('kyc_logs.results_found', '=', '0');
                    }
                    if($request->matchFound == "True") {
                        $dataAml->where('kyc_logs.results_found', '>', '0');
                    }
                }
                if (isset($request->amlCreatedStartDate) && !empty($request->amlCreatedStartDate) &&
                    isset($request->amlCreatedEndDate) && !empty($request->amlCreatedEndDate)) {
                        $dataAml->whereRaw('DATE(kyc_logs.created_at) BETWEEN "'.$request->amlCreatedStartDate.'" AND "'.$request->amlCreatedEndDate.'"');
                }
            }

            return DataTables::of($dataAml)
                    ->addIndexColumn()
                    ->addColumn('action', function($row){
                        return view('aml.actions', compact('row'))->render();
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        return view('aml.view',compact('quoteTypes','quoteStatuses'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AML  $aml
     * @return \Illuminate\Http\Response
     */
    public function show(AML $aml)
    {
        $amlResults = json_decode($aml->results);

        return view('aml.show',compact('aml','amlResults'));
    }

    public function amlQuoteDetails($quoteTypeId, $quoteRequestId)
    {
        //return $this->checkAmlService->checkAml("Saddam","Hussain",$quoteRequestId,$quoteTypeId);

        $quoteType = QuoteType::where('id', '=', $quoteTypeId)->get(array('code','text'));
        $quoteTypeCode = $quoteType[0]->code;
        $quoteTypeText = $quoteType[0]->text;

        if($quoteTypeCode != "") {

            $kycLogs = AML::where('quote_request_id', '=', $quoteRequestId)->orderBy('created_at', 'desc')->get();

            if($quoteTypeCode == quoteTypeCode::Car) {

                $quoteRequest = CarQuote::select('car_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name', 'uae_license_held_for.text as uae_license_text'
                , 'car_make.text as car_make_text', 'car_model.text as car_model_text', 'emirates.text as emirates_text'
                , 'car_type_insurance.text as car_type_ins_text', 'claim_history.text as claim_history_text'
                , 'nationality.text as nationality_text')
                ->leftjoin('quote_status','car_quote_request.quote_status_id','quote_status.id')
                ->leftjoin('payment_status','car_quote_request.payment_status_id','payment_status.id')
                ->leftjoin('customer','car_quote_request.customer_id','customer.id')
                ->leftjoin('uae_license_held_for','car_quote_request.uae_license_held_for_id','uae_license_held_for.id')
                ->leftjoin('car_make','car_quote_request.car_make_id','car_make.id')
                ->leftjoin('car_model','car_quote_request.car_model_id','car_model.id')
                ->leftjoin('emirates','car_quote_request.emirate_of_registration_id','emirates.id')
                ->leftjoin('car_type_insurance','car_quote_request.car_type_insurance_id','car_type_insurance.id')
                ->leftjoin('claim_history','car_quote_request.claim_history_id','claim_history.id')
                ->leftjoin('nationality','car_quote_request.nationality_id','nationality.id')
                ->where('car_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = "CarQuote";
            }
            else if($quoteTypeCode == quoteTypeCode::Health) {

                $quoteRequest = HealthQuote::select('health_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name'
                , 'health_cover_for.text as health_cover_text', 'marital_status.text as marital_status_text', 'emirates.text as emirates_text'
                , 'nationality.text as nationality_text')
                ->leftjoin('quote_status','health_quote_request.quote_status_id','quote_status.id')
                ->leftjoin('payment_status','health_quote_request.payment_status_id','payment_status.id')
                ->leftjoin('customer','health_quote_request.customer_id','customer.id')
                ->leftjoin('health_cover_for','health_quote_request.cover_for_id','health_cover_for.id')
                ->leftjoin('marital_status','health_quote_request.marital_status_id','marital_status.id')
                ->leftjoin('emirates','health_quote_request.emirate_of_your_visa_id','emirates.id')
                ->leftjoin('nationality','health_quote_request.nationality_id','nationality.id')
                ->where('health_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = "HealthQuote";
            }
            else if($quoteTypeCode == quoteTypeCode::Home) {

                $quoteRequest = HomeQuote::select('home_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name'
                , 'home_possession_type.text as home_possession_type_text'
                , 'home_accommodation_type.text as home_accommodation_type_text')
                ->leftjoin('quote_status','home_quote_request.quote_status_id','quote_status.id')
                ->leftjoin('payment_status','home_quote_request.payment_status_id','payment_status.id')
                ->leftjoin('customer','home_quote_request.customer_id','customer.id')
                ->leftjoin('home_possession_type','home_quote_request.iam_possesion_type_id','home_possession_type.id')
                ->leftjoin('home_accommodation_type','home_quote_request.ilivein_accommodation_type_id','home_accommodation_type.id')
                ->where('home_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = "HomeQuote";
            }
            else if($quoteTypeCode == quoteTypeCode::Travel) {

                $quoteRequest = TravelQuote::select('travel_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name'
                , 'region.text as region_cover_text', 'travel_cover_for.text as cover_for_text', 'nationality.text as nationality_text')
                ->leftjoin('quote_status','travel_quote_request.quote_status_id','quote_status.id')
                ->leftjoin('payment_status','travel_quote_request.payment_status_id','payment_status.id')
                ->leftjoin('customer','travel_quote_request.customer_id','customer.id')
                ->leftjoin('region','travel_quote_request.region_cover_for_id','region.id')
                ->leftjoin('travel_cover_for','travel_quote_request.travel_cover_for_id','travel_cover_for.id')
                ->leftjoin('nationality','travel_quote_request.nationality_id','nationality.id')
                ->where('travel_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = "TravelQuote";
            }
            else if($quoteTypeCode == quoteTypeCode::Life) {

                $quoteRequest = LifeQuote::select('life_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name'
                , 'life_insurance_purpose.text as purpose_text', 'life_children.text as children_text', 'marital_status.text as marital_text'
                , 'life_insurance_tenure.text as tenure_text', 'life_number_of_year.text as number_of_year_text'
                , 'currency_type.text as currency_text', 'nationality.text as nationality_text')
                ->leftjoin('quote_status','life_quote_request.quote_status_id','quote_status.id')
                ->leftjoin('payment_status','life_quote_request.payment_status_id','payment_status.id')
                ->leftjoin('customer','life_quote_request.customer_id','customer.id')
                ->leftjoin('life_insurance_purpose','life_quote_request.purpose_of_insurance_id','life_insurance_purpose.id')
                ->leftjoin('life_children','life_quote_request.children_id','life_children.id')
                ->leftjoin('marital_status','life_quote_request.marital_status_id','marital_status.id')
                ->leftjoin('life_insurance_tenure','life_quote_request.tenure_of_insurance_id','life_insurance_tenure.id')
                ->leftjoin('life_number_of_year','life_quote_request.number_of_years_id','life_number_of_year.id')
                ->leftjoin('currency_type','life_quote_request.sum_insured_currency_id','currency_type.id')
                ->leftjoin('nationality','life_quote_request.nationality_id','nationality.id')
                ->where('life_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = "LifeQuote";
            }
            else if($quoteTypeCode == quoteTypeCode::Bike) {

                $quoteRequest = BikeQuote::select('bike_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name'
                , 'nationality.text as nationality_text', 'uae_license_held_for.text as uae_license_text')
                ->leftjoin('quote_status','bike_quote_request.quote_status_id','quote_status.id')
                ->leftjoin('payment_status','bike_quote_request.payment_status_id','payment_status.id')
                ->leftjoin('customer','bike_quote_request.customer_id','customer.id')
                ->leftjoin('nationality','bike_quote_request.nationality_id','nationality.id')
                ->leftjoin('uae_license_held_for','bike_quote_request.uae_license_held_for_id','uae_license_held_for.id')
                ->where('bike_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = "BikeQuote";
            }
            else if($quoteTypeCode == quoteTypeCode::Yacht) {

                $quoteRequest = YachtQuote::select('yacht_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name')
                ->leftjoin('quote_status','yacht_quote_request.quote_status_id','quote_status.id')
                ->leftjoin('payment_status','yacht_quote_request.payment_status_id','payment_status.id')
                ->leftjoin('customer','yacht_quote_request.customer_id','customer.id')
                ->where('yacht_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = "YachtQuote";
            }
            else if($quoteTypeCode == quoteTypeCode::Business) {

                $quoteRequest = BusinessQuote::select('business_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name'
                , 'business_type_of_insurance.text as business_type_text')
                ->leftjoin('quote_status','business_quote_request.quote_status_id','quote_status.id')
                ->leftjoin('payment_status','business_quote_request.payment_status_id','payment_status.id')
                ->leftjoin('customer','business_quote_request.customer_id','customer.id')
                ->leftjoin('business_type_of_insurance','business_quote_request.business_type_of_insurance_id','business_type_of_insurance.id')
                ->where('business_quote_request.id', $quoteRequestId)->first();
                $auditLogLine = "BusinessQuote";

                $businessTypeCode = BusinessQuoteType::where('id', '=', $quoteRequest->business_type_of_insurance_id)->value('code');
                $businessCoverTypeText = BusinessCoverType::where('id', '=', $quoteRequest->business_cover_type_id)->value('text');
                $businessCommuModeText = CommunicationMode::where('id', '=', $quoteRequest->communication_mode_id)->value('text');
            }
            else {
                $quoteRequest = "";
            }
        }
        else { return redirect()->route('aml.details')->with('message','Not Found!'); }

        if($quoteRequest->quote_status_id && $quoteRequest->quote_status_id != "") {
            $quoteStatus = QuoteStatus::where('id', '=', $quoteRequest->quote_status_id)->get(array('code'));
            $quoteStatusCode = $quoteStatus[0]->code;
        }
        else {
            $quoteStatusCode = "";
        }

        if(Auth::user()->hasRole("COMPLIANCE")) {
            $isCurrentUserFromCompliance = 1;
        }
        else {
            $isCurrentUserFromCompliance = 0;
        }
        if(Auth::user()->hasRole("pa") || Auth::user()->hasRole("AML")) {
            $isCurrentUserFromPaAml = 1;
        }
        else {
            $isCurrentUserFromPaAml = 0;
        }

        $getTotalResults = AML::where('quote_type_id', $quoteTypeId)
        ->where('quote_request_id', $quoteRequestId)
        ->sum('results_found');
        if($getTotalResults > 0) {
            $resultsFound = 1;
        }
        else {
            $resultsFound = 0;
        }

        $getAMLRows = AML::where('quote_type_id', '=', $quoteTypeId)
        ->where('quote_request_id', $quoteRequestId)->get();
        $getAMLNumRows = $getAMLRows->count();

        if($quoteTypeCode == quoteTypeCode::Business) {
            return view("aml.details", compact("quoteTypeCode","quoteTypeText","quoteRequest","businessTypeCode"
            ,"businessCoverTypeText","businessCommuModeText","kycLogs","quoteStatusCode","auditLogLine"
            ,"isCurrentUserFromCompliance","isCurrentUserFromPaAml","resultsFound","getAMLNumRows", "quoteTypeId"));
        }
        else {
            return view("aml.details", compact("quoteTypeCode","quoteTypeText","quoteRequest","kycLogs"
            ,"quoteStatusCode","auditLogLine","isCurrentUserFromCompliance","isCurrentUserFromPaAml"
            ,"resultsFound","getAMLNumRows", "quoteTypeId"));
        }
    }

    public function quoteStatusUpdate($quoteTypeId, $quoteRequestId, $quoteStatusType)
    {
        $updateQuoteStatusResp = $this->quoteStatusService->updateQuoteStatus($quoteTypeId, $quoteRequestId, $quoteStatusType);

        if($updateQuoteStatusResp == "false") {
            return redirect()->back()->with('message', 'Quote Status is not updated');
        }
        else {
            $quoteStatusText = $updateQuoteStatusResp[0];
            $quoteCdbId = $updateQuoteStatusResp[1];
            $quoteTypeText = $updateQuoteStatusResp[2];
            $quotePaID = $updateQuoteStatusResp[3];
            $clientFullName = $updateQuoteStatusResp[4];
            if(Auth::user()->hasRole("COMPLIANCE")) {
                $this->checkAmlService->sendAMLQuoteStatusChangeNotification($quoteTypeId, $quoteRequestId, $quoteStatusText, $quoteCdbId, $quoteTypeText, $quotePaID, $clientFullName);
            }
            return redirect()->back()->with('success', 'Quote Status is set to '.$quoteStatusText.'');
        }

    }

    public function quoteUpdate(Request $request, $quoteTypeId, $quoteRequestId)
    {
        $this->validate($request,[
            'first_name' => 'required|max:200',
            'last_name' => 'required|max:200',
        ]);

        $quoteTypeCode = QuoteType::where('id', '=', $quoteTypeId)->value('code');

        if($quoteTypeCode == quoteTypeCode::Car) { $updateQuote = CarQuote::find($quoteRequestId); }
        if($quoteTypeCode == quoteTypeCode::Home) { $updateQuote = HomeQuote::find($quoteRequestId); }
        if($quoteTypeCode == quoteTypeCode::Health) { $updateQuote = HealthQuote::find($quoteRequestId); }
        if($quoteTypeCode == quoteTypeCode::Life) { $updateQuote = LifeQuote::find($quoteRequestId); }
        if($quoteTypeCode == quoteTypeCode::Business) { $updateQuote = BusinessQuote::find($quoteRequestId); }
        if($quoteTypeCode == quoteTypeCode::Bike) { $updateQuote = BikeQuote::find($quoteRequestId); }
        if($quoteTypeCode == quoteTypeCode::Yacht) { $updateQuote = YachtQuote::find($quoteRequestId); }
        if($quoteTypeCode == quoteTypeCode::Travel) { $updateQuote = TravelQuote::find($quoteRequestId); }

        $quoteUpdate = $updateQuote;
        $firstName = ucwords(strtolower($request->first_name));
        $lastName = ucwords(strtolower($request->last_name));
        $quoteUpdate->first_name = $firstName;
        $quoteUpdate->last_name = $lastName;

        // Check current user role is pa/AML > If yes > update pa_id - current_user_id
        if(Auth::user()->hasRole("AML") || Auth::user()->hasRole("pa")) {
            $quoteUpdate->pa_id = Auth::user()->id;
        }

        $quoteUpdate->save();

        $this->checkAmlService->checkAml($firstName,$lastName,$quoteRequestId,$quoteTypeId, true);

        return redirect()->back()->with('success', 'Quote is updated');
    }

    public function sanctionListHistory(Request $request) {
        $data = [];
        $data = SanctionListDownloads::select('id', 'file_name', 'file_path', 'source', 'total_records', 'created_at', 'updated_at')->orderBy('created_at','desc')->get();

        if($request->ajax()) {
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('aml.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('aml.history');
    }

    public function uaeSanctionListUpload(Request $request) {
        $this->validate($request, [
            'file_name' => 'required|mimetypes:application/vnd.ms-excel,text/anytext,application/octet-stream,application/txt,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet|max:2048',
        ]);

        $fileNameOriginal = $request->file_name->getClientOriginalName();
        $fileNameAzure = date('d-m-Y').'_'.$fileNameOriginal;
        $request->file('file_name')->storeAs('/', $fileNameAzure, 'azureForRyu');

        dd($fileNameAzure);
        $newUpload = UAEAMLListUploads::where('id', '=', 1)->get()->first();
        $newUpload->file_name = $fileNameAzure;
        $newUpload->is_updated = true;
        $newUpload->save();

        return redirect('/kyc/aml/upload/uae')->with('success', 'UAE Sanction list uploaded successfully');
    }

    public function uploadUaeSanctionList() {
        return view('aml.upload');
    }

}
