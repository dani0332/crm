<?php

namespace App\Http\Controllers;

use App\Enums\GenericRequestEnum;
use App\Http\Requests\InsurerProviderNetworkRequest;
use App\Http\Requests\MemberDetailRequest;
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
        $request->validate([
            'quoteUID' => 'required',
            'formData' => 'required|array',
            'membersPrice' => 'required',
        ]);

        $quoteUID = $request->quoteUID;
        $planId = $request->formData['plan_id'];
        $copayId = $request->formData['deductibles'];

        $membersBreakDown = [];

        foreach ($request->membersPrice as $member) {
            $array = [
                'healthPlanCoPaymentId' => (int) $copayId,
                'basePrice' => (float) $member['base_price'],
                'loadingPrice' => (float) $member['loading_price'],
            ];

            $membersBreakDown[] = [
                'memberId' => $member['member_id'],
                'ratesPerCopay' => [$array],
            ];
        }

        $planData = [
            'quoteUID' => $quoteUID,
            'update' => false,
            'healthBusinessType' => 'RM',
        ];

        $planData['plans'][] = [
            'planId' => $planId,
            'isManualUpdate' => true,
            'isHidden' => false,
            'selectedCopayId' => (int) $copayId,
            'memberPremiumBreakdown' => $membersBreakDown,
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
        $request->validate([
            'quoteUID' => 'required',
            'planId' => 'required',
            'planDetails' => 'required|array',
            'selectedCopay' => 'sometimes|nullable',
            'defaultCopayId' => 'required_without:selectedCopay',
        ]);

        $response = $this->healthQuoteService->healthPlanModifyV2($request);

        $message = '';
        if ($response['message'] && $response['message'] === 'health quote plan updated successfully') {
            $message = 'Plan has been updated';
        } else {
            if (isset($response->message)) {
                $responseMessage = $response->message;
            } else {
                $responseMessage = $response;
            }
            $message = 'Plan has not been updated '.json_encode($responseMessage);
        }

        return $message;
    }

    public function healthPlanNotifyAgent(Request $request)
    {
        $request->validate([
            'quoteUID' => 'required',
            'planId' => 'required',
            'memberId' => 'required',
            'notifyAgent' => 'required',
            'selectedCopay' => 'sometimes|nullable',
            'defaultCopayId' => 'required_without:selectedCopay',
        ]);

        $response = $this->healthQuoteService->updateNotifyAgentFlag($request);

        $message = '';
        if (gettype($response) == GenericRequestEnum::INTEGER && ($response == 200 || $response == 201)) {
            $message = 'Base Price has been revised';
        } else {
            if (isset($response->message)) {
                $responseMessage = $response->message;
            } else {
                $responseMessage = $response;
            }
            $message = 'Base price has not been updated '.json_encode($responseMessage);
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
            $message = 'Request not processed. '.json_encode($responseMessage);
        }

        return redirect()->back();
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
            $message = 'Request not processed. '.json_encode($responseMessage);
        }

        return redirect()->back();
    }

    public function healthQuoteDeleteMember(Request $request)
    {
        $response = $this->healthQuoteService->healthQuoteDeleteMember($request);

        $message = '';
        if (gettype($response) == GenericRequestEnum::INTEGER && ($response == 200 || $response == 201)) {
            $message = 'Member Updated.';
        } else {
            if (isset($response->message)) {
                $responseMessage = $response->message;
            } else {
                $responseMessage = $response;
            }
            $message = 'Request not processed. '.json_encode($responseMessage);
        }

        return redirect()->back();
    }

    public function plansByInsuranceProvider(Request $request)
    {
        $insuranceProviderId = $request->insuranceProviderId;
        $quoteUuId = $request->quoteUuId;

        $networks = InsuranceProviderRepository::networksByInsuranceProviders([
            'insuranceProviderId' => $insuranceProviderId,
        ]);

        $data = [
            'networks' => $networks->toArray(),
        ];

        return response()->json($data);
    }

    public function plansByNetwork(Request $request)
    {
        $request->validate([
            'network' => 'required',
            'quoteUuId' => 'required',
            'insuranceProviderId' => 'required',
        ]);

        $network = trim($request->network);
        $quoteUuId = $request->quoteUuId;
        $insuranceProviderId = $request->insuranceProviderId;

        $quotePlans = $this->healthQuoteService->getQuotePlans($quoteUuId);

        $quotePlanId = [];
        $healthPlans = [];
        $listQuotePlans = [];

        if (isset($quotePlans->quote->plans)) {
            $listQuotePlans = $quotePlans->quote->plans;
        }

        foreach ($listQuotePlans as $key => $quotePlan) {
            if (! isset($quotePlan->id)) {
                continue;
            }

            if ($quotePlan->providerId == $insuranceProviderId) {

                $quotePlanId[] = $quotePlan->id;

            }
        }

        $networkId = InsuranceProviderRepository::networksIdByInsuranceProvider($insuranceProviderId, $network);

        $healthPlans = $this->healthQuoteService->getNonQuotedHealthPlans($insuranceProviderId, $quotePlanId, $networkId);

        $data = [
            'healthPlans' => $healthPlans,
        ];

        return response()->json($data);
    }

    public function copaysByPlan(Request $request)
    {
        $request->validate([
            'planId' => 'required',
        ]);

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
