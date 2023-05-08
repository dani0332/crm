<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Models\CarQuote;
use App\Models\GenericModel;
use App\Repositories\CarQuoteRepository;
use App\Repositories\CarRevivalQuoteRepository;
use App\Repositories\QuoteStatusRepository;
use App\Services\CarQuoteService;

class CarRevivalQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $carRevivalQuotes = CarRevivalQuoteRepository::getData();
//        dd($carRevivalQuotes->toArray());
        $formOptionsData = CarQuoteRepository::getFormOptions();

        return inertia('CarRevivalQuote/Index', [
            'quotes' => $carRevivalQuotes,
            'leadStatuses' => $formOptionsData,
            'advisors' => []
        ]);
    }

}
