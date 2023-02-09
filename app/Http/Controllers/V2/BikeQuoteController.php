<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\BikeQuoteRequest;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
use App\Models\PersonalQuote;
use App\Models\UAELicenseHeldFor;
use App\Models\YearOfManufacture;
use App\Repositories\BikeQuoteRepository;
use App\Repositories\PersonalQuoteRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BikeQuoteController extends Controller
{

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $personalQuotes = BikeQuoteRepository::getData();

        return inertia('BikeQuote/Index', [
            'quotes' => $personalQuotes,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function create()
    {
        $data = BikeQuoteRepository::getFormOptions();
        return inertia('BikeQuote/Form', $data);
    }

    /**
     * @param $quoteTypeCode
     * @param BikeQuoteRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(BikeQuoteRequest $request)
    {
        $response = BikeQuoteRepository::create($request->validated());

        if(!empty($response->errors) || (!empty($response->errorType) && $response->errorType == "ERROR" )) {
            vAbort($response->msg);
        }

        return back()->with('message' , 'Quote created successfully');
    }

    /**
     * @param $uuid
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $data = BikeQuoteRepository::getFormOptions();

        $quote = BikeQuoteRepository::getBy('uuid', $uuid);

        return inertia('BikeQuote/Form', array_merge($data, [
            'quote' => $quote
            ])
        );
    }

    /**
     * @param $uuid
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show($uuid)
    {
        $quote = BikeQuoteRepository::getBy('uuid', $uuid);

        return inertia('BikeQuote/Show', [
            'quote' => $quote
        ]);
    }

    /**
     * @param $quoteTypeCode
     * @param $quoteId
     * @param BikeQuoteRequest $request
     * @return void
     */
    public function update($uuid, BikeQuoteRequest $request)
    {
        $response = BikeQuoteRepository::update($uuid, $request->validated());
        return back()->with('message' , 'Quote updated successfully');
    }
}
