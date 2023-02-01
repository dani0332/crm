<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\PersonalQuoteRequest;
use App\Models\InsuranceProvider;
use App\Models\Nationality;
use App\Models\UAELicenseHeldFor;
use App\Models\YearOfManufacture;
use App\Repositories\PersonalQuoteRepository;
use Illuminate\Http\Request;

class PersonalQuoteController extends Controller
{
    public function index($quoteTypeCode)
    {
        $personalQuotes = PersonalQuoteRepository::getData($quoteTypeCode);

        return inertia('PersonalQuote/Index', [
            'personalQuotes' => $personalQuotes,
        ]);
    }
}
