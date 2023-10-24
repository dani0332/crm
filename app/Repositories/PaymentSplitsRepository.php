<?php

namespace App\Repositories;

use App\Models\PaymentSplits;
use App\Enums\PaymentStatusEnum;
use App\Models\QuoteDocument;

use App\Controllers\SageApi;

class PaymentSplitsRepository
{
    public static function getByCode($code)
    {
        return PaymentSplits::with(['paymentStatus', 'paymentMethod'])
            ->where('code', $code)
            ->get();
    }

    public function addPaymentSplits($request,$quoteID)
    {
        for($i=1; $i<=(count($request->split_payment_details['split_amount'])-1); $i++) {
            if (isset($request->split_payment_details['payment_type'][$i]) && $request->split_payment_details['payment_type'][$i]!=NULL) {
                $splitPaymentInformation = [
                    'code' => $quoteID,
                    'sr_no' => $i,
                    'payment_method' => $request->split_payment_details['payment_type'][$i],
                    'check_detail' => isset($request->split_payment_details['check_detail'][$i]) ? $request->split_payment_details['check_detail'][$i] : null,
                    'payment_amount' => $request->split_payment_details['split_amount'][$i],
                    'due_date' => $request->split_payment_details['due_date'][$i],   
                    'payment_status_id' => PaymentStatusEnum::NEW,             
                ];
                
                $paymentSplitRecord = PaymentSplits::create($splitPaymentInformation);
                
                //add document references
                if(isset($request->split_payment_details['document_detail'][$i]) 
                    && $paymentSplitRecord 
                    && count($request->split_payment_details['document_detail'][$i]) 
                    ){
                    foreach($request->split_payment_details['document_detail'][$i] as $document){
                        $quoteDocumentRec = QuoteDocument::find($document['id']);
                        $quoteDocumentRec = QuoteDocument::find($document['id']);
                        if ($quoteDocumentRec){
                            $quoteDocumentRec->payment_split_id = $paymentSplitRecord->id;
                            $quoteDocumentRec->save();
                        }
                    }
                }
            }
        }
    }

    public function updatePaymentSplits($request)
    {
        $paymentSplits = PaymentSplits::with('documents')->where(['code'=>$request->paymentCode])->get(); 
        $paymentPaidSerialNo = [];
        $splitPaymentDocumentIds = [];
        if($paymentSplits){
            foreach($paymentSplits as $paymentSplit){
                if($paymentSplit->payment_status_id == PaymentStatusEnum::PAID) {
                    $paymentPaidSerialNo[] = $paymentSplit->sr_no;
                    continue;
                }
                //dd($paymentSplit->documents()->count());
                foreach($paymentSplit->documents as $document){
                    $splitPaymentDocumentIds[$paymentSplit->sr_no][] = $document->id;
                }
                
                $paymentSplit->documents()->delete();
                $paymentSplit->delete();
            }
        }

        for($i=1; $i<=(count($request->split_payment_details['split_amount'])-1); $i++) {
            if(in_array($i, $paymentPaidSerialNo)){
                continue;
            }

            if (isset($request->split_payment_details['payment_type'][$i]) && $request->split_payment_details['payment_type'][$i]!=NULL) {
                $splitPaymentInformation = [
                    'code' => $request->paymentCode,
                    'sr_no' => $i,
                    'payment_method' => $request->split_payment_details['payment_type'][$i],
                    'check_detail' => isset($request->split_payment_details['check_detail'][$i]) ? $request->split_payment_details['check_detail'][$i] : null,
                    'payment_amount' => $request->split_payment_details['split_amount'][$i],
                    'due_date' => $request->split_payment_details['due_date'][$i],   
                    'payment_status_id' => PaymentStatusEnum::NEW,
                ];
                
                $paymentSplitRecord = PaymentSplits::create($splitPaymentInformation);
                
                //add document references
                if(isset($request->split_payment_details['document_detail'][$i]) 
                    && $paymentSplitRecord 
                    && count($request->split_payment_details['document_detail'][$i]) 
                    ){
                    foreach($request->split_payment_details['document_detail'][$i] as $document){
                        
                        if( isset($splitPaymentDocumentIds[$i]) && in_array($document['id'], $splitPaymentDocumentIds[$i])){
                            $quoteDocumentRec = QuoteDocument::withTrashed()->find($document['id']);
                            if ($quoteDocumentRec){
                                $quoteDocumentRec->restore();
                                $quoteDocumentRec->payment_split_id = $paymentSplitRecord->id;
                                $quoteDocumentRec->save();
                            }
                            continue;
                        }
                        $quoteDocumentRec = QuoteDocument::find($document['id']);                    
                        if ($quoteDocumentRec){
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
        $successMessage='Payment Verified';
        if($request->is_approved) {
            $paymentInformation = [
                'collection_amount' => $request->collection_amount,
                'bank_reference_number' => $request->bank_reference_number,
                'payment_status_id' => PaymentStatusEnum::PAID,
                'updated_by' => $request->user()->id,
            ];
            /*
            $sageRequest->discount = floatval($request->discount);
            $sageRequest->insurerInvoiceDate = date("Y-m-d", strtotime($request->insurerInvoiceDate));
            $sageRequest->policyExpiryDate   = date("Ymd", strtotime($request->policyExpiryDate));
            $sageRequest->premiumWithoutTax = floatval($request->premiumWithoutTax);
            $sageRequest->premiumWithTax = floatval($request->premiumWithTax);
            $sageRequest->vatOnCommission = floatval($request->vatOnCommission);
            $sageRequest->commission = floatval($request->commission);
            $sageRequest->commissionIncludingVat = floatval($request->commissionIncludingVat);  
            $sageApi = new SageApi();
            $sageApi->processSagePost($sageRequest);*/

        } elseif($request->is_declined) {
            $paymentInformation = [
                'declined_reason_id' => $request->declined_reason,
                'declined_custom_reason' => $request->declined_custom_reason,
                'payment_status_id' => PaymentStatusEnum::DECLINED,
                'updated_by' => $request->user()->id,
            ];
            $successMessage='Payment Declined';
        }
        PaymentSplits::find($request->splitPaymentId)->update($paymentInformation);
        return $successMessage;
    }
}

