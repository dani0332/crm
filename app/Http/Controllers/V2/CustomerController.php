<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerAdditionalContactRequest;
use App\Repositories\CustomerRepository;
use App\Repositories\NationalityRepository;

class CustomerController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $customers = CustomerRepository::getData();

        return inertia('Customer/Index', [
            'customers' => $customers
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($uuid)
    {
        $customer = CustomerRepository::getBy('uuid', $uuid);

        return inertia('Customer/Show', [
            'customer' => $customer
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeAdditionalContact($customerId, CustomerAdditionalContactRequest $request)
    {
        CustomerRepository::storeAdditionalContact($customerId, $request->validated());

        return back();
    }
}
