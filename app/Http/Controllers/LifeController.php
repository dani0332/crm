<?php

namespace App\Http\Controllers;

use App\Enums\QuoteTypes;
use App\Http\Requests\LifeCardLoadMoreRequest;
use App\Http\Requests\LifeQuoteRequest;
use App\Http\Requests\LifeSendOCAEmailRequest;
use App\Jobs\SendOCAEmailJob;
use App\Services\Life\LifeQuoteService;
use App\Services\Logger\LoggerService;
use App\Services\LookupService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
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
        $data = $this->lifeQuoteService->getLifeQuoteData();
        $subSources = app(LookupService::class)->getSubSource();

        $data['subSources'] = $subSources;

        return inertia('LifeQuote/Index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        // Log parameters from CreateLeadModal
        LoggerService::info('Life create method called with parameters', [
            'type' => $request->input('type'),
            'subSourceId' => $request->input('subSourceId'),
            'subSourceOptionsId' => $request->input('subSourceOptionsId'),
        ]);

        $data = $this->lifeQuoteService->getFormOptions();
        $subSources = app(LookupService::class)->getSubSource();

        $data['subSources'] = $subSources;
        $data['leadSourceParams'] = [
            'type' => $request->input('type'),
            'subSource' => $request->input('subSourceId'),
            'subSourceOption' => $request->input('subSourceOptionsId'),
        ];

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
        $subSources = app(LookupService::class)->getSubSource();

        $data['subSources'] = $subSources;
        $data['leadSourceParams'] = [];

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

    public function lifePlanSelected(Request $request)
    {
        LoggerService::startQuoteLogging($request->quoteId);

        LoggerService::info('fn: lifePlanSelected - selecting plan for life quote');

        $request->validate([
            'planId' => 'required',
            'quoteId' => 'required',
            'version' => 'required',
        ]);

        $model = $this->lifeQuoteService->selectPlan(
            $request->quoteId,
            $request->planId,
            $request->version,
            $request?->saveQuote,
            $request?->isUW,
            $request?->callSource
        );

        LoggerService::info('fn: lifePlanSelected -  Plan selected for life quote');

        return $model;
    }

    public function getLifeProviderPlan(Request $request)
    {
        $providerPlan = $this->lifeQuoteService->getLifeProviderPlan($request->data);

        return response()->json(['providerPlan' => $providerPlan]);
    }

    public function riderDetails(Request $request)
    {
        $riderDetails = $this->lifeQuoteService->getRiderDetails($request->planId);

        return response()->json($riderDetails);
    }

    public function sendOCAEmail(LifeSendOCAEmailRequest $request)
    {
        SendOCAEmailJob::dispatch($request->quote_uuid, $request->validated());

        return response()->json(['message' => 'OCA Email Sent Successfully']);
    }

    public function downloadComparisionPdf(LifeSendOCAEmailRequest $request)
    {
        $pdfData = $this->lifeQuoteService->exportPlansPdf(QuoteTypes::LIFE->value, $request->validated());

        $pdf = $pdfData['pdf'];
        $filename = $pdfData['name'] ?? 'Life Insurance Comparison Table.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function currencyCoverages(Request $request)
    {
        $currencyCoverages = $this->lifeQuoteService->getCurrencyCoverages($request->planId);

        return response()->json($currencyCoverages);
    }

    public function toggleLifePlanVisibility(Request $request)
    {
        LoggerService::startQuoteLogging($request->quoteUID);
        $this->lifeQuoteService->toggleLifePlanVisibility($request->all());
        LoggerService::info('fn: toggleLwebifePlanVisibility - Life plan visibility toggled successfully');

        return response()->json(['message' => 'Life plan visibility toggled successfully']);
    }

    public function riders(Request $request)
    {
        $riders = $this->lifeQuoteService->getRiders($request->planId);

        return response()->json($riders);
    }

    public function updateExchangeRate(Request $request)
    {
        $this->lifeQuoteService->updateExchangeRate($request->quoteUID, $request->exchangeRate);

        return response()->json(['message' => 'Exchange rate updated successfully']);
    }
}
