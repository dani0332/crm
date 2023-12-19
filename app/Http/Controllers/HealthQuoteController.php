<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enums\GenericRequestEnum;
use App\Services\HealthQuoteService;
use App\Http\Requests\MemberDetailRequest;
use App\Repositories\InsuranceProviderRepository;
use App\Http\Requests\InsurerProviderNetworkRequest;

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

    public function healthPlanUpdateManualProcessV2(Request $request)
    {
        $response = $this->healthQuoteService->healthPlanModifyV2($request);

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

    public function healthQuoteAddMember(MemberDetailRequest $request)
    {
        $request->validated();

        $response = $this->healthQuoteService->healthQuoteAddMember($request);

        $message = '';
        if (gettype($response) == GenericRequestEnum::INTEGER && ($response == 200 || $response == 201)) {
            $message = 'Member Added.';
        } else {
            if (isset($response->message)) {
                $responseMessage = $response->message;
            } else {
                $responseMessage = $response;
            }
            $message = 'Request not processed. '.$responseMessage;
        }

        return $message;
    }

    public function healthQuoteUpdateMember(MemberDetailRequest $request)
    {
        $request->validated();

        $response = $this->healthQuoteService->healthQuoteUpdateMember($request);

        $message = '';
        if (gettype($response) == GenericRequestEnum::INTEGER && ($response == 200 || $response == 201)) {
            $message = 'Member Updated.';
        } else {
            if (isset($response->message)) {
                $responseMessage = $response->message;
            } else {
                $responseMessage = $response;
            }
            $message = 'Request not processed. '.$responseMessage;
        }

        return $message;
    }

    public function plansByInsuranceProvider(Request $request)
    {
        $insuranceProviderId = $request->insuranceProviderId;
        $quoteUuId = $request->quoteUuId;

        $networks = InsuranceProviderRepository::networksByInsuranceProviders([
            'insuranceProviderId' => $insuranceProviderId,
        ]);

        // dd($networks);

        // $quotePlans = $this->healthQuoteService->getQuotePlans($quoteUuId);
        // dd($quotePlans);

        // $quotePlanId = [];
        // $healthPlans = [];
        // $listQuotePlans = [];
        // $copays = [];

        // if (isset($quotePlans->quote->plans)) {
        //     $listQuotePlans = $quotePlans->quote->plans;
        // }

        // dd($listQuotePlans);
        // return;

        // foreach ($listQuotePlans as $key => $quotePlan) {
        //     if (! isset($quotePlan->id)) {
        //         continue;
        //     }

        //     // dd($quotePlan);

        //     $quotePlanId[] = $quotePlan->id;

        //     $copays[$quotePlan->id] = $quotePlan->coPayments;
        // }

        // dd($healthPlans);

        // $healthPlans = $this->healthQuoteService->getNonQuotedHealthPlans($insuranceProviderId, $quotePlanId);

        // dd($healthPlans->toArray());

        $data = [
            // 'healthPlans' => $healthPlans,
            'networks' => $networks->toArray(),
            // 'copays' => $copays,
        ];

        // dd($data);
        return response()->json($data);
    }

    public function plansByNetwork(Request $request)
    {
        $network = trim($request->network);
        $quoteUuId = $request->quoteUuId;
        $insuranceProviderId = $request->insuranceProviderId;

        // dd($request->toArray());

        // $networks = InsuranceProviderRepository::networksByInsuranceProviders([
        //     'insuranceProviderId' => $insuranceProviderId,
        // ]);

        // dd($networks);

        $quotePlans = $this->healthQuoteService->getQuotePlans($quoteUuId);
        // dd($quotePlans);

        $quotePlanId = [];
        $healthPlans = [];
        $listQuotePlans = [];
        // $copays = [];

        if (isset($quotePlans->quote->plans)) {
            $listQuotePlans = $quotePlans->quote->plans;
        }

        // dd($listQuotePlans);
        // return;

        foreach ($listQuotePlans as $key => $quotePlan) {
            if (! isset($quotePlan->id)) {
                continue;
            }
            // dd($quotePlan->eligibilityName);

            if (isset($quotePlan->eligibilityName) && $quotePlan->eligibilityName === $network
            && $quotePlan->providerId == $insuranceProviderId) {

                $healthPlans[] = [
                    'id' => $quotePlan->id,
                    'text' => $quotePlan->name,
                ];
            }

            // $quotePlanId[] = $quotePlan->id;

        }

        // dd($healthPlans);

        // if ($insuranceProviderId){
        $healthPlans = $this->healthQuoteService->getNonQuotedHealthPlans($insuranceProviderId, $quotePlanId);
        // }else {
        //     return response()->json(['message' => 'Insurnace provider not found.']);
        // }

        // dd($healthPlans->toArray());

        $data = [
            'healthPlans' => $healthPlans,
            // 'networks' => $networks->toArray(),
            // 'copays' => $copays,
        ];

        // dump($insuranceProviderId);
        // dd($data);
        return response()->json($data);
    }

    public function copaysByPlan(Request $request)
    {
        $healthPlanId = $request->planId;

        $copays = $this->healthQuoteService->getCopaysByPlanId($healthPlanId);

        $data = [
            'copays' => $copays,
        ];

        return response()->json($data);
    }

    public function networksByInsuranceProvider(InsurerProviderNetworkRequest $request)
    {
        $networks = InsuranceProviderRepository::networksByInsuranceProviders($request->validated());

        return $networks;
    }

    // TODO: Code Refactor
    public function cardsView(Request $request)
    {
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
