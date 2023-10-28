<?php

namespace App\Repositories;

use App\Enums\PaymentMethodsEnum;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTypeId;
use App\Models\QuoteDocument;
use App\Models\PersonalQuote;

use App\Controllers\SageApi;
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
                if($paymentSplitRecord){
                    if( $paymentSplitRecord->payment_method=='CC' ){
                        $this->generateSplitPaymentLink($quoteID,$paymentSplitRecord->id,$request->modelType,$request->quote_id);
                    }
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
    }


    public function generateSplitPaymentLink($code,$splitPaymentId,$modelType,$quoteId)
    {
        $payment = Payment::where('code', '=', $code)->first();
        $splitPayment = PaymentSplits::where(['code'=>$code, 'id'=>$splitPaymentId])->first();        
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
                'code' => $payment->code,
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
                'merchantOrderReference' => strtoupper($payment->code),
            ];

            $splitPayment->payment_link = $paymentLinkURL;
            $splitPayment->payment_link_created_at=now();
            $splitPayment->save();
            return;
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
            PaymentSplits::find($request->splitPaymentId)->update($paymentInformation);
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
            PaymentSplits::find($request->splitPaymentId)->update($paymentInformation);
            $successMessage='Payment Declined';
        }

        //Update parent payment status if all splits are paid
        $paymentSplitRecord = PaymentSplits::with('payment')->find($request->splitPaymentId);
        $totalPaidPayments = PaymentSplits::where('payment_status_id', PaymentStatusEnum::PAID)->count();
        if ($totalPaidPayments == $paymentSplitRecord->payment->total_payments){
            Payment::where('code', $paymentSplitRecord->code)->update(['payment_status_id' => PaymentStatusEnum::PAID]);            
        }
        
        //dd($paymentSplitRecord->payment->total_payments);



        //PaymentSplits::find($request->splitPaymentId)->update($paymentInformation);
        return $successMessage;
    }
}

