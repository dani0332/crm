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

    public function index()
    {
        $personalQuotes = BikeQuoteRepository::getData();

        return inertia('BikeQuote/Index', [
            'personalQuotes' => $personalQuotes,
        ]);
    }

    public function create()
    {
        $nationalities = Nationality::withActive()->get();
        $uaeLicenses = UAELicenseHeldFor::withActive()->get();
        $yearOfManufacture = YearOfManufacture::get();
        $insuranceProviders = InsuranceProvider::select('id', 'text')->orderBy('text', 'asc')->get();

        return inertia('BikeQuote/Form', [
            'nationalities' => $nationalities,
            'uaeLicenses' => $uaeLicenses,
            'yearOfManufacture' => $yearOfManufacture,
            'insuranceProviders' => $insuranceProviders
        ]);
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

    public function edit($uuid)
    {
        $nationalities = Nationality::withActive()->get();
        $uaeLicenses = UAELicenseHeldFor::withActive()->get();
        $yearOfManufacture = YearOfManufacture::get();
        $insuranceProviders = InsuranceProvider::select('id', 'text')->orderBy('text', 'asc')->get();

        $bikeQuote = BikeQuoteRepository::getBy('uuid', $uuid);

        return inertia('BikeQuote/Form', [
            'nationalities' => $nationalities,
            'uaeLicenses' => $uaeLicenses,
            'yearOfManufacture' => $yearOfManufacture,
            'insuranceProviders' => $insuranceProviders,
            'bikeQuote' => $bikeQuote
        ]);
    }

    public function update($quoteTypeCode, $quoteId, PersonalQuoteRequest $request)
    {
        dd($quoteTypeCode, $quoteId, $request->validated());
    }
}
