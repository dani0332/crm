<?php

namespace App\Http\Controllers;

use App\Enums\QuoteTypes;
use App\Http\Requests\LifeCardLoadMoreRequest;
use App\Http\Requests\LifeQuoteRequest;
use App\Repositories\PaymentRepository;
use App\Services\Life\LifeQuoteService;
use App\Traits\GenericQueriesAllLobs;
use Inertia\ResponseFactory;

class LifeController extends Controller
{
    use GenericQueriesAllLobs;

    protected $lifeQuoteService;

    /**
     * TravelController constructor.
     *
     * @param  LifeQuoteService  $service
     */
    public function __construct(LifeQuoteService $lifeQuoteService)
    {
        $this->lifeQuoteService = $lifeQuoteService;
    }

    /**
     * @return ResponseFactory|Response
     *
     * @throws RuntimeException
     */
    public function index()
    {
        $data = $this->lifeQuoteService->getData();

        return inertia('LifeQuote/Index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = $this->lifeQuoteService->getFormOptions();

        return inertia('LifeQuote/Form', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(LifeQuoteRequest $request)
    {
        $response = $this->lifeQuoteService->saveLifeQuote($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect(route('life-quotes-show', $response->quoteUID))->with('message', 'Quote is created successfully.');
    }

    public function show($uuid)
    {
        /* Start - Temporarily adding for correcting historic data  */
        $quote = $this->lifeQuoteService->getQuoteBy('uuid', $uuid);
        abort_if(! $quote, 404);
        (new PaymentRepository)->updatePriceVatApplicableAndVat($quote, QuoteTypes::LIFE->value);
        /* End - Temporarily adding for correcting historic data  */

        $data = $this->lifeQuoteService->getShowData($uuid);

        return inertia('LifeQuote/Show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        $data = $this->lifeQuoteService->getEditData($uuid);

        return inertia('LifeQuote/Form', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function update(LifeQuoteRequest $request, $uuid)
    {
        $this->lifeQuoteService->updateLifeQuote($uuid, $request->validated());

        return redirect('personal-quotes/life/'.$uuid)->with('message', 'Quote updated successfully');
    }

    public function cardsView()
    {
        $data = $this->lifeQuoteService->getCardsViewData();

        return inertia('LifeQuote/Cards', $data);
    }

    public function getCardsViewLoadMore(LifeCardLoadMoreRequest $request)
    {
        return $this->lifeQuoteService->getCardsViewLoadMore($request->validated());
    }
}
