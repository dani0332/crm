<?php

namespace App\Http\Controllers;

use App\Enums\GenericRequestEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use App\Http\Requests\InsurerProviderNetworkRequest;
use App\Http\Requests\MemberDetailRequest;
use App\Repositories\HealthQuoteRepository;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LostReasonRepository;
use App\Services\CRUDService;
use App\Services\DropdownSourceService;
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

    public function cardsView(Request $request)
    {

        $userTeams = auth()->user()->getUserTeams(auth()->id())->toArray();

        $newBusinessTeam = in_array(TeamNameEnum::RM_NB, $userTeams);
        $renewalsTeam = in_array(TeamNameEnum::RM_RENEWALS, $userTeams);

        $areBothTeamsPresent = $newBusinessTeam && $renewalsTeam;

        $isManagerOrDeputy = auth()->user()->hasAnyRole(RolesEnum::HealthManager, RolesEnum::HealthDeputyManager, RolesEnum::HealthRenewalManager);

        if (($request->is_renewal === null && $areBothTeamsPresent) || ($request->is_renewal === null && $isManagerOrDeputy)) {
            $request->merge(['is_renewal' => quoteTypeCode::yesText]);
        } elseif ($request->is_renewal === null && $newBusinessTeam) {
            $request->merge(['is_renewal' => quoteTypeCode::noText]);
        } elseif ($request->is_renewal === null && $renewalsTeam) {
            $request->merge(['is_renewal' => quoteTypeCode::yesText]);
        }

        $quotes = [
            ['id' => QuoteStatusEnum::Lost, 'title' => quoteStatusCode::LOST, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::Lost, $request)],
            ['id' => QuoteStatusEnum::Allocated, 'title' => quoteStatusCode::ALLOCATED, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::Allocated, $request)],
            ['id' => QuoteStatusEnum::Quoted, 'title' => quoteStatusCode::QUOTED, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::Quoted, $request)],
            ['id' => QuoteStatusEnum::RenewalTermsReceived, 'title' => quoteStatusCode::RENEWAL_TERMS_RECEIVED, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::RenewalTermsReceived, $request)],
            ['id' => QuoteStatusEnum::FollowedUp, 'title' => quoteStatusCode::FOLLOWEDUP, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::FollowedUp, $request)],
            ['id' => QuoteStatusEnum::ApplicationPending, 'title' => quoteStatusCode::APPLICATION_PENDING, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::ApplicationPending, $request)],
            ['id' => QuoteStatusEnum::ApplicationSubmitted, 'title' => quoteStatusCode::APPLICATION_SUBMITTED, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::ApplicationSubmitted, $request)],
            ['id' => QuoteStatusEnum::InNegotiation, 'title' => quoteStatusCode::NEGOTIATION, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::InNegotiation, $request)],
            ['id' => QuoteStatusEnum::PaymentPending, 'title' => quoteStatusCode::PAYMENTPENDING, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::PaymentPending, $request)],
            ['id' => QuoteStatusEnum::PolicyDocumentsPending, 'title' => quoteStatusCode::POLICY_DOCUMENTS_PENDING, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::PolicyDocumentsPending, $request)],
            ['id' => QuoteStatusEnum::TransactionApproved, 'title' => quoteStatusCode::TRANSACTIONAPPROVED, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::TransactionApproved, $request)],
            ['id' => QuoteStatusEnum::PolicySentToCustomer, 'title' => quoteStatusCode::POLICY_SENT_TO_CUSTOMER, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::PolicySentToCustomer, $request)],
            ['id' => QuoteStatusEnum::PolicyIssued, 'title' => quoteStatusCode::POLICY_ISSUED, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::PolicyIssued, $request)],
            ['id' => QuoteStatusEnum::PolicyBooked, 'title' => quoteStatusCode::POLICY_BOOKED, 'data' => getDataAgainstStatus(QuoteTypes::HEALTH->value, QuoteStatusEnum::PolicyBooked, $request)],
        ];

        $quoteStatusEnums = QuoteStatusEnum::asArray();
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        $newBusiness = [
            QuoteStatusEnum::Quoted => 0,
            QuoteStatusEnum::FollowedUp => 1,
            QuoteStatusEnum::ApplicationPending => 2,
            QuoteStatusEnum::ApplicationSubmitted => 3,
            QuoteStatusEnum::InNegotiation => 4,
            QuoteStatusEnum::PaymentPending => 5,
            QuoteStatusEnum::PolicyDocumentsPending => 7,
            QuoteStatusEnum::TransactionApproved => 8,
            QuoteStatusEnum::PolicySentToCustomer => 9,
            QuoteStatusEnum::PolicyBooked => 10,
        ];

        $renewals = [
            QuoteStatusEnum::Lost => 0,
            QuoteStatusEnum::Allocated => 1,
            QuoteStatusEnum::RenewalTermsReceived => 2,
            QuoteStatusEnum::Quoted => 3,
            QuoteStatusEnum::InNegotiation => 4,
            QuoteStatusEnum::ApplicationPending => 5,
            QuoteStatusEnum::PaymentPending => 6,
            QuoteStatusEnum::PolicyDocumentsPending => 7,
            QuoteStatusEnum::TransactionApproved => 8,
            QuoteStatusEnum::PolicySentToCustomer => 9,
            QuoteStatusEnum::PolicyBooked => 10,
        ];

        if ($areBothTeamsPresent || $isManagerOrDeputy) {
            if ($request->is_renewal === quoteTypeCode::yesText) {
                $renewalKeys = array_keys($renewals);
                $quotes = array_filter($quotes, function ($quote) use ($renewalKeys) {
                    return in_array($quote['id'], $renewalKeys);
                });

                // Sort filtered quotes based on the renewals array order
                usort($quotes, function ($a, $b) use ($renewals) {
                    return $renewals[$a['id']] <=> $renewals[$b['id']];
                });
            }
            if ($request->is_renewal === quoteTypeCode::noText) {
                $newBusinessKeys = array_keys($newBusiness);
                $quotes = array_filter($quotes, function ($quote) use ($newBusinessKeys) {
                    return in_array($quote['id'], $newBusinessKeys);
                });

                // Sort filtered quotes based on the newBusiness array order
                usort($quotes, function ($a, $b) use ($newBusiness) {
                    return $newBusiness[$a['id']] <=> $newBusiness[$b['id']];
                });
            }

        } elseif ($newBusinessTeam) {
            $newBusinessKeys = array_keys($newBusiness);
            $quotes = array_filter($quotes, function ($quote) use ($newBusinessKeys) {
                return in_array($quote['id'], $newBusinessKeys);
            });

            // Sort filtered quotes based on the newBusiness array order
            usort($quotes, function ($a, $b) use ($newBusiness) {
                return $newBusiness[$a['id']] <=> $newBusiness[$b['id']];
            });
        } elseif ($renewalsTeam) {
            $renewalKeys = array_keys($renewals);
            $quotes = array_filter($quotes, function ($quote) use ($renewalKeys) {
                return in_array($quote['id'], $renewalKeys);
            });

            // Sort filtered quotes based on the renewals array order
            usort($quotes, function ($a, $b) use ($renewals) {
                return $renewals[$a['id']] <=> $renewals[$b['id']];
            });
        } elseif (array_intersect([TeamNameEnum::EBP, TeamNameEnum::RM_NB, TeamNameEnum::RM_SPEED], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::Lost,
                QuoteStatusEnum::Allocated,
                QuoteStatusEnum::RenewalTermsReceived,
            ])->values()->toArray();
        } elseif (array_intersect([TeamNameEnum::RM_RENEWALS], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::FollowedUp,
                QuoteStatusEnum::ApplicationSubmitted,
                QuoteStatusEnum::TransactionApproved])->values()->toArray();
        }

        $totalLeads = 0;
        $hasOtherFilters = count(array_diff_key(request()->all(), ['page' => ''])) > 0;

        foreach ($quotes as $item) {
            $totalLeads += $item['data']['total_leads'];
        }

        $advisors = app(CRUDService::class)->getAdvisorsByModelType(quoteTypeCode::Health);
        $leadStatuses = app(DropdownSourceService::class)->getDropdownSource('quote_status_id', QuoteTypeId::Health);

        return inertia('HealthQuote/Cards', [
            'quotes' => $quotes,
            'quoteStatusEnum' => $quoteStatusEnums,
            'lostReasons' => $lostReasons,
            'leadStatuses' => $leadStatuses,
            'advisors' => $advisors,
            'teams' => $userTeams,
            'quoteTypeId' => QuoteTypes::HEALTH->id(),
            'quoteType' => QuoteTypes::HEALTH->value,
            'totalCount' => count(request()->all()) > 1 || $hasOtherFilters ? $totalLeads : HealthQuoteRepository::getData(true, true),
            'areBothTeamsPresent' => $areBothTeamsPresent || $isManagerOrDeputy ? true : false,
            'is_renewal' => ($areBothTeamsPresent || $isManagerOrDeputy ? 'Yes' : $renewalsTeam) ? 'Yes' : ($newBusinessTeam ? 'No' : null),
        ]);
    }
}
