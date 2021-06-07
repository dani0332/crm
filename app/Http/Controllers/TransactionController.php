<?php

namespace App\Http\Controllers;

use App\Models\Handler;
use App\Models\InsuranceCompany;
use App\Models\PaymentMode;
use App\Models\Reason;
use App\Models\Status;
use App\Models\Transaction;
use Auth;
use DataTables;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */

    public function __construct()
    {

        $this->middleware('permission:transapp-list|transapp-create|transapp-edit|transapp-delete', ['only' => ['index', 'store']]);

        $this->middleware('permission:transapp-create', ['only' => ['create', 'store']]);

        $this->middleware('permission:transapp-edit', ['only' => ['edit', 'update']]);

        $this->middleware('permission:transapp-delete', ['only' => ['destroy']]);
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
                select('transactions.*', 'statuses.name as status', 'insurance_companies.name as insurance', 'handlers.name as handler', 'handlers.name as handler', 'payment_modes.name as payment_mode')
                ->leftjoin('insurance_companies', 'insurance_companies.id', 'transactions.insurance_company_id')
                ->leftjoin('handlers', 'handlers.id', 'transactions.handler_id')
                ->leftjoin('payment_modes', 'payment_modes.id', 'transactions.payment_mode_id')
                ->leftjoin('statuses', 'statuses.id', 'transactions.status_id');
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
        return view('transaction.add', compact('insurancecompanies', 'handlers', 'paymentmodes'));
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
            'insurance_company' => 'required',
            'customer_name' => 'required',
            'handler' => 'required',
            'paymentmode' => 'required',
            'amount_paid' => 'required',
            'risk_detail' => 'required',
        ]);

        $transaction = new Transaction;
        $transaction->insurance_company_id = $request->insurance_company;
        $transaction->customer_name = $request->customer_name;
        $transaction->handler_id = $request->handler;
        $transaction->payment_mode_id = $request->paymentmode;
        $transaction->risk_details = $request->risk_detail;
        $transaction->amount_paid = $request->amount_paid;
        $transaction->created_by = Auth::user()->email;
        $transaction->save();
        if($transaction->save()){
            Transaction::where('id',$transaction->id)->update(['approval_code'=>generate_code('T').$transaction->id]);
        }
        if (isset($request->return_to_view)) {
            return redirect("transapp/transaction");
        }

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
        $transaction = Transaction::select('transactions.*', 'insurance_companies.name as insurance', 'handlers.name as handler', 'handlers.name as handler', 'payment_modes.name as payment_mode')
            ->leftjoin('insurance_companies', 'insurance_companies.id', 'transactions.insurance_company_id')
            ->leftjoin('handlers', 'handlers.id', 'transactions.handler_id')
            ->leftjoin('payment_modes', 'payment_modes.id', 'transactions.payment_mode_id')
            ->where('transactions.id', $transaction->id)->first();
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
        return view('transaction.edit', compact('transaction', 'insurancecompanies', 'handlers', 'paymentmodes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Transaction  $transaction
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, Transaction $transaction)
    {

        $this->validate($request, [
            'insurance_company' => 'required',
            'customer_name' => 'required',
            'handler' => 'required',
            'paymentmode' => 'required',
            'amount_paid' => 'required',
            'risk_detail' => 'required',
        ]);
        $transaction->insurance_company_id = $request->insurance_company;
        $transaction->customer_name = $request->customer_name;
        $transaction->handler_id = $request->handler;
        $transaction->payment_mode_id = $request->paymentmode;
        $transaction->risk_details = $request->risk_detail;
        $transaction->amount_paid = $request->amount_paid;
        $transaction->updated_by = Auth::user()->email;
        $transaction->save();
        if (isset($request->return_to_view)) {
            return redirect("transapp/transaction");
        }

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

    public function transectionHome(Request $request)
    {
        $route = 'showtransaction';
        $title = 'Home';
        return view('transaction.re-issue.search', compact('route','title'));
    }
    public function showTransaction(Request $request){
        $transaction = Transaction::select('transactions.*', 'insurance_companies.name as insurance', 'handlers.name as handler', 'handlers.name as handler', 'payment_modes.name as payment_mode')
            ->leftjoin('insurance_companies', 'insurance_companies.id', 'transactions.insurance_company_id')
            ->leftjoin('handlers', 'handlers.id', 'transactions.handler_id')
            ->leftjoin('payment_modes', 'payment_modes.id', 'transactions.payment_mode_id')
            ->where('approval_code', $request->approval_code)->first();

        return view('transaction.show',compact('transaction'));
    }

    public function cancelAndReIssueTransectionView(Request $request)
    {
        $route = '';
        $title = '';
        if (\Request::route()->getName() == 'cancel_view') {
            $route = 'cancel_transaction_form';
            $title = 'Cancel';
        } else {
            $route = 're_issue_transaction_form';
            $title = 'Re Issue';
        }
        return view('transaction.re-issue.search', compact('route','title'));
    }


    public function cancelAndReIssueTransectionForm(Request $request)
    {
        $this->validate($request, [
            'approval_code' => 'required',
        ]);
        $route = '';
        $title = '';
        if (\Request::route()->getName() == 'cancel_transaction_form') {
            $route = 'cancel';
            $title = 'Cancel';
        } else {
            $route = 're_issue';
            $title = 'Re Issue';
        }
        $insurancecompanies = InsuranceCompany::all();
        $handlers = Handler::all();
        $paymentmodes = PaymentMode::all();
        $transaction = Transaction::where('approval_code', $request->approval_code)->get();
        $reasons = Reason::all();
        $statuses = Status::all();
        if (count($transaction) > 0) {
            $transaction = $transaction[0];
            return view('transaction.re-issue.form', compact('title','route', 'statuses', 'reasons', 'transaction', 'insurancecompanies', 'handlers', 'paymentmodes'));
        } else {
            return redirect()->route('reissue_view')
                ->withErrors([
                    'approval_code' => [
                        __('Approval code ' . $request->approval_code . ' not found'),
                    ],
                ]);
        }
    }

    public function cancelAndReIssueTransection(Request $request)
    {
        $this->validate($request, [
            'insurance_company' => 'required',
            'customer_name' => 'required',
            'handler' => 'required',
            'paymentmode' => 'required',
            'amount_paid' => 'required',
            'risk_detail' => 'required',
            'status' => 'required',
            'reason' => 'required',
        ]);
        $is_cancelled = \Request::route()->getName() == 'cancel' ? true : false;

        $previous_transaction = Transaction::where('approval_code', $request->approval_code)->first();
        $previous_transaction->is_cancelled=true;
        $previous_transaction->save();
        $transaction = new Transaction;
        $transaction->insurance_company_id = $request->insurance_company;
        $transaction->customer_name = $request->customer_name;
        $transaction->handler_id = $request->handler;
        $transaction->payment_mode_id = $request->paymentmode;
        $transaction->risk_details = $request->risk_detail;
        $transaction->amount_paid = $request->amount_paid;
        $transaction->created_by = Auth::user()->email;
        $transaction->reason_id = $request->reason;
        $transaction->status_id = $request->status;
        $transaction->prev_approval_code = $previous_transaction->approval_code;
        if($is_cancelled)
            $transaction->is_cancelled=true;
        $transaction->prev_transaction_date = $previous_transaction->created_at;
        if($transaction->save())
        {
            $approval_code = $is_cancelled ? generate_code('C') : generate_code('CR');
            Transaction::where('id',$transaction->id)->update(['approval_code'=>$approval_code.$transaction->id]);
        }

        return redirect("transapp/transaction");
    }

}
