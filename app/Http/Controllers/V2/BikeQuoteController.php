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

        if(!empty($response->errors)) {
            vAbort($response->message);
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

        $bikeQuote = BikeQuoteRepository::getBy('uuid', $uuid);

        return inertia('BikeQuote/Form', array_merge($data, [
            'bikeQuote' => $bikeQuote
            ])
        );
    }

    public function show($uuid)
    {
        $bikeQuote = BikeQuoteRepository::getBy('uuid', $uuid);
        return inertia('BikeQuote/Show', [
            'bikeQuote' => $bikeQuote
        ]);
    }

    /**
     * @param $quoteTypeCode
     * @param $quoteId
     * @param BikeQuoteRequest $request
     * @return void
     */
    public function update($quoteTypeCode, $quoteId, BikeQuoteRequest $request)
    {
        dd($quoteTypeCode, $quoteId, $request->validated());
    }
}
