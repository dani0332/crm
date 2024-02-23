<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\PersonalQuote;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\PersonalQuoteRepository;
use App\Repositories\PolicyIssuanceStatusRepository;
use App\Repositories\SendUpdateLogDetailsRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Services\LookupService;
use App\Services\SendUpdateLogService;
use Illuminate\Http\Request;

class SendUpdateLogController extends Controller
{
    private object $sendUpdateLogService;
    public function __construct(SendUpdateLogService $sendUpdateLogService)
    {
        $this->sendUpdateLogService = $sendUpdateLogService;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $response = SendUpdateLogRepository::create($data);

        if (! empty($response->message)) {
            vAbort($response->message);
        }

        $this->updateQuoteLeadStatus($data, 'create');

        return redirect(route('send-update-logs.show', ['uuid' => $response->uuid, 'refURL' => $data['refURL']]));
    }

    /**
     * Display the specified resource.
     */
    public function show($uuid)
    {
        $sendUpdateLog = SendUpdateLogRepository::getLogByUuid($uuid);

        $quoteTypeId = $sendUpdateLog->quote_type_id;

        $sendUpdateOptions = (new LookupService)->getSendUpdateOptions($quoteTypeId);
        $insuranceProviders = InsuranceProviderRepository::byQuoteTypeMapping($quoteTypeId);

        $quoteType = QuoteTypes::getName($quoteTypeId)->value;

        $quote = PersonalQuoteRepository::getById($sendUpdateLog->personal_quote_id);

        if (in_array($quoteType, [QuoteTypes::CAR, QuoteTypes::HEALTH, QuoteTypes::TRAVEL])) {
            $quote->load('plan.insuranceProvider');
        }

        $issuanceStatuses = PolicyIssuanceStatusRepository::getColumns(['id', 'text']);

        if (checkPersonalQuotes($quoteType)) {
            $repository = 'App\\Repositories\\'.$quoteType.'QuoteRepository';
            $realQuote = $repository::getBy('uuid', $quote->uuid);
        } else {
            $quoteServiceFile = app(getServiceObject($quoteType));
            $realQuote = $quoteServiceFile->getEntity($quoteType, $quote->uuid);
        }

        $payments = $this->sendUpdateLogService->getPayments($realQuote->id, $realQuote->uuid, $quoteType);

        $bookingDetails = [];
        if ($payments && is_countable($payments) && count($payments) > 0) {
            // it will also fetch broker_invoice_number and invoice_description, from lead detail page, lead detail broker_invoice_number will
            // always same as ```send update log details``` broker_invoice_number but invoice_description will be overwritten from ```send update log details``` page.
            $bookingDetails = $this->sendUpdateLogService->getInvoiceDescription($realQuote, $quoteType, $payments[0]['insurance_provider_id']);
            // it will get all invoice_descriptions for booking details
            $paymentInvoices = collect($payments)->pluck('insurer_tax_number');
        }

        if (isset($sendUpdateLog->details) && count($sendUpdateLog->details) > 0) {
            $bookingDetails = array_merge($bookingDetails, $sendUpdateLog->details[0]->data);
            $bookingDetails['type'] = $sendUpdateLog->details[0]->type;
        }

        return inertia('SendUpdateLog/Show', [
            'quote' => $quote,
            'quoteType' => $quoteType,
            'sendUpdateLog' => $sendUpdateLog,
            'issuanceStatuses' => $issuanceStatuses,
            'sendUpdateOptions' => $sendUpdateOptions,
            'insuranceProviders' => $insuranceProviders,
            'sendUpdateStatusEnum' => SendUpdateLogStatusEnum::asArray(),
            'realQuote' => $realQuote,
            'isNegativeValue' => $this->sendUpdateLogService->isNegativeValue($sendUpdateLog),
            'bookingDetails' => $bookingDetails,
            'updateToCustomerBtn' => count($sendUpdateLog->details) > 0,
            'paymentInvoices' => $paymentInvoices ?? [],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->all();

        $log = SendUpdateLogRepository::updateLog($id, $data);

        if (isset($log->message) && !empty($log->message)) {
            vAbort($log->message);
        }

        $this->updateQuoteLeadStatus($data, 'update');

        return redirect()->back();
    }

    public function updateQuoteLeadStatus($data, $type)
    {
        $quoteUuid = $data['quote_uuid'];

        $quoteTypeId = $data['quote_type_id'];
        
        $selectedType = $data['childCategory']['slug'];
        
        $subType = $data['childCategory']['option'];

        $model = PersonalQuote::class;
        
        if ($type === 'create') {

            switch ($selectedType) {
                case SendUpdateLogStatusEnum::EF:
                    if ($subType && $subType['slug'] === 'MPC') {
                        $model::where(['uuid' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                            'quote_status_id' => QuoteStatusEnum::CancellationPending
                        ]);
                    }
                    break;
                case SendUpdateLogStatusEnum::CI:
                case SendUpdateLogStatusEnum::CIR:
                    $model::where(['uuid' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                        'quote_status_id' => QuoteStatusEnum::CancellationPending
                    ]);
                    break;
            }
        } else {
            
            switch ($selectedType) {
                case SendUpdateLogStatusEnum::EF:
                case SendUpdateLogStatusEnum::CI:
                    if ($data['status'] === SendUpdateLogStatusEnum::UPDATE_BOOKED) {
                        $model::where(['uuid' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                            'quote_status_id' => QuoteStatusEnum::PolicyCancelled
                        ]);
                    }
                    break;
                case SendUpdateLogStatusEnum::CIR:
                    if ($data['status'] === SendUpdateLogStatusEnum::UPDATE_BOOKED) {
                        $model::where(['uuid' => $quoteUuid, 'quote_type_id' => $quoteTypeId])->update([
                            'quote_status_id' => QuoteStatusEnum::PolicyBooked
                        ]);

                        // TODO: send it to sage, need to confirm what the sage is.
                    }
                    break;
            }
        }
    }

    public function savePriceDetails(Request $request)
    {
        $data = $request->all();

        SendUpdateLogRepository::updateLogPriceDetails($data);
        
        return redirect()->back();
    }

    public function savePolicyDetails(Request $request)
    {
        $data = $request->all();

        SendUpdateLogRepository::savePolicyDetails($data);
        
        return redirect()->back();
    }

    public function saveBookingDetails(Request $request)
    {
        SendUpdateLogDetailsRepository::createOrUpdate($request->all());
    }

    public function getReversalEntries(Request $request)
    {
        $reversalEntries = $this->sendUpdateLogService->getReversalEntries($request->input());

        return response()->json(['response' => $reversalEntries]);
    }
}
