<?php

namespace App\Http\Controllers\V2;

use App\Enums\InvestmentFrequencyEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\SavingsQuoteRequest;
use App\Services\Quotes\SavingsQuoteService;
use App\Http\Requests\SavingsPlanUpdateRequest;

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
        $quotePlans = $this->savingsQuoteService->getQuotePlans($quoteId);

        if (gettype($quotePlans) == 'string') {
            return response()->json([
                'message' => $quotePlans,
            ], 404);
        }

        if (! isset($quotePlans->quotes->plans)) {
            return response()->json([
                'message' => 'No plans available',
            ], 404);
        }

        $plans = [];
        $planSource = null; // Track whether plan came from regular or lumpsum

        // Handle both regular and lumpsum plans
        if (isset($quotePlans->quotes->plans->regular)) {
            foreach ($quotePlans->quotes->plans->regular as $plan) {
                if ($plan->id == $planId) {
                    $plans[] = $plan;
                    $planSource = 'regular';
                    break;
                }
            }
        }

        if (empty($plans) && isset($quotePlans->quotes->plans->lumpsum)) {
            foreach ($quotePlans->quotes->plans->lumpsum as $plan) {
                if ($plan->id == $planId) {
                    $plans[] = $plan;
                    $planSource = 'lumpsum';
                    break;
                }
            }
        }

        // If plans are in a different structure
        if (empty($plans) && is_array($quotePlans->quotes->plans)) {
            foreach ($quotePlans->quotes->plans as $plan) {
                if ($plan->id == $planId) {
                    $plans[] = $plan;
                    // Default to regular if we can't determine from structure
                    $planSource = 'regular';
                    break;
                }
            }
        }

        if (empty($plans)) {
            return response()->json([
                'message' => 'Plan not found',
            ], 404);
        }

        $foundPlan = $plans[0];

        // Helper function to extract value from eligibility array
        $getEligibilityValue = function ($eligibility, $code) {
            if (! is_array($eligibility)) {
                return 'N/A';
            }

            $found = collect($eligibility)->firstWhere('code', $code);

            return $found ? $found->value : 'N/A';
        };

        // Determine investment frequency based on the plan source
        $investmentFrequency = match ($planSource) {
            'regular' => InvestmentFrequencyEnum::REGULAR->value,
            'lumpsum' => InvestmentFrequencyEnum::LUMPSUM->value,
            default => InvestmentFrequencyEnum::REGULAR->value
        };

        $data = [
            'id' => $foundPlan->id,
            'name' => $foundPlan->name ?? '',
            'providerCode' => $foundPlan->providerCode ?? '',
            'providerName' => $foundPlan->providerName ?? '',
            'planTypeId' => $foundPlan->planTypeId ?? null,
            'investmentFrequency' => ucfirst($investmentFrequency),
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
}
