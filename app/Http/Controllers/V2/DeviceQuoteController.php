<?php

namespace App\Http\Controllers\V2;

use App\Enums\AssignmentTypeEnum;
use App\Enums\PaymentGatewayIdEnum;
use App\Enums\PermissionsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeviceQuoteRequest;
use App\Models\InsuranceProviderPlan;
use App\Services\AMLService;
use App\Services\LookupService;
use App\Services\QuoteDocumentService;
use App\Services\Quotes\DeviceQuoteService;
use App\Traits\CentralTrait;

class DeviceQuoteController extends Controller
{
    use CentralTrait;

    public function __construct(
        private DeviceQuoteService $deviceQuoteService,
        private QuoteDocumentService $quoteDocumentService
    ) {
        $this->middleware('permission:'.PermissionsEnum::DEVICE_QUOTES_LIST.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['index']]);
        $this->middleware('permission:'.PermissionsEnum::DEVICE_QUOTES_CREATE, ['only' => ['create', 'store']]);
        $this->middleware('permission:'.PermissionsEnum::DEVICE_QUOTES_EDIT.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['edit', 'update']]);
        $this->middleware('permission:'.PermissionsEnum::DEVICE_QUOTES_SHOW.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['show']]);
    }

    public function index()
    {
        $advisors = $this->deviceQuoteService->getAdvisors();
        $quoteStatuses = $this->deviceQuoteService->getQuoteStatuses([QuoteStatusEnum::Lost]);
        $authorizedDays = $this->deviceQuoteService->getPaymentAuthorizedDays();
        $insurerAMLStatus = AMLService::getInsurerAMLStatuses();
        $paymentStatuses = app(LookupService::class)->getPaymentStatuses();
        $query = $this->deviceQuoteService->getData();

        $totalCount = count(request()->all()) > 1 || $this->deviceQuoteService->hasOtherFilters() ? $query->count() :
                    $this->deviceQuoteService->getData(forExport: true, getTotalCount: true);
        $data = $query->simplePaginate(10)->withQueryString();
        $data = $this->deviceQuoteService->postProcessDeviceQuotes($data);

        $deviceCoverages = $this->deviceQuoteService->getDeviceCoverages();

        return inertia('DeviceQuote/Index', [
            'quotes' => $data,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'totalCount' => $totalCount,
            'authorizedDays' => intval($authorizedDays->value),
            'insurerAMLStatus' => $insurerAMLStatus,
            'paymentStatuses' => $paymentStatuses,
            'devicePlans' => InsuranceProviderPlan::where('quote_type_id', (int) QuoteTypes::DEVICE->id())->select(['id', 'code', 'text'])->get(),
            'deviceCoverages' => $deviceCoverages,
            'assignmentTypes' => AssignmentTypeEnum::withLabels(),
            'renewalBatches' => $this->deviceQuoteService->getRenewalBatches(),
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
        $planURL = $this->getEcomQuoteLink(QuoteTypes::DEVICE, $uuid);

        return inertia('DeviceQuote/Show', array_merge($data,
            [
                'paymentGatewayEnum' => PaymentGatewayIdEnum::asArray(),
                'planURL' => $planURL,
            ]));
    }

}
