<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarQuoteRequest;
use App\Repositories\CarQuoteRepository;
use App\Repositories\CarRevivalQuoteRepository;

class CarRevivalQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $formOptionsData = CarQuoteRepository::getFormOptions();
        $carRevivalQuotes = CarRevivalQuoteRepository::getData();

        return inertia('CarRevivalQuote/Index', [
            'quotes' => $carRevivalQuotes,
            'leadStatuses' => $formOptionsData
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $formOptionsData = CarQuoteRepository::getFormOptions(false);
        $quote = CarQuoteRepository::getBy('uuid', $uuid);

        return inertia('CarRevivalQuote/Form',
            [
                'form_options' => $formOptionsData,
                'quote' => $quote
            ]
        );
    }

    /**
     * @param $quoteTypeCode
     * @param $quoteId
     * @return void
     */
    public function update($uuid, CarQuoteRequest $carQuoteRequest)
    {
        CarQuoteRepository::where(['uuid' => $uuid])->update($carQuoteRequest->validated());

        return back()->with('message', 'Quote updated successfully');
    }

}
