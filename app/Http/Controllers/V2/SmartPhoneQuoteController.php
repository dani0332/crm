<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Enums\PermissionsEnum;
use App\Services\Quotes\DeviceQuoteService;
use App\Services\AMLService;

class DeviceQuoteController extends Controller
{
    public function __construct(
        public DeviceQuoteService $deviceQuoteService,
    ) {
        // $this->middleware('permission:'.PermissionsEnum::SMART_PHONE_QUOTES_LIST.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['index']]);
        // $this->middleware('permission:'.PermissionsEnum::SMART_PHONE_QUOTES_CREATE, ['only' => ['create', 'store']]);
        // $this->middleware('permission:'.PermissionsEnum::SMART_PHONE_QUOTES_EDIT.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['edit', 'update']]);
        // $this->middleware('permission:'.PermissionsEnum::SMART_PHONE_QUOTES_SHOW.'|'.PermissionsEnum::VIEW_ALL_LEADS, ['only' => ['show']]);
    }
    public function index()
    {
        $quotes = $this->deviceQuoteService->getData();
        $quoteStatuses = $this->deviceQuoteService->getQuoteStatuses();
        $advisors = $this->deviceQuoteService->getAdvisors();
        $renewalBatches = $this->deviceQuoteService->getRenewalBatches();   
        $authorizedDays = $this->deviceQuoteService->getPaymentAuthorizedDays();
        $insurerAMLStatus = AMLService::getInsurerAMLStatuses();

        return inertia('DeviceQuote/Index', [
            'quotes' => $quotes,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'renewalBatches' => $renewalBatches,
            'authorizedDays' => $authorizedDays,
            'insurerAMLStatus' => $insurerAMLStatus,
        ]);
    }

}
