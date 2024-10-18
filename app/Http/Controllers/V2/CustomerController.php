<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteTypeId;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerAdditionalContactRequest;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\CustomerUploadRequest;
use App\Jobs\MAWelcomeJob;
use App\Repositories\CustomerRepository;
use App\Repositories\NationalityRepository;
use App\Services\BerlinService;
use App\Services\SendEmailCustomerService;

class CustomerController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
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
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($uuid)
    {
        $customer = CustomerRepository::getBy('uuid', $uuid);

        return inertia('Customer/Show', [
            'customer' => $customer,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
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
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update($uuid, CustomerRequest $customerRequest)
    {
        $customer = CustomerRepository::where(['uuid' => $uuid])->firstorFail();

        $sendWelcomeEmail = ((! $customer->has_alfred_access || $customer->has_reward_access) &&
                                $customerRequest->has_alfred_access && $customerRequest->has_reward_access);

        $customer->update($customerRequest->validated());

        if ($sendWelcomeEmail && config('constants.ENABLE_TRANSAPP_WE') == '1' && ! $customer->is_we_sent) {
            MAWelcomeJob::dispatch(
                $customer,
                'CUSTOMER_UPDATE',
                'customer-update-myalfred-we'
            );
        }

        return redirect('customer/'.$uuid)->with('message', 'Customer information has been updated');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
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
}
