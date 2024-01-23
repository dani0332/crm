<?php

namespace App\Repositories;

use App\Enums\PaymentAllocationStatus;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Factories\SagePayloadFactory;
use App\Http\Controllers\SageApi;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\PaymentStatusLog;
use App\Models\PersonalQuote;
use App\Models\QuoteDocument;
use App\Services\CRUDService;
use App\Services\SageApiService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PaymentSplitsRepository
{
    use GenericQueriesAllLobs;
    public static function getByCode($code)
    {
        return PaymentSplits::with(['paymentStatus', 'paymentMethod'])
            ->where('code', $code)
            ->get();
    }

    public function calculateDiscount($totalSplitPayments, $discountValue)
    {
        $discount = 0;
        if ($totalSplitPayments > 0) {
            $discount = $discountValue / $totalSplitPayments;
        }

        return $discount;
    }

    public function addPaymentSplits($request, $quoteID)
    {
        $totalSplitPayments = (count($request->split_payment_details['split_amount']) - 1);
        $discount = 0;
        if (isset($request->discount_value) && $request->discount_value > 0) {
            $discount = $this->calculateDiscount($totalSplitPayments, $request->discount_value);
        }

        for ($i = 1; $i <= $totalSplitPayments; $i++) {
            if (isset($request->split_payment_details['payment_type'][$i]) && $request->split_payment_details['payment_type'][$i] != null) {
                $childPaymentStatus = $this->getChildPaymentStatus($request->split_payment_details['payment_type'][$i]);
                $splitPaymentInformation = [
                    'code' => $quoteID,
                    'sr_no' => $i,
                    'payment_method' => $request->split_payment_details['payment_type'][$i],
                    'check_detail' => isset($request->split_payment_details['check_detail'][$i]) ? $request->split_payment_details['check_detail'][$i] : null,
                    'payment_amount' => $request->split_payment_details['split_amount'][$i],
                    'due_date' => $request->split_payment_details['due_date'][$i],
                    'payment_status_id' => $childPaymentStatus,
                    'discount_value' => $discount,
                ];

                $paymentSplitRecord = PaymentSplits::create($splitPaymentInformation);
                if ($paymentSplitRecord) {
                    //Update parent payment status
                    $this->setMasterPaymentStatus($paymentSplitRecord->id);
                    /*
                    if ($paymentSplitRecord->payment_method == 'CC') {
                        $this->generateSplitPaymentLink($quoteID, $paymentSplitRecord->id, $request->modelType, $request->quote_id);
                    }*/
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
        $this->uploadDiscountDocuments($request->split_payment_details['discount_documents'], $quoteID);
    }

    public function updatePaymentSplits($request)
    {
        $paymentSplits = PaymentSplits::with('documents')->where(['code' => $request->paymentCode])->get();
        //dd($request->all()); //payment_no
        //dd($paymentSplits->count());
        $paymentPaidSerialNo = [];
        $splitPaymentDocumentIds = [];
        $splitPaymentDetails = $request->split_payment_details['split_amount'];
        if ($paymentSplits) {
            foreach ($paymentSplits as $paymentSplit) {
                if ($paymentSplit->payment_status_id == PaymentStatusEnum::PAID ||
                    $paymentSplit->payment_status_id == PaymentStatusEnum::AUTHORISED) {
                    $paymentPaidSerialNo[] = $paymentSplit->sr_no;

                    continue;
                }
                if (($request->payment_no < $paymentSplits->count()) && $paymentSplit->sr_no > $request->payment_no) {
                    QuoteDocument::where('payment_split_id', $paymentSplit->id)->delete();
                    $paymentSplit->delete();
                    unset($splitPaymentDetails[$paymentSplit->sr_no]);

                    continue;
                }
            }
        }

        $totalSplitPayments = (count($splitPaymentDetails) - 1);
        $discount = 0;
        if (isset($request->discount_value) && $request->discount_value > 0 && count($paymentPaidSerialNo) == 0) {
            $discount = $this->calculateDiscount($totalSplitPayments, $request->discount_value);
        }

        for ($i = 1; $i <= $totalSplitPayments; $i++) {
            if (in_array($i, $paymentPaidSerialNo)) {
                continue;
            }

            if (isset($request->split_payment_details['payment_type'][$i]) && $request->split_payment_details['payment_type'][$i] != null) {

                $splitPaymentInformation = [
                    'code' => $request->paymentCode,
                    'sr_no' => $i,
                    'payment_method' => $request->split_payment_details['payment_type'][$i],
                    'check_detail' => isset($request->split_payment_details['check_detail'][$i]) ? $request->split_payment_details['check_detail'][$i] : null,
                    'payment_amount' => $request->split_payment_details['split_amount'][$i],
                    'due_date' => $request->split_payment_details['due_date'][$i],
                    'discount_value' => $discount,
                ];
                $childPaymentStatus = $this->getChildPaymentStatus($request->split_payment_details['payment_type'][$i]);
                $splitPaymentInformation['payment_status_id'] = $childPaymentStatus;
                $paymentSplitRecord = PaymentSplits::where(['code' => $request->paymentCode, 'sr_no' => $i])->first();
                if (! $paymentSplitRecord) {
                    $paymentSplitRecord = PaymentSplits::create($splitPaymentInformation);
                } else {
                    $paymentSplitRecord->update($splitPaymentInformation);
                    $this->setMasterPaymentStatus($paymentSplitRecord->id);
                }
                //add document references
                if (isset($request->split_payment_details['document_detail'][$i])
                    && $paymentSplitRecord
                    && count($request->split_payment_details['document_detail'][$i])
                ) {
                    foreach ($request->split_payment_details['document_detail'][$i] as $document) {
                        $quoteDocumentRec = QuoteDocument::find($document['id']);
                        if ($quoteDocumentRec) {
                            $quoteDocumentRec->payment_split_id = $paymentSplitRecord->id;
                            $quoteDocumentRec->save();
                        }
                    }
                }
            }
        }
        $this->uploadDiscountDocuments($request->split_payment_details['discount_documents'], $request->paymentCode);
    }

    public function uploadDiscountDocuments($discountDocuments, $code)
    {
        if (isset($discountDocuments) && count($discountDocuments)) {
            $paymentSplitRecord = PaymentSplits::where(['code' => $code])->first();
            foreach ($discountDocuments[0] as $document) {
                $quoteDocumentRec = QuoteDocument::find($document['id']);
                if ($quoteDocumentRec) {
                    $quoteDocumentRec->payment_split_id = $paymentSplitRecord->id;
                    $quoteDocumentRec->save();
                }
            }
        }
    }

    public function getChildPaymentStatus($paymentType)
    {
        $childPaymentStatus = PaymentStatusEnum::NEW;
        if ($paymentType == PaymentMethodsEnum::BankTransfer ||
            $paymentType == PaymentMethodsEnum::InsurerPayment ||
            $paymentType == PaymentMethodsEnum::Cheque ||
            $paymentType == PaymentMethodsEnum::PostDatedCheque
        ) {
            $childPaymentStatus = PaymentStatusEnum::PENDING;
        } elseif ($paymentType == PaymentMethodsEnum::CreditApproval) {
            $childPaymentStatus = PaymentStatusEnum::CREDIT_APPROVED;
        }

        return $childPaymentStatus;

    }

    public function updateSplitPaymentsApprove($request)
    {
        $quoteModel = $this->getQuoteObject($request->modelType, $request->quote_id);
        if (! $quoteModel) {
            return response()->json(['success' => false]);
        }

        if ($request->is_declined) {
            $firstPayment = $quoteModel->payments()->first();
            $firstPayment->update([
                'decline_reason_id' => $request->declined_reason,
                'decline_custom_reason' => $request->declined_custom_reason,
                'updated_by' => Auth::user()->id,
            ]);
            $quoteModel->quote_status_id = QuoteStatusEnum::TransactionDeclined;
            $quoteModel->save();
            $successMessage = 'Transaction declined';
        } else {

            $totalCapturedPayment = 0;
            if ($request->is_capture) { //update collected amount in childs
                $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($request->modelType));
                foreach ($request->collection_amount as $key => $splitAmount) {
                    $paymentSplit = PaymentSplits::where(['code' => $quoteModel->code, 'sr_no' => $key])->first();
                    if ($paymentSplit) {
                        if ($paymentSplit->payment_method == PaymentMethodsEnum::CreditCard) {
                            //Marshal Service to capture split payment
                            $response = app(CRUDService::class)->capturePayment($quoteModel, $paymentSplit, $quoteTypeId, $splitAmount);
                            $paymentSplit->payment_status_id = PaymentStatusEnum::PAID;
                        }
                        $paymentSplit->collection_amount = $splitAmount;
                        $paymentSplit->save();
                    }
                    $totalCapturedPayment += $splitAmount;
                }
            }
            $firstPayment = $quoteModel->payments()->first();
            $masterPaymentStatus = $firstPayment->payment_status_id;
            $totalPaidPayments = PaymentSplits::where([
                'payment_status_id' => PaymentStatusEnum::PAID,
                'code' => $firstPayment->code,
            ])->count();
            if ($totalPaidPayments == $firstPayment->total_payments) {
                $masterPaymentStatus = PaymentStatusEnum::PAID;
            }
            $firstPayment->update([
                'is_approved' => 1,
                'captured_amount' => ($firstPayment->captured_amount + $totalCapturedPayment),
                'payment_status_id' => $masterPaymentStatus,
                'updated_by' => Auth::user()->id,
            ]);

            $quoteModel->quote_status_id = QuoteStatusEnum::TransactionApproved;
            $quoteModel->save();
            $successMessage = 'Transaction approved';
        }

        return $successMessage;
    }

    public function updatePaymentStatus($request)
    {
        $successMessage = 'Payment Verified';
        if ($request->is_approved) {
            $paymentInformation = [
                'collection_amount' => $request->collection_amount,
                'bank_reference_number' => $request->bank_reference_number,
                'payment_status_id' => PaymentStatusEnum::PAID,
                'payment_allocation_status' => PaymentAllocationStatus::NOT_ALLOCATED,
                'updated_by' => $request->user()->id,
            ];
            $splitPayment = PaymentSplits::find($request->splitPaymentId);
            //$splitPayment = PaymentSplits::find($request->splitPaymentId)->update($paymentInformation);
            $payment = Payment::where('code', $splitPayment->code)->first();
            if ($payment) {
                $payment->update(
                    ['captured_amount' => ($payment->captured_amount + $request->collection_amount),
                        'payment_allocation_status' => PaymentAllocationStatus::NOT_ALLOCATED]
                );
            }
            //associate approved documents with payment split
            if (isset($request->approved_document_model[$splitPayment->sr_no])
                && count($request->approved_document_model[$splitPayment->sr_no]) > 0) {
                foreach ($request->approved_document_model[$splitPayment->sr_no] as $document) {
                    $quoteDocumentRec = QuoteDocument::find($document['id']);
                    if ($quoteDocumentRec) {
                        $quoteDocumentRec->payment_split_id = $splitPayment->id;
                        $quoteDocumentRec->save();
                    }
                }
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
            // NEW CUSTOMER CREATION
            //dd($request->all());
            $quote = $this->getQuoteObject($request->modelType, $request->quote_id);
            $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($request->modelType));
            $customerData = [];
            $customerData['quoteTypeId'] = $quoteTypeId;
            $customerData['id'] = $quote->id;
            $sageApiService = new SageApiService();
            $sageCustomerNumber = $sageApiService->verifySageCustomer($request->customer_id, $customerData);
            //$sageCustomerNumber = 'IC008';
            $request->merge(['sage_customer_number' => $sageCustomerNumber]);
            // create prepayment reciept
            $payLoadOptions = SagePayloadFactory::createPrepaymentPayload($request);
            $message = $sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($message, true);

            if (isset($sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'])) {
                $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptArPayment($sageResponse['BatchNumber']);
                $resp = $sageApiService->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
                $aRPostReceipts = SagePayloadFactory::aRPostReceiptsPayment($sageResponse['BatchNumber']);
                $resp = $sageApiService->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $documentNumberForReciept = $sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'];
                $paymentInformation['sage_reciept_id'] = $documentNumberForReciept;
                $splitPayment->update($paymentInformation);
            } else {
                $successMessage = 'Sage Error: Reciept not generated';

                return $successMessage;
            }

        } elseif ($request->is_declined) {
            $splitPayment = PaymentSplits::find($request->splitPaymentId);
            $paymentInformation = [
                'decline_reason_id' => $request->declined_reason,
                'decline_custom_reason' => $request->declined_custom_reason,
                'payment_status_id' => PaymentStatusEnum::DECLINED,
                'updated_by' => $request->user()->id,
            ];
            $splitPayment->update($paymentInformation);
            $successMessage = 'Payment Declined';
        }

        //Update parent payment status
        $this->setMasterPaymentStatus($request->splitPaymentId);

        /*
        $totalPaidPayments = PaymentSplits::where('payment_status_id', PaymentStatusEnum::PAID)->count();
        if ($totalPaidPayments == $paymentSplitRecord->payment->total_payments) {
            Payment::where('code', $paymentSplitRecord->code)->update(['payment_status_id' => PaymentStatusEnum::PAID]);
        } elseif ($request->is_declined) {
            Payment::where('code', $paymentSplitRecord->code)->update(['payment_status_id' => PaymentStatusEnum::NEW]);
        } else {
            Payment::where('code', $paymentSplitRecord->code)->update(['payment_status_id' => PaymentStatusEnum::PARTIALLY_PAID]);
        }*/

        return $successMessage;
    }

    public function setMasterPaymentStatus($splitPaymentId)
    {
        $splitPayment = PaymentSplits::with('payment')->find($splitPaymentId);
        $payment = $splitPayment->payment;
        if ($payment) {
            if ($payment->frequency == 'upfront') {
                $payment->update(
                    ['payment_status_id' => $splitPayment->payment_status_id]
                );
            } else {
                $totalPaidPayments = PaymentSplits::where([
                    'payment_status_id' => PaymentStatusEnum::PAID,
                    'code' => $splitPayment->code,
                ])->count();
                if ($totalPaidPayments == $payment->total_payments) {
                    $payment->update(
                        ['payment_status_id' => PaymentStatusEnum::PAID]
                    );
                } elseif ($totalPaidPayments > 0) {
                    $payment->update(
                        ['payment_status_id' => PaymentStatusEnum::PARTIALLY_PAID]
                    );
                } else {
                    $payment->update(
                        ['payment_status_id' => PaymentStatusEnum::NEW]
                    );
                }
            }
        }
    }

    public function logPaymentStatus($code, $status, $splitPaymentNo = 0, $declineReason = '')
    {
        $paymentLog = new PaymentStatusLog([
            'current_payment_status_id' => PaymentStatusEnum::NEW,
            'payment_code' => $paymentInformation['code'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentLog->save();
    }

    public function generateSplitPaymentLink($code, $splitPaymentId, $modelType, $quoteId)
    {
        $splitPayment = PaymentSplits::where(['code' => $code, 'id' => $splitPaymentId])->first();
        $payment = $splitPayment->payment;

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
}
