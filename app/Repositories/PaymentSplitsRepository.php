<?php

namespace App\Repositories;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Factories\SagePayloadFactory;
use App\Http\Controllers\SageApi;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\PersonalQuote;
use App\Models\QuoteDocument;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;

class PaymentSplitsRepository
{
    use GenericQueriesAllLobs;
    public static function getByCode($code)
    {
        return PaymentSplits::with(['paymentStatus', 'paymentMethod'])
            ->where('code', $code)
            ->get();
    }

    public function addPaymentSplits($request, $quoteID)
    {
        for ($i = 1; $i <= (count($request->split_payment_details['split_amount']) - 1); $i++) {
            if (isset($request->split_payment_details['payment_type'][$i]) && $request->split_payment_details['payment_type'][$i] != null) {
                $childPaymentStatus = PaymentStatusEnum::NEW;
                if ($request->split_payment_details['payment_type'][$i] == PaymentMethodsEnum::BankTransfer || 
                    $request->split_payment_details['payment_type'][$i] == PaymentMethodsEnum::InsurerPayment || 
                    $request->split_payment_details['payment_type'][$i] == PaymentMethodsEnum::Cheque ||
                    $request->split_payment_details['payment_type'][$i] == PaymentMethodsEnum::PostDatedCheque  
                ) {
                    $childPaymentStatus = PaymentStatusEnum::PENDING;
                }
                $splitPaymentInformation = [
                    'code' => $quoteID,
                    'sr_no' => $i,
                    'payment_method' => $request->split_payment_details['payment_type'][$i],
                    'check_detail' => isset($request->split_payment_details['check_detail'][$i]) ? $request->split_payment_details['check_detail'][$i] : null,
                    'payment_amount' => $request->split_payment_details['split_amount'][$i],
                    'due_date' => $request->split_payment_details['due_date'][$i],
                    'payment_status_id' => $childPaymentStatus,
                ];

                $paymentSplitRecord = PaymentSplits::create($splitPaymentInformation);
                if ($paymentSplitRecord) {
                    if ($paymentSplitRecord->payment_method == 'CC') {
                        $this->generateSplitPaymentLink($quoteID, $paymentSplitRecord->id, $request->modelType, $request->quote_id);
                    }
                    //add document references
                    if (isset($request->split_payment_details['document_detail'][$i])
                        && $paymentSplitRecord
                        && count($request->split_payment_details['document_detail'][$i])
                    ) {
                        foreach ($request->split_payment_details['document_detail'][$i] as $document) {
                            $quoteDocumentRec = QuoteDocument::find($document['id']);
                            $quoteDocumentRec = QuoteDocument::find($document['id']);
                            if ($quoteDocumentRec) {
                                $quoteDocumentRec->payment_split_id = $paymentSplitRecord->id;
                                $quoteDocumentRec->save();
                            }
                        }
                    }
                }
            }
        }
    }

    public function generateSplitPaymentLink($code, $splitPaymentId, $modelType, $quoteId)
    {
        $payment = Payment::where('code', '=', $code)->first();
        $splitPayment = PaymentSplits::where(['code' => $code, 'id' => $splitPaymentId])->first();
        //dd($payment);

        if (! $payment) {
            return false;
        }
        if ($splitPayment->payment_link != null && now() < Carbon::parse($splitPayment->payment_link_created_at)->addDays(3)) {
            return;
        } else {
            $quoteModel = $this->getQuoteObject($modelType, $quoteId);
            $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($modelType));

            $description = (get_class($quoteModel) == PersonalQuote::class) ? ($payment->personalPlan->text ?? '') : ($quoteModel->plan->text ?? '');

            $paymentLink = config('constants.PAYMENT_REDIRECT_LINK');

            $paymentLink = $splitPayment->payment_method == PaymentMethodsEnum::InsureNowPayLater ? $paymentLink.'tabby' : $paymentLink.'checkout';

            $paymentParams = [
                'code' => $payment->code.'-'.$splitPayment->sr_no,
                'quoteTypeId' => $quoteTypeId,
            ];
            $paymentLinkURL = $paymentLink.'?'.http_build_query($paymentParams);

            $invoiceRequestData = [
                'firstName' => $quoteModel->first_name,
                'lastName' => $quoteModel->last_name,
                'email' => $quoteModel->email,
                'emailSubject' => 'Payment Request',
                'items' => [
                    [
                        'description' => $description,
                        'totalPrice' => [
                            'currencyCode' => 'AED',
                            'value' => ceil($splitPayment->payment_amount * 100),
                        ],
                        'quantity' => 1,
                    ],
                ],
                'total' => [
                    'currencyCode' => 'AED',
                    'value' => ceil($splitPayment->payment_amount * 100),
                ],
                'merchantOrderReference' => strtoupper($payment->code.'-'.$splitPayment->sr_no),
            ];

            $splitPayment->payment_link = $paymentLinkURL;
            $splitPayment->payment_link_created_at = now();
            $splitPayment->save();

            return;
        }
    }

    public function updatePaymentSplits($request)
    {
        $paymentSplits = PaymentSplits::with('documents')->where(['code' => $request->paymentCode])->get();
        $paymentPaidSerialNo = [];
        $splitPaymentDocumentIds = [];
        if ($paymentSplits) {
            foreach ($paymentSplits as $paymentSplit) {
                if ($paymentSplit->payment_status_id == PaymentStatusEnum::PAID) {
                    $paymentPaidSerialNo[] = $paymentSplit->sr_no;

                    continue;
                }
                //dd($paymentSplit->documents()->count());
                foreach ($paymentSplit->documents as $document) {
                    $splitPaymentDocumentIds[$paymentSplit->sr_no][] = $document->id;
                }

                $paymentSplit->documents()->delete();
                $paymentSplit->delete();
            }
        }

        for ($i = 1; $i <= (count($request->split_payment_details['split_amount']) - 1); $i++) {
            if (in_array($i, $paymentPaidSerialNo)) {
                continue;
            }

            if (isset($request->split_payment_details['payment_type'][$i]) && $request->split_payment_details['payment_type'][$i] != null) {

                $childPaymentStatus = PaymentStatusEnum::NEW;
                if ($request->split_payment_details['payment_type'][$i] == PaymentMethodsEnum::BankTransfer || 
                    $request->split_payment_details['payment_type'][$i] == PaymentMethodsEnum::InsurerPayment || 
                    $request->split_payment_details['payment_type'][$i] == PaymentMethodsEnum::Cheque ||
                    $request->split_payment_details['payment_type'][$i] == PaymentMethodsEnum::PostDatedCheque  
                ) {
                    $childPaymentStatus = PaymentStatusEnum::PENDING;
                }
                $splitPaymentInformation = [
                    'code' => $request->paymentCode,
                    'sr_no' => $i,
                    'payment_method' => $request->split_payment_details['payment_type'][$i],
                    'check_detail' => isset($request->split_payment_details['check_detail'][$i]) ? $request->split_payment_details['check_detail'][$i] : null,
                    'payment_amount' => $request->split_payment_details['split_amount'][$i],
                    'due_date' => $request->split_payment_details['due_date'][$i],
                    'payment_status_id' => $childPaymentStatus,
                ];

                $paymentSplitRecord = PaymentSplits::create($splitPaymentInformation);

                if ($paymentSplitRecord->payment_method == 'CC') {
                    $this->generateSplitPaymentLink($request->paymentCode, $paymentSplitRecord->id, $request->modelType, $request->quote_id);
                }

                //add document references
                if (isset($request->split_payment_details['document_detail'][$i])
                    && $paymentSplitRecord
                    && count($request->split_payment_details['document_detail'][$i])
                ) {
                    foreach ($request->split_payment_details['document_detail'][$i] as $document) {

                        if (isset($splitPaymentDocumentIds[$i]) && in_array($document['id'], $splitPaymentDocumentIds[$i])) {
                            $quoteDocumentRec = QuoteDocument::withTrashed()->find($document['id']);
                            if ($quoteDocumentRec) {
                                $quoteDocumentRec->restore();
                                $quoteDocumentRec->payment_split_id = $paymentSplitRecord->id;
                                $quoteDocumentRec->save();
                            }

                            continue;
                        }
                        $quoteDocumentRec = QuoteDocument::find($document['id']);
                        if ($quoteDocumentRec) {
                            $quoteDocumentRec->payment_split_id = $paymentSplitRecord->id;
                            $quoteDocumentRec->save();
                        }
                    }
                }
            }
        }
    }

    public function updatePaymentStatus($request)
    {       
        $successMessage = 'Payment Verified';
        if ($request->is_approved) {
            $paymentInformation = [
                'collection_amount' => $request->collection_amount,
                'bank_reference_number' => $request->bank_reference_number,
                'payment_status_id' => PaymentStatusEnum::PAID,
                'updated_by' => $request->user()->id,
            ];
            $splitPayment = PaymentSplits::find($request->splitPaymentId);
            //$splitPayment = PaymentSplits::find($request->splitPaymentId)->update($paymentInformation);
            $payment = Payment::where('code', $splitPayment->code)->first();
            if ($payment) {
                $payment->update(['captured_amount' => ($payment->captured_amount + $request->collection_amount)]);
            }
            
            /* STILL PARAMETERS REQUIRED FROM OTHER DEVELOPING 
            $sageRequest = new \stdClass();
            $sageRequest->discount = 0.00;
            $sageRequest->insurerInvoiceDate = $splitPayment->due_date;
            $sageRequest->policyExpiryDate   = $splitPayment->due_date;
            $sageRequest->premiumWithoutTax = $request->collection_amount;
            $sageRequest->premiumWithTax = $request->collection_amount;
            $sageRequest->vatOnCommission = 0;
            $sageRequest->commission = 0;
            $sageRequest->commissionIncludingVat = 0;
            $sageRequest->invoicePaymentStatus = 'paid';
            $sageRequest->insurerPremiumTaxInvoiceNumber='';
            $sageApiService = new SageApiService();
            $payLoadOptions = SagePayloadFactory::createPayload($sageRequest, $leadStatus);
            $endPoint = $payLoadOptions['endPoint'];
            $payLoad = $payLoadOptions['payload'];
            $sageResponse = $sageApiService->postToSage300($endPoint, $payLoad);
            //$sageApi = new SageApi(new SageApiService());
            //$sageResponse = $sageApi->processSagePost($sageRequest);
            */
            
            /* CUSTOMER PAYLOAD
            $payLoadOptions = SagePayloadFactory::createCustomerPayload($successMessage);
            $jsonResponse = $sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            dd($jsonResponse);
            $leadStatus = 'policy booked';
            */
            
            //$message = $sageApiService->postToSage300('AR/ARReceiptAndAdjustmentBatches', $createPrepaymentReciept);
            /* NEW CUSTOMER CREATION           
            $sageApiService = new SageApiService();
            $sageCustomerNumber = $sageApiService->verifySageCustomer($request->customer_id);
            */
            $sageApiService = new SageApiService();
            $payLoadOptions = SagePayloadFactory::createPrepaymentPayload($request);
            $message = $sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);            
            $sageResponse = json_decode($message, true);
            $documentNumberForReciept = $sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'];
            $paymentInformation['sage_reciept_id'] = $documentNumberForReciept;
            $splitPayment->update($paymentInformation);
            //dd($documentNumberForReciept);

        } elseif ($request->is_declined) {
            $paymentInformation = [
                'declined_reason_id' => $request->declined_reason,
                'declined_custom_reason' => $request->declined_custom_reason,
                'payment_status_id' => PaymentStatusEnum::DECLINED,
                'updated_by' => $request->user()->id,
            ];
            PaymentSplits::find($request->splitPaymentId)->update($paymentInformation);
            $successMessage = 'Payment Declined';            
        }

        //Update parent payment status if all splits are paid
        $paymentSplitRecord = PaymentSplits::with('payment')->find($request->splitPaymentId);
        $totalPaidPayments = PaymentSplits::where('payment_status_id', PaymentStatusEnum::PAID)->count();
        if ($totalPaidPayments == $paymentSplitRecord->payment->total_payments) {
            Payment::where('code', $paymentSplitRecord->code)->update(['payment_status_id' => PaymentStatusEnum::PAID]);
        } elseif ($request->is_declined) {
            Payment::where('code', $paymentSplitRecord->code)->update(['payment_status_id' => PaymentStatusEnum::NEW]);
        } else {
            Payment::where('code', $paymentSplitRecord->code)->update(['payment_status_id' => PaymentStatusEnum::PARTIALLY_PAID]);
        }

        return $successMessage;
    }
}
