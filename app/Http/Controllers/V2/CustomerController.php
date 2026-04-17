<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteTypeId;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerAdditionalContactRequest;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\CustomerUploadRequest;
use App\Jobs\ExtendCustomerSubscriptionViaSQS;
use App\Repositories\CustomerRepository;
use App\Repositories\NationalityRepository;
use App\Services\BerlinService;
use App\Services\SendEmailCustomerService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Inertia\Response;
use Inertia\ResponseFactory;

class CustomerController extends Controller
{
    /**
     * @return Response|ResponseFactory
     */
    public function index()
    {
        $customers = CustomerRepository::getData();
        $quoteTypes = QuoteTypeId::getOptions();

        return inertia('Customer/Index', [
            'customers' => $customers,
            'userId' => auth()->id(),
            'quoteTypes' => $quoteTypes,
        ]);
    }

    /**
     * @return Response|ResponseFactory
     */
    public function show($uuid)
    {
        $customer = CustomerRepository::getBy('uuid', $uuid);

        return inertia('Customer/Show', [
            'customer' => $customer,
        ]);
    }

    /**
     * @return Response|ResponseFactory
     */
    public function edit($uuid)
    {
        $nationalities = NationalityRepository::withActive()->get();
        $customer = CustomerRepository::getBy('uuid', $uuid);

        return inertia('Customer/Form', [
            'nationalities' => $nationalities,
            'customer' => $customer,
        ]);
    }

    /**
     * @param  $quoteTypeCode
     * @param  $quoteId
     * @return Application|RedirectResponse|Redirector
     */
    public function update($uuid, CustomerRequest $customerRequest)
    {
        $customer = CustomerRepository::where(['uuid' => $uuid])->firstorFail();

        $sendWelcomeEmail = ((! $customer->has_alfred_access || $customer->has_reward_access) &&
                                $customerRequest->has_alfred_access && $customerRequest->has_reward_access);

        $customer->update($customerRequest->validated());

        if ($sendWelcomeEmail && config('constants.ENABLE_TRANSAPP_WE') == '1' && ! $customer->is_we_sent) {
            ExtendCustomerSubscriptionViaSQS::dispatch(
                $customer,
                'CUSTOMER_UPDATE',
                'customer-update-myalfred-we'
            );
        }

        return redirect('customer/'.$uuid)->with('message', 'Customer information has been updated');
    }

    /**
     * @return RedirectResponse
     */
    public function storeAdditionalContact($customerId, CustomerAdditionalContactRequest $request)
    {
        CustomerRepository::storeAdditionalContact($customerId, $request->validated());

        return back();
    }

    public function uploadCustomers()
    {
        return inertia('Customer/Upload');
    }

    public function processCustomerUpload(CustomerUploadRequest $customerUploadRequest, SendEmailCustomerService $sendEmailCustomerService, BerlinService $berlinService)
    {
        if ($customerUploadRequest->validated()) {
            CustomerRepository::customerUploadRecordsCreate($customerUploadRequest, $sendEmailCustomerService, $berlinService);
        }

        return redirect('customer-upload')->with('success', 'Upload customers records has been stored');
    }

    public function listByEmail(Request $request)
    {
        $leads = CustomerRepository::getDataByContacts($request->all());

        return inertia('Customer/Contacts', [
            'leads' => $leads,
            'userId' => auth()->id(),
        ]);
    }
}
