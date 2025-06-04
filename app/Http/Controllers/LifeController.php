<?php

namespace App\Http\Controllers;

use App\Http\Requests\LifeCardLoadMoreRequest;
use App\Http\Requests\LifeQuoteRequest;
use App\Models\PersonalQuote;
use App\Services\Life\LifeQuoteService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Inertia\ResponseFactory;
use PDF;

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
        $data = $this->lifeQuoteService->getLifeQuoteData();

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
        /* Start - Temporarily adding for correcting historic data */
        $quote = $this->lifeQuoteService->getQuoteBy('uuid', $uuid);
        abort_if(! $quote, 404);
        $quote = $this->lifeQuoteService->correctHistoricData($quote);
        /* End - Temporarily adding for correcting historic data */

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

    public function getProviderPlans($providerId)
    {
        $providerPlans = $this->lifeQuoteService->getProviderPlans($providerId);

        return response()->json(['plans' => $providerPlans]);
    }

    public function lifePlanCreateQuote(Request $request)
    {

        LoggerService::startQuoteLogging($request->quoteUID);

        LoggerService::info('fn: lifePlanCreateQuoteLife - creating plan for life quote');

        $request->validate([
            'quoteUID' => 'required',
            'formData' => 'required|array',
        ]);

        $model = $this->lifeQuoteService->lifePlanCreateQuote($request->quoteUID, $request->formData);

        LoggerService::info('fn: lifePlanCreateQuoteLife - plan created for life', [
            'trace_id' => $request->quoteUID,
        ]);

        return $model;
    }

    public function lifePlanUpdate(Request $request): void
    {

        $request->validate([
            'quoteUID' => 'required',
            'formData' => 'required|array',
        ]);

        // return $this->lifeQuoteService->lifePlanUpdate($request->quoteUID, $request->formData);
    }

    public function lifePlanSelected(Request $request)
    {
        LoggerService::startQuoteLogging($request->quoteId);

        LoggerService::info('fn: lifePlanSelected - selecting plan for life quote');

        $request->validate([
            'planId' => 'required',
            'quoteId' => 'required',
            'version' => 'required',
        ]);

        $model = $this->lifeQuoteService->lifePlanSelected($request->quoteId, $request->planId, $request->version, $request?->saveQuote);

        LoggerService::info('fn: lifePlanSelected -  Plan selected for life quote');

        return $model;

    }

    public function getLifeProviderPlan(Request $request)
    {
        // dd($request->data);
        $providerPlan = $this->lifeQuoteService->getLifeProviderPlan($request->data);

        return response()->json(['providerPlan' => $providerPlan]);
    }

    function riderDetails(Request $request)
    {
        $riderDetails = $this->lifeQuoteService->getRiderDetails($request->planId);
        return response()->json(['riderDetails' => $riderDetails]);
    }

    public function comparisionPdf()
    {

        $quote = PersonalQuote::where('uuid', 'PFLS7T47')->first();
        $quotePlans = $this->lifeQuoteService->getQuotePlans($quote->uuid);
        $lifePlans = $quotePlans->quotes->plans;
        $planIds = collect($lifePlans)->take(5)->pluck('_id')->toArray();

        $pdf = PDF::setOption([
            'isHtml5ParserEnabled' => true,
            'dpi' => 150,
        ])->loadView('pdf.life.comparision_pdf', compact('quote', 'planIds', 'lifePlans'));

        return $pdf->stream('Life Insurance Comparison Table.pdf');
    }
}
