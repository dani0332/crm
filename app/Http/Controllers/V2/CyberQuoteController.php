<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Http\Controllers\Controller;
use App\Services\Quotes\CyberQuoteService;

class CyberQuoteController extends Controller
{
    public function __construct(
        public CyberQuoteService $cyberQuoteService,
    ) {
        $this->middleware('permission:'.PermissionsEnum::CYBER_QUOTES_LIST.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['index']]);
        $this->middleware('permission:'.PermissionsEnum::CYBER_QUOTES_CREATE, ['only' => ['create', 'store']]);
        $this->middleware('permission:'.PermissionsEnum::CYBER_QUOTES_EDIT.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.PermissionsEnum::CYBER_QUOTES_SHOW.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['show']]);
    }

    public function index()
    {
        $advisors = $this->cyberQuoteService->getAdvisors();
        $quoteStatuses = $this->cyberQuoteService->getQuoteStatuses([QuoteStatusEnum::Lost]);
        $renewalBatches = $this->cyberQuoteService->getRenewalBatches();
        $authorizedDays = $this->cyberQuoteService->getPaymentAuthorizedDays();

        $query = $this->cyberQuoteService->getData();

        $count = count(request()->all()) > 1 || $this->cyberQuoteService->hasOtherFilters() ?
                    $query->count() :
                    $this->cyberQuoteService->getData(forExport: true, getTotalCount: true);

        $data = $query->simplePaginate(10)->withQueryString();

        return inertia('CyberQuote/Index', [
            'quotes' => $data,
            'quoteStatuses' => $quoteStatuses,
            'renewalBatches' => $renewalBatches,
            'advisors' => $advisors,
            'totalCount' => $count,
            'authorizedDays' => intval($authorizedDays->value),
        ]);
    }

    public function show($uuid)
    {
        $data = $this->cyberQuoteService->getShowData($uuid);

        return inertia('CyberQuote/Show', $data);
    }
}

