<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\PersonalQuotes\HomeQuoteRequest;
use App\Repositories\HomeQuoteRepository;
use App\Repositories\QuoteStatusRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class HomeQuoteController extends Controller
{
    public function index()
    {
        $homeQuotes = HomeQuoteRepository::getData();
        $advisors = UserRepository::getPersonalQuoteAdvisors(QuoteTypes::HOME->value);
        $quoteStatuses = QuoteStatusRepository::byQuoteTypeId(QuoteTypes::HOME->id())->get();
        $userRoles = auth()->user()->usersroles->pluck('name')->map(fn($item) => strtolower($item));
        $isManager = $userRoles->contains(fn($role) => str_contains($role, 'manager') && ! str_contains($role, 'deputy'));
        $isManualAllocationAllowed = auth()->user()->isAdmin() ?: $isManager;

        $count = $homeQuotes->count();
        $hasOtherFilters = count(array_diff_key(request()->all(), ['page' => ''])) > 0;

        return inertia('HomeQuote/Index', [
            'quotes' => $homeQuotes,
            'leadStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'isManualAllocationAllowed' => $isManualAllocationAllowed,
            'totalCount' => count(request()->all()) > 1 || $hasOtherFilters ? $count : HomeQuoteRepository::getData(true, true),
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
            vAbort($response->msg);
        }

        return redirect('personal-quotes/home/' . $response->quoteUID)->with('message', 'Quote created successfully');
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

        return redirect('personal-quotes/home/' . $uuid)->with('message', 'Quote updated successfully');
    }

    public function cardsView(Request $request)
    {
        return HomeQuoteRepository::cardsView($request);
    }
}
