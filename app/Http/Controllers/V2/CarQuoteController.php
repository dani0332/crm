<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\DuplicateLobRequest;
use App\Repositories\CarQuoteRepository;
use App\Services\CentralService;
use App\Http\Requests\ChangeInsurerRequest;
use Illuminate\Http\Request;

class CarQuoteController extends Controller
{

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function getCarSoldQuotes()
    {
        $quotes = CarQuoteRepository::getLostQuotes(QuoteStatusEnum::CarSold);

        return inertia('LostQuotes/CarSold', [
            'quotes' => $quotes
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function getCarUncontactableQuotes()
    {
        $quotes = CarQuoteRepository::getLostQuotes(QuoteStatusEnum::Uncontactable);

        return inertia('LostQuotes/CarUncontactable', [
            'quotes' => $quotes
        ]);
    }

    /**
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeInsurer(ChangeInsurerRequest $request)
    {
        $response = CarQuoteRepository::changeInsurer($request->validated());

        return response()->json($response);
    }
}
