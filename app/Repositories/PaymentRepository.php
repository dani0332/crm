<?php

namespace App\Repositories;

use App\Interfaces\PaymentRepositoryInterface;
use App\Models\Payment;
use App\Models\PaymentStatusLog;
use App\Models\PaymentSplits;
use App\Models\QuoteDocument;
use App\Services\PaymentLinkService;
use App\Services\SplitPaymentService;
use Illuminate\Support\Facades\DB;
use App\Traits\GenericQueriesAllLobs;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\PaymentAllocationStatus;
use App\Enums\QuoteTypeId;


class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    use GenericQueriesAllLobs;
    protected $paymentService;

    public function model()
    {
        return Payment::class;
    }

    public function getPaymentsByQuoteId($quoteId, $quoteTypeId)
    {
        return Payment::where('quote_id', $quoteId)->where('quote_type_id', $quoteTypeId)->get();
    }

    public function getPaymentById($paymentId)
    {
        return Payment::find($paymentId);
    }

    public function deletePayment($paymentId)
    {
        return Payment::destroy($paymentId);
    }

    public function createPayment(array $paymentInformation)
    {
        return Payment::create($paymentInformation);
    }

    public function updatePayment($paymentId, array $newInformation)
    {
        return Payment::find($paymentId)->update($newInformation);
    }

    public function getPaymentLink(PaymentLinkService $paymentLinkService, $paymentId, $quoteTypeId, $leadId)
    {
        $payment = $this->getPaymentById($paymentId);
        $paymentLink = $this->paymentService->getPaymentLink($payment, $quoteTypeId, $leadId);

        return $paymentLink;
    }

    public function fetchCreateNewPayment($request, $quoteModel)
    {   
        DB::beginTransaction();
        try {
        
            $masterPaymentStatus = PaymentStatusEnum::NEW;
            if ($request->payment_methods == PaymentMethodsEnum::CreditApproval) {
                $masterPaymentStatus = PaymentStatusEnum::CREDIT_APPROVED;
            }
            $paymentInformation = [
                'total_price' => $request->total_price,
                'notes' => ! empty($request->notes) ? $request->notes : null,
                'custom_reason' => ! empty($request->custom_reason) ? $request->custom_reason : null,
                'discount_reason' => $request->discount_reason,
                'discount_custom_reason' => $request->discount_custom_reason,
                'discount_type' => $request->discount,
                'frequency' => $request->frequency,
                'credit_approval' => $request->credit_approval,
                'total_payments' => $request->payment_no,
                'collection_type' => $request->collection_type,
                'captured_amount' => 0,
                'total_amount' => $request->total_amount, //amount after discount
                'collection_date' => $request->collection_date,
                'discount_value' => $request->discount_value,
                'payment_methods_code' => $request->payment_methods,
                'payment_status_id' => $masterPaymentStatus,
                'plan_id' => ! empty($request->plan_id) ? $request->plan_id : null,
                'insurance_provider_id' => ! empty($request->insurance_provider_id) ? $request->insurance_provider_id : null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ];

            $count = $quoteModel->payments->count();
            $paymentInformation['code'] = ($count > 0) ? $quoteModel->code.'-'.$count : $quoteModel->code;

            if ($request->reference) {
                $paymentInformation['reference'] = $request->reference;
            }
            if ($request->payment_methods != PaymentMethodsEnum::CreditCard && $request->payment_methods != PaymentMethodsEnum::InsureNowPayLater) {
                $paymentInformation['authorized_at'] = now();
            }
            $payment = Payment::create($paymentInformation);

            //Add split payments start
            $this->addPaymentSplits($request, $paymentInformation['code']);
            //Add split payments ends

            $quoteModel->payments()->save($payment);
            $paymentLog = new PaymentStatusLog([
                'current_payment_status_id' => PaymentStatusEnum::NEW,
                'payment_code' => $paymentInformation['code'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $paymentLog->save();
            $quoteModel->quote_status_id = QuoteStatusEnum::PaymentPending;
            $quoteModel->save();
            DB::commit(); 
            return back()->with('success', 'Payment Added');
        } catch (Exception $exception) {
            DB::rollBack(); // Rollback changes if any error occurred
             return back()->with('error', $exception->getMessage());
        }
    }

    public function fetchUpdateNewPayment($request)
    {   
        DB::beginTransaction();
        try {
            $paymentInformation = [
                'total_price' => $request->total_price,
                'notes' => ! empty($request->notes) ? $request->notes : null,
                'custom_reason' => ! empty($request->custom_reason) ? $request->custom_reason : null,
                'discount_reason' => $request->discount_reason,
                'discount_custom_reason' => $request->discount_custom_reason,
                'discount_type' => $request->discount,
                'frequency' => $request->frequency,
                'credit_approval' => $request->credit_approval,
                'total_payments' => $request->payment_no,
                'collection_type' => $request->collection_type,
                'total_amount' => $request->total_amount, //amount after discount
                'collection_date' => $request->collection_date,
                'discount_value' => $request->discount_value,
                'payment_methods_code' => $request->payment_methods,
                'insurance_provider_id' => ! empty($request->insurance_provider_id) ? $request->insurance_provider_id : null,
                'updated_by' => $request->user()->id,
            ];

            if ($request->reference) {
                $paymentInformation['reference'] = $request->reference;
            }
            $payment = Payment::where('code', $request->paymentCode)->first();
            if (! $payment) {
                return back()->with('message', 'Payment record not found');
            }

            if ($request->payment_methods == PaymentMethodsEnum::CreditApproval) {
                $paymentInformation['payment_status_id'] = PaymentStatusEnum::CREDIT_APPROVED;
            } elseif ($payment->payment_status_id == PaymentStatusEnum::CREDIT_APPROVED) {
                $paymentInformation['payment_status_id'] = PaymentStatusEnum::NEW;
            }
            $payment->update($paymentInformation);

            //Update split payments start
            $this->updatePaymentSplits($request);
            DB::commit(); // Commit changes if everything went well
            return back()->with('success', 'Payment Updated');
        } catch (Exception $exception) {
            DB::rollBack(); // Rollback changes if any error occurred        
            return back()->with('error', $exception->getMessage());
        }    
    }    

    public function addPaymentSplits($request, $quoteID)
    {
        $totalSplitPayments = (count($request->split_payment_details['split_amount']) - 1);
        $discount = 0;
        if (isset($request->discount_value) && $request->discount_value > 0) {
            $discount = app(SplitPaymentService::class)->calculateDiscount($totalSplitPayments, $request->discount_value);
        }

        for ($i = 1; $i <= $totalSplitPayments; $i++) {
            if (isset($request->split_payment_details['payment_type'][$i]) && $request->split_payment_details['payment_type'][$i] != null) {
                $childPaymentStatus = app(SplitPaymentService::class)->getChildPaymentStatus($request->split_payment_details['payment_type'][$i]);
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
        //Update parent payment status        
        $this->setMasterPaymentStatus($request->modelType, $request->quote_id);
        app(SplitPaymentService::class)->uploadDiscountDocuments($request->split_payment_details['discount_documents'], $quoteID);
    }

    public function updatePaymentSplits($request)
    {
        $paymentSplits = PaymentSplits::with('documents')->where(['code' => $request->paymentCode])->get();
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
            $discount = app(SplitPaymentService::class)->calculateDiscount($totalSplitPayments, $request->discount_value);
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
                $childPaymentStatus = app(SplitPaymentService::class)->getChildPaymentStatus($request->split_payment_details['payment_type'][$i]);
                $splitPaymentInformation['payment_status_id'] = $childPaymentStatus;
                $paymentSplitRecord = PaymentSplits::where(['code' => $request->paymentCode, 'sr_no' => $i])->first();
                if (! $paymentSplitRecord) {
                    $paymentSplitRecord = PaymentSplits::create($splitPaymentInformation);
                } else {
                    $paymentSplitRecord->update($splitPaymentInformation);                    
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
        $this->setMasterPaymentStatus($request->modelType, $request->quote_id);
        app(SplitPaymentService::class)->uploadDiscountDocuments($request->split_payment_details['discount_documents'], $request->paymentCode);
    }

    public function fetchUpdateSplitPaymentsApprove($request)
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
                            
                            //create sage reciept
                            if ($paymentSplit->sage_reciept_id==null || $paymentSplit->sage_reciept_id=='' ) {
                                $request->collection_amount = $splitAmount;
                                $sageResponse = app(SplitPaymentService::class)->createSageRecipt($request,$paymentSplit);
                                if ($sageResponse['status'] == 'success'){
                                    $paymentSplit->sage_reciept_id = $sageResponse['response'];
                                } else {
                                    $sageMessage = $sageResponse['response'];
                                    vAbort($sageMessage);                                  
                                }
                            }                                                        
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

    public function fetchUpdatePaymentStatus($request)
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
            //create sage reciept  
            $sageResponse = app(SplitPaymentService::class)->createSageRecipt($request,$splitPayment);
            if ($sageResponse['status'] == 'success'){
                $paymentInformation['sage_reciept_id'] = $sageResponse['response'];
                $splitPayment->update($paymentInformation);               
            } else {
                $failMessage = $sageResponse['response'];
                vAbort($failMessage);                
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
        $this->setMasterPaymentStatus($request->modelType, $request->quote_id);
        return $successMessage;
    }    

    public function setMasterPaymentStatus($modelType, $quote_id)
    {
        $quoteModel = $this->getQuoteObject($modelType, $quote_id);
        $payment = $quoteModel->payments()->first();
        if ($payment) {
            if ($payment->frequency == 'upfront') {
                $payment->update(
                    ['payment_status_id' => $payment->paymentSplits[0]->payment_status_id]
                );
            } else {
                $totalPaidPayments = PaymentSplits::where([
                    'payment_status_id' => PaymentStatusEnum::PAID,
                    'code' => $payment->code,
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
}
