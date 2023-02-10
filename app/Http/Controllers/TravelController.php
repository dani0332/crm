<?php

namespace App\Http\Controllers;


use Inertia\Response;
use RuntimeException;
use App\Enums\QuoteTypeId;
use App\Enums\quoteTypeCode;
use Illuminate\Http\Request;
use Inertia\ResponseFactory;
use App\Services\TravelQuoteService;


class TravelController extends Controller
{
    protected $service;
    protected $genericModel;

    const TYPE = quoteTypeCode::Travel;

    const TYPE_ID = QuoteTypeId::Travel;

    /**
     * TravelController constructor.
     * @param TravelQuoteService $travelQuoteService
     */
    public function __construct(TravelQuoteService $travelQuoteService)
    {
        $this->service = $travelQuoteService;
        $this->genericModel = $this->service->getGenericModel(self::TYPE);
    }


    /**
     * @return ResponseFactory|Response
     * @throws RuntimeException
     */
    public function index(Request $request)
    {
        $dropdownSource = $this->service->getDropdownSource(self::TYPE_ID);
        $gridData = $this->service->getGridData($this->genericModel, $request);
        $quotes = $gridData->simplePaginate(10)->withQueryString();
        return inertia('TravelQuote/Index', [
            'quotes' => $quotes,
            'dropdownSource' => $dropdownSource,
        ]);
    }


    /**
     * @param Request $request
     *
     * @return ResponseFactory|Response
     * @throws RuntimeException
     */
    public function cardsView(Request $request)
    {
        $quotes = [];

        $quotes[] = [
            'id' => 8,
            'title' => 'New Lead',
            'data' => getDataAgainstStatus('Travel', 8),
        ];
        $quotes[] = [
            'id' => 2,
            'title' => 'Quoted',
            'data' => getDataAgainstStatus('Travel', 2),
        ];
        $quotes[] = [
            'id' => 31,
            'title' => 'Qualified',
            'data' => getDataAgainstStatus('Travel', 31),
        ];
        $quotes[] = [
            'id' => 28,
            'title' => 'Payment Pending',
            'data' => getDataAgainstStatus('Travel', 28),
        ];

        return inertia('TravelQuote/Cards', [
            'quotes' => $quotes,
        ]);
    }
}
