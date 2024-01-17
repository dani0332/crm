<?php

namespace App\Http\Controllers\V2;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CustomerTypeEnum;
use App\Enums\GenericRequestEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Exports\AmtQuoteExport;
use App\Exports\BusinessQuoteExport;
use App\Exports\CarQuoteExport;
use App\Exports\CarQuoteExportWithEmailMobile;
use App\Exports\CarQuoteExportWithMakeModelTrims;
use App\Exports\CarQuoteExportWithPlans;
use App\Exports\HealthQuotesExport;
use App\Exports\HomeQuoteExport;
use App\Exports\LifeQuotesExport;
use App\Exports\PersonalQuotesExport;
use App\Exports\TravelQuoteExport;
use App\Factories\SagePayloadFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookPolicyRequest;
use App\Http\Requests\CustomerProfileRequest;
use App\Http\Requests\DuplicateLobRequest;
use App\Http\Requests\LeadAssignRequest;
use App\Http\Requests\PlanDetailsRequest;
use App\Http\Requests\SendBookPolicyRequest;
use App\Http\Requests\UpdateLastYearPolicyRequest;
use App\Jobs\SendBookPolicyDocumentsJob;
use App\Models\ApplicationStorage;
use App\Models\Customer;
use App\Models\Entity;
use App\Models\Lookup;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\QuoteRequestEntityMapping;
use App\Models\QuoteStatus;
use App\Models\QuoteSync;
use App\Models\User;
use App\Services\ActivitiesService;
use App\Services\CentralService;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use League\CommonMark\Extension\SmartPunct\Quote;
use Maatwebsite\Excel\Facades\Excel;

class CentralController extends Controller
{
    use GenericQueriesAllLobs;
    public function createDuplicate(DuplicateLobRequest $request)
    {
        $response = (new CentralService())->saveDuplicateLeads($request->validated());

        if (!empty($response['errors'])) {
            return redirect()->back()->withErrors($response['errors']);
        }

        return back()->with('message', 'Quote is created successfully.');
    }

    public function exportLeads(Request $request, $quoteType, $exportTye = null)
    {
        $diffInDays = 120;

        if (!$quoteType) {
            return abort(404);
        }

        if ($exportTye != GenericRequestEnum::EXPORT_MAKES_MODELS) {
            $request->validate([
                'created_at_start' => 'required',
                'created_at_end' => 'required',
            ]);
            if (request()->has('created_at')) {
                request()->merge(['created_at_start' => request()->get('created_at')]);
                request()->query->remove('created_at');
            }
            if (ucfirst($quoteType) == QuoteTypes::CAR->value) {
                $diffInDays = 31;
            }

            $created_at_start = Carbon::parse($request->created_at_start)->format('Y-m-d');
            $created_at_end = Carbon::parse($request->created_at_end)->format('Y-m-d');
            $diff = Carbon::parse($created_at_start)->diffInDays(Carbon::parse($created_at_end));
            if ($diff > $diffInDays) {
                return back()->with('error', 'Maximum of ' . $diffInDays . ' days (created date) are allowed to be exported.');
            }
        }

        // For Personal Quotes
        if (in_array(ucfirst($quoteType), [
            QuoteTypes::BIKE->value,
            QuoteTypes::YACHT->value,
            QuoteTypes::PET->value,
            QuoteTypes::CYCLE->value,
            QuoteTypes::JETSKI->value,
        ])) {
            return Excel::download(new PersonalQuotesExport, $quoteType . '_leads.xlsx');
        }

        if (QuoteTypes::CAR->value == ucfirst($quoteType)) {
            if ($exportTye == GenericRequestEnum::EXPORT_PLAN_DETAIL) {
                return app(CarQuoteExportWithPlans::class)->download(ucfirst(GenericRequestEnum::EXPORT_PLAN_DETAIL));
            } elseif ($exportTye == GenericRequestEnum::EXPORT_LEADS_DETAIL_WITH_EMAIL_MOBILE) {
                return app(CarQuoteExportWithEmailMobile::class)->download(ucfirst(GenericRequestEnum::EXPORT_LEADS_DETAIL_WITH_EMAIL_MOBILE));
            } elseif ($exportTye == GenericRequestEnum::EXPORT_MAKES_MODELS) {
                return app(CarQuoteExportWithMakeModelTrims::class)->download(ucfirst(GenericRequestEnum::EXPORT_MAKES_MODELS));
            }
        }

        switch (ucfirst($quoteType)) {
            case QuoteTypes::LIFE->value:
                return Excel::download(new LifeQuotesExport, 'life_leads.xlsx');

            case QuoteTypes::HOME->value:
                return Excel::download(new HomeQuoteExport, 'home_leads.xlsx');

            case QuoteTypes::AMT->value:
                return Excel::download(new AmtQuoteExport, 'amt_leads.xlsx');

            case QuoteTypes::BUSINESS->value:
                return Excel::download(new BusinessQuoteExport, 'business_leads.xlsx');

            case QuoteTypes::TRAVEL->value:
                return Excel::download(new TravelQuoteExport, 'travel_leads.xlsx');

            case QuoteTypes::CAR->value:
                return app(CarQuoteExport::class)->download('Car-List');

            case QuoteTypes::HEALTH->value:
                return Excel::download(new HealthQuotesExport, 'Health-List.xlsx');

            default:
                return false;
        }
    }

    public function manualLeadAssign(LeadAssignRequest $leadAssignRequest)
    {
        (new CentralService())->assignLeadToAdvisor($leadAssignRequest);

        return redirect()->back()->with('success', ucfirst($leadAssignRequest->modelType) . ' Leads has been Assigned');
    }

    public function updateCustomerProfileDetails(CustomerProfileRequest $customerProfileRequest)
    {
        if ($customerProfileRequest->customer_type == CustomerTypeEnum::Individual) {
            $customer = Customer::where('id', $customerProfileRequest->customer_id)->firstOrFail();

            $customer->update($customerProfileRequest->only([
                'insured_first_name', 'insured_last_name', 'emirates_id_number', 'emirates_id_expiry_date',
            ]));
        }

        if ($customerProfileRequest->customer_type == CustomerTypeEnum::Entity) {
            $entity = Entity::updateOrCreate(['trade_license_no' => $customerProfileRequest->trade_license_no], $customerProfileRequest->validated());
            $entity->update(['code' => CustomerTypeEnum::EntityShort . '-' . $entity->id]);

            QuoteRequestEntityMapping::updateOrCreate([
                'quote_type_id' => $customerProfileRequest->quote_type_id,
                'quote_request_id' => $customerProfileRequest->quote_request_id,
            ], ['entity_id' => $entity->id, 'entity_type_code' => $customerProfileRequest->entity_type_code]);
        }

        return redirect()->back();
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateLastYearPolicy(UpdateLastYearPolicyRequest $request)
    {
        $quote = $this->getQuoteObject($request->model_type, $request->quote_id);

        if (!$quote) {
            return redirect()->back()->with('error', 'Error Updating Policy Details.');
        }

        $quote->update([
            'renewal_batch' => $request->renewal_batch,
        ]);

        return redirect()->back()->with('success', 'Last Year Policy Detail has been updated.');
    }

    public function updateBookingPolicy(BookPolicyRequest $bookPolicyRequest)
    {
        $validatedData =  $bookPolicyRequest->validated();


        $paymentInformation = [
            'tax_invoice_number' => $validatedData['insurer_tax_invoice_number'],
            'transaction_payment_status' => $validatedData['transaction_payment_status'],
            'insurer_commmission_invoice_number' => $validatedData['insurer_commmission_invoice_number'],
            'broker_invoice_number' => $validatedData['broker_invoice_number'],
            'insurer_invoice_date' => $validatedData['invoice_date'],
            'commission_vat_not_applicable' => $validatedData['commission_vat_not_applicable'],
            'commission_vat_applicable' => $validatedData['commission_vat_applicable'],
            'commmission_percentage' => $validatedData['commission_percentage'],
            'commission_vat' =>  $validatedData['vat_on_commission'],
            'commission' => $validatedData['total_commission'],
            'invoice_description' => $validatedData['invoice_description'],
        ];
        $payment = Payment::where('code', $validatedData['payment_code'])->first();
        if (!$payment) {
            return back()->with('message', 'Payment record not found');
        }
        $payment->update($paymentInformation);

        $quote = $this->getQuoteObject($validatedData['model_type'], $validatedData['quote_id']);
        $quote->update(['policy_booking_date' => $validatedData['booking_date']]);

        return redirect()->back()->with('success', 'Booking Status has been updated.');
    }


    public function sendBookingPolicy(SendBookPolicyRequest $sendBookPolicyRequest)
    {

        $request =  (object)$sendBookPolicyRequest->validated();
        $quote = $this->getQuoteObject($request->model_type, $request->quote_id);

        if (!$quote) {
            return redirect()->back()->with('error', 'Quote not found.');
        }
        if ($request->send_policy_type == 'customer') {

            dispatch(new SendBookPolicyDocumentsJob($request));
            $quote->update([
                'quote_status_id' => QuoteStatusEnum::PolicySentToCustomer,
            ]);
            return response()->json(['message' => 'policy sent successfully'], 200);
        }

        if ($request->send_policy_type == 'sage') {

            $quoteTypeId = app(ActivitiesService::class)->getQuoteTypeId(strtolower($request->model_type));
            $payment = Payment::where('code', $quote['code'])->first();
            $paymentSplits = PaymentSplits::where('code', $quote['code'])->first();
            $data['quoteTypeId'] = $quoteTypeId;
            $data['id'] =  $quote->id;
            if ($payment->first() && $paymentSplits->first()) {

                // dispatch(new SendBookPolicyDocumentsJob($request));

                // $quote->update([
                //     'quote_status_id' => QuoteStatusEnum::PolicyBooked,
                // ]);

                // sage api service
                $sageApiService = new SageApiService();

                // payload
                $sageRequest =  $sageApiService->sagePayLoad($request->model_type, $payment, $quote, $paymentSplits);
                // sape customer number generation
                $sageCustomerNumber = $sageApiService->verifySageCustomer(36, $data);
                // $sageCustomerNumber = $sageApiService->verifySageCustomer($quote->customer_id, $data);

                if ($sageCustomerNumber) {


                    $sageRequest->customerId =  $sageCustomerNumber;
                    /* createARInvoicePremAndComm */
                    $createARInvoicePremAndCommPayload =  SagePayloadFactory::createARInvoicePremAndComm($sageRequest);

                    info('createARInvoicePremAndComm===payload===' . json_encode($createARInvoicePremAndCommPayload));

                    $resp = $sageApiService->postToSage300($createARInvoicePremAndCommPayload['endPoint'], $createARInvoicePremAndCommPayload['payload']);

                    info('createARInvoicePremAndComm===resp===' . $resp);

                    $response = json_decode($resp, true);

                    if (!empty($response['BatchNumber'])) {
                        /* readyToPostInvoiceAr */
                        $readyToPostInvoiceAr =  SagePayloadFactory::readyToPostInvoiceAr($response['BatchNumber']);

                        info('readyToPostInvoiceAr===payload===' . json_encode($readyToPostInvoiceAr));

                        $resp = $sageApiService->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');

                        info('readyToPostInvoiceAr===resp===' . $resp);

                        /* aRPostInvoices */
                        $aRPostInvoices =  SagePayloadFactory::aRPostInvoices($response['BatchNumber']);

                        info('aRPostInvoices===payload===' . json_encode($aRPostInvoices));

                        $resp = $sageApiService->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);


                        info('aRPostInvoices===resp===' . $resp);
                    }

                    /* createAPInvoicePrem */
                    $createAPInvoicePrem =  SagePayloadFactory::createAPInvoicePrem($sageRequest);

                    info('createAPInvoicePrem===payload===' . json_encode($createAPInvoicePrem));

                    $resp = $sageApiService->postToSage300($createAPInvoicePrem['endPoint'], $createAPInvoicePrem['payload']);

                    info('createAPInvoicePrem===resp===' . $resp);

                    $response = json_decode($resp, true);


                    if (!empty($response['BatchNumber'])) {

                        /* readyToPostInvoiceAP */
                        $readyToPostInvoiceAP =  SagePayloadFactory::readyToPostInvoiceAP($response['BatchNumber']);

                        info('readyToPostInvoiceAP===payload===' . json_encode($readyToPostInvoiceAP));

                        $resp = $sageApiService->postToSage300($readyToPostInvoiceAP['endPoint'],  $readyToPostInvoiceAP['payload'], 'PATCH');

                        info('readyToPostInvoiceAP===resp===' . $resp);

                        /* aPPostInvoices */
                        $aPPostInvoices =  SagePayloadFactory::aPPostInvoices($response['BatchNumber']);

                        info('aPPostInvoices===payload===' . json_encode($aPPostInvoices));
                        $resp = $sageApiService->postToSage300($aPPostInvoices['endPoint'], $aPPostInvoices['payload']);

                        info('aPPostInvoices===resp===' . $resp);
                    }

                    if ($sageRequest->discount > 0) {
                        /* createARInvoiceDis */
                        $createARInvoiceDis =  SagePayloadFactory::createARInvoiceDis($sageRequest);
                        info('createARInvoiceDis===payload===' . json_encode($createARInvoiceDis));
                        $resp = $sageApiService->postToSage300($createARInvoiceDis['endPoint'], $createARInvoiceDis['payload']);
                        info('createARInvoiceDis===resp===' . $resp);

                        $response = json_decode($resp, true);

                        if (!empty($response['BatchNumber'])) {
                            /* readyToPostInvoiceAr */
                            $readyToPostInvoiceAr =  SagePayloadFactory::readyToPostInvoiceAr($response['BatchNumber']);

                            info('readyToPostInvoiceAr===disc===payload===' . json_encode($readyToPostInvoiceAr));

                            $resp = $sageApiService->postToSage300($readyToPostInvoiceAr['endPoint'], $readyToPostInvoiceAr['payload'], 'PATCH');

                            info('readyToPostInvoiceAr===disc====resp===' . $resp);

                            /* aRPostInvoices */
                            $aRPostInvoices =  SagePayloadFactory::aRPostInvoices($response['BatchNumber']);

                            info('aRPostInvoices===disc===payload===' . json_encode($aRPostInvoices));

                            $resp = $sageApiService->postToSage300($aRPostInvoices['endPoint'], $aRPostInvoices['payload']);


                            info('aRPostInvoices===disc===resp===' . $resp);
                        }
                    }


                    if (strtolower($sageRequest->invoicePaymentStatus) == 'paid') {
                        /* createPaymontRecieptOneInvoice */
                        $createPaymontRecieptOneInvoice =  SagePayloadFactory::createPaymontRecieptOneInvoice($sageRequest);
                        info('createPaymontRecieptOneInvoice===payload===' . json_encode($createPaymontRecieptOneInvoice));
                        $resp = $sageApiService->postToSage300($createPaymontRecieptOneInvoice['endPoint'], $createPaymontRecieptOneInvoice['payload']);

                        info('createPaymontRecieptOneInvoice===resp===' . $resp);

                        $response = json_decode($resp, true);
                        if (!empty($response['BatchNumber'])) {
                            /* readyToPostReceiptAr */
                            $readyToPostReceiptAr =  SagePayloadFactory::readyToPostReceiptAr($response['BatchNumber']);

                            info('readyToPostReceiptAr===payload===' . json_encode($readyToPostReceiptAr));

                            $resp = $sageApiService->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');

                            info('readyToPostReceiptAr===resp===' . $resp);

                            /* aRPostInvoices */
                            $aRPostReceipts =  SagePayloadFactory::aRPostReceipts($response['BatchNumber']);

                            info('aRPostReceipts===payload===' . json_encode($aRPostReceipts));

                            $resp = $sageApiService->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);


                            info('aRPostReceipts===resp===' . $resp);
                        }
                    }
                }
            }



            return response()->json(['message' => 'policy booked successfully'], 200);
        }
    }

    public function loadAvailablePlans($type, $id)
    {
        return (new CentralService())->loadAvailablePlans($type, $id);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function savePlanDetails($quoteType, $code, PlanDetailsRequest $request)
    {
        $response = (new CentralService())->savePlanDetails($quoteType, $code, $request->safe());

        return redirect()->back();
    }

    public function updateSelectedPlan($quoteType, $uuid, $planId)
    {
        $repository = getRepositoryObject($quoteType);

        $quote = $repository::where('uuid', $uuid)->firstOrFail();

        $quote->update(['prefill_plan_id' => $planId]);

        return redirect()->back()->with('success', 'updated successfully');
    }
}
