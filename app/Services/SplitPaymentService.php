<?php

namespace App\Services;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Factories\SagePayloadFactory;
use App\Http\Controllers\SageApi;
use App\Models\PaymentSplits;
use App\Models\QuoteDocument;
use App\Services\SageApiService;
use App\Traits\SageLoggable;
use App\Traits\GenericQueriesAllLobs;

use Illuminate\Support\Facades\Auth;

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

    public function createSageRecipt($request,$splitPayment)
    {        
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
            
            if($readyToPostResponse !== ''){
                $this->logSageApiCall($readyToPostReceiptAr, $readyToPostResponse, $splitPayment, 3, 4, 'fail');
                $returnMessage['response'] = 'Error while making ready to post to sage';
                return $returnMessage;
            } else {
                if($isLiveApiCallStep3){
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

            if(isset($postedResponse['error'])){
                $returnMessage['response'] = 'Error while posting to sage';
                $this->logSageApiCall($aRPostReceipts, $postedResponse, $splitPayment, 4, 4, 'fail');
                return $returnMessage;
            } else {
                if($isLiveApiCallStep4){
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
}