<?php

namespace App\Http\Controllers\V2;

use App\Enums\ApplicationStorageEnums;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\LifeQuoteRequest;
use App\Models\ApplicationStorage;
use App\Repositories\LifeQuoteRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;
use App\Services\Reports\RenewalBatchReportService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;

class LifeQuoteController extends Controller
{
    use GenericQueriesAllLobs;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $lifeQuotes = LifeQuoteRepository::getData();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::LIFE->value);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::LIFE->id())->get();
        $authorizedDays = ApplicationStorage::where('key_name', '=', ApplicationStorageEnums::PAYMENT_AUTHORISED_DAYS)->first();
        $renewalBatches = app(RenewalBatchReportService::class)->getAllNonMotorBatches();

        return inertia('LifeQuote/Index', [
            'quotes' => $lifeQuotes,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'renewalBatches' => $renewalBatches,
            'authorizedDays' => intval($authorizedDays->value),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = LifeQuoteRepository::getFormOptions();

        return inertia('LifeQuote/Form', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(LifeQuoteRequest $request)
    {
        $response = LifeQuoteRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect(route('life-quotes-show', $response->quoteUID))->with('message', 'Quote is created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($uuid)
    {
        /* Start - Temporarily adding for correcting historic data  */
        $quote = LifeQuoteRepository::where('uuid', $uuid)->first();
        abort_if(! $quote, 404);
        (new PaymentRepository)->updatePriceVatApplicableAndVat($quote, QuoteTypes::LIFE->value);
        /* End - Temporarily adding for correcting historic data  */

        $quote = LifeQuoteRepository::getBy('uuid', $uuid);
        $quoteData = LifeQuoteRepository::getShowFormOptions($quote);
        return inertia('LifeQuote/Show', $quoteData);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        $data = LifeQuoteRepository::getFormOptions();
        $quote = LifeQuoteRepository::getBy('uuid', $uuid);

        return inertia('LifeQuote/Form', array_merge($data, [
            'quote' => $quote,
        ]));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(LifeQuoteRequest $request, $uuid)
    {
        LifeQuoteRepository::update($uuid, $request->validated());

        return redirect('personal-quotes/life/'.$uuid)->with('message', 'Quote updated successfully');
    }

    public function cardsView(Request $request)
    {
        return inertia('LifeQuote/Cards', LifeQuoteRepository::getCardData($request));
    }
}
