<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Http\Requests\TransactionRequest;
use App\Models\CarQuote;
use App\Models\Team;
use App\Models\Transaction;
use App\Services\CustomerService;
use App\Services\ReasonService;
use App\Services\TransAppService;
use App\Traits\TeamHierarchyTrait;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use TeamHierarchyTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private $transactionService;

    private $customerService;
    private $reasonService;

    public function __construct(TransAppService $service, CustomerService $cusService, ReasonService $reasService)
    {
        $this->transactionService = $service;
        $this->customerService = $cusService;
        $this->reasonService = $reasService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Transaction $transaction)
    {
        $transactors = $this->transactionService->getTransactors();
        $handlers = $this->transactionService->getHandlers();
        $insuranceCompanies = $this->transactionService->getInsuranceCompanies();
        $paymentModes = $this->transactionService->getPaymentModes();
        $reasons = $this->transactionService->getReasons();
        $isTransappAdmin = $this->transactionService->checkTransappAdmin();
        $isTransappNonAdmin = $this->transactionService->checkTransappNonAdmin();

        $dataTransapp = $transaction::select(
            'transactions.approval_code',
            'transactions.created_at',
            'insurance_companies.name as insurance',
            'transactions.amount_paid',
            DB::raw('CONCAT(customer.first_name, " ", customer.last_name) AS customer_name'),
            'transactions.risk_details',
            'creator.name as created_by_name',
            'handlers.name as handler_name',
            'payment_modes.name as payment_mode',

        )
            ->leftjoin('customer', 'customer.id', 'transactions.customer_id')
            ->leftjoin('insurance_companies', 'insurance_companies.id', 'transactions.insurance_company_id')
            ->leftjoin('users as handlers', 'transactions.assigned_to_id', 'handlers.id')
            ->leftjoin('users as creator', 'transactions.created_by_id', 'creator.id')
            ->leftjoin('payment_modes', 'payment_modes.id', 'transactions.payment_mode_id')->orderBy('transactions.created_at', 'desc')
            ->where('transactions.is_deleted', 0);
        if ($isTransappNonAdmin == '1') {
            $dataTransapp->where('transactions.assigned_to_id', auth()->user()->id);
        }
        if (! empty($request->team_id) && $request->team_id[0] != null) {
            $dataTransapp->leftjoin('user_team', 'handlers.id', 'user_team.user_id');
            $dataTransapp->whereIn('user_team.team_id', $request->team_id);
            $dataTransapp->groupBy('transactions.id');
        }
        if (isset($request->transapp_start_date) && ! empty($request->transapp_start_date)
        && isset($request->transapp_stop_date) && ! empty($request->transapp_stop_date)) {
            $dataTransapp->whereBetween('transactions.created_at', [Carbon::parse($request->transapp_start_date)->format('Y-m-d').' 00:00:00', Carbon::parse($request->transapp_stop_date)->format('Y-m-d').' 23:59:59']);
        } else {
            $dataTransapp->whereBetween('transactions.created_at', [now()->startOfDay(), now()->endOfDay()]);
        }
        if (! empty($request->transapp_approval_code)) {
            $dataTransapp->where('transactions.approval_code', $request->transapp_approval_code)->orWhere('transactions.prev_approval_code', $request->transapp_approval_code);
        }

        if (! empty($request->transapp_customer_email)) {
            $dataTransapp->where('customer.email', $request->transapp_customer_email);
        }

        if (! empty($request->transapp_customer_name)) {
            $dataTransapp->where('customer.first_name', 'like', '%'.$request->transapp_customer_name.'%')->orWhere('customer.last_name', 'like', '%'.$request->transapp_customer_name.'%');
        }

        if (isset($request->transactor) && ! empty($request->transactor)) {
            $dataTransapp->where('transactions.created_by_id', $request->transactor);
        }
        if (isset($request->handler) && ! empty($request->handler)) {
            $dataTransapp->where('transactions.assigned_to_id', $request->handler);
        }
        if (isset($request->insurance_company) && ! empty($request->insurance_company)) {
            $dataTransapp->where('transactions.insurance_company_id', $request->insurance_company);
        }
        if (isset($request->reason) && ! empty($request->reason)) {
            $dataTransapp->where('transactions.reason_id', $request->reason);
        }
        if (isset($request->payment_mode) && ! empty($request->payment_mode)) {
            $dataTransapp->where('transactions.payment_mode_id', $request->payment_mode);
        }

        // $premiumAmount = $dataTransapp->get('amount_paid')->sum('amount_paid');

        $teams = [];
        $teamIds = $this->getUserTeams(auth()->user()->id);
        if (count($teamIds) > 0) {
            $teams = Team::whereIn('id', $teamIds->pluck('id'))
                ->select('name', 'id')
                ->orderBy('name')
                ->where('is_active', 1)
                ->get();
        }
        $isCarManager = auth()->user()->hasAnyRole([RolesEnum::CarManager]);

        return inertia('TransApp/Index', [
            'transactors' => $transactors,
            'handlers' => $handlers,
            'insuranceCompanies' => $insuranceCompanies,
            'paymentModes' => $paymentModes,
            'reasons' => $reasons,
            'isTransappAdmin' => $isTransappAdmin,
            'teams' => $teams,
            'isCarManager' => $isCarManager,
            'data' => $dataTransapp->paginate(15)->withQueryString(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $carQuote = [];
        if ($request->has('carQuote')) {
            $carQuote = CarQuote::with(['insurance_coverage.insurance_company_id', 'advisor_id', 'payment_detail'])->where('id', $request->input('carQuote'))->first();
            if ($carQuote) {
                $carQuote = $carQuote->toArray();
            }
        }

        $handlers = $this->transactionService->getHandlers();
        $insuranceCompanies = $this->transactionService->getInsuranceCompanies();
        $typeOfInsurances = $this->transactionService->getTypeOfInsurances();
        $paymentModes = $this->transactionService->getPaymentModes();

        return view('transaction.add', compact('insuranceCompanies', 'handlers', 'paymentModes', 'typeOfInsurances', 'carQuote'));
    }

    /**
     * Store a newly created resource in storage.
     *
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
            'amount_paid' => 'required|numeric|regex:/^\d{1,10}(\.\d{1,2})?$/',
            'risk_detail' => 'required|max:2000',
        ]);

        $approvalCode = $this->transactionService->createTransaction($request);

        if (gettype($approvalCode) == 'string') {
            return redirect('transapp/home')->with('success', 'Transaction added successfully, Approval code is '.$approvalCode);
        } else {
            return back()->withInput()->with('message', 'myAlfred signup link creation failed. Please try recreating Transapp.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
        $isTransappNonAdmin = $this->transactionService->checkTransappNonAdmin();

        if ($isTransappNonAdmin == '1' && Auth::user()->id != $transaction->assigned_to_id) {
            return redirect()->route('transaction.index')->with('message', 'Access Forbidden');
        }

        $transaction = $this->transactionService->getTransactionDetailById($transaction->id);

        return view('transaction.show', compact('transaction'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Transaction $transaction)
    {
        $isTransappNonAdmin = $this->transactionService->checkTransappNonAdmin();
        $insuranceCompanies = $this->transactionService->getInsuranceCompanies();
        $paymentModes = $this->transactionService->getPaymentModes();
        $handlers = $this->transactionService->getHandlers();

        if ($isTransappNonAdmin == '1' && Auth::user()->id != $transaction->assigned_to_id) {
            return redirect()->route('transaction.index')->with('message', 'Access Forbidden');
        }

        return view('transaction.edit', compact('transaction', 'insuranceCompanies', 'handlers', 'paymentmodes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transaction $transaction)
    {
        $customer = $this->customerService->getCustomerByEmail($request->email);

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
        $transaction->customer_id = $customer->id;
        $transaction->assigned_to_id = $request->assigned_to_id;
        $transaction->payment_mode_id = $request->paymentmode;
        $transaction->risk_details = $request->risk_detail;
        $transaction->amount_paid = $request->amount_paid;
        $transaction->modified_by_id = Auth::user()->id;
        $transaction->save();

        if (isset($request->return_to_view)) {
            return redirect('transapp/transaction');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->is_deleted = 1;
        $transaction->save();

        return redirect()->route('transaction.index')->with('message', 'Transaction '.$transaction->approval_code.' has been deleted');
    }

    public function transectionHome(Request $request)
    {
        $route = 'showtransaction';
        $title = 'Home';

        return view('transaction.re-issue.search', compact('route', 'title'));
    }

    public function showTransaction(TransactionRequest $request)
    {
        $isTransappNonAdmin = $this->transactionService->checkTransappNonAdmin();
        $transaction = $this->transactionService->getTransactionDetailByApprovalCode($request->approval_code);

        if ($isTransappNonAdmin == '1' && $transaction && auth()->id() != $transaction->assigned_to_id) {
            return redirect('transapp/home')->withErrors(['approval_code' => [__('Access Forbidden')]]);
        }

        if (empty($transaction)) {
            return redirect('transapp/home')->withErrors([
                'approval_code' => [__('Approval code '.$request->approval_code.' is invalid'),
                ],
            ]);
        } else {
            $customer = $this->customerService->getCustomerById($transaction->customer_id);
            $reason = $this->reasonService->getReasonById($transaction->reason_id)->first();
            $status = '';

            if ($transaction->status_id) {
                if ($transaction->status_id == 2) {
                    $status = 'Active';
                }
                if ($transaction->status_id == 3) {
                    $status = 'In Active';
                }
            }

            return view('transaction.show', compact('transaction', 'customer', 'reason', 'status'));
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

        return view('transaction.re-issue.search', compact('route', 'title'));
    }

    public function cancelAndReIssueTransectionForm(Request $request)
    {
        $this->validate($request, [
            'approval_code' => 'required',
        ]);

        $isTransappNonAdmin = $this->transactionService->checkTransappNonAdmin();
        $transappAssignedToId = $this->transactionService->getTransappAssignedToIdByApprovalCode($request->approval_code);
        $isCancelled = $this->transactionService->getTransappIsCancelledByApprovalCode($request->approval_code);
        $transaction = $this->transactionService->getTransactionByApprovalCode($request->approval_code);
        $insuranceCompanies = $this->transactionService->getInsuranceCompanies();
        $paymentModes = $this->transactionService->getPaymentModes();
        $reasons = $this->transactionService->getReasons();
        $statuses = $this->transactionService->getStatuses();
        $typeOfInsurances = $this->transactionService->getTypeOfInsurances();
        $handlers = $this->transactionService->getHandlers();

        $route = '';
        $title = '';
        if (\Request::route()->getName() == 'cancel_transaction_form') {
            $route = 'cancel';
            $title = 'Cancel (without re-issue)';
        } else {
            $route = 're_issue';
            $title = 'Cancel & Re-Issue';
        }

        if ($route == 're_issue') {
            $route_to = 'reissue_view';
        }
        if ($route == 'cancel') {
            $route_to = 'cancel_view';
        }

        if ($isTransappNonAdmin == '1' && Auth::user()->id != $transappAssignedToId) {
            return redirect()->route($route_to)->withErrors([
                'approval_code' => [__('Access Forbidden')], ]);
        }

        if ($isCancelled == '1') {
            return redirect()->route($route_to)->withErrors([
                'approval_code' => [__('Policy for Approval code '.$request->approval_code.' is not active'),
                ],
            ]);
        }

        if (count($transaction) > 0) {
            $transaction = $transaction[0];
            $customer = $this->customerService->getCustomerById($transaction->customer_id);

            return view('transaction.re-issue.form', compact('customer', 'title', 'route', 'statuses', 'reasons', 'transaction', 'insuranceCompanies', 'handlers', 'paymentModes', 'typeOfInsurances'));
        } else {
            if ($route == 're_issue') {
                $route_to = 'reissue_view';
            }
            if ($route == 'cancel') {
                $route_to = 'cancel_view';
            }

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
            'reason' => 'required',
        ]);

        $is_cancelled = \Request::route()->getName() == 'cancel' ? true : false;
        $inactiveStatusId = $this->transactionService->getStatusId('Inactive');
        $activeStatusId = $this->transactionService->getStatusId('Active');

        $previousTransaction = $this->transactionService->getPreviousTransactionByApprovalCode($request->approval_code);
        $previousTransaction->is_cancelled = true;
        $previousTransaction->status_id = $inactiveStatusId;
        $previousTransaction->save();

        $transaction = new Transaction;
        $transaction->insurance_company_id = $request->insurance_company;
        $customer = $this->customerService->getCustomerByEmail($request->email);
        $transaction->customer_id = $customer->id;
        $transaction->assigned_to_id = $request->assigned_to_id;
        $transaction->payment_mode_id = $request->paymentmode;
        $transaction->risk_details = $request->risk_detail;
        $transaction->amount_paid = $request->amount_paid;
        $transaction->reason_id = $request->reason;
        $transaction->comments = $request->comments;
        $transaction->status_id = $activeStatusId;
        $transaction->created_by_id = Auth::user()->id;
        $transaction->modified_by_id = Auth::user()->id;
        $transaction->prev_approval_code = $previousTransaction->approval_code;

        if ($is_cancelled) {
            $transaction->is_cancelled = true;
        }

        $transaction->prev_transaction_date = $previousTransaction->created_at;

        if ($transaction->save()) {
            $approvalCode = $is_cancelled ? generate_code('C') : generate_code('CR');
            Transaction::where('id', $transaction->id)->update(['approval_code' => $approvalCode.$transaction->id]);
        }

        $approvalCode = $this->transactionService->getTransappApprovalCodeById($transaction->id);

        return redirect('transapp/home')->with('success', 'Transaction added successfully, new Approval code is '.$approvalCode);
    }
}
