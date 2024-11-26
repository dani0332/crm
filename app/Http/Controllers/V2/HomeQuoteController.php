<?php

namespace App\Http\Controllers\V2;

use App\Enums\GenericRequestEnum;
use App\Enums\quoteStatusCode;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\TeamNameEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\HomePlanUpdateRequest;
use App\Http\Requests\ManualPlanToggleRequest;
use App\Http\Requests\PersonalQuotes\HomeQuoteRequest;
use App\Repositories\HomeQuoteRepository;
use App\Repositories\LostReasonRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;
use App\Services\CRUDService;
use App\Services\DropdownSourceService;
use App\Services\HomeQuoteService;
use Illuminate\Http\Request;

class HomeQuoteController extends Controller
{
    public function index()
    {
        $homeQuotes = HomeQuoteRepository::getData();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::HOME->value);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::HOME->id())->get();

        $user = auth()->user();
        $isManualAllocationAllowed = $user->isAdmin() || $user->isManagerOrDeputy();

        return inertia('HomeQuote/Index', [
            'quotes' => $homeQuotes,
            'leadStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'isManualAllocationAllowed' => $isManualAllocationAllowed,
        ]);
    }

    public function create()
    {
        $data = HomeQuoteRepository::getFormOptions();

        return inertia('HomeQuote/Form', $data);
    }

    public function store(HomeQuoteRequest $request)
    {
        $response = HomeQuoteRepository::create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            dd($response->errors, $response->msg);
            vAbort($response->msg);
        }

        return redirect('personal-quotes/home/'.$response->quoteUID)->with('message', 'Quote created successfully');
    }

    public function show($uuid)
    {
        $quote = HomeQuoteRepository::getBy('uuid', $uuid);
        $quoteWithData = HomeQuoteRepository::getShowFormOptions($quote);

        return inertia('HomeQuote/Show', $quoteWithData);
    }

    public function edit($uuid)
    {
        $data = HomeQuoteRepository::getFormOptions();
        $quote = HomeQuoteRepository::getBy('uuid', $uuid);

        return inertia(
            'HomeQuote/Form',
            [
                ...$data,
                'quote' => $quote,
            ]
        );
    }

    public function update(HomeQuoteRequest $request, $uuid)
    {
        HomeQuoteRepository::update($uuid, $request->validated());
        dd('Done');
        // return redirect('personal-quotes/home/' . $uuid)->with('message', 'Quote updated successfully');
    }

    public function planDetails($quoteId, $planId)
    {
        return app(HomeQuoteService::class)->planDetails($quoteId, $planId);
    }

    public function manualPlanToggle(ManualPlanToggleRequest $request, $quoteType)
    {
        $response = app(HomeQuoteService::class)->updateManualPlansBulk($request);

        if (gettype($response) == GenericRequestEnum::INTEGER && ($response == 200 || $response == 201)) {
            return redirect()->back()->with('success', 'Plan has been updated');
        } else {
            if (isset($response->message)) {
                $responseMessage = $response->message;
            } else {
                $responseMessage = $response;
            }

            return redirect()->back()->with('message', $responseMessage);
        }
    }

    public function homePlanUpdateManualProcess(HomePlanUpdateRequest $request)
    {
        // Get the response from the service
        $response = app(HomeQuoteService::class)->homePlanModify($request);

        // Check if the response is a success (e.g., 200 or 201)
        if (is_int($response) && in_array($response, [200, 201])) {
            return response()->json([
                'message' => 'Plan has been updated successfully',
            ], 200);
        }

        // Determine the response message
        $responseMessage = 'Unknown error';
        if (is_object($response) && isset($response->message)) {
            $responseMessage = $response->message;
        } elseif (is_string($response)) {
            $responseMessage = $response;
        }

        // Return error as JSON for API consumption
        return response()->json([
            'message' => 'Home Plan has not been updated. '.$responseMessage,
        ], 400); // 400 Bad Request or any relevant error code
    }

    public function cardsView(Request $request)
    {
        // Initialize the quotes array
        $quotes = [
            ['id' => QuoteStatusEnum::NewLead, 'title' => quoteStatusCode::NEW_LEAD, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::NewLead, $request)],
            ['id' => QuoteStatusEnum::Allocated, 'title' => quoteStatusCode::ALLOCATED, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::Allocated, $request)],
            ['id' => QuoteStatusEnum::Quoted, 'title' => quoteStatusCode::QUOTED, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::Quoted, $request)],
            ['id' => QuoteStatusEnum::FollowedUp, 'title' => quoteStatusCode::FOLLOWEDUP, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::FollowedUp, $request)],
            ['id' => QuoteStatusEnum::InNegotiation, 'title' => quoteStatusCode::NEGOTIATION, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::InNegotiation, $request)],
            ['id' => QuoteStatusEnum::PaymentPending, 'title' => quoteStatusCode::PAYMENTPENDING, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::PaymentPending, $request)],
            ['id' => QuoteStatusEnum::TransactionApproved, 'title' => quoteStatusCode::TRANSACTIONAPPROVED, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::TransactionApproved, $request)],
            ['id' => QuoteStatusEnum::PolicyIssued, 'title' => quoteStatusCode::POLICY_ISSUED, 'data' => getDataAgainstStatus(QuoteTypes::HOME->value, QuoteStatusEnum::PolicyIssued, $request)],
        ];

        // Fetch lost reasons
        $lostReasons = LostReasonRepository::orderBy('text', 'asc')->get();

        // Get the current user's ID and teams
        $userId = auth()->id();
        $userTeams = auth()->user()->getUserTeams($userId)->toArray();

        // Filter quotes based on user teams
        if (array_intersect([TeamNameEnum::HOME], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::Allocated,
                QuoteStatusEnum::InNegotiation,
            ])->values()->toArray();
        } elseif (array_intersect([TeamNameEnum::HOME_RENEWALS], $userTeams)) {
            $quotes = collect($quotes)->whereNotIn('id', [
                QuoteStatusEnum::NewLead,
                QuoteStatusEnum::InNegotiation,
            ])->values()->toArray();
        }

        // Calculate total leads
        $totalLeads = 0;
        $hasOtherFilters = count(array_diff_key(request()->all(), ['page' => ''])) > 0;

        foreach ($quotes as $item) {
            $totalLeads += $item['data']['total_leads'];
        }

        // Fetch advisors and lead statuses
        $advisors = app(CRUDService::class)->getAdvisorsByModelType(quoteTypeCode::Home);
        $leadStatuses = app(DropdownSourceService::class)->getDropdownSource('quote_status_id', QuoteTypeId::Home);

        return inertia('HomeQuote/Cards', [
            'quotes' => $quotes,
            'quoteStatusEnum' => QuoteStatusEnum::asArray(),
            'lostReasons' => $lostReasons,
            'leadStatuses' => $leadStatuses,
            'advisors' => $advisors,
            'teams' => $userTeams,
            'quoteTypeId' => QuoteTypes::HOME->id(),
            'quoteType' => QuoteTypes::HOME->value,
            'totalCount' => count(request()->all()) > 1 || $hasOtherFilters ? $totalLeads : HomeQuoteRepository::GetData(true, true),
        ]);
    }
}
