<?php

namespace App\Http\Controllers;

use App\Enums\GenericRequestEnum;
use App\Http\Requests\InsurerProviderNetworkRequest;
use App\Repositories\InsuranceProviderRepository;
use App\Services\HealthQuoteService;
use Illuminate\Http\Request;

class HealthQuoteController extends Controller
{
    protected $healthQuoteService;

    public function __construct(HealthQuoteService $healthQuoteService)
    {
        $this->healthQuoteService = $healthQuoteService;
    }

    public function healthPlanCreateQuote(Request $request)
    {
        $planData = [
            'quoteUID' => $request->quoteUID,
            'update' => false,
        ];

        $planData['plans'][] = [
            'planId' => $request->planId,
            'actualPremium' => (float) $request->actualPremium,
            'discountPremium' => 0,
            'isManualUpdate' => false,
            'isManualPremium' => true,
        ];

        $response = $this->healthQuoteService->renewalCreatePlan($planData);

        return $response;
    }

    public function healthPlanUpdateManualProcess(Request $request)
    {
        $response = $this->healthQuoteService->healthPlanModify($request);

        $message = '';
        if (gettype($response) == GenericRequestEnum::INTEGER && ($response == 200 || $response == 201)) {
            $message = 'Plan has been updated';
        } else {
            if (isset($response->message)) {
                $responseMessage = $response->message;
            } else {
                $responseMessage = $response;
            }
            $message = 'Plan has not been updated '.$responseMessage;
        }

        return $message;
    }

    public function plansByInsuranceProvider(Request $request)
    {
        $insuranceProviderId = $request->insuranceProviderId;
        $quoteUuId = $request->quoteUuId;

        $quotePlans = $this->healthQuoteService->getQuotePlans($quoteUuId);

        $quotePlanId = [];
        $listQuotePlans = [];
        if (isset($quotePlans->quotes->plans)) {
            $listQuotePlans = $quotePlans->quotes->plans;
        }

        foreach ($listQuotePlans as $key => $quotePlan) {
            if (! isset($quotePlan->id)) {
                continue;
            }

            $quotePlanId[] = $quotePlan->id;
        }

        $healthPlans = $this->healthQuoteService->getNonQuotedHealthPlans($insuranceProviderId, $quotePlanId);

        return response()->json($healthPlans);
    }

    public function networksByInsuranceProvider(InsurerProviderNetworkRequest $request)
    {
        $networks = InsuranceProviderRepository::networksByInsuranceProviders($request->validated());

        return $networks;
    }

    // TODO: Code Refactor
    public function cardsView(Request $request)
    {
        //        $quotes = [];
//        if (in_array(auth()->user()->sub_team_id, [8]) || auth()->user()->hasRole(RolesEnum::HealthManager) || true) {
//
//            $allowedQuoteStatuses =
//            $commonStatuses = [
//                ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::Quoted],
//                ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::ApplicationPending],
//                ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::InNegotiation],
//                ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::PaymentPending],
//                ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::PolicyIssued],
//            ];
//
//            if (auth()->user()->sub_team_id == 8 || true) {
//                $businessSubTeamStatus = [
//                    ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::FollowedUp],
//                    ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::ApplicationSubmitted],
//                    ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::TransactionApproved],
//                ];
//
//                $allowedQuoteStatuses = array_merge($commonStatuses, $businessSubTeamStatus);
//            }
//
//            if (auth()->user()->sub_team_id == 9) {
//                $renewalSubTeamStatus = [
//                    ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::Lost],
//                    ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::RenewwalTermsReceived],
//                ];
//
//                $allowedQuoteStatuses = array_merge($commonStatuses, $renewalSubTeamStatus);
//            }
//
//            if (auth()->user()->hasRole(RolesEnum::HealthManager)) {
//                $unitManagerRoleStatus = [
//                    ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::Lost],
//                    ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::Allocated],
//                    ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::RenewwalTermsReceived],
//                    ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::ApplicationSubmitted],
//                    ['sorting_order' => 1, 'quote_status' => QuoteStatusEnum::TransactionApproved],
//                ];
//
//                $allowedQuoteStatuses = array_merge($commonStatuses, $unitManagerRoleStatus);
//            }
//
//            dd($allowedQuoteStatuses);
//            $quoteStatus = QuoteStatus::whereIn('id', $healthCommonStatuses)->get();
//        }
//
//
//        $quotes = [
//            [
//                'id' => QuoteStatusEnum::Quoted,
//                'title' => quoteStatusCode::QUOTED,
//                'data' => getDataAgainstStatus(QuoteTypes::HEALTH->name, QuoteStatusEnum::Quoted),
//            ]
//        ];


        $quotes = [];
        $quotes[] = [
            'id' => 8,
            'title' => 'New Lead',
            'data' => getDataAgainstStatus('Health', 8),
        ];
        $quotes[] = [
            'id' => 2,
            'title' => 'Quoted',
            'data' => getDataAgainstStatus('Health', 2),
        ];
        $quotes[] = [
            'id' => 31,
            'title' => 'Qualified',
            'data' => getDataAgainstStatus('Health', 31),
        ];
        $quotes[] = [
            'id' => 25,
            'title' => 'In Negotiation',
            'data' => getDataAgainstStatus('Health', 25),
        ];
        $quotes[] = [
            'id' => 26,
            'title' => 'Application Pending',
            'data' => getDataAgainstStatus('Health', 26),
        ];
        $quotes[] = [
            'id' => 28,
            'title' => 'Payment Pending',
            'data' => getDataAgainstStatus('Health', 28),
        ];
        $quotes[] = [
            'id' => 36,
            'title' => 'Application Submitted',
            'data' => getDataAgainstStatus('Health', 36),
        ];
        $quotes[] = [
            'id' => 15,
            'title' => 'Transaction Approved',
            'data' => getDataAgainstStatus('Health', 15),
        ];
        $quotes[] = [
            'id' => 29,
            'title' => 'Policy Documents Pending',
            'data' => getDataAgainstStatus('Health', 29),
        ];

        return inertia('HealthQuote/Cards', [
            'quotes' => $quotes,
        ]);
    }
}
