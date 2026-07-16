<?php

namespace App\Http\Controllers;

use App\Enums\GenericRequestEnum;
use App\Enums\RolesEnum;
use App\Enums\SLAActionTypeEnum;
use App\Http\Requests\CustomerPrimaryEmailRequest;
use App\Http\Requests\DeleteAdditionalContactRequest;
use App\Jobs\ExtendCustomerSubscriptionViaSQS;
use App\Models\BusinessQuote;
use App\Models\Customer;
use App\Models\CustomerAdditionalContact;
use App\Services\BerlinService;
use App\Services\CustomerService;
use App\Services\CustomerUploadService;
use App\Services\Logger\LoggerService;
use App\Services\LookupService;
use App\Services\QuoteDocumentAccessService;
use App\Services\SLA\SLAService;
use App\Services\TransAppService;
use App\Traits\GenericQueriesAllLobs;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    use GenericQueriesAllLobs;

    private $customerUploadFileService;
    private $transAppService;
    private $berlinService;
    private $customerService;
    private $lookupService;
    private $slaService;
    private QuoteDocumentAccessService $quoteDocumentAccessService;

    public function __construct(
        CustomerUploadService $customerUploadFileService,
        TransAppService $transAppService,
        BerlinService $berlinService,
        CustomerService $customerService,
        LookupService $lookupService,
        SLAService $slaService,
        QuoteDocumentAccessService $quoteDocumentAccessService,
    ) {
        $this->customerUploadFileService = $customerUploadFileService;
        $this->transAppService = $transAppService;
        $this->berlinService = $berlinService;
        $this->customerService = $customerService;
        $this->lookupService = $lookupService;
        $this->slaService = $slaService;
        $this->quoteDocumentAccessService = $quoteDocumentAccessService;
        $this->middleware('permission:customers-list', ['only' => ['index', 'store']]);
        $this->middleware('permission:customers-edit', ['only' => ['edit', 'update']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
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
     * @return Response
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
     * @return Response
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
     * @return Response
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
            ExtendCustomerSubscriptionViaSQS::dispatch(
                $customer,
                'CUSTOMER_UPDATE',
                'customer-update-myalfred-we'
            );
        }

        return redirect('customer/'.$customer->uuid)->with('success', 'Customer information has been updated.');
    }

    /**
     * Store a newly uploaded customer.
     *
     * @param Response
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

    public function deleteAdditionalContact($id, DeleteAdditionalContactRequest $request)
    {
        $result = $this->customerService->deleteCustomerAdditionalContacts($id);

        if (isset($request->isInertia) && $request->isInertia) {
            return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
        }

        if ($result['success']) {
            return response()->json(['data' => ['message' => $result['message']]], 200);
        }

        return response()->json(['error' => ['message' => $result['message']]], 404);
    }

    public function makeAdditionalContactPrimary(CustomerPrimaryEmailRequest $request)
    {
        $quoteObject = $this->getQuoteObject($request->quote_type, $request->quote_id);
        if (! $quoteObject) {
            if (isset($request->isInertia) && $request->isInertia) {
                return redirect()->back()->with('error', 'Quote not found.');
            }

            return response()->json(['error' => ['message' => 'Quote not found.']], 404);
        }
        if ($quoteObject instanceof BusinessQuote) {
            if (! $this->checkBusinessQuotePermission($quoteObject->advisor_id)) {
                if (isset($request->isInertia) && $request->isInertia) {
                    return redirect()->back()->with('error', 'You are not authorized to update the primary contact for this quote.');
                }

                return response()->json(['error' => ['message' => 'You are not authorized to update the primary contact for this quote.']], 403);
            }
        }

        $keepExistingPrimaryEmail = isset($request->keep_existing_primary_email) ? $request->keep_existing_primary_email : 1;

        $this->customerService->makeAdditionalContactPrimary($quoteObject, $request->key, $request->value, (bool) $keepExistingPrimaryEmail);
        $this->slaService->meetSLAOnEdit($quoteObject, SLAActionTypeEnum::ADDITIONAL_CONTACTS_PRIMARY_UPDATE);

        if (isset($request->isInertia) && $request->isInertia) {
            return redirect()->back()->with('success', 'Primary Contact Updated');
        }

        return response()->json(['data' => [
            'message' => 'Primary Contact Updated',
        ]]);
    }
    public function checkBusinessQuotePermission($advisorId)
    {
        $user = Auth::user();
        if ($user->hasRole(RolesEnum::Admin) || $user->hasRole(RolesEnum::Engineering)) {
            return true;
        }

        if ($user->hasRole([RolesEnum::CorplineRenewalManager, RolesEnum::CorplineClaimManager, RolesEnum::CorplineManager, RolesEnum::CorplineDeputyManager, RolesEnum::BusinessManager, RolesEnum::BusinessDeputyManager, RolesEnum::GMClaimManager, RolesEnum::GMDeputyManager, RolesEnum::GMManager, RolesEnum::GMRenewalManager, RolesEnum::EBPManager, RolesEnum::EBPDeputyManager])) {
            return true;
        }

        if ($user->hasRole([RolesEnum::CorpLineRenewalAdvisor, RolesEnum::GMRenewalAdvisor, RolesEnum::BusinessAdvisor, RolesEnum::CorpLineAdvisor, RolesEnum::GMAdvisor, RolesEnum::EBPAdvisor])) {
            return $user->id == $advisorId ? true : false;
        }

        return false;
    }

    public function addAdditionalContact(Request $request)
    {
        LoggerService::info('addAdditionalContact called', extra: ['request' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required',
            'additional_contact_type' => 'required',
            'additional_contact_val' => 'required',
        ]);

        if ($request->isInertia == true && $validator->fails()) {
            LoggerService::info('addAdditionalContact validation failed', extra: ['errors' => $validator->errors()]);

            return redirect()->back()->withErrors($validator->errors());
        }

        if ($validator->fails()) {
            LoggerService::info('addAdditionalContact validation failed', extra: ['errors' => $validator->errors()]);

            return response()->json(['error' => [
                'message' => $validator->errors(),
            ]]);
        }

        $key = $request->additional_contact_type;
        $value = $request->additional_contact_val;
        $quoteObject = $this->getQuoteObject($request->quote_type, $request->quote_id);
        if ($quoteObject) {
            $user = auth()->user();
            if (! $this->quoteDocumentAccessService->userCanAccessQuoteDocumentable($user, $quoteObject, forAdditionalContact: true)) {
                $authorizationMessage = 'You are not authorized to add additional contact for this quote.';
                LoggerService::info('addAdditionalContact authorization failed', extra: ['customerId' => $request->customer_id, 'userId' => $user?->id]);
                if ($request->isInertia) {
                    vAbort($authorizationMessage);
                }

                return response()->json(['error' => [
                    'message' => $authorizationMessage,
                ]]);
            }
        }
        if ($key == GenericRequestEnum::EMAIL) {
            $isExistEmail = CustomerAdditionalContact::where('customer_id', $request->customer_id)
                ->where('value', $request->additional_contact_val)->where('key', 'email')->first();

            if ($isExistEmail) {
                LoggerService::info('addAdditionalContact email already exists', extra: ['customerId' => $request->customer_id, 'value' => $value]);
                if ($request->isInertia) {
                    vAbort('Email already Exist. Please try another.');
                }

                return response()->json(['error' => [
                    'message' => 'Email already Exist. Please try another.',
                ]]);
            }
        }

        if ($key == GenericRequestEnum::MOBILE_NO) {
            $isExistMobile = CustomerAdditionalContact::where('customer_id', $request->customer_id)
                ->where('value', $request->additional_contact_val)->where('key', 'mobile_no')->first();

            if ($isExistMobile) {
                LoggerService::info('addAdditionalContact mobile number already exists', extra: ['customerId' => $request->customer_id, 'value' => $value]);
                if ($request->isInertia) {
                    vAbort('Mobile Number already Exist. Please try another.');
                }

                return response()->json(['error' => [
                    'message' => 'Mobile Number already Exist. Please try another.',
                ]]);
            }
        }

        $additionalContact = CustomerAdditionalContact::create([
            'customer_id' => $request->customer_id,
            'key' => $key,
            'value' => trim($value),
        ]);

        LoggerService::info('addAdditionalContact created', extra: ['customerId' => $request->customer_id, 'additionalContactId' => $additionalContact->id, 'key' => $key, 'value' => $value]);

        if ($quoteObject) {
            $this->slaService->meetSLAOnEdit($quoteObject, SLAActionTypeEnum::ADDITIONAL_CONTACTS_ADD);
        }

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
