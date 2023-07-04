<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarRevivalQuoteRequest;
use App\Repositories\CarRevivalQuoteRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CarRevivalQuoteController extends Controller
{
    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        $formOptionsData = CarRevivalQuoteRepository::getFormOptions();
        $carRevivalQuotes = CarRevivalQuoteRepository::getData();

        return inertia('CarRevivalQuote/Index', [
            'quotes' => $carRevivalQuotes,
            'leadStatuses' => $formOptionsData,
        ]);
    }

    /**
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function edit($uuid)
    {
        $formOptionsData = CarRevivalQuoteRepository::getFormOptions(false);
        $quote = CarRevivalQuoteRepository::getBy('uuid', $uuid);

        return inertia('CarRevivalQuote/Form',
            [
                'form_options' => $formOptionsData,
                'quote' => $quote,
            ]
        );
    }

    /**
     * @param $quoteTypeCode
     * @param $quoteId
     * @return void
     */
    public function update($uuid, CarRevivalQuoteRequest $carRevivalQuoteRequest)
    {
        CarRevivalQuoteRepository::where(['uuid' => $uuid])->update($carRevivalQuoteRequest->validated());

        return back()->with('message', 'Quote updated successfully');
    }

    public function updateQuote(Request $request){

        CarRevivalQuoteRepository::updateQuote($request);

    }

}
