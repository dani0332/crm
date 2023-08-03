<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerAdditionalContactRequest;
use App\Repositories\CustomerRepository;

class CustomerController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $customers = [];
        return inertia('Customer/Index', [
            'customers' => $customers
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
