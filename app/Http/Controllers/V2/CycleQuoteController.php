<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\BikeQuoteRequest;
use App\Http\Requests\CycleQuoteRequest;
use App\Repositories\ActivityRepository;
use App\Repositories\BikeQuoteRepository;
use App\Repositories\CycleQuoteRepository;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\PersonalPlanRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;

class CycleQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $personalQuotes = CycleQuoteRepository::getData();

        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::CYCLE->id())->get();

        return inertia('CycleQuote/Index', [
            'quotes' => $personalQuotes,
            'quoteStatuses' => $quoteStatuses
        ]);
    }


    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        $data = BikeQuoteRepository::getFormOptions();

        return inertia('CycleQuote/Form', $data);
    }

    /**
     * @param $quoteTypeCode
     * @param  BikeQuoteRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CycleQuoteRequest $request)
    {
        $response = CycleQuoteRepository::create($request->validated());

        if (! empty($response->errors) || (! empty($response->errorType) && $response->errorType == 'ERROR')) {
            vAbort($response->msg);
        }

        return back()->with('message', 'Quote created successfully');
    }


}
