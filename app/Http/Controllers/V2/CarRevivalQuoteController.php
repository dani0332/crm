<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Repositories\CarQuoteRepository;
use App\Repositories\QuoteStatusRepository;

class CarRevivalQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $carRevivalQuotes = CarQuoteRepository::getRevivalData();

//        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::CYCLE->id())->get();

        return inertia('CarRevivalQuote/Index', [
            'quotes' => $carRevivalQuotes,
            'quoteStatuses' => [],
        ]);
    }
}
