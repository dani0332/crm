<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\LifeQuoteRequest;
use App\Services\Life\LifeQuoteService;
use App\Services\LifeRevivalService;
use App\Services\LookupService;
use App\Traits\GenericQueriesAllLobs;
use Inertia\Response;

class LifeRevivalQuoteController extends Controller
{
    use GenericQueriesAllLobs;
    public function __construct(
        protected LifeRevivalService $lifeRevivalService,
        protected LifeQuoteService $lifeQuoteService,
    ) {}

    public function index(): Response
    {
        return inertia('LifeRevivalQuote/Index', [
            'quotes' => $this->lifeRevivalService->getPaginatedRevivalQuotes(),
            'formOptions' => $this->lifeRevivalService->getIndexFormOptions(),
        ]);
    }

    public function show($uuid): Response
    {
        $quote = $this->lifeQuoteService->getQuoteBy('uuid', $uuid);
        abort_if(! $quote, 404);

        $data = $this->lifeQuoteService->getShowData($uuid);

        return inertia('LifeRevivalQuote/Show', $data);
    }

    public function edit($uuid): Response
    {
        $data = $this->lifeQuoteService->getEditData($uuid);
        $subSources = app(LookupService::class)->getSubSource();

        $data['subSources'] = $subSources;
        $data['leadSourceParams'] = [];

        return inertia('LifeRevivalQuote/Form', $data);
    }

    public function update(LifeQuoteRequest $request, $uuid)
    {
        $this->lifeQuoteService->updateLifeQuote($uuid, $request->validated());

        return redirect('personal-quotes/life-revival/'.$uuid)->with('message', 'Quote updated successfully');
    }

}
