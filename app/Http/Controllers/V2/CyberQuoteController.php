<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cyber\CyberQuoteRequest;
use App\Models\PaymentStatus;
use App\Services\AMLService;
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
        $authorizedDays = $this->cyberQuoteService->getPaymentAuthorizedDays();
        $insurerApiStatus = PolicyIssuanceEnum::getInsurerAPIStatuses();
        $issuanceStatuses = PolicyIssuanceEnum::getAPIIssuanceStatuses(getAll: true);
        $insurerAMLStatus = AMLService::getInsurerAMLStatuses();
        $paymentStatuses = PaymentStatus::where('is_active', 1)
            ->orderBy('text')
            ->get(['id', 'text']);

        $query = $this->cyberQuoteService->getData();

        $count = count(request()->all()) > 1 || $this->cyberQuoteService->hasOtherFilters() ?
                    $query->count() :
                    $this->cyberQuoteService->getData(forExport: true, getTotalCount: true);

        $data = $query->simplePaginate(10)->withQueryString();

        return inertia('CyberQuote/Index', [
            'quotes' => $data,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'totalCount' => $count,
            'authorizedDays' => intval($authorizedDays->value),
            'insurerApiStatus' => $insurerApiStatus,
            'issuanceStatuses' => $issuanceStatuses,
            'insurerAMLStatus' => $insurerAMLStatus,
            'paymentStatuses' => $paymentStatuses,
        ]);
    }

    public function create()
    {
        $data = $this->cyberQuoteService->getFormOptions();

        return inertia('CyberQuote/Form', $data);
    }

    public function store(CyberQuoteRequest $request)
    {
        $response = $this->cyberQuoteService->create($request->validated());

        if (! empty($response->errors) || ! empty($response->msg)) {
            vAbort($response->msg);
        }

        return redirect(route('cyber-quotes-show', $response->quoteUID))->with('message', 'Quote is created successfully.');
    }

    public function edit($uuid)
    {
        $data = $this->cyberQuoteService->getFormOptions();
        $quote = $this->cyberQuoteService->getOne($uuid);

        return inertia('CyberQuote/Form', array_merge($data, [
            'quote' => $quote,
        ]));
    }

    public function update(CyberQuoteRequest $request, $uuid)
    {
        $this->cyberQuoteService->update($uuid, $request->validated());

        return redirect(route('cyber-quotes-show', $uuid))->with('message', 'Quote is updated successfully.');
    }

    public function show($uuid)
    {
        $data = $this->cyberQuoteService->getShowData($uuid);

        return inertia('CyberQuote/Show', $data);
    }
}

