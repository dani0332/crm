<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStorageEnums;
use App\Enums\GenericRequestEnum;
use App\Jobs\MAWelcomeJob;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use App\Services\BerlinService;
use App\Services\CustomerService;
use App\Services\CustomerUploadService;
use App\Services\LookupService;
use App\Services\TransAppService;
use App\Traits\GenericQueriesAllLobs;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    use GenericQueriesAllLobs;

    private $customerUploadFileService;
    private $transAppService;
    private $berlinService;
    private $customerService;
    private $lookupService;

    public function __construct(
        CustomerUploadService $customerUploadFileService,
        TransAppService $transAppService,
        BerlinService $berlinService,
        CustomerService $customerService,
        LookupService $lookupService
    ) {
        $this->customerUploadFileService = $customerUploadFileService;
        $this->transAppService = $transAppService;
        $this->berlinService = $berlinService;
        $this->customerService = $customerService;
        $this->lookupService = $lookupService;
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

            return DataTables::of($data)->addIndexColumn()->make(true);
        }

        return view('customers.view');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Customer  $carquote
     * @return \Illuminate\Http\Response
     */
    public function show($uuid)
    {
        $customer = $this->customerService->getCustomerByUuid($uuid);
        if (! $customer) {
            return abort(404);
        }

        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        $customer = $this->customerService->getCustomerByUuid($uuid);
        $nationalities = $this->lookupService->getNationalities();

        return view('customers.edit', compact('customer', 'nationalities'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uuid)
    {
        $customer = $this->customerService->getCustomerByUuid($uuid);

        $this->validate($request, [
            'first_name' => 'required|max:120',
            'last_name' => 'required|max:120',
            'email' => 'required|email:rfc,dns|max:150',

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

        if ($sendWelcomeEmail && config('constants.ENABLE_TRANSAPP_WE') == '1' && ! $customer->is_we_sent) {
            MAWelcomeJob::dispatchUnless(
                isMyAlfredCampaignEnabled(getAppStorageValueByKey(ApplicationStorageEnums::EMAIL_CAMPAIGN)),
                $customer->first_name,
                $customer->last_name,
                $customer->email,
                $customer->mobile_no,
                'CUSTOMER_UPDATE',
                'customer-update-myalfred-we'
            );
        }

        return redirect('customer/'.$customer->uuid)->with('success', 'Customer information has been updated.');
    }

    /**
     * Store a newly uploaded customer.
     *
     * @param \Illuminate\Http\Response
     */
    public function processCustomerUpload(Request $request)
    {
        $this->validate($request, [
            'file_name' => 'required|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/excel|max:2048',
            'cdb_id' => 'required',
            'myalfred_expiry_date' => 'required',
        ]);

        $customerUploadId = $this->customerUploadFileService->customerUploadRecordsCreate($request);

        if ($customerUploadId == 0) {
            return redirect('customer-upload')->with('message', 'Ref-ID : '.$request->cdb_id." doesn't exists in system.")->withInput();
        }

        return redirect('customer-upload')->with('success', 'Upload customers records has been stored');
    }

    public function uploadCustomers()
    {
        return view('customers.upload');
    }

    public function deleteAdditionalContact($id, Request $request)
    {
        $deleteCustomerAdditionalContact = CustomerAdditionalContact::find($id);

        if ($deleteCustomerAdditionalContact) {
            Log::info('Customer additional contact deleted. ID: '.$id);
            $deleteCustomerAdditionalContact->delete();
        }

        if (isset($request->isInertia) && $request->isInertia) {
            return redirect()->back();
        }

        return response()->json(['data' => [
            'message' => 'Additional Contact Deleted.',
        ]]);
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
            return response()->json(['error' => [
                'message' => $validator->errors(),
            ]]);
        }
        $quoteObject = $this->getQuoteObject($request->quote_type, $request->quote_id);
        if ($request->key == GenericRequestEnum::EMAIL) {
            if ($quoteObject->customer && ! $this->customerService->getCustomerByEmail($request->value)) {
                Log::info('Customer additional contact primary email updated. Previous Email: '.$quoteObject->email.' New Email: '.$request->value);
                $customerArray = [
                    'first_name' => $quoteObject->first_name,
                    'last_name' => $quoteObject->last_name,
                    'mobile_no' => $quoteObject->mobile_no,
                    'email' => $request->value,
                    'dob' => $quoteObject->dob,
                ];
                $customer = Customer::create($customerArray);
                $customer->update(['code', 'IND-'.$customer->id]);
                $getCustomerAdditionalContact = CustomerAdditionalContact::where('customer_id', $quoteObject->customer->id)
                    ->get();
                foreach ($getCustomerAdditionalContact as $contact) {
                    CustomerAdditionalContact::create([
                        'customer_id' => $customer->id,
                        'key' => $contact->key,
                        'value' => $contact->value,
                    ]);
                }
                $email = trim($quoteObject->email);

                // Check if the email ends with the specified domains
                if (! str_ends_with($email, '@insurancemarket.ae') && ! str_ends_with($email, '@afia.ae')) {
                    $isExist = CustomerAdditionalContact::where('key', 'email')
                        ->where('customer_id', $quoteObject->customer->id)
                        ->where('value', $email)
                        ->exists();

                    if (! $isExist) {
                        CustomerAdditionalContact::create([
                            'customer_id' => $customer->id,
                            'key' => 'email',
                            'value' => $email,
                        ]);
                    }
                }
                $quoteObject->update(['customer_id' => $customer->id, 'email' => $request->value]);

                // REMOVE EMAIL TO MAKE PRIMARY IN ADDITIONAL CONTACT
                $removeEmail = CustomerAdditionalContact::where('customer_id', $quoteObject->customer->id)
                    ->where('value', $quoteObject->email)
                    ->where('key', 'email')
                    ->first();
                if (isset($removeEmail->id)) {
                    DB::table('customer_additional_contact')->where('id', $removeEmail->id)->delete();
                }
                //ADD PRIMARY EMAIL IN ADDITIONAL CONTACT
                $removeAdvisorEmail = CustomerAdditionalContact::where('customer_id', $quoteObject->customer->id)
                    ->where('key', 'email')
                    ->where(function ($query) {
                        $query->where('value', 'like', '%@insurancemarket.ae')
                            ->orWhere('value', 'like', '%@afia.ae');
                    })
                    ->first();
                if (isset($removeAdvisorEmail->id)) {
                    DB::table('customer_additional_contact')->where('id', $removeAdvisorEmail->id)->delete();
                }
            } else {
                $oldCustomer = $this->customerService->getCustomerByEmail($request->value);
                $quoteObject->update(['customer_id' => $oldCustomer->id]);
            }
        } elseif ($request->key == GenericRequestEnum::MOBILE_NO) {
            $quoteObject->mobile_no = $request->value;
            if ($quoteObject->customer) {
                Log::info('Customer additional contact primary mobile_no updated. Previous Mobile_No: '.$quoteObject->mobile_no.' New Mobile_No: '.$request->value);
                $quoteObject->customer->update(['mobile_no' => $request->value]);

                if (isset($request->quote_primary_mobile_no) && isset($request->quote_customer_id)) {
                    CustomerAdditionalContact::create([
                        'customer_id' => $request->quote_customer_id,
                        'key' => 'mobile_no',
                        'value' => trim($request->quote_primary_mobile_no),
                    ]);
                }
            }
            // Add quote_previous_primary_mobile_no in customer_additional_contact
        }
        $quoteObject->save();

        if (isset($request->isInertia) && $request->isInertia) {
            return redirect()->back();
        }

        return response()->json(['data' => [
            'message' => 'Primary Contact Updated',
        ]]);
    }

    public function addAdditionalContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required',
            'additional_contact_type' => 'required',
            'additional_contact_val' => 'required',
        ]);

        if ($request->isInertia == true && $validator->fails()) {
            return redirect()->back()->withErrors($validator->errors());
        }

        if ($validator->fails()) {
            return response()->json(['error' => [
                'message' => $validator->errors(),
            ]]);
        }

        $key = $request->additional_contact_type;
        $value = $request->additional_contact_val;
        $quoteObject = $this->getQuoteObject($request->quote_type, $request->quote_id);
        if ($key == GenericRequestEnum::EMAIL) {
            $isExistEmail = CustomerAdditionalContact::where('customer_id', $request->customer_id)
                ->where('value', $request->additional_contact_val)->where('key', 'email')->first();

            if ($isExistEmail) {
                if ($request->isInertia) {
                    vAbort('Email already Exist. Please try another.');
                }

                return response()->json(['error' => [
                    'message' => 'Email already Exist. Please try another.',
                ]]);
            }
        }

        if ($key == GenericRequestEnum::MOBILE_NO) {
            $isAdditionalMobileNoExist = $this->customerService->checkAdditionalMobileNoExist($quoteObject, $value);

            if ($isAdditionalMobileNoExist) {
                if ($request->isInertia) {
                    vAbort('Mobile Number already in use for a customer. Please try another.');
                }

                return response()->json(['error' => [
                    'message' => 'Mobile Number already in use for a customer. Please try another.',
                ]]);
            }
        }

        Log::info('Customer additional contact id: '.$request->customer_id.' new: '.$key.' value: '.$value);
        CustomerAdditionalContact::create([
            'customer_id' => $request->customer_id,
            'key' => $key,
            'value' => trim($value),
        ]);

        if (isset($request->isInertia) && $request->isInertia) {
            return redirect()->back();
        }

        return response()->json(['data' => [
            'message' => 'Contact added successfully.',
        ]]);
    }

    public function customerAlreadyEmailExistCheck(Request $request)
    {
        return response()->json(['response' => (bool) $this->customerService->getCustomerByEmail($request->value)]);
    }
}
