<?php

namespace App\Http\Controllers;

use App\Models\Transection;
use Illuminate\Http\Request;
use DataTables;
use Auth;
use App\Models\InsuranceCompany;
use App\Models\Handler;
use App\Models\PaymentMode;

class TransectionController extends Controller
{
    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function __construct()
    {

        $this->middleware('permission:transection-list|transection-create|transection-edit|transection-delete', ['only' => ['index', 'store']]);

        $this->middleware('permission:transection-create', ['only' => ['create', 'store']]);

        $this->middleware('permission:transection-edit', ['only' => ['edit', 'update']]);

        $this->middleware('permission:transection-delete', ['only' => ['destroy']]);
    }

    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Transection::
            select('transections.*','insurance_companies.name as insurance','handlers.name as handler','handlers.name as handler','payment_modes.name as payment_mode')
            ->leftjoin('insurance_companies','insurance_companies.id','transections.insurance_company_id')
            ->leftjoin('handlers','handlers.id','transections.handler_id')
            ->leftjoin('payment_modes','payment_modes.id','transections.payment_mode_id');
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('transection.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('transection.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        $insurancecompanies = InsuranceCompany::all();
        $handlers = Handler::all();
        $paymentmodes = PaymentMode::all();
        return view('transection.add',compact('insurancecompanies','handlers','paymentmodes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        $this->validate($request, [
            'insurance_company'=> 'required',
            'customer_name'=> 'required',
            'handler'=> 'required',
            'paymentmode'=> 'required',
            'amount_paid' =>'required',
            'risk_detail'=> 'required',
        ]);

        $transection = new Transection;
        $transection->approval_code = substr(md5(uniqid(rand(1,6))), 0, 8);
        $transection->insurance_company_id = $request->insurance_company;
        $transection->customer_name = $request->customer_name;
        $transection->handler_id = $request->handler;
        $transection->payment_mode_id = $request->paymentmode;
        $transection->risk_details = $request->risk_detail;
        $transection->amount_paid = $request->amount_paid;
        $transection->created_by = Auth::user()->email;
        $transection->save();
        if(isset($request->return_to_view))
            return redirect("transapp/transection");

        return redirect()->back()
        ->with('success', 'Transection created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  Transection  $transection
     * @return \Illuminate\Http\Response
     */

    public function show(Transection $transection)
    {
        $transection = Transection::select('transections.*','insurance_companies.name as insurance','handlers.name as handler','handlers.name as handler','payment_modes.name as payment_mode')
            ->leftjoin('insurance_companies','insurance_companies.id','transections.insurance_company_id')
            ->leftjoin('handlers','handlers.id','transections.handler_id')
            ->leftjoin('payment_modes','payment_modes.id','transections.payment_mode_id')
            ->where('transections.id',$transection->id)->first();
        return view('transection.show', compact('transection'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Transection  $transection
     * @return \Illuminate\Http\Response
     */

    public function edit(Transection $transection)
    {
        $insurancecompanies = InsuranceCompany::all();
        $handlers = Handler::all();
        $paymentmodes = PaymentMode::all();
        return view('transection.edit', compact('transection','insurancecompanies','handlers','paymentmodes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Transection  $transection
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request,Transection $transection)
    {

        $this->validate($request, [
            'insurance_company'=> 'required',
            'customer_name'=> 'required',
            'handler'=> 'required',
            'paymentmode'=> 'required',
            'amount_paid' =>'required',
            'risk_detail'=> 'required',
        ]);
        $transection->insurance_company_id = $request->insurance_company;
        $transection->customer_name = $request->customer_name;
        $transection->handler_id = $request->handler;
        $transection->payment_mode_id = $request->paymentmode;
        $transection->risk_details = $request->risk_detail;
        $transection->amount_paid = $request->amount_paid;
        $transection->updated_by = Auth::user()->email;
        $transection->save();
        if(isset($request->return_to_view))
        return redirect("transapp/transection");


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Transection  $transection
     * @return \Illuminate\Http\Response
     */

    public function destroy(Transection $transection)
    {

        $transection->delete();
        return redirect()->route('transection.index')
            ->with('success', 'Transection deleted successfully');
    }
}
