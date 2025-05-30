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

    public function planDetails($quoteId, $planId)
    {
        $quotePlans = $this->savingsQuoteService->getQuotePlans($quoteId);

        if (gettype($quotePlans) == 'string') {
            return response()->json([
                'message' => $quotePlans,
            ], 404);
        }

        if (!isset($quotePlans->quotes->plans)) {
            return response()->json([
                'message' => 'No plans available',
            ], 404);
        }

        $plans = [];

        // Handle both regular and lumpsum plans
        if (isset($quotePlans->quotes->plans->regular)) {
            $plans = array_merge($plans, $quotePlans->quotes->plans->regular);
        }

        if (isset($quotePlans->quotes->plans->lumpsum)) {
            $plans = array_merge($plans, $quotePlans->quotes->plans->lumpsum);
        }

        // If plans are in a different structure
        if (empty($plans) && is_array($quotePlans->quotes->plans)) {
            $plans = $quotePlans->quotes->plans;
        }

        $foundPlan = null;
        foreach ($plans as $plan) {
            if ($plan->id == $planId) {
                $foundPlan = $plan;
                break;
            }
        }

        if (!$foundPlan) {
            return response()->json([
                'message' => 'Plan not found',
            ], 404);
        }

        // Helper function to extract value from eligibility array
        $getEligibilityValue = function($eligibility, $code) {
            if (!is_array($eligibility)) return 'N/A';

            $found = collect($eligibility)->firstWhere('code', $code);
            return $found ? $found->value : 'N/A';
        };

        $data = [
            'id' => $foundPlan->id,
            'name' => $foundPlan->name ?? '',
            'providerCode' => $foundPlan->providerCode ?? '',
            'providerName' => $foundPlan->providerName ?? '',
            'planTypeId' => $foundPlan->planTypeId ?? null,
            'investmentFrequency' => $foundPlan->planTypeId === 9961 ? 'Regular' : 'Lumpsum',
            'currency' => 'USD',
            'minimumInvestment' => $getEligibilityValue($foundPlan->eligibility ?? [], 'minimum_investment_amount'),
            'policyTerm' => $getEligibilityValue($foundPlan->eligibility ?? [], 'policy_term'),
            'eligibility' => $foundPlan->eligibility ?? [],
            'includedBenefits' => $foundPlan->includedBenefits ?? [],
            'keyFeatureDocument' => $foundPlan->keyFeatureDocument ?? [],
            'description' => $foundPlan->description ?? '',
        ];

        return response()->json($data, 200);
    }
}
