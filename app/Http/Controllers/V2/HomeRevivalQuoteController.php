<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\PersonalQuotes\HomeQuoteRequest;
use App\Repositories\HomeQuoteRepository;
use App\Services\HomeRevivalService;
use App\Services\LookupService;
use App\Traits\GenericQueriesAllLobs;
use Inertia\Response;

class HomeRevivalQuoteController extends Controller
{
    use GenericQueriesAllLobs;

    public function __construct(
        protected HomeRevivalService $homeRevivalService,
    ) {}

    public function index(): Response
    {
        $user = auth()->user();

        return inertia('HomeRevivalQuote/Index', [
            'quotes' => $this->homeRevivalService->getPaginatedRevivalQuotes(),
            'formOptions' => $this->homeRevivalService->getIndexFormOptions(),
            'isManualAllocationAllowed' => $user->isAdmin() || $user->isManagerOrDeputy(),
        ]);
    }

    public function show($uuid): Response
    {
        $quote = HomeQuoteRepository::getBy('uuid', $uuid);
        abort_if(! $quote, 404);

        $data = HomeQuoteRepository::getShowFormOptions($quote);

        return inertia('HomeRevivalQuote/Show', $data);
    }

    public function edit($uuid): Response
    {
        $data = HomeQuoteRepository::getFormOptions();
        $quote = HomeQuoteRepository::getBy('uuid', $uuid);
        $subSources = app(LookupService::class)->getSubSource();

        return inertia('HomeRevivalQuote/Form', [
            ...$data,
            'subSources' => $subSources,
            'leadSourceParams' => [],
            'quote' => $quote,
        ]);
    }

    public function update(HomeQuoteRequest $request, $uuid)
    {
        HomeQuoteRepository::update($uuid, $request->validated());

        return redirect('personal-quotes/home-revival/'.$uuid)->with('message', 'Quote updated successfully');
    }
}
