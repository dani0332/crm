<?php

namespace App\Http\Controllers;


use Inertia\Response;
use RuntimeException;
use App\Enums\QuoteTypeId;
use App\Enums\quoteTypeCode;
use Illuminate\Http\Request;
use Inertia\ResponseFactory;
use App\Services\TravelQuoteService;
use Yajra\DataTables\Facades\DataTables;

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
}
