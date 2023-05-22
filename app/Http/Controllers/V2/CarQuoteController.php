<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\DuplicateLobRequest;
use App\Repositories\CarQuoteRepository;
use App\Services\CentralService;

class CarQuoteController extends Controller
{

    public function getCarSoldQuotes()
    {
        $quotes = CarQuoteRepository::getLostQuotes(QuoteStatusEnum::CarSold);

        return inertia('LostQuotes/CarSold', [
            'quotes' => $quotes
        ]);
    }
}
