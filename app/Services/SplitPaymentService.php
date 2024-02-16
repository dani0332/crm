<?php

namespace App\Services;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\DocumentTypeCode;
use App\Factories\SagePayloadFactory;
use App\Models\PaymentSplits;
use App\Models\QuoteDocument;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\SageLoggable;
use App\Services\QuoteDocumentService;
use App\Repositories\CustomerRepository;
use PDF;

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

    public function createReciept($modelType, $quoteId, $splitPayment)
    {
        try {
            $quote = $this->getQuoteObject($modelType, $quoteId);
            $quote->load(['customer']);               
            $data = [];
            $data['order_amount'] = number_format($splitPayment->collection_amount, 2, '.', ',');
            $data['payment_split_id'] = $splitPayment->id;

            $data['customer_name'] = $quote->customer->first_name.' '.$quote->customer->last_name;
            $data['receipt_number'] = $splitPayment->code;
            $data['order_number'] = $splitPayment->code.'-'.$splitPayment->sr_no;
            $data['pdf_filename'] = $splitPayment->code.'-'.$splitPayment->sr_no;
            $data['captured_at'] = date('Y-m-d', strtotime($splitPayment->captured_at));
            $data['order_at'] =  $splitPayment->created_at;
            
            if($modelType == QuoteTypes::BUSINESS->value || $modelType == QuoteTypes::GROUP_MEDICAL->value
            || $modelType == QuoteTypes::HOME->value){
                $quote->load(['insuranceProviderDetails']);
                $data['insurance_company'] = $quote->insuranceProviderDetails->text; 
            } else if($modelType == QuoteTypes::CAR->value || $modelType == QuoteTypes::HEALTH->value
            || $modelType == QuoteTypes::TRAVEL->value){
                $quote->load(['plan']);
                $data['insurance_company'] = $quote->plan->text; 
            } else {
                $quote->load(['insuranceProvider']);
                $data['insurance_company'] = $quote->insuranceProvider->text;
            }

            $splitPayment->load(['payment','paymentMethod']);
            $data['payment_method'] = $splitPayment->paymentMethod->name;
            $data['remarks'] = $splitPayment->payment->notes;
            $data['vat'] = number_format(0, 2, '.', ',');
            $data['discount'] = number_format(0, 2, '.', ',');
            
            if($modelType == QuoteTypes::BUSINESS->value){
                $quote->load(['businessTypeOfInsurance']);
                $data['type_of_insurance'] = $quote->businessTypeOfInsurance->text;
            } else {
                $data['type_of_insurance'] =  $modelType.' Insurance';
            }

            $documentType = DocumentTypeCode::CPD; // default car
            if($modelType == QuoteTypes::HOME->value){
                $documentType = DocumentTypeCode::HOMPD;
            }else if($modelType == QuoteTypes::HEALTH->value){
                $documentType = DocumentTypeCode::HPD;
            }else if($modelType == QuoteTypes::LIFE->value){
                $documentType = DocumentTypeCode::LPD;
            }else if($modelType == QuoteTypes::BUSINESS->value){
                $documentType = DocumentTypeCode::CLPD;
            }else if($modelType == QuoteTypes::BIKE->value){
                $documentType = DocumentTypeCode::BPD;
            }else if($modelType == QuoteTypes::YACHT->value){
                $documentType = DocumentTypeCode::YPD;
            }else if($modelType == QuoteTypes::TRAVEL->value){
                $documentType = DocumentTypeCode::TPD;
            }else if($modelType == QuoteTypes::PET->value){
                $documentType = DocumentTypeCode::PPD;
            }else if($modelType == QuoteTypes::CYCLE->value){
                $documentType = DocumentTypeCode::CYCPD;
            }else if($modelType == QuoteTypes::GROUP_MEDICAL->value){
                $documentType = DocumentTypeCode::GMQPD;
            }
        
            $data['document_type_code'] = $documentType;
            $data['quote_uuid'] = $quote->uuid;
        
            $pdf = PDF::loadView('pdf.payment_receipt', compact('data'))->setOptions(['defaultFont' => 'DejaVu Sans']);
            $pdf->setPaper('A4');
            $pdfFile = $pdf->output();
            $document = app(QuoteDocumentService::class)->uploadQuoteDocument($pdfFile, $data, $quote, false, true);
        } catch (\Exception $ex) {
            info("Payment Reciept - ERROR:".$ex->getMessage());
        }
        return;       
    }
}
