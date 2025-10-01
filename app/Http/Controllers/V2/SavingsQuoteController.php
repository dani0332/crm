<?php

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\SavingsPlanUpdateRequest;
use App\Http\Requests\SavingsQuoteRequest;
use App\Repositories\LostReasonRepository;
use App\Services\LookupService;
use App\Services\Quotes\SavingsQuoteService;
use Illuminate\Http\Request;

class SavingsQuoteController extends Controller
{
    public function __construct(
        public SavingsQuoteService $savingsQuoteService,
    ) {
        $this->middleware('permission:'.PermissionsEnum::SAVINGS_QUOTES_LIST.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['index', 'cardsView']]);
        $this->middleware('permission:'.PermissionsEnum::SAVINGS_QUOTES_CREATE, ['only' => ['create', 'store']]);
        $this->middleware('permission:'.PermissionsEnum::SAVINGS_QUOTES_EDIT.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.PermissionsEnum::SAVINGS_QUOTES_SHOW.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['show']]);
    }

    public function index()
    {
        $advisors = $this->savingsQuoteService->getAdvisors();
        $quoteStatuses = $this->savingsQuoteService->getQuoteStatuses([QuoteStatusEnum::Lost]);
        $renewalBatches = $this->savingsQuoteService->getRenewalBatches();
        $authorizedDays = $this->savingsQuoteService->getPaymentAuthorizedDays();
        $subSources = app(LookupService::class)->getSubSource(QuoteTypeId::Savings);

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
            'investmentFrequencies' => $this->savingsQuoteService->getInvestmentFrequencies(),
            'subSources' => $subSources,
        ]);
    }

    public function create(Request $request)
    {
        $data = $this->savingsQuoteService->getFormOptions();
        $subSources = app(LookupService::class)->getSubSource(QuoteTypeId::Savings);

        $data['subSources'] = $subSources;
        $data['leadSourceParams'] = [
            'type' => $request->input('type'),
            'subSource' => $request->input('subSourceId'),
            'subSourceOption' => $request->input('subSourceOptionsId'),
            'primaryRefId' => $request->input('primaryRefId'),
            'partnerName' => $request->input('partnerName'),
        ];

        return inertia('SavingsQuote/Form', $data);
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
        $data = $this->savingsQuoteService->getFormOptions();
        $quote = $this->savingsQuoteService->getOne($uuid);
        $subSources = app(LookupService::class)->getSubSource(QuoteTypeId::Savings);

        $data['subSources'] = $subSources;
        $data['leadSourceParams'] = [];

        return inertia('SavingsQuote/Form', array_merge($data, [
            'quote' => $quote,
        ]));
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

    public function planDetails($quoteId, $planId)
    {
        $result = $this->savingsQuoteService->getPlanDetails($quoteId, $planId);

        if (isset($result['error']) && $result['error']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['status']);
        }

        return response()->json($result['data'], 200);
    }

    public function savingsPlanUpdateManualProcess(SavingsPlanUpdateRequest $request)
    {
        // Get the response from the service
        $response = $this->savingsQuoteService->savingsPlanModify($request);

        // Check if the response is a success (e.g., 200 or 201)
        if (is_int($response) && in_array($response, [200, 201])) {
            return redirect()->back()->with('message', 'Savings plan has been updated successfully');
        }

        // Determine the response message
        $responseMessage = 'Unknown error';
        if (is_object($response) && isset($response->message)) {
            $responseMessage = $response->message;
        } elseif (is_string($response)) {
            $responseMessage = $response;
        }

        // Return error as redirect for Inertia
        return redirect()->back()->with('error', 'Savings plan has not been updated. '.$responseMessage);
    }

    public function cardsView(Request $request)
    {
        // Initialize the quotes array
        $quotes = [
            ['id' => QuoteStatusEnum::NewLead, 'title' => quoteStatusCode::NEW_LEAD, 'data' => getDataAgainstStatus(QuoteTypes::SAVINGS->value, QuoteStatusEnum::NewLead, $request)],
            ['id' => QuoteStatusEnum::Allocated, 'title' => quoteStatusCode::ALLOCATED, 'data' => getDataAgainstStatus(QuoteTypes::SAVINGS->value, QuoteStatusEnum::Allocated, $request)],
            ['id' => QuoteStatusEnum::Quoted, 'title' => quoteStatusCode::QUOTED, 'data' => getDataAgainstStatus(QuoteTypes::SAVINGS->value, QuoteStatusEnum::Quoted, $request)],
            ['id' => QuoteStatusEnum::FollowedUp, 'title' => quoteStatusCode::FOLLOWEDUP, 'data' => getDataAgainstStatus(QuoteTypes::SAVINGS->value, QuoteStatusEnum::FollowedUp, $request)],
            ['id' => QuoteStatusEnum::InNegotiation, 'title' => quoteStatusCode::NEGOTIATION, 'data' => getDataAgainstStatus(QuoteTypes::SAVINGS->value, QuoteStatusEnum::InNegotiation, $request)],
            ['id' => QuoteStatusEnum::PaymentPending, 'title' => quoteStatusCode::PAYMENTPENDING, 'data' => getDataAgainstStatus(QuoteTypes::SAVINGS->value, QuoteStatusEnum::PaymentPending, $request)],
            ['id' => QuoteStatusEnum::TransactionApproved, 'title' => quoteStatusCode::TRANSACTIONAPPROVED, 'data' => getDataAgainstStatus(QuoteTypes::SAVINGS->value, QuoteStatusEnum::TransactionApproved, $request)],
            ['id' => QuoteStatusEnum::PolicyIssued, 'title' => quoteStatusCode::POLICY_ISSUED, 'data' => getDataAgainstStatus(QuoteTypes::SAVINGS->value, QuoteStatusEnum::PolicyIssued, $request)],
        ];

        // Fetch lost reasons
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        // Calculate total leads
        $totalLeads = 0;
        $hasOtherFilters = count(array_diff_key(request()->all(), ['page' => ''])) > 0;

        foreach ($quotes as $item) {
            $totalLeads += $item['data']['total_leads'] ?? 0;
        }

        // Fetch advisors and lead statuses
        $advisors = $this->savingsQuoteService->getAdvisors();
        $leadStatuses = $this->savingsQuoteService->getQuoteStatuses();

        return inertia('SavingsQuote/Cards', [
            'quotes' => $quotes,
            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
            'lostReasons' => $lostReasons,
            'leadStatuses' => $leadStatuses,
            'advisors' => $advisors,
            'quoteTypeId' => QuoteTypes::SAVINGS->id(),
            'quoteType' => QuoteTypes::SAVINGS->value,
            'totalCount' => count(request()->all()) > 1 || $hasOtherFilters ? $totalLeads : $this->savingsQuoteService->getData(forExport: true, getTotalCount: true),
            'investmentFrequencies' => $this->savingsQuoteService->getInvestmentFrequencies(),
        ]);
    }
}
