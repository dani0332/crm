<?php

namespace App\Http\Controllers\V2;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\SavingsQuoteRequest;
use App\Services\Quotes\SavingsQuoteService;

class SavingsQuoteController extends Controller
{
    public function __construct(
        public SavingsQuoteService $savingsQuoteService,
    ) {
        $this->middleware('permission:'.PermissionsEnum::SAVINGS_QUOTES_LIST, ['only' => ['index']]);
        $this->middleware('permission:'.PermissionsEnum::SAVINGS_QUOTES_CREATE, ['only' => ['create', 'store']]);
        $this->middleware('permission:'.PermissionsEnum::SAVINGS_QUOTES_EDIT, ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.PermissionsEnum::SAVINGS_QUOTES_SHOW, ['only' => ['show']]);
    }

    public function index()
    {
        $advisors = $this->savingsQuoteService->getAdvisors();
        $quoteStatuses = $this->savingsQuoteService->getQuoteStatuses([QuoteStatusEnum::Lost]);
        $renewalBatches = $this->savingsQuoteService->getRenewalBatches();
        $authorizedDays = $this->savingsQuoteService->getPaymentAuthorizedDays();

        $query = $this->savingsQuoteService->getData();

        $count = count(request()->all()) > 1 || $this->savingsQuoteService->hasOtherFilters() ?
                    $query->count() :
                    $this->savingsQuoteService->getData(forExport: true, getTotalCount: true);

        $data = $query->simplePaginate(10)->withQueryString();

        return inertia('SavingsQuote/Index', [
            'quotes' => $data,
            'quoteStatuses' => $quoteStatuses,
            'renewalBatches' => $renewalBatches,
            'advisors' => $advisors,
            'totalCount' => $count,
            'authorizedDays' => intval($authorizedDays->value),
            'investmentFrequencies' => InvestmentFrequencyEnum::withLabels(),
        ]);
    }

    public function create()
    {
        $data = $this->savingsQuoteService->getFormOptions();

        return inertia('SavingsQuote/Form', [
            'lookUpData' => $data['lookUpData'],
            'genders' => $data['genders'],
        ]);
    }

    public function store(SavingsQuoteRequest $request)
    {
        $response = $this->savingsQuoteService->create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect(route('savings-quotes-show', $response->quoteUID))->with('message', 'Quote is created successfully.');
    }

    public function edit($uuid)
    {
        $quote = $this->savingsQuoteService->getOne($uuid);
        $data = $this->savingsQuoteService->getFormOptions();

        return inertia('SavingsQuote/Form', [
            'quote' => $quote,
            'lookUpData' => $data['lookUpData'],
            'genders' => $data['genders'],
        ]);
    }

    public function update(SavingsQuoteRequest $request, $uuid)
    {
        $this->savingsQuoteService->update($uuid, $request->validated());

        return redirect(route('savings-quotes-show', $uuid))->with('message', 'Quote is updated successfully.');
    }

    public function show($uuid)
    {
        $data = $this->savingsQuoteService->getShowData($uuid);

        return inertia('SavingsQuote/Show', $data);
    }
}
