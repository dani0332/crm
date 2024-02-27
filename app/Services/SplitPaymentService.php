<?php

namespace App\Services;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Factories\SagePayloadFactory;
use App\Models\PaymentSplits;
use App\Models\Payment;
use App\Models\QuoteDocument;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\SageLoggable;
use Illuminate\Support\Facades\Log;

class SplitPaymentService
{
    use GenericQueriesAllLobs;
    use SageLoggable;

    public function calculateDiscount($totalSplitPayments, $discountValue)
    {
        $discount = 0;
        if ($totalSplitPayments > 0) {
            $discount = $discountValue / $totalSplitPayments;
        }

        return $discount;
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

    public function createSageRecipt($request, $splitPayment, $splitAmount = null)
    {
        if ($splitAmount != null) {
            $request->collection_amount = $splitAmount;
        }
        $returnMessage = ['status' => 'error', 'response' => ''];
        $quote = $this->getQuoteObject($request->modelType, $request->quote_id);
        $quoteTypeId = collect(QuoteTypeId::getOptions())->search(ucfirst($request->modelType));
        $customerData = ['quoteTypeId' => $quoteTypeId, 'id' => $quote->id];

        $sageLogArray = $splitPayment->sageLog->keyBy('step')->toArray();

        $sageApiService = new SageApiService();
        $sageCustomerNumber = $sageApiService->verifySageCustomer($request->customer_id, $customerData, $splitPayment, $sageLogArray);
        if ($sageCustomerNumber == '') {
            $returnMessage['response'] = 'Customer not found in sage';

            return $returnMessage;
        }
        $request->merge(['sage_customer_number' => $sageCustomerNumber]);
        // create prepayment reciept
        $isLiveApiCallStep2 = true;
        if (isset($sageLogArray[2]) && $sageLogArray[2]['status'] == 'success') {
            $isLiveApiCallStep2 = false;
            $sageResponse = json_decode($sageLogArray[2]['response'], true);
        } else {
            $payLoadOptions = SagePayloadFactory::createPrepaymentPayload($request);
            $message = $sageApiService->postToSage300($payLoadOptions['endPoint'], $payLoadOptions['payload']);
            $sageResponse = json_decode($message, true);
        }

        if (isset($sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'])) {
            if ($isLiveApiCallStep2) {
                $this->logSageApiCall($payLoadOptions, $sageResponse, $splitPayment, 2, 4);
            }
            $isLiveApiCallStep3 = true;
            if (isset($sageLogArray[3]) && $sageLogArray[3]['status'] == 'success') {
                $isLiveApiCallStep3 = false;
                $readyToPostResponse = json_decode($sageLogArray[3]['response'], true);
            } else {
                $readyToPostReceiptAr = SagePayloadFactory::readyToPostReceiptArPayment($sageResponse['BatchNumber']);
                $readyToPostResponse = $sageApiService->postToSage300($readyToPostReceiptAr['endPoint'], $readyToPostReceiptAr['payload'], 'PATCH');
            }

            if ($readyToPostResponse !== '') {
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $splitPayment, 3, 4, 'fail');
                $returnMessage['response'] = 'Error while making ready to post to sage';

                return $returnMessage;
            } else {
                if ($isLiveApiCallStep3) {
                    $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $splitPayment, 3, 4);
                }
            }

            $isLiveApiCallStep4 = true;
            if (isset($sageLogArray[4]) && $sageLogArray[4]['status'] == 'success') {
                $isLiveApiCallStep4 = false;
                $postedResponse = json_decode($sageLogArray[4]['response'], true);
            } else {
                $aRPostReceipts = SagePayloadFactory::aRPostReceiptsPayment($sageResponse['BatchNumber']);
                $postedResponse = $sageApiService->postToSage300($aRPostReceipts['endPoint'], $aRPostReceipts['payload']);
                $postedResponse = json_decode($postedResponse, true);
            }

            if (isset($postedResponse['error'])) {
                $returnMessage['response'] = 'Error while posting to sage';
                $this->logSageApiCall($aRPostReceipts, $postedResponse, $splitPayment, 4, 4, 'fail');

                return $returnMessage;
            } else {
                if ($isLiveApiCallStep4) {
                    $this->logSageApiCall($aRPostReceipts, $postedResponse, $splitPayment, 4, 4);
                }
            }
            $documentNumberForReciept = $sageResponse['ReceiptsAdjustments'][0]['DocumentNumber'];
            $returnMessage = ['status' => 'success', 'response' => $documentNumberForReciept];
        } else {
            $this->logSageApiCall($payLoadOptions, $sageResponse, $splitPayment, 2, 4, 'fail');
            $returnMessage['response'] = 'Document number not generated from sage';
        }

        return $returnMessage;
    }
    // functon to check if the payment structure is new
    public function isNewPaymentStructure($payments)
    {
        if ($payments->count() == 0 || $payments[0]->total_payments > 0) {
            return true;
        }

        return false;
    }

    // Migrate payments from old system to new system
    public function migratePayments($payment)
    {
        if ($payment){
            $skipEmbededProducts = ['App\Models\EmbeddedTransactions','App\Models\EmbeddedTransaction'];
            // Extract the code and check if it has child payments
            $code = $payment->code;
            $tempCode = explode('-', $code);
            if (count($tempCode) == 3) {
                $code = $tempCode[0].'-'.$tempCode[1];
            }            

            $splitPaymentExists = PaymentSplits::where('code', $code)->count();
            if ($splitPaymentExists > 0) {
                Log::info('MigratePayment::Split Payment already exists for Payment Code: '.$payment->code);
                return true;
            }

            // verify master payment exists or not
            $masterPaymentExists = Payment::where('code', $code)->count();
            if (! ($masterPaymentExists > 0)) {                
                Log::info('MigratePayment::Master Payment does not exists for Payment Code: '.$payment->code);
                return false;               
            }
            
            $parentCollectionAmount = 0;
            if ($payment->payment_status_id == PaymentStatusEnum::PAID || $payment->payment_status_id == PaymentStatusEnum::CAPTURED //if paid or captured
                || $payment->payment_status_id == PaymentStatusEnum::PARTIAL_CAPTURED || $payment->payment_status_id == PaymentStatusEnum::PARTIALLY_PAID //if partial paid or captured
            ) { 
                $parentCollectionAmount = $payment->captured_amount;
            }

            $childPayments = Payment::where('code', 'like', "$code%")->whereNotIn('paymentable_type', $skipEmbededProducts)->get();

            Log::info('MigratePayment::Total Child Payments for Payment Code: '.$payment->code.' are: '.$childPayments->count());
            //echo $code . "==".$childPayments->count()."<hr>"; //continue;
            //update `payments` set total_payments=NULL, frequency=NULL
            $parentCollectionAmount = 0;
            $grandTotal = $childPayments->sum('captured_amount');
            $payment->total_payments = $childPayments->count();            
            if ($childPayments->count()===1){
                $payment->frequency = 'upfront';
            } else {
                $payment->frequency = 'split_payments';
            }
            $payment->total_price = $grandTotal;
            $payment->total_amount = $grandTotal;
            $payment->collection_type = 'broker';

            if ($payment->payment_status_id == PaymentStatusEnum::DRAFT) { //draft
                $payment->payment_status_id = PaymentStatusEnum::NEW; //new
            }
            //$payment->captured_amount = $parentCollectionAmount;
            $payment->collection_date = $payment->updated_at;
            $payment->save();

            if ($childPayments->isNotEmpty()) {
                $payment_sr_no = 1;
                foreach ($childPayments as $childPayment) {

                    if ($childPayment->payment_status_id == PaymentStatusEnum::DRAFT) { //draft
                        $childPayment->payment_status_id = PaymentStatusEnum::NEW; //new
                    }
                    // Create a new SplitPayment record
                    $collectionAmount = 0;
                    if ($childPayment->payment_status_id == PaymentStatusEnum::PAID || $childPayment->payment_status_id == PaymentStatusEnum::CAPTURED //if paid or captured
                    || $childPayment->payment_status_id == PaymentStatusEnum::PARTIAL_CAPTURED || $childPayment->payment_status_id == PaymentStatusEnum::PARTIALLY_PAID //if partial paid or captured
                    ) { 
                        $collectionAmount = $childPayment->captured_amount;
                        $parentCollectionAmount += $childPayment->captured_amount;
                    }

                    PaymentSplits::create([
                        'sr_no' => $payment_sr_no,
                        'code' => $code,
                        'payment_method' => $childPayment->payment_methods_code,
                        'payment_amount' => $childPayment->captured_amount,
                        'due_date' => $childPayment->updated_at,
                        'payment_status_id' => $childPayment->payment_status_id,
                        'collection_amount' => $collectionAmount,
                        'cc_payment_id' => $childPayment->amount,
                        'cc_payment_gateway' => $childPayment->amount,
                        'payment_link' => $childPayment->payment_link,
                        'payment_link_created_at' => $childPayment->payment_link_created_at,
                        'reference' => $childPayment->reference,
                        'authorized_at' => $childPayment->authorized_at,
                        'captured_at' => $childPayment->captured_at,
                        'premium_authorized' => $childPayment->premium_authorized,
                        'premium_captured' => $childPayment->premium_captured,
                        'payment_status_message' => $childPayment->payment_status_message,
                        'payment_gateway_id' => $childPayment->payment_gateway_id,
                        'customer_payment_instrument_id' => $childPayment->customer_payment_instrument_id,
                        'created_at' => $childPayment->created_at,
                        'updated_at' => $childPayment->updated_at,
                    ]);

                    $payment_sr_no++;
                    // Delete the child payment from the old table
                    ////$childPayment->delete();
                }
                if ($payment->code == $code) {
                    $payment->captured_amount = $parentCollectionAmount;
                    $payment->save();
                }
                Log::info('MigratePayment::Payment migrated for Payment Code: '.$payment->code);
            }            
            return true;       
        } else {
            Log::info('MigratePayment::Payment does not exists for Payment Code: '.$payment->code);
            return false;
        }
    }
}
