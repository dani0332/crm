<?php

namespace App\Http\Controllers;

use App\Models\AML;
use App\Models\QuoteType;
use Illuminate\Http\Request;
use DataTables;
use Config;
use DB;
use App\Models\CarQuote;

class AMLController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('permission:aml-list', ['only' => ['index']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $quotetypes = QuoteType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();

        if ($request->ajax()) {
            $data = AML::select('kyc_logs.*', 'quote_type.text as quote_type_text')
            ->leftjoin('quote_type', 'quote_type.id', 'kyc_logs.quote_type_id')->orderBy('kyc_logs.created_at','desc');

            if (isset($request->searchtype) && !empty($request->searchtype)
            && isset($request->searchfield) && !empty($request->searchfield)) {
                if($request->searchtype == 'quote_request_id') {
                    $data->where('kyc_logs.quote_request_id',$request->searchfield);
                }
                else if($request->searchtype == 'id') {
                    $data->where('kyc_logs.id',$request->searchfield);
                }
                else {
                    $data->where($request->searchtype, $request->searchfield);
                }
            }

            if(isset($request->quotetype) && !empty($request->quotetype)) {
                $data->where('kyc_logs.quote_type_id', $request->quotetype);
            }

            return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('action', function($row){
                        return view('aml.actions', compact('row'))->render();
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
        return view('aml.view',compact('quotetypes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AML  $aml
     * @return \Illuminate\Http\Response
     */
    public function show(AML $aml)
    {
        $aml_results = json_decode($aml->results);
        
        return view('aml.show',compact('aml','aml_results'));
    }

    public function aml_details($quote_type_id, $quote_request_id)
    {
        $quote_type_code = DB::table('quote_type')->where('id', $quote_type_id)->value('code');
        $quote_type_text = DB::table('quote_type')->where('id', $quote_type_id)->value('text');

        if($quote_type_code != "") {

            $kyc_logs = DB::table('kyc_logs')->where('quote_request_id', '=', $quote_request_id)->orderBy('created_at', 'desc')->get();
            $quote_type_text = $quote_type_text;

            if($quote_type_code == "Car") {
                $quote_request = DB::table('car_quote_request')
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
                ->where('car_quote_request.id', $quote_request_id)
                ->select('car_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name', 'uae_license_held_for.text as uae_license_text'
                , 'car_make.text as car_make_text', 'car_model.text as car_model_text', 'emirates.text as emirates_text'
                , 'car_type_insurance.text as car_type_ins_text', 'claim_history.text as claim_history_text'
                , 'nationality.text as nationality_text')->first();
            }
            else if($quote_type_code == "Health") {
                $quote_request = DB::table('health_quote_request')
                ->leftjoin('quote_status','health_quote_request.quote_status_id','quote_status.id')
                ->leftjoin('payment_status','health_quote_request.payment_status_id','payment_status.id')
                ->leftjoin('customer','health_quote_request.customer_id','customer.id')
                ->leftjoin('health_cover_for','health_quote_request.cover_for_id','health_cover_for.id')
                ->leftjoin('marital_status','health_quote_request.marital_status_id','marital_status.id')
                ->leftjoin('emirates','health_quote_request.emirate_of_your_visa_id','emirates.id')
                ->leftjoin('nationality','health_quote_request.nationality_id','nationality.id')
                ->where('health_quote_request.id', $quote_request_id)
                ->select('health_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name'
                , 'health_cover_for.text as health_cover_text', 'marital_status.text as marital_status_text', 'emirates.text as emirates_text'
                , 'nationality.text as nationality_text')->first();
            }
            else if($quote_type_code == "Home") {
                $quote_request = DB::table('home_quote_request')
                ->leftjoin('quote_status','home_quote_request.quote_status_id','quote_status.id')
                ->leftjoin('payment_status','home_quote_request.payment_status_id','payment_status.id')
                ->leftjoin('customer','home_quote_request.customer_id','customer.id')
                ->leftjoin('home_possession_type','home_quote_request.iam_possesion_type_id','home_possession_type.id')
                ->leftjoin('home_accommodation_type','home_quote_request.ilivein_accommodation_type_id','home_accommodation_type.id')
                ->where('home_quote_request.id', $quote_request_id)
                ->select('home_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name'
                , 'home_possession_type.text as home_possession_type_text'
                , 'home_accommodation_type.text as home_accommodation_type_text')->first();
            }
            else if($quote_type_code == "Travel") {
                $quote_request = DB::table('travel_quote_request')
                ->leftjoin('quote_status','travel_quote_request.quote_status_id','quote_status.id')
                ->leftjoin('payment_status','travel_quote_request.payment_status_id','payment_status.id')
                ->leftjoin('customer','travel_quote_request.customer_id','customer.id')
                ->leftjoin('region','travel_quote_request.region_cover_for_id','region.id')
                ->leftjoin('travel_cover_for','travel_quote_request.travel_cover_for_id','travel_cover_for.id')
                ->leftjoin('nationality','travel_quote_request.nationality_id','nationality.id')
                ->where('travel_quote_request.id', $quote_request_id)
                ->select('travel_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name'
                , 'region.text as region_cover_text', 'travel_cover_for.text as cover_for_text', 'nationality.text as nationality_text')->first();
            }
            else if($quote_type_code == "Life") {
                $quote_request = DB::table('life_quote_request')
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
                ->where('life_quote_request.id', $quote_request_id)
                ->select('life_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name'
                , 'life_insurance_purpose.text as purpose_text', 'life_children.text as children_text', 'marital_status.text as marital_text'
                , 'life_insurance_tenure.text as tenure_text', 'life_number_of_year.text as number_of_year_text'
                , 'currency_type.text as currency_text', 'nationality.text as nationality_text')->first();
            }
            else if($quote_type_code == "Bike") {
                $quote_request = DB::table('bike_quote_request')
                ->leftjoin('quote_status','bike_quote_request.quote_status_id','quote_status.id')
                ->leftjoin('payment_status','bike_quote_request.payment_status_id','payment_status.id')
                ->leftjoin('customer','bike_quote_request.customer_id','customer.id')
                ->leftjoin('nationality','bike_quote_request.nationality_id','nationality.id')
                ->leftjoin('uae_license_held_for','bike_quote_request.uae_license_held_for_id','uae_license_held_for.id')
                ->where('bike_quote_request.id', $quote_request_id)
                ->select('bike_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name'
                , 'nationality.text as nationality_text', 'uae_license_held_for.text as uae_license_text')->first();
            }
            else if($quote_type_code == "Yacht") {
                $quote_request = DB::table('yacht_quote_request')
                ->leftjoin('quote_status','yacht_quote_request.quote_status_id','quote_status.id')
                ->leftjoin('payment_status','yacht_quote_request.payment_status_id','payment_status.id')
                ->leftjoin('customer','yacht_quote_request.customer_id','customer.id')
                ->where('yacht_quote_request.id', $quote_request_id)
                ->select('yacht_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name')->first();
            }
            else if($quote_type_code == "Business") {
                $quote_request = DB::table('business_quote_request')
                ->leftjoin('quote_status','business_quote_request.quote_status_id','quote_status.id')
                ->leftjoin('payment_status','business_quote_request.payment_status_id','payment_status.id')
                ->leftjoin('customer','business_quote_request.customer_id','customer.id')
                ->leftjoin('business_type_of_insurance','business_quote_request.business_type_of_insurance_id','business_type_of_insurance.id')
                ->where('business_quote_request.id', $quote_request_id)
                ->select('business_quote_request.*', 'quote_status.text as quote_status_text', 'payment_status.text as payment_status_text'
                , 'customer.first_name as cust_f_name', 'customer.last_name as cust_l_name'
                , 'business_type_of_insurance.text as business_type_text')->first();
                $business_type_code = DB::table('business_type_of_insurance')->where('id', $quote_request->business_type_of_insurance_id)->value('code');
                $business_cover_type_text = DB::table('business_cover_type')->where('id', $quote_request->business_cover_type_id)->value('text');
                $business_communication_mode_text = DB::table('communication_mode')->where('id', $quote_request->communication_mode_id)->value('text');
            }
            else {
                $quote_request = "";
            }
        }
        else { return redirect()->route('aml.details')->with('message','Not Found!'); }

        if($quote_type_code == "Business") {
            return view("aml.details", compact("quote_type_code","quote_type_text","quote_request","business_type_code"
            ,"business_cover_type_text","business_communication_mode_text","kyc_logs"));
        }
        else {
            return view("aml.details", compact("quote_type_code","quote_type_text","quote_request","kyc_logs"));
        }
    }

    public function quote_status_rejected($quote_type_id, $quote_request_id)
    {
        $quote_type_code = DB::table('quote_type')->where('id', $quote_type_id)->value('code');

        if($quote_type_code && $quote_type_code != "") {
            if($quote_type_code == "Car") { $update_table = "car_quote_request"; }
            if($quote_type_code == "Home") { $update_table = "home_quote_request"; }
            if($quote_type_code == "Health") { $update_table = "health_quote_request"; }
            if($quote_type_code == "Life") { $update_table = "life_quote_request"; }
            if($quote_type_code == "Business") { $update_table = "business_quote_request"; }
            if($quote_type_code == "Bike") { $update_table = "bike_quote_request"; }
            if($quote_type_code == "Yacht") { $update_table = "yacht_quote_request"; }
            if($quote_type_code == "Travel") { $update_table = "travel_quote_request"; }
            $update_quote_status = DB::table($update_table)->where('id', $quote_request_id)->update(['quote_status_id' => 3]);
        }
        else {
            return redirect()->back()->with('message', 'Quote Type not exist!');
        }

        return redirect()->back()->with('success', 'Quote Status is set to Rejected');
    }
    public function quote_status_approved($quote_type_id, $quote_request_id)
    {
        $quote_type_code = DB::table('quote_type')->where('id', $quote_type_id)->value('code');

        if($quote_type_code && $quote_type_code != "") {
            if($quote_type_code == "Car") { $update_table = "car_quote_request"; }
            if($quote_type_code == "Home") { $update_table = "home_quote_request"; }
            if($quote_type_code == "Health") { $update_table = "health_quote_request"; }
            if($quote_type_code == "Life") { $update_table = "life_quote_request"; }
            if($quote_type_code == "Business") { $update_table = "business_quote_request"; }
            if($quote_type_code == "Bike") { $update_table = "bike_quote_request"; }
            if($quote_type_code == "Yacht") { $update_table = "yacht_quote_request"; }
            if($quote_type_code == "Travel") { $update_table = "travel_quote_request"; }
            $update_quote_status = DB::table($update_table)->where('id', $quote_request_id)->update(['quote_status_id' => 1]);
        }
        else {
            return redirect()->back()->with('message', 'Quote Type not exist!');
        }

        return redirect()->back()->with('success', 'Quote Status is set to Approved');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AML  $aml
     * @return \Illuminate\Http\Response
     */
    public function edit(AML $aml)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AML  $aml
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AML $aml)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AML  $aml
     * @return \Illuminate\Http\Response
     */
    public function destroy(AML $aml)
    {
        //
    }
}
