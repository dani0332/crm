<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Nationality;
use App\Services\CustomerUploadService;
use App\Services\CustomerWEGenerateUrlService;
use App\Services\TransAppService;
use App\Traits\GenericQueriesAllLobs;
use Config;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    use GenericQueriesAllLobs;

    private $customerUploadFileService;
    private $transAppService;
    private $customerWeEmailGenerateUrlService;

    public function __construct(CustomerUploadService $customerUploadFileService, TransAppService $transAppService, CustomerWEGenerateUrlService $customerWeEmailGenerateUrlService)
    {
        $this->customerUploadFileService = $customerUploadFileService;
        $this->transAppService = $transAppService;
        $this->customerWeEmailGenerateUrlService = $customerWeEmailGenerateUrlService;
        $this->middleware('permission:customers-list', ['only' => ['index', 'store']]);
        $this->middleware('permission:customers-edit', ['only' => ['edit', 'update']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = [];
            if (isset($request->searchtype) && ! empty($request->searchtype)
            && isset($request->searchfield) && ! empty($request->searchfield)) {
                $data = Customer::select('*')->orderBy('created_at', 'desc');
                $data->where($request->searchtype, $request->searchfield);
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return view('customers.actions', compact('row'))->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('customers.view');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Customer  $carquote
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function edit(Customer $customer)
    {
        $nationalities = Nationality::all();

        return view('customers.edit', compact('customer', 'nationalities'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Customer $customer)
    {
        $this->validate($request, [
            'first_name' => 'required|max:120',
            'last_name' => 'required|max:120',
            'email' => 'required|email',

        ]);
        $existingCustomer = $customer;
        $sendWelcomeEmail = (! $existingCustomer->has_alfred_access || ! $existingCustomer->has_reward_access) && ($request->has_alfred_access && $request->has_reward_access) ? true : false;

        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->mobile_no = $request->mobile_no;
        $customer->lang = $request->lang;
        $customer->gender = $request->gender;
        $customer->dob = $request->dob;
        $customer->nationality_id = $request->nationality_id;
        $customer->has_alfred_access = $request->has_alfred_access == 'on' ? 1 : 0;
        $customer->has_reward_access = $request->has_reward_access == 'on' ? 1 : 0;
        $customer->save();

        if ($sendWelcomeEmail && Config::get('constants.ENABLE_TRANSAPP_WE') == '1' && ! $customer->is_we_sent) {
            $WEGenerateUrlResponse = $this->customerWeEmailGenerateUrlService->getCustomerWeUrl();

            if (gettype($WEGenerateUrlResponse) == 'string') {
                $this->transAppService->sendWelcomeEmail($customer->id, $WEGenerateUrlResponse, $tag = 'customer-myalfred-we');
            }
            $customer->is_we_sent = true;
            $customer->save();
        }

        return redirect('customer/'.$customer->id)->with('success', 'Customer has been Updated');
    }

    /**
     * Store a newly uploaded customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response
     */
    public function processCustomerCSV(Request $request)
    {
        $this->validate($request, [
            'file_name' => 'required|mimetypes:text/csv,text/plain,application/csv,text/comma-separated-values,text/anytext,application/octet-stream,application/txt,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet|max:2048',
            'cdb_id' => 'required',
            'myalfred_expiry_date' => 'required',
        ]);

        $customerUploadID = $this->customerUploadFileService->customerUploadRecordsCreate($request);

        if ($customerUploadID == 0) {
            return redirect('customer-upload')->with('message', 'CDB Id : '.$request->cdb_id." doesn't exists in system.")->withInput();
        }

        return redirect('customer-upload')->with('success', 'Upload customers records has been stored');
    }

    public function uploadCustomers()
    {
        return view('customers.upload');
    }

    public function deleteAdditionalContact(Request $request)
    {
        dd('deleteAdditionalContact: ', $request->all());
    }

    public function makeAdditionalContactPrimary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quote_id' => 'required',
            'quote_type' => 'required',
            'key' => 'required',
            'value' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['error'=>[
                'message' => $validator->errors(),
            ]]);
        }
        $quoteObject = $this->getQuoteObject($request->quote_type, $request->quote_id);
        if ($request->key == 'email') {
            $quoteObject->email = $request->value;
            if ($quoteObject->customer) {
                $quoteObject->customer->update(['email' => $request->value]);
            }
        } elseif ($request->key == 'mobile_no') {
            $quoteObject->mobile_no = $request->value;
            if ($quoteObject->customer) {
                $quoteObject->customer->update(['mobile_no' => $request->value]);
            }
        }
        $quoteObject->save();

        return response()->json(['data'=>[
            'message' => 'success',
        ]]);
    }
}
