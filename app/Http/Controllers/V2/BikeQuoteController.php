<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\PersonalQuoteRequest;
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
            'personalQuotes' => $personalQuotes,
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
     * @param PersonalQuoteRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(PersonalQuoteRequest $request)
    {
        BikeQuoteRepository::create($request->validated());
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

    /**
     * @param $quoteTypeCode
     * @param $quoteId
     * @param PersonalQuoteRequest $request
     * @return void
     */
    public function update($quoteTypeCode, $quoteId, PersonalQuoteRequest $request)
    {
        dd($quoteTypeCode, $quoteId, $request->validated());
    }
}
