<?php

namespace App\Http\Controllers\V2;

use App\Enums\PaymentGatewayIdEnum;
use App\Http\Controllers\Controller;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Services\QuoteDocumentService;
use App\Enums\QuoteTypes;
use App\Http\Requests\DeviceQuoteRequest;
use App\Models\InsuranceProviderPlan;
use App\Models\PaymentStatus;
use App\Services\AMLService;
use App\Services\Quotes\DeviceQuoteService;

class DeviceQuoteController extends Controller
{
    public function __construct(
        private DeviceQuoteService $deviceQuoteService,
        private QuoteDocumentService $quoteDocumentService
    ) {
        // $this->middleware('permission:'.PermissionsEnum::SMART_PHONE_QUOTES_LIST.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['index']]);
        // $this->middleware('permission:'.PermissionsEnum::SMART_PHONE_QUOTES_CREATE, ['only' => ['create', 'store']]);
        // $this->middleware('permission:'.PermissionsEnum::SMART_PHONE_QUOTES_EDIT.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['edit', 'update']]);
        // $this->middleware('permission:'.PermissionsEnum::SMART_PHONE_QUOTES_SHOW.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['show']]);
    }

    public function index()
    {
        $advisors = $this->deviceQuoteService->getAdvisors();
        $quoteStatuses = $this->deviceQuoteService->getQuoteStatuses([QuoteStatusEnum::Lost]);
        $authorizedDays = $this->deviceQuoteService->getPaymentAuthorizedDays();
        $insurerAMLStatus = AMLService::getInsurerAMLStatuses();
        $paymentStatuses = PaymentStatus::where('is_active', 1)
            ->orderBy('text')
            ->get(['id', 'text']);

        $query = $this->deviceQuoteService->getData();

        $count = count(request()->all()) > 1 || $this->deviceQuoteService->hasOtherFilters() ?
                    $query->count() :
                    $this->deviceQuoteService->getData(forExport: true, getTotalCount: true);

        $data = $query->simplePaginate(10)->withQueryString();

        // $deviceCoverages = $this->deviceQuoteService->getDeviceCoverages(); // commented because not in use.

        return inertia('DeviceQuote/Index', [
            'quotes' => $data,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'totalCount' => $count,
            'authorizedDays' => intval($authorizedDays->value),
            'insurerAMLStatus' => $insurerAMLStatus,
            'paymentStatuses' => $paymentStatuses,
            'devicePlans' => InsuranceProviderPlan::where('quote_type_id', (int) QuoteTypes::DEVICE->id())->select(['id', 'code', 'text'])->get(),
            'deviceCoverages' => [],
        ]);
    }

    public function create()
    {
        $data = $this->deviceQuoteService->getFormOptions();

        return inertia('DeviceQuote/Form', $data);
    }

    public function store(DeviceQuoteRequest $request)
    {
        $response = $this->deviceQuoteService->create($request->validated());

        return redirect(route('device-quotes-show', $response->uuid))->with('message', $response->message ?? 'Quote is created successfully.');
    }

    public function edit($uuid)
    {
        $data = $this->deviceQuoteService->getFormOptions();
        $quote = $this->deviceQuoteService->getOne($uuid);

        return inertia('DeviceQuote/Form', array_merge($data, [
            'quote' => $quote,
        ]));
    }

    public function update(DeviceQuoteRequest $request, $uuid)
    {
        $this->deviceQuoteService->update($uuid, $request->validated());

        return redirect(route('device-quotes-show', $uuid))->with('message', 'Quote is updated successfully.');
    }

    public function show($uuid)
    {
        $data = $this->deviceQuoteService->getShowData($uuid);
        @[$documentTypes, $paymentDocument] = $this->quoteDocumentService->getDocumentTypes(QuoteTypeId::Device);
        // dd(array_merge(compact('documentTypes', 'paymentDocument'), $data));
        return inertia('DeviceQuote/Show', array_merge(compact('documentTypes', 'paymentDocument'), $data,
        [
            'paymentGatewayEnum' => PaymentGatewayIdEnum::asArray(),
        ]));
    }

}
