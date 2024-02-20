<?php

namespace App\Http\Controllers\V2;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\SendUpdateLogStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PersonalQuote;
use App\Models\SendUpdateLogDetails;
use App\Repositories\InsuranceProviderRepository;
use App\Repositories\LookupRepository;
use App\Repositories\SendUpdateLogRepository;
use App\Services\LookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SendUpdateLogController extends Controller
{
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

        $quote = $this->getQuote($sendUpdateLog->personal_quote_id);

        if (in_array($quoteType, [QuoteTypes::CAR, QuoteTypes::HEALTH, QuoteTypes::TRAVEL])) {
            $quote->load('plan.insuranceProvider');
        }

        $issuanceStatuses = DB::table('policy_issuance_status')->select('id', 'text')->get();

        $realQuote = $this->getRealQuote($quoteType, $quote->uuid);
        $serviceFile = 'App\\Services\\'.$quoteType.'QuoteService';

        if (checkPersonalQuotes($quoteType)) {
            $repository = 'App\\Repositories\\'.$quoteType.'QuoteRepository';
            $payments = $repository::getBy('uuid', $quote->uuid)->payments;
        } else {
            $payments = app($serviceFile)->getEntityPlain($realQuote->id)?->payments ?? null;
            if (! is_null($payments)) {
                $payments->load(['paymentStatus', 'paymentStatusLog', 'paymentMethod', 'insuranceProvider']);
            }
        }

        $bookingDetails = [];
        if ($payments && is_countable($payments) && count($payments) > 0) {
            // it will also fetch broker_invoice_number and invoice_description, from lead detail page, lead detail broker_invoice_number will
            // always same as ```send update log details``` broker_invoice_number but invoice_description will be overwritten from ```send update log details``` page.
            $bookingDetails = $this->getInvoiceDescription($realQuote, $quoteType, $payments[0]['insurance_provider_id']);
        }

        if (count($sendUpdateLog->details) > 0) {
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
            'isNegativeValue' => $this->isNegativeValue($sendUpdateLog),
            'bookingDetails' => $bookingDetails,
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

    private function getQuote($personalQuoteId)
    {
        $repository = 'App\\Repositories\\PersonalQuoteRepository';

        return $repository::where('id', $personalQuoteId)->first();
    }

    private function getRealQuote($quoteType, $quoteUuid)
    {
        $repository = 'App\\Repositories\\'.ucwords($quoteType).'QuoteRepository';

        return $repository::where('uuid', $quoteUuid)->first();
    }

    private function isNegativeValue($sendUpdateLog): bool
    {
        $category = LookupRepository::where('id', $sendUpdateLog->category_id)->value('code');

        if (in_array($category, [SendUpdateLogStatusEnum::CI, SendUpdateLogStatusEnum::CIR])) {
            return true;
        }

        if ($category == SendUpdateLogStatusEnum::EF) {
            $option = LookupRepository::where('id', $sendUpdateLog->option_id)->value('code');
            if (in_array($option, [
                SendUpdateLogStatusEnum::MPC,
                SendUpdateLogStatusEnum::MDOM,
                SendUpdateLogStatusEnum::MDOV,
                SendUpdateLogStatusEnum::ED,
                SendUpdateLogStatusEnum::DM,
            ])) {
                return true;
            }
        }

        return false;
    }

    private function getInvoiceDescription($quote, $quoteType, $insurance_provider_id)
    {
        $insuranceProviderCode = InsuranceProviderRepository::where('id', $insurance_provider_id)->value('code');
        $insuranceProviderLeadCount = Payment::where('insurance_provider_id', '=', $insurance_provider_id)->count();

        return [
            'broker_invoice_number' => $insuranceProviderCode.$insuranceProviderLeadCount,
            'invoice_description' => $insuranceProviderCode.'-'.$quoteType.'-'.$quote->policy_number,
        ];
    }

    public function saveBookingDetails(Request $request)
    {
        SendUpdateLogDetails::updateOrCreate(
            ['send_update_log_id' => $request->id], [
                'type' => $request->send_update_type,
                'data' => [
                    'booking_date' => $request->booking_date,
                    'invoice_description' => $request->invoice_description,
                    'broker_invoice_number' => $request->broker_invoice_number,
                    'transaction_payment_status' => $request->transaction_payment_status,
                    'invoice_date' => $request->invoice_date,
                    'insurer_tax_invoice_number' => $request->insurer_tax_invoice_number,
                    'insurer_commission_invoice_number' => $request->insurer_commission_invoice_number,
                    'discount' => $request->discount,
                    'commission_percentage' => $request->commission_percentage,
                    'commission_vat_not_applicable' => $request->commission_vat_not_applicable,
                    'vat_on_commission' => $request->vat_on_commission,
                    'commission_vat_applicable' => strToFloat($request->commission_vat_applicable),
                    'total_commission' => $request->total_commission,
                    'total_vat_amount' => $request->total_vat_amount,
                    'price_vat_applicable' => strToFloat($request->price_vat_applicable),
                    'price_vat_not_applicable' => $request->price_vat_not_applicable,
                    'total_price' => $request->total_price,
                ],
            ]
        );
    }
}
