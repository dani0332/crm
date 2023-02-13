<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\BikeQuoteRequest;
use App\Http\Requests\PersonalQuotePaymentRequest;
use App\Http\Requests\PersonalQuoteStatusRequest;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
use App\Models\PersonalQuote;
use App\Models\QuoteStatus;
use App\Models\UAELicenseHeldFor;
use App\Models\YearOfManufacture;
use App\Repositories\BikeQuoteRepository;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\PersonalQuoteRepository;
use App\Repositories\QuoteStatusRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PersonalQuoteController extends Controller
{

    /**
     * @param $quoteType
     * @param $quoteId
     * @param PersonalQuoteStatusRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus($quoteType, $quoteId, PersonalQuoteStatusRequest $request)
    {
        PersonalQuoteRepository::updateStatus($quoteType, $quoteId, $request->validated());
        return back()->with('message' , 'Status updated successfully');
    }


    public function uploadDocument($quoteType, $quoteId)
    {
        PersonalQuoteRepository::uploadDocument(request()->file('file'), request()->all());
        return back()->with('message' , 'Document uploaded successfully');
    }

    public function createPayment(PersonalQuotePaymentRequest $request)
    {
        PersonalQuoteRepository::createPayment($request->validated());
    }

}
