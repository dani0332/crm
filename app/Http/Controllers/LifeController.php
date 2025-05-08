<?php

namespace App\Http\Controllers;

use App\Enums\GenericRequestEnum;
use App\Enums\PaymentGatewayIdEnum;
use App\Enums\PermissionsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Http\Requests\StoreLifeRequest;
use App\Services\CRUDService;
use App\Services\DropdownSourceService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Requests\LifeCardLoadMoreRequest;
use App\Http\Requests\LifeQuoteRequest;
use App\Services\Life\LifeQuoteService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Inertia\ResponseFactory;
use Illuminate\Support\Facades\DB;

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
            'version' => 'required'
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
}
