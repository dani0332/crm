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
use App\Models\User;
use App\Services\CustomerService;
use DB;
use App\Services\TransAppService;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private $transactionService;
    private $customerService;
    public function __construct(TransAppService $service, CustomerService $cusService)
    {
        $this->transactionService = $service;
        $this->customerService = $cusService;
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
        $transactors = User::select('users.*')
        ->leftjoin('model_has_roles','users.id','model_has_roles.model_id')
        ->leftjoin('roles','roles.id','model_has_roles.role_id')
        ->whereIn('roles.name', ['TRANSAPP_ADVISOR', 'TRANSAPP_APPROVER', 'TRANSAPP_ADMIN'])->orderBy('roles.name', 'asc')->get();

        $handlers = User::select('users.*')
        ->leftjoin('model_has_roles','users.id','model_has_roles.model_id')
        ->leftjoin('roles','roles.id','model_has_roles.role_id')
        ->whereIn('roles.name', ['TRANSAPP_ADVISOR', 'TRANSAPP_APPROVER', 'TRANSAPP_ADMIN'])->orderBy('roles.name', 'asc')->get();

        $insurance_companies = InsuranceCompany::where('is_active', '=', 1)->where('is_deleted', 0)->orderBy('created_at', 'desc')->get();
        $payment_modes = PaymentMode::where('is_active', '=', 1)->where('is_deleted', 0)->orderBy('created_at', 'desc')->get();
        $reasons = Reason::where('is_active', '=', 1)->where('is_deleted', 0)->orderBy('created_at', 'desc')->get();

        if ($request->ajax()) {

            $data = Transaction::select('transactions.*', 'statuses.name as status', 'customer.email as customer_email', 'insurance_companies.name as insurance',
            'handlers.name as handler_name', 'creaters.name as created_by_name', 'payment_modes.name as payment_mode', DB::raw('CONCAT(customer.first_name, " ", customer.last_name) AS customer_name'))
            ->leftjoin('customer', 'customer.id', 'transactions.customer_id')
            ->leftjoin('insurance_companies', 'insurance_companies.id', 'transactions.insurance_company_id')
            ->leftjoin('users as handlers', 'transactions.assigned_to_id','handlers.id')
            ->leftjoin('users as creaters', 'transactions.created_by_id','creaters.id')
            ->leftjoin('payment_modes', 'payment_modes.id', 'transactions.payment_mode_id')
            ->leftjoin('statuses', 'statuses.id', 'transactions.status_id')->orderBy('transactions.created_at','desc')
            ->where('transactions.is_deleted', 0);

            if(Auth::user()->hasRole('TRANSAPP_ADVISOR') || Auth::user()->hasRole('TRANSAPP_APPROVER')) {
                $data->where('transactions.assigned_to_id', Auth::user()->id);
            }

            if (isset($request->transapp_start_date) && !empty($request->transapp_start_date)
            && isset($request->transapp_stop_date) && !empty($request->transapp_stop_date)) {
                $data->whereBetween('transactions.created_at', [\Carbon\Carbon::parse($request->transapp_start_date)->format('Y-m-d')." 00:00:00", \Carbon\Carbon::parse($request->transapp_stop_date)->format('Y-m-d')." 23:59:59"]);
            }

            if(isset($request->transactor) && !empty($request->transactor)) {
                $data->where('transactions.created_by_id', $request->transactor);
            }
            if(isset($request->handler) && !empty($request->handler)) {
                $data->where('transactions.assigned_to_id', $request->handler);
            }
            if(isset($request->insurance_company) && !empty($request->insurance_company)) {
                $data->where('transactions.insurance_company_id', $request->insurance_company);
            }
            if(isset($request->reason) && !empty($request->reason)) {
                $data->where('transactions.reason_id', $request->reason);
            }
            if(isset($request->payment_mode) && !empty($request->payment_mode)) {
                $data->where('transactions.payment_mode_id', $request->payment_mode);
            }

            return Datatables::of($data)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                return view('transaction.actions', compact('row'))->render();
            })
            ->rawColumns(['action'])
            ->make(true);
        }
        return view('transaction.view',compact('transactors','handlers','insurance_companies','payment_modes','reasons'));
        //return view('transaction.view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $handlers = User::select('users.*')
        ->leftjoin('model_has_roles','users.id','model_has_roles.model_id')
        ->leftjoin('roles','roles.id','model_has_roles.role_id')
        ->whereIn('roles.name', ['TRANSAPP_ADVISOR', 'TRANSAPP_APPROVER', 'TRANSAPP_ADMIN'])->orderBy('roles.name', 'asc')->get();
        $insurancecompanies = InsuranceCompany::where('is_active', '=', 1)->where('is_deleted', 0)->orderBy('created_at', 'desc')->get();
        $paymentmodes = PaymentMode::where('is_active', '=', 1)->where('is_deleted', 0)->orderBy('created_at', 'desc')->get();
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
            'first_name' => 'required|max:150',
            'last_name' => 'required|max:150',
            'email' => 'required|email',
            'assigned_to_id' => 'required',
            'paymentmode' => 'required',
            'amount_paid' => "required|max:12|regex:/^\d*(\.\d{1,2})?$/",
            'risk_detail' => 'required|max:2000',
        ]);


        $approval_code = $this->transactionService->createTransaction($request);
        return redirect("transapp/home")->with('success', 'Transaction added successfully, Approval code is '.$approval_code);
    }

    /**
     * Display the specified resource.
     *
     * @param  Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
        if(Auth::user()->hasRole('TRANSAPP_ADVISOR') || Auth::user()->hasRole('TRANSAPP_APPROVER')) {
            if(Auth::user()->id != $transaction->assigned_to_id) {
                return redirect()->route('transaction.index')->with('message','Access Forbidden');
            }
        }

        $transaction = Transaction::select('transactions.*', 'insurance_companies.name as insurance',
        'handlers.name as handler_name', 'creaters.name as created_by_name', 'payment_modes.name as payment_mode')
        ->leftjoin('insurance_companies', 'insurance_companies.id', 'transactions.insurance_company_id')
        ->leftjoin('users as handlers', 'transactions.assigned_to_id','handlers.id')
        ->leftjoin('users as creaters', 'transactions.created_by_id','creaters.id')
        ->leftjoin('payment_modes', 'payment_modes.id', 'transactions.payment_mode_id')
        ->where('transactions.id', $transaction->id)->where('transactions.is_deleted', 0)->first();

        if(Auth::user()->hasRole('TRANSAPP_ADVISOR') || Auth::user()->hasRole('TRANSAPP_APPROVER')) {
            $transaction->where('transactions.assigned_to_id', Auth::user()->id);
        }
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
        if(Auth::user()->hasRole('TRANSAPP_ADVISOR') || Auth::user()->hasRole('TRANSAPP_APPROVER')) {
            if(Auth::user()->id != $transaction->assigned_to_id) {
                return redirect()->route('transaction.index')->with('message','Access Forbidden');
            }
        }

        $insurancecompanies = InsuranceCompany::where('is_active', '=', 1)->where('is_deleted', 0)->orderBy('created_at', 'desc')->get();
        $paymentmodes = PaymentMode::where('is_active', '=', 1)->where('is_deleted', 0)->orderBy('created_at', 'desc')->get();
        $handlers = User::select('users.*')
        ->leftjoin('model_has_roles','users.id','model_has_roles.model_id')
        ->leftjoin('roles','roles.id','model_has_roles.role_id')
        ->whereIn('roles.name', ['TRANSAPP_ADVISOR', 'TRANSAPP_APPROVER', 'TRANSAPP_ADMIN'])->orderBy('roles.name', 'asc')->get();
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
            'first_name' => 'required|max:150',
            'last_name' => 'required|max:150',
            'email' => 'required|email',
            'assigned_to_id' => 'required',
            'paymentmode' => 'required',
            'amount_paid' => "required|max:12|regex:/^\d*(\.\d{1,2})?$/",
            'risk_detail' => 'required|max:2000',
        ]);

        $transaction->insurance_company_id = $request->insurance_company;
        $customer = $this->customerService->getCustomerByEmail($request->email);
        $transaction->customer_id = $customer->id;
        $transaction->assigned_to_id = $request->assigned_to_id;
        $transaction->payment_mode_id = $request->paymentmode;
        $transaction->risk_details = $request->risk_detail;
        $transaction->amount_paid = $request->amount_paid;
        $transaction->modified_by_id = Auth::user()->id;
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
        $transaction->is_deleted = 1;
        $transaction->save();
        return redirect()->route("transaction.index")->with("message", "Transaction ".$transaction->approval_code." has been deleted");
    }

    public function transectionHome(Request $request)
    {
        $route = 'showtransaction';
        $title = 'Home';
        return view('transaction.re-issue.search', compact('route','title'));
    }

    public function showTransaction(Request $request)
    {
        $this->validate($request, [
            'approval_code' => 'required|max:50',
        ]);

        $transaction = Transaction::select('transactions.*', 'insurance_companies.name as insurance',
        'handlers.name as handler_name', 'creaters.name as created_by_name', 'payment_modes.name as payment_mode')
        ->leftjoin('insurance_companies', 'insurance_companies.id', 'transactions.insurance_company_id')
        ->leftjoin('users as handlers', 'transactions.assigned_to_id','handlers.id')
        ->leftjoin('users as creaters', 'transactions.created_by_id','creaters.id')
        ->leftjoin('payment_modes', 'payment_modes.id', 'transactions.payment_mode_id')
        ->where('approval_code', $request->approval_code)->where('transactions.is_deleted', 0)->where('transactions.is_deleted', 0)->first();

        if(Auth::user()->hasRole('TRANSAPP_ADVISOR') || Auth::user()->hasRole('TRANSAPP_APPROVER')) {
            $transaction->where('transactions.assigned_to_id', Auth::user()->id);
        }

        if(Auth::user()->hasRole('TRANSAPP_ADVISOR') || Auth::user()->hasRole('TRANSAPP_APPROVER')) {
            if(Auth::user()->id != $transaction->assigned_to_id) {
                return redirect('transapp/home')->withErrors([
                    'approval_code' => [__('Access Forbidden'),
                ],
                ]);
            }
        }

        if(empty($transaction)) {
            return redirect('transapp/home')->withErrors([
                    'approval_code' => [__('Approval code '. $request->approval_code .' is invalid'),
                ],
            ]);
        }
        else {
            $customer = $this->customerService->getCustomerById($transaction->customer_id);
            return view('transaction.show',compact('transaction', 'customer'));
        }
    }

    public function cancelAndReIssueTransectionView(Request $request)
    {
        $route = '';
        $title = '';
        if (\Request::route()->getName() == 'cancel_view') {
            $route = 'cancel_transaction_form';
            $title = 'Cancel (without re-issue)';
        } else {
            $route = 're_issue_transaction_form';
            $title = 'Cancel & Re-Issue';
        }
        return view('transaction.re-issue.search', compact('route','title'));
    }

    public function cancelAndReIssueTransectionForm(Request $request)
    {
        $this->validate($request, [
            'approval_code' => 'required',
        ]);

        $trans_assigned_to_id = DB::table('transactions')->where('approval_code', $request->approval_code)->value('assigned_to_id');

        $route = '';
        $title = '';
        if (\Request::route()->getName() == 'cancel_transaction_form') {
            $route = 'cancel';
            $title = 'Cancel (without re-issue)';
        } else {
            $route = 're_issue';
            $title = 'Cancel & Re-Issue';
        }

        if($route == 're_issue') { $route_to = 'reissue_view'; }
        if($route == 'cancel') { $route_to = 'cancel_view'; }

        if(Auth::user()->hasRole('TRANSAPP_ADVISOR') || Auth::user()->hasRole('TRANSAPP_APPROVER')) {
            if(Auth::user()->id != $trans_assigned_to_id) {
                return redirect()->route($route_to)->withErrors([
                    'approval_code' => [__('Access Forbidden'),
                ],
                ]);
            }
        }

        $is_cancelled = DB::table('transactions')->where('approval_code', $request->approval_code)->value('is_cancelled');
        if($is_cancelled == 1) {
            return redirect()->route($route_to)->withErrors([
                    'approval_code' => [__('Policy for Approval code '.$request->approval_code.' is not active'),
                ],
            ]);
        }

        $transaction = Transaction::where('approval_code', $request->approval_code)->where('is_deleted', 0)->get();
        $insurancecompanies = InsuranceCompany::where('is_active', '=', 1)->where('is_deleted', 0)->orderBy('created_at', 'desc')->get();
        $paymentmodes = PaymentMode::where('is_active', '=', 1)->where('is_deleted', 0)->orderBy('created_at', 'desc')->get();
        $reasons = Reason::where('is_active', '=', 1)->where('is_deleted', 0)->orderBy('created_at', 'desc')->get();
        $statuses = Status::where('is_active', '=', 1)->where('is_deleted', 0)->orderBy('created_at', 'desc')->get();
        $handlers = User::select('users.*')
        ->leftjoin('model_has_roles','users.id','model_has_roles.model_id')
        ->leftjoin('roles','roles.id','model_has_roles.role_id')
        ->whereIn('roles.name', ['TRANSAPP_ADVISOR', 'TRANSAPP_APPROVER', 'TRANSAPP_ADMIN'])->orderBy('roles.name', 'asc')->get();

        if (count($transaction) > 0) {
            $transaction = $transaction[0];
            $customer = $this->customerService->getCustomerById($transaction->customer_id);
            return view('transaction.re-issue.form', compact('customer','title','route', 'statuses', 'reasons', 'transaction', 'insurancecompanies', 'handlers', 'paymentmodes'));
        } else {
            if($route == 're_issue') { $route_to = 'reissue_view'; }
            if($route == 'cancel') { $route_to = 'cancel_view'; }
            return redirect()->route($route_to)->withErrors([
                    'approval_code' => [__('Approval code '.$request->approval_code.' is invalid '),
                ],
            ]);
        }
    }

    public function cancelAndReIssueTransection(Request $request)
    {
        $this->validate($request, [
            'insurance_company' => 'required',
            'assigned_to_id' => 'required',
            'paymentmode' => 'required',
            'amount_paid' => "required|max:12|regex:/^\d*(\.\d{1,2})?$/",
            'risk_detail' => 'required|max:2000',
            'reason' => 'required',
        ]);

        $is_cancelled = \Request::route()->getName() == 'cancel' ? true : false;
        $status_id = DB::table('statuses')->where('name', 'Inactive')->value('id');

        $previous_transaction = Transaction::where('approval_code', $request->approval_code)->first();
        $previous_transaction->is_cancelled=true;
        $previous_transaction->status_id = $status_id;
        $previous_transaction->save();
        $transaction = new Transaction;
        $transaction->insurance_company_id = $request->insurance_company;
        $customer = $this->customerService->getCustomerByEmail($request->email);
        $transaction->customer_id = $customer->first()->id;
        $transaction->assigned_to_id = $request->assigned_to_id;
        $transaction->payment_mode_id = $request->paymentmode;
        $transaction->risk_details = $request->risk_detail;
        $transaction->amount_paid = $request->amount_paid;
        $transaction->reason_id = $request->reason;
        $transaction->comments = $request->comments;
        $transaction->created_by_id = Auth::user()->id;
        $transaction->modified_by_id = Auth::user()->id;
        $transaction->prev_approval_code = $previous_transaction->approval_code;
        if($is_cancelled) {
            $transaction->is_cancelled=true;
        }
        $transaction->prev_transaction_date = $previous_transaction->created_at;
        if($transaction->save()) {
            $approval_code = $is_cancelled ? generate_code('C') : generate_code('CR');
            Transaction::where('id',$transaction->id)->update(['approval_code'=>$approval_code.$transaction->id]);
        }
        $approval_code = DB::table('transactions')->where('id', $transaction->id)->value('approval_code');
        return redirect("transapp/home")->with('success', 'Transaction added successfully, new Approval code is '.$approval_code);
    }
}
