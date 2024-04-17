<?php

namespace App\Http\Controllers;

use App\Enums\GenericRequestEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Http\Requests\InsurerProviderNetworkRequest;
use App\Repositories\HealthQuoteRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LostReasonRepository;
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

    public function cardsView(Request $request)
    {
        $quotes = [
            ['id' => QuoteStatusEnum::Lost, 'title' => quoteStatusCode::LOST, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::Lost, $request)],
            ['id' => QuoteStatusEnum::Allocated, 'title' => quoteStatusCode::ALLOCATED, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::Allocated, $request)],
            ['id' => QuoteStatusEnum::RenewalTermsReceived, 'title' => quoteStatusCode::RENEWAL_TERMS_RECEIVED, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::RenewalTermsReceived, $request)],
            ['id' => QuoteStatusEnum::Quoted, 'title' => quoteStatusCode::QUOTED, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::Quoted, $request)],
            ['id' => QuoteStatusEnum::FollowedUp, 'title' => quoteStatusCode::FOLLOWEDUP, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::FollowedUp, $request)],
            ['id' => QuoteStatusEnum::ApplicationPending, 'title' => quoteStatusCode::APPLICATION_PENDING, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::ApplicationPending, $request)],
            ['id' => QuoteStatusEnum::ApplicationSubmitted, 'title' => quoteStatusCode::APPLICATION_SUBMITTED, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::ApplicationSubmitted, $request)],
            ['id' => QuoteStatusEnum::InNegotiation, 'title' => quoteStatusCode::NEGOTIATION, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::InNegotiation, $request)],
            ['id' => QuoteStatusEnum::PaymentPending, 'title' => quoteStatusCode::PAYMENTPENDING, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::PaymentPending, $request)],
            ['id' => QuoteStatusEnum::TransactionApproved, 'title' => quoteStatusCode::TRANSACTIONAPPROVED, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::TransactionApproved, $request)],
            ['id' => QuoteStatusEnum::PolicyIssued, 'title' => quoteStatusCode::POLICY_ISSUED, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::PolicyIssued, $request)],
        ];

        $quoteStatusEnums = QuoteStatusEnum::asArray();
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();
        // $isManagerOrAdminAccess = auth()->user()->hasAnyRole([RolesEnum::HealthManager, RolesEnum::Admin]);
        $userId = auth()->id();
        $userTeams = auth()->user()->getUserTeams($userId)->toArray();
        if(array_intersect([TeamNameEnum::EBP, TeamNameEnum::RM_NB, TeamNameEnum::RM_SPEED], $userTeams)){
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::Lost,
                QuoteStatusEnum::Allocated,
                QuoteStatusEnum::RenewalTermsReceived,
            ])->values()->toArray();
        }elseif(array_intersect([TeamNameEnum::RM_RENEWALS], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::FollowedUp,
                QuoteStatusEnum::ApplicationSubmitted,
                QuoteStatusEnum::TransactionApproved])->values()->toArray();
        }
        // dd($userTeams);
        // if (! $isManagerOrAdminAccess) {
        //     if (auth()->user()->hasAnyRole([RolesEnum::HealthNewBusinessAdvisor, RolesEnum::HealthNewBusinessManager])) {
        //         $quotes = collect($quotes)->whereNotIn('id', [
        //             QuoteStatusEnum::Lost,
        //             QuoteStatusEnum::Allocated,
        //             QuoteStatusEnum::RenewalTermsReceived,
        //         ])->values()->toArray();

        //     } elseif (auth()->user()->hasAnyRole([RolesEnum::HealthRenewalAdvisor, RolesEnum::HealthRenewalManager])) {
        //         $quotes = collect($quotes)->whereNotIn('id', [
        //             QuoteStatusEnum::FollowedUp,
        //             QuoteStatusEnum::ApplicationSubmitted,
        //             QuoteStatusEnum::TransactionApproved])->values()->toArray();
        //     } else {
        //         $quotes = [];
        //     }
        // }

        return inertia('HealthQuote/Cards', [
            'quotes' => $quotes,
            'quoteStatusEnum' => $quoteStatusEnums,
            'lostReasons' => $lostReasons,
            'quoteTypeId' => QuoteTypes::HEALTH->id(),
            'quoteType' => QuoteTypes::HEALTH->value,
            'totalCount' => HealthQuoteRepository::getData(true, true),
        ]);
    }
}
