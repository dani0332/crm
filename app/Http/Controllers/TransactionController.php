<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use DataTables;
use Auth;
use App\Models\InsuranceCompany;
use App\Models\Handler;
use App\Models\PaymentMode;

class TransactionController extends Controller
{
    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function __construct()
    {

        $this->middleware('permission:transaction-list|transaction-create|transaction-edit|transaction-delete', ['only' => ['index', 'store']]);

        $this->middleware('permission:transaction-create', ['only' => ['create', 'store']]);

        $this->middleware('permission:transaction-edit', ['only' => ['edit', 'update']]);

        $this->middleware('permission:transaction-delete', ['only' => ['destroy']]);
    }

    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Transaction::
            select('transactions.*','insurance_companies.name as insurance','handlers.name as handler','handlers.name as handler','payment_modes.name as payment_mode')
            ->leftjoin('insurance_companies','insurance_companies.id','transactions.insurance_company_id')
            ->leftjoin('handlers','handlers.id','transactions.handler_id')
            ->leftjoin('payment_modes','payment_modes.id','transactions.payment_mode_id');
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('transaction.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('transaction.view');
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
        return view('transaction.add',compact('insurancecompanies','handlers','paymentmodes'));
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

        $transaction = new Transaction;
        $transaction->approval_code = substr(md5(uniqid(rand(1,6))), 0, 8);
        $transaction->insurance_company_id = $request->insurance_company;
        $transaction->customer_name = $request->customer_name;
        $transaction->handler_id = $request->handler;
        $transaction->payment_mode_id = $request->paymentmode;
        $transaction->risk_details = $request->risk_detail;
        $transaction->amount_paid = $request->amount_paid;
        $transaction->created_by = Auth::user()->email;
        $transaction->save();
        if(isset($request->return_to_view))
            return redirect("transapp/transaction");

        return redirect()->back()
        ->with('success', 'Transaction created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  Transaction  $transaction
     * @return \Illuminate\Http\Response
     */

    public function show(Transaction $transaction)
    {
        $transaction = Transaction::select('transactions.*','insurance_companies.name as insurance','handlers.name as handler','handlers.name as handler','payment_modes.name as payment_mode')
            ->leftjoin('insurance_companies','insurance_companies.id','transactions.insurance_company_id')
            ->leftjoin('handlers','handlers.id','transactions.handler_id')
            ->leftjoin('payment_modes','payment_modes.id','transactions.payment_mode_id')
            ->where('transactions.id',$transaction->id)->first();
        return view('transaction.show', compact('transaction'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Transaction  $transaction
     * @return \Illuminate\Http\Response
     */

    public function edit(Transaction $transaction)
    {
        $insurancecompanies = InsuranceCompany::all();
        $handlers = Handler::all();
        $paymentmodes = PaymentMode::all();
        return view('transaction.edit', compact('transaction','insurancecompanies','handlers','paymentmodes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Transaction  $transaction
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request,Transaction $transaction)
    {

        $this->validate($request, [
            'insurance_company'=> 'required',
            'customer_name'=> 'required',
            'handler'=> 'required',
            'paymentmode'=> 'required',
            'amount_paid' =>'required',
            'risk_detail'=> 'required',
        ]);
        $transaction->insurance_company_id = $request->insurance_company;
        $transaction->customer_name = $request->customer_name;
        $transaction->handler_id = $request->handler;
        $transaction->payment_mode_id = $request->paymentmode;
        $transaction->risk_details = $request->risk_detail;
        $transaction->amount_paid = $request->amount_paid;
        $transaction->updated_by = Auth::user()->email;
        $transaction->save();
        if(isset($request->return_to_view))
        return redirect("transapp/transaction");


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Transaction  $transaction
     * @return \Illuminate\Http\Response
     */

    public function destroy(Transaction $transaction)
    {

        $transaction->delete();
        return redirect()->route('transaction.index')
            ->with('success', 'Transaction deleted successfully');
    }
}
