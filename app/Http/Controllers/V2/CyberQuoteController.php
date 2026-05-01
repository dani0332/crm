<?php

declare(strict_types=1);

namespace App\Http\Controllers\V2;

use App\Enums\PermissionsEnum;
use App\Enums\PolicyIssuanceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cyber\CyberQuoteRequest;
use App\Jobs\OCB\SendCyberOCBIntroEmailJob;
use App\Models\InsuranceProviderPlan;
use App\Services\CustomerAddressService;
use App\Services\CustomerService;
use App\Services\Logger\LoggerService;
use App\Services\MACRMService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
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
        $insurerAMLStatus = $this->cyberQuoteService->getInsurerAMLStatuses();
        $paymentStatuses = $this->cyberQuoteService->getPaymentStatuses();

        $query = $this->cyberQuoteService->getData();

        $count = count(request()->all()) > 1 || $this->cyberQuoteService->hasOtherFilters() ?
                    $query->count() :
                    $this->cyberQuoteService->getData(forExport: true, getTotalCount: true);

        $data = $query->simplePaginate(10)->withQueryString();
        $data = $this->cyberQuoteService->postProcessCyberQuotes($data);

        $cyberCoverages = $this->cyberQuoteService->getCyberCoverages();

        return inertia('CyberQuote/Index', [
            'quotes' => $data,
            'quoteStatuses' => $quoteStatuses,
            'advisors' => $advisors,
            'totalCount' => $count,
            'authorizedDays' => intval($authorizedDays->value),
            'insurerAMLStatus' => $insurerAMLStatus,
            'paymentStatuses' => $paymentStatuses,
            'cyberPlans' => InsuranceProviderPlan::where('quote_type_id', (int) QuoteTypes::CYBER->id())->select(['id', 'code', 'text'])->get(),
            'cyberCoverages' => $cyberCoverages,
            'apiIssuanceStatuses' => PolicyIssuanceEnum::getAPIIssuanceStatuses(null, true),
            'insurerApiStatuses' => app(PolicyIssuanceService::class)->getInsurerAPIStatuses(),
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

        $customerId = app(CustomerService::class)->getCustomerIdByEmail($request->email);
        $addressObj = $request->input('addressObj', []);
        $addressType = $addressObj['address_type'] ?? null;
        if ($customerId && in_array($addressType, ['Home', 'Office'], true) && ! empty(array_filter((array) $addressObj))) {
            app(CustomerAddressService::class)->createOrUpdateCustomerAddress(
                $addressObj,
                $customerId,
                $response->quoteUID,
                $this->cyberQuoteService->quoteType->id()
            );
        }

        return redirect(route('cyber-quotes-show', $response->quoteUID))->with('message', 'Quote is created successfully.');
    }

    public function edit($uuid)
    {
        $data = $this->cyberQuoteService->getFormOptions();
        $quote = $this->cyberQuoteService->getOne($uuid);
        $quoteType = $this->cyberQuoteService->quoteType;

        $customerAddressData = app(CustomerService::class)->getCustomerAddressData($quote);

        $courierQuoteResponse = app(MACRMService::class)->getCourierQuoteStatus($quote->uuid, $quoteType->id());
        $courierQuoteStatus = isset($courierQuoteResponse['data']['status'])
            ? $courierQuoteResponse['data']['status']
            : 'Pending';

        return inertia('CyberQuote/Form', array_merge($data, [
            'quote' => $quote,
            'customerAddressData' => $customerAddressData,
            'courierQuoteStatus' => $courierQuoteStatus,
        ]));
    }

    public function update(CyberQuoteRequest $request, $uuid)
    {
        $quote = $this->cyberQuoteService->update($uuid, $request->validated());

        $quoteType = $this->cyberQuoteService->quoteType;
        app(CustomerAddressService::class)->syncCustomerAddress($request, $quoteType, $quote, $request->email);

        return redirect(route('cyber-quotes-show', $uuid))->with('message', 'Quote is updated successfully.');
    }

    public function show($uuid)
    {
        $data = $this->cyberQuoteService->getShowData($uuid);

        return inertia('CyberQuote/Show', $data);
    }
    public function sendEmailOneClickBuy($quoteUuid)
    {
        LoggerService::startQuoteLogging(QuoteTypes::CYBER->refId($quoteUuid));
        LoggerService::info(self::class.' - sendEmailOneClickBuy OCB email sending started for quote');

        SendCyberOCBIntroEmailJob::dispatch($quoteUuid);
        LoggerService::info(self::class.' - sendEmailOneClickBuy OCB email Job dispatched for quote ');

        LoggerService::endLogging();

        return response()->json(['success' => 'OCB email sent to customer']);
    }
}
