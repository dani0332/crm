<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\TypeOfInsurance;
use App\Models\SubTypeOfInsurance;
use App\Models\ClaimsStatus;
use App\Models\CarRepairCoverage;
use App\Models\CarRepairType;
use App\Models\RentACar;
use App\Models\User;
use App\Models\CarMake;
use App\Models\CarModel;
use App\Models\CarPlan;
use App\Models\InsuranceProvider;
use Illuminate\Http\Request;
use Auth;
use DataTables;
use DB;
use Config;
use App\Services\CarQuoteService;
use App\Services\CRUDService;
use App\Services\CarPlanService;

class ClaimController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $carQuoteService;
    protected $crudService;
    protected $carPlanService;

    function __construct(CarQuoteService $carQuoteService, CRUDService $crudService, CarPlanService $carPlanService)
    {
        $this->middleware('permission:claim-list|claim-create|claim-edit|claim-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:claim-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:claim-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:claim-delete', ['only' => ['destroy']]);
        $this->carQuoteService = $carQuoteService;
        $this->crudService = $crudService;
        $this->carPlanService = $carPlanService;
    }

    public function index(Request $request)
    {
        $claimsstatuses = ClaimsStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $typeofinsurances = TypeOfInsurance::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $advisors = User::select('users.*')
            ->leftjoin('model_has_roles', 'users.id', 'model_has_roles.model_id')
            ->leftjoin('roles', 'roles.id', 'model_has_roles.role_id')
            ->whereIn('roles.name', ['CLAIMS_ADVISOR', 'CLAIMS_MANAGER', 'CLAIMS_ADMIN'])->orderBy('roles.name', 'asc')->get();

        if ($request->ajax()) {

            $data = Claim::select(
                'claims.*',
                'type_of_insurances.text as type_of_insurance_text',
                'claims_statuses.text as claims_status_text'
            )
                ->leftjoin('type_of_insurances', 'claims.type_of_insurances_id', 'type_of_insurances.id')
                ->leftjoin('claims_statuses', 'claims.claims_status_id', 'claims_statuses.id')
                ->orderBy('created_at', 'desc');

            if (Auth::user()->hasRole('CLAIMS_ADVISOR')) {
                $data->where('assigned_to_id', Auth::user()->id);
            }

            if (
                isset($request->searchtype) && !empty($request->searchtype)
                && isset($request->searchfield) && !empty($request->searchfield)
            ) {
                if ($request->searchtype == 'id') {
                    $data->where('claims.id', $request->searchfield);
                } else {
                    $data->where($request->searchtype, $request->searchfield);
                }
            }
            if (isset($request->claimstatus) && !empty($request->claimstatus))
                $data->where('claims_status_id', $request->claimstatus);
            if (isset($request->assignedto) && !empty($request->assignedto))
                $data->where('assigned_to_id', $request->assignedto);
            if (isset($request->type_of_insurance) && !empty($request->type_of_insurance))
                $data->where('type_of_insurances_id', $request->type_of_insurance);

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('claim.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('claim.view', compact('claimsstatuses', 'typeofinsurances', 'advisors'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $typeofinsurances = TypeOfInsurance::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $subtypeofinsurances = SubTypeOfInsurance::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $claimsstatuses = ClaimsStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carrepaircoverages = CarRepairCoverage::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carrepairtypes = CarRepairType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $rentacars = RentACar::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $advisors = User::all();
        $carmakes = CarMake::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carmodels = CarModel::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $insuranceproviders = InsuranceProvider::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        return view('claim.add', compact(
            'typeofinsurances',
            'subtypeofinsurances',
            'claimsstatuses',
            'carrepaircoverages',
            'carrepairtypes',
            'rentacars',
            'advisors',
            'carmakes',
            'carmodels',
            'insuranceproviders'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $type_of_insurances_text = DB::table('type_of_insurances')->where('id', $request->type_of_insurances_id)->value('text');

        if ($type_of_insurances_text == 'Business') {
            $this->validate($request, [
                'sub_type_of_insurance_id' => 'required',
            ]);
        }

        $this->validate($request, [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email_address' => 'required|email|max:150',
            'phone_number' => 'required|max:20',
            'insurance_provider_id' => 'required',
            'policy_number' => 'required|max:150',
            'additional_notes' => 'required|max:2000',
            'type_of_insurances_id' => 'required',
            'claims_status_id' => 'required',
        ]);

        $claim = new Claim();
        $claim->first_name = $request->first_name;
        $claim->last_name = $request->last_name;
        $claim->email_address = strtolower(trim(ltrim(rtrim($request->email_address))));
        $claim->phone_number = $request->phone_number;
        $claim->insurance_provider_id = $request->insurance_provider_id;
        $claim->policy_number = $request->policy_number;
        $claim->additional_notes = $request->additional_notes;
        $claim->ticket_number = $request->ticket_number;
        $claim->plate_number = $request->plate_number;
        $claim->standard_excess_payable = $request->standard_excess_payable;
        $claim->liability = $request->liability;
        $claim->workshop = $request->workshop;
        $claim->insurer_reference = $request->insurer_reference;
        $claim->date_of_loss = $request->date_of_loss;
        $claim->claim_amount = $request->claim_amount;
        $claim->type_of_insurances_id = $request->type_of_insurances_id;
        $claim->sub_type_of_insurance_id = $request->sub_type_of_insurance_id;
        $claim->claims_status_id = $request->claims_status_id;
        $claim->car_repair_coverage_id = $request->car_repair_coverage_id;
        $claim->car_repair_type_id = $request->car_repair_type_id;
        $claim->rent_a_car_id = $request->rent_a_car_id;
        $claim->assigned_to_id = $request->assigned_to_id;
        $claim->car_make_id = $request->car_make_id;
        $claim->car_model_id = $request->car_model_id;
        $claim->created_by_id = Auth::user()->id;
        $claim->modified_by_id = Auth::user()->id;
        $claim->is_rent_a_car = $request->is_rent_a_car == 'on' ? 1 : 0;
        $claim->save();

        if (isset($request->return_to_view)) {
            return redirect("claim/claims/" . $claim->id)->with('success', 'Claim has been stored');
        }
        return redirect()->back()->with('success', 'Claim has been stored');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Claim  $claim
     * @return \Illuminate\Http\Response
     */
    public function show(Claim $claim)
    {
        if (Auth::user()->hasRole('CLAIMS_ADVISOR')) {
            if (Auth::user()->id != $claim->assigned_to_id) {
                return redirect()->route('claims.index')->with('message', 'Access Forbidden');
            }
        }
        return view('claim.show', compact('claim'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Claim  $claim
     * @return \Illuminate\Http\Response
     */
    public function edit(Claim $claim)
    {
        if (Auth::user()->hasRole('CLAIMS_ADVISOR')) {
            if (Auth::user()->id != $claim->assigned_to_id) {
                return redirect()->route('claims.index')->with('message', 'Access Forbidden');
            }
        }
        $typeofinsurances = TypeOfInsurance::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $subtypeofinsurances = SubTypeOfInsurance::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $claimsstatuses = ClaimsStatus::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carrepaircoverages = CarRepairCoverage::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carrepairtypes = CarRepairType::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $rentacars = RentACar::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $advisors = User::all();
        $carmakes = CarMake::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $carmodels = CarModel::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        $insuranceproviders = InsuranceProvider::where('is_active', '=', 1)->orderBy('sort_order', 'asc')->get();
        return view('claim.edit', compact(
            'typeofinsurances',
            'claim',
            'subtypeofinsurances',
            'claimsstatuses',
            'carrepaircoverages',
            'carrepairtypes',
            'rentacars',
            'advisors',
            'carmakes',
            'carmodels',
            'insuranceproviders'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Claim  $claim
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Claim $claim)
    {
        $type_of_insurances_text = DB::table('type_of_insurances')->where('id', $request->type_of_insurances_id)->value('text');
        $claim_status_text = DB::table('claims_statuses')->where('id', $request->claims_status_id)->value('text');

        if ($type_of_insurances_text == 'Business') {
            $this->validate($request, [
                'sub_type_of_insurance_id' => 'required',
            ]);
        }
        $this->validate($request, [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email_address' => 'required|email|max:150',
            'phone_number' => 'required|max:20',
            'insurance_provider_id' => 'required',
            'policy_number' => 'required|max:150',
            'additional_notes' => 'required|max:2000',
            'type_of_insurances_id' => 'required',
            'claims_status_id' => 'required',
            'assigned_to_id' => 'required',
        ]);
        $claim->first_name = $request->first_name;
        $claim->last_name = $request->last_name;
        $claim->email_address = strtolower(trim(ltrim(rtrim($request->email_address))));
        $claim->phone_number = $request->phone_number;
        $claim->insurance_provider_id = $request->insurance_provider_id;
        $claim->policy_number = $request->policy_number;
        $claim->additional_notes = $request->additional_notes;
        $claim->ticket_number = $request->ticket_number;
        $claim->plate_number = $request->plate_number;
        $claim->standard_excess_payable = $request->standard_excess_payable;
        $claim->liability = $request->liability;
        $claim->workshop = $request->workshop;
        $claim->insurer_reference = $request->insurer_reference;
        $claim->date_of_loss = $request->date_of_loss;
        $claim->claim_amount = $request->claim_amount;
        $claim->type_of_insurances_id = $request->type_of_insurances_id;
        $claim->sub_type_of_insurance_id = $request->sub_type_of_insurance_id;
        $claim->claims_status_id = $request->claims_status_id;
        $claim->car_repair_coverage_id = $request->car_repair_coverage_id;
        $claim->car_repair_type_id = $request->car_repair_type_id;
        $claim->rent_a_car_id = $request->rent_a_car_id;
        $claim->assigned_to_id = $request->assigned_to_id;
        $claim->car_make_id = $request->car_make_id;
        $claim->car_model_id = $request->car_model_id;
        $claim->modified_by_id = Auth::user()->id;
        $claim->is_rent_a_car = $request->is_rent_a_car == 'on' ? 1 : 0;

        if ($claim_status_text == 'Settled') {

            $name = $request->first_name . ' ' . $request->last_name;
            $phone = $request->phone_number;
            $email = $request->email_address;

            $phonefirstCharacter = substr($phone, 0, 1);
            if ($phonefirstCharacter == "0") {
                $phone_number = ltrim($phone, $phone[0]);
            } else {
                $phone_number = $phone;
            }

            $dayOfWeek = date("l");
            if ($dayOfWeek == "Sunday") {
                $nps_delay = 259200;
            } else if ($dayOfWeek == "Monday") {
                $nps_delay = 259200;
            } else if ($dayOfWeek == "Tuesday") {
                $nps_delay = 432000;
            } else if ($dayOfWeek == "Wednesday") {
                $nps_delay = 432000;
            } else if ($dayOfWeek == "Thursday") {
                $nps_delay = 432000;
            } else if ($dayOfWeek == "Friday") {
                $nps_delay = 345600;
            } else if ($dayOfWeek == "Saturday") {
                $nps_delay = 259200;
            } else {
            }

            $data_d = array();
            $data_d['name'] = "$name";
            $data_d['email'] = "$email";
            $data_d['phone_number'] = "+971" . $phone_number;
            $data_d['delay'] = $nps_delay;
            $data_d['properties'] = array();
            $data_d['properties']['Type of Insurance'] = $type_of_insurances_text;
            $delighted_data = json_encode($data_d);
            $delighted_curl = curl_init();
            curl_setopt_array(
                $delighted_curl,
                array(
                    CURLOPT_URL => "https://api.delighted.com/v1/peopjosle.n",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $delighted_data,
                    CURLOPT_HTTPHEADER => array("Content-Type: application/json", "Authorization: Basic " . base64_encode("sU5Iy4HKzOoIILcRH8P4Rnzax6bbcje7"),),
                )
            );
            $delighted_response = curl_exec($delighted_curl);
            $delighted_decoded_response = json_decode($delighted_response, true);
            if (isset($delighted_decoded_response['errors'])) {

                $emailSys = Config::get('constants.email_sys');
                $refUrl = Request::url();
                $subject = $emailSys . " NPS API ERROR | CLAIMS FORM | " . \Request::url() . " | " . date('d-m-Y H:i:s');
                $curlMesg =
                    'Customer Name: ' . $name .
                    ', Email: ' . $email .
                    ', Phone: ' . $phone .
                    ', Error status: ' . $delighted_decoded_response['status'] .
                    ', Error Message: ' . $delighted_decoded_response['message'] .
                    ', Errors: ' . $delighted_decoded_response['errors'] .
                    ',' . date('d-m-Y H:i:s') . "\n";

                Mail::send(['html' => 'apiemail'], [
                    'refUrl' => $refUrl,
                    'insuranceName' => $type_of_insurances_text,
                    'curlMesg' => $curlMesg,
                ], function ($message) use ($subject) {
                    $message->to(['muhammad.shajiuddin@afia.ae'])->subject($subject);
                    $message->from('alfred@insurancemarket.ae', 'Alfred - Error');
                });
            }
            curl_close($delighted_curl);
        }

        $claim->save();

        if (isset($request->return_to_view)) {
            return redirect("claim/claims/" . $claim->id)->with('success', 'Claim has been updated');
        }
        return redirect()->back()->with('success', 'Claim has been updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Claim  $claim
     * @return \Illuminate\Http\Response
     */
    public function destroy(Claim $claim)
    {
        $claim->claimsAttachments()->delete(); // Delete related attachments
        $claim->delete();
        return redirect()->route('claims.index')->with('message', 'Claim has been deleted');
    }

    public function carPlansByInsuranceProvider(Request $request)
    {
        $insuranceProviderId = $request->insuranceProviderId;
        $quoteUuId = $request->quoteUuId;

        $quotePlans = $this->carQuoteService->getQuotePlans($quoteUuId);

        $quotePlanId = [];
        $listQuotePlans = [];
        if (isset($quotePlans->quotes->plans)) {
            $listQuotePlans = $quotePlans->quotes->plans;
        }

        foreach ($listQuotePlans as $key => $quotePlan) {
            $quotePlanId[] = $quotePlan->id;
        }

        $carPlans = $this->carPlanService->getNonQuotedCarPlans($insuranceProviderId, $quotePlanId);

        return response()->json($carPlans);
    }

    public function carPlanUpdateManualProcess(Request $request)
    {
        $response = $this->carQuoteService->carPlanModify($request);

        $message = '';
        if (gettype($response) == 'integer' && ($response == 200 || $response == 201)) {
            $message = 'Car Plan has been updated';
        } else {
            if (isset($response->message)) {
                $responseMessage = $response->message;
            } else {
                $responseMessage = $response;
            }
            $message = 'Car Plan has not been updated ' . $responseMessage;
        }

        return $message;
    }


    public function getoverdueleads(Request $request)
    {
        return DataTables::of([])
            ->addIndexColumn()
            ->make(true);;
        $allowedTypes = ['car', 'home', 'business', 'health', 'life', 'travel'];
        if (!in_array(strtolower($request->teamName), $allowedTypes)) {
            return DataTables::of([])
                ->addIndexColumn()
                ->make(true);
        } else {
            $gridData = $this->crudService->getOverDueFollowups($request, $request->teamName)->get();
            return DataTables::of($gridData)
                ->addIndexColumn()
                ->make(true);
        }
    }
}
