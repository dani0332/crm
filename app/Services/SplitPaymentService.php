<?php

namespace App\Services;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Factories\SagePayloadFactory;
use App\Models\PaymentSplits;
use App\Models\QuoteDocument;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\SageLoggable;
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
        $quote = $this->getQuoteObject($modelType, $quoteId);

        dd($quote);
        $data = [];
        $data['customer_name'] = $splitPayment->customer->name;
        
        $data['sage_document_number'] = $splitPayment->customer->id;
        $data['recieved_date'] = $splitPayment->customer->sage_customer_number;
        $data['payment_method'] = $splitPayment->customer->sage_customer_number;
        $data['order_number'] = $splitPayment->customer->sage_customer_number;
        $data['order_date_time'] = $splitPayment->customer->sage_customer_number;
        $data['order_amount'] = $splitPayment->customer->sage_customer_number;
        $data['amount_recieved'] = $splitPayment->customer->sage_customer_number;
        $data['discount'] = $splitPayment->customer->sage_customer_number;

        $data['insurance_company'] = $splitPayment->customer->sage_customer_number;
        $data['type_of_insurance'] = $splitPayment->customer->sage_customer_number;
        $data['vat'] = 0;

        $pdf = PDF::loadView('pdf.payment_receipt', compact('data'))->setOptions(['defaultFont' => 'DejaVu Sans']);
        $pdf->setPaper('A4');
        $pdfFile = $pdf->output();
        $document = $this->quoteDocumentService->uploadQuoteDocument($pdfFile, $data, $quote, true);
       


        /*
        try {
            $quote = $this->getQuoteObject($quoteType, $request->quote_uuid);
            
            $data['company_position_text'] = LookupRepository::where('code', $data['company_position'])->where('key', LookupsEnum::COMPANY_POSITION)->value('text');
            $data['professional_title_text'] = LookupRepository::where('code', $data['professional_title'])->where('key', LookupsEnum::PROFESSIONAL_TITLE)->value('text');
            $data['premium'] = $quote->premium;
            $data['payment_method'] = isset($quote->payments[0]) ? $quote->payments[0]->paymentMethod->name : '';
            
            $data['product_type'] = ucfirst($quoteType).' Insurance';
            $data['document_type_code'] = DocumentTypeCode::KYCDOC;

            $pdf = PDF::loadView('pdf.payment_receipt', compact('data'))->setOptions(['defaultFont' => 'DejaVu Sans']);
            $pdf->setPaper('A4');
            $pdfFile = $pdf->output();
            $document = $this->quoteDocumentService->uploadQuoteDocument($pdfFile, $data, $quote, true);

            if ($document) {
            }
        } catch (\Exception $ex) {
            info("Payment Reciept $request->quote_uuid - ERROR:".$ex->getMessage());
        }
        return $quote;*/
    }








}
