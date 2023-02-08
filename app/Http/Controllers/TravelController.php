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
    public function index()
    {
        $dropdownSource = $this->service->getDropdownSource(self::TYPE_ID);
        return inertia('TravelQuote/Index', [
            'quotes' => [],
            'dropdownSource' => $dropdownSource,
        ]);
    }
}
