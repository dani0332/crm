<?php

namespace App\Repositories;

use App\Enums\ApplicationStorageEnums;
use App\Enums\CollectionTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\DocumentTypeEnum;
use App\Enums\PaymentAllocationStatus;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\SendUpdateLogStatusEnum;
use App\Interfaces\PaymentRepositoryInterface;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\PaymentStatusLog;
use App\Models\QuoteDocument;
use App\Models\SendUpdateLog;
use App\Models\TravelQuote;
use App\Services\ApplicationStorageService;
use App\Services\PaymentLinkService;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\HandlesDeadlockRetries;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    use GenericQueriesAllLobs;
    use HandlesDeadlockRetries;

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

    public function fetchCreateNewPayment($request)
    {
        DB::beginTransaction();
        try {
            $quoteModel = $this->getQuoteObject($request->modelType, $request->quote_id);
            $masterPayment = (object) $request->payment;
            $masterPaymentStatus = PaymentStatusEnum::NEW;
            if ($masterPayment->payment_methods == PaymentMethodsEnum::CreditApproval) {
                $masterPaymentStatus = PaymentStatusEnum::CREDIT_APPROVED;
            }

            $paymentInformation = [
                'total_price' => $masterPayment->total_price,
                'notes' => ! empty($masterPayment->notes) ? $masterPayment->notes : null,
                'custom_reason' => ! empty($masterPayment->custom_reason) ? $masterPayment->custom_reason : null,
                'discount_reason' => ! empty($masterPayment->discount_reason) ? $masterPayment->discount_reason : null,
                'discount_custom_reason' => ! empty($masterPayment->discount_custom_reason) ? $masterPayment->discount_custom_reason : null,
                'discount_type' => ! empty($masterPayment->discount) ? $masterPayment->discount : null,
                'frequency' => $masterPayment->frequency,
                'credit_approval' => $masterPayment->credit_approval,
                'total_payments' => $masterPayment->payment_no,
                'collection_type' => $masterPayment->collection_type,
                'captured_amount' => 0,
                'total_amount' => $masterPayment->total_amount, //amount after discount
                'collection_date' => $masterPayment->collection_date,
                'discount_value' => $masterPayment->discount_value,
                'payment_methods_code' => $masterPayment->payment_methods,
                'payment_status_id' => $masterPaymentStatus,
                'plan_id' => ! empty($request->plan_id) ? $request->plan_id : null,
                'insurance_provider_id' => ! empty($request->insurance_provider_id) ? $request->insurance_provider_id : null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ];

            // Payment follow up count is now iterative (- nth+1) and not dependent on the count of payments in the quote
            // Count will be iterative for each payment added through the send update or Child lead
            $mainLeadCode = implode('-', array_slice(explode('-', $quoteModel->code), 0, 2));
            $paymentCount = $this->getPaymentsCountByLeadCode($mainLeadCode);
            $paymentInformation['code'] = ($paymentCount > 0) ? $mainLeadCode.'-'.$paymentCount : $mainLeadCode;

            if ($request->send_update_id) {
                // it will make $quoteModel as SendUpdateLog model.
                $paymentInformation['send_update_log_id'] = $request->send_update_id;
                $quoteModel = SendUpdateLogRepository::getLogById($request->send_update_id);
            }

            if ($masterPayment->reference) {
                $paymentInformation['reference'] = $masterPayment->reference;
            }
            if ($masterPayment->payment_methods != PaymentMethodsEnum::CreditCard && $masterPayment->payment_methods != PaymentMethodsEnum::InsureNowPayLater) {
                $paymentInformation['authorized_at'] = now();
            }
            $quoteModel->payments()->create($paymentInformation);
            //Add split payments start
            $this->addPaymentSplits($request, $paymentInformation['code']);
            //Add split payments ends

            $paymentLog = new PaymentStatusLog([
                'current_payment_status_id' => PaymentStatusEnum::NEW,
                'payment_code' => $paymentInformation['code'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $paymentLog->save();
            if (! $request->send_update_id) { // it will check if the payment is added from send update.
                $quoteModel->quote_status_id = QuoteStatusEnum::PaymentPending;
            }
            $quoteModel->save();
            DB::commit();

            return ['status' => 'success', 'message' => 'Payment Added'];
        } catch (Exception $exception) {
            DB::rollBack(); // Rollback changes if any error occurred

            return ['status' => 'error', 'message' => $exception->getMessage()];
        }
    }

    public function fetchUpdateNewPayment($request)
    {
        $maxRetries = 2;

        return $this->handleWithDeadlockRetries(function () use ($request) {
            $masterPayment = (object) $request->payment;

            $payment = Payment::where('code', $request->paymentCode)->first();
            if (! $payment) {
                return ['status' => 'error', 'message' => 'Payment record not found'];
            }

            if ($request->isPaymentLocked) { // Check if payment is locked to update specific fields
                $paymentInformation = [
                    'notes' => ! empty($masterPayment->notes) ? $masterPayment->notes : null,
                    'custom_reason' => ! empty($masterPayment->custom_reason) ? $masterPayment->custom_reason : null,
                    'credit_approval' => $masterPayment->credit_approval,
                    'updated_by' => $request->user()->id,
                ];

                // Check if payment frequency is upfron and Old or new Payment method is Proforma Payment Request, only than update parent payment method
                $isProformaPaymentNewParentPaymentMethod = $masterPayment->payment_methods == PaymentMethodsEnum::ProformaPaymentRequest;
                $isProformaPaymentOldParentPaymentMethod = $payment->payment_methods_code == PaymentMethodsEnum::ProformaPaymentRequest;
                $isParentPaymentFrequencyUpfront = $payment->frequency == PaymentFrequency::UPFRONT;
                if ($isParentPaymentFrequencyUpfront && ($isProformaPaymentNewParentPaymentMethod || $isProformaPaymentOldParentPaymentMethod)) {
                    $paymentInformation['payment_methods_code'] = $masterPayment->payment_methods;
                }

            } else {

                $paymentInformation = [
                    'total_price' => $masterPayment->total_price,
                    'notes' => ! empty($masterPayment->notes) ? $masterPayment->notes : null,
                    'custom_reason' => ! empty($masterPayment->custom_reason) ? $masterPayment->custom_reason : null,
                    'discount_reason' => $masterPayment->discount_reason,
                    'discount_custom_reason' => $masterPayment->discount_custom_reason,
                    'discount_type' => $masterPayment->discount,
                    'frequency' => $masterPayment->frequency,
                    'credit_approval' => $masterPayment->credit_approval,
                    'total_payments' => $masterPayment->payment_no,
                    'collection_type' => $masterPayment->collection_type,
                    'total_amount' => $masterPayment->total_amount, //amount after discount
                    'collection_date' => $masterPayment->collection_date,
                    'discount_value' => $masterPayment->discount_value,
                    'payment_methods_code' => $masterPayment->payment_methods,
                    'insurance_provider_id' => ! empty($request->insurance_provider_id) ? $request->insurance_provider_id : null,
                    'updated_by' => $request->user()->id,
                ];

                if ($masterPayment->reference) {
                    $paymentInformation['reference'] = $masterPayment->reference;
                }
                if ($masterPayment->payment_methods == PaymentMethodsEnum::CreditApproval) {
                    $paymentInformation['payment_status_id'] = PaymentStatusEnum::CREDIT_APPROVED;
                } elseif ($payment->payment_status_id == PaymentStatusEnum::CREDIT_APPROVED) {
                    $paymentInformation['payment_status_id'] = PaymentStatusEnum::NEW;
                }
            }
            $payment->update($paymentInformation);

            //Update split payments start
            if (! empty($request->trashedFilesModal)) {
                QuoteDocument::whereIn('doc_name', $request->trashedFilesModal)->delete();
            }
            $this->updatePaymentSplits($request);

            return ['status' => 'success', 'message' => 'Payment Updated'];
        }, $maxRetries);
    }

    //Add split payments
    public function addPaymentSplits($request, $quoteID)
    {
        $masterPayment = (object) $request->payment;
        $totalSplitPayments = count($masterPayment->payment_splits);
        $discount = 0;
        if (isset($masterPayment->discount_value) && $masterPayment->discount_value > 0) {
            $discount = app(SplitPaymentService::class)->calculateDiscount($totalSplitPayments, $masterPayment->discount_value);
        }

        foreach ($masterPayment->payment_splits as $splitPayment) {
            if (isset($splitPayment['payment_method']) && $splitPayment['payment_method'] != null) {
                //[$priceWithoutVat, $vat] = app(SplitPaymentService::class)->calculatePriceAndVat($masterPayment->frequency, $masterPayment->total_price, $splitPayment['sr_no'], $splitPayment['payment_amount'], $request->modelType, $request->quote_id, $totalSplitPayments);
                $splitPaymentInformation = [
                    'code' => $quoteID,
                    'sr_no' => $splitPayment['sr_no'],
                    'payment_method' => $splitPayment['payment_method'],
                    'check_detail' => isset($splitPayment['check_detail']) ? $splitPayment['check_detail'] : null,
                    'payment_amount' => $splitPayment['payment_amount'],
                    'due_date' => $splitPayment['due_date'],
                    'payment_status_id' => PaymentStatusEnum::NEW,
                    'discount_value' => $discount,
                ];
                $paymentSplitRecord = PaymentSplits::create($splitPaymentInformation);
                if ($paymentSplitRecord) {
                    //add document references
                    if (isset($splitPayment['document_detail']) && count($splitPayment['document_detail'])) {
                        foreach ($splitPayment['document_detail'] as $document) {
                            $quoteDocumentRec = QuoteDocument::find($document['id'] ?? '');
                            if ($quoteDocumentRec) {
                                $quoteDocumentRec->payment_split_id = $paymentSplitRecord->id;
                                $quoteDocumentRec->save();
                            }
                        }
                    }
                    $childPaymentStatus = app(SplitPaymentService::class)->getChildPaymentStatus($paymentSplitRecord);
                    $paymentSplitRecord->update(['payment_status_id' => $childPaymentStatus]);
                }
            }
        }
        //Update parent payment status
        $payment = Payment::where('code', $quoteID)->first();
        $this->setMasterPaymentStatus($payment);
        app(SplitPaymentService::class)->uploadDiscountDocuments($masterPayment->payment_splits[0]['discount_documents'], $quoteID);
    }

    public function updatePaymentSplits($request)
    {
        $masterPayment = (object) $request->payment;
        $paymentSplits = PaymentSplits::with('documents')->where(['code' => $request->paymentCode])->get();
        $paymentPaidSerialNo = [];
        $splitPaymentDocumentIds = [];
        //Skipping paid payments and deleting extra payments
        if ($paymentSplits) {
            foreach ($paymentSplits as $paymentSplit) {
                if (
                    in_array($paymentSplit->payment_status_id, [
                        PaymentStatusEnum::PAID,
                        PaymentStatusEnum::PARTIAL_CAPTURED,
                        PaymentStatusEnum::PARTIALLY_PAID,
                        PaymentStatusEnum::CAPTURED,
                        PaymentStatusEnum::AUTHORISED,
                    ])
                ) {
                    $paymentPaidSerialNo[] = $paymentSplit->sr_no;

                    continue;
                }
                if (($masterPayment->payment_no < $paymentSplits->count()) && $paymentSplit->sr_no > $masterPayment->payment_no) {
                    // Delete QuoteDocuments referencing the payment split
                    $paymentSplit->documents()->forceDelete();
                    // Then delete the payment split
                    $paymentSplit->delete();

                    // Unset/remove the element with sr_no from the split payment object
                    foreach ($masterPayment->payment_splits as $key => $payment_split) {
                        if ($payment_split['sr_no'] === $paymentSplit->sr_no) {
                            unset($masterPayment->payment_splits[$key]);
                        }
                    }
                }
            }
        }
        $totalSplitPayments = count($masterPayment->payment_splits);
        $discount = 0;
        if (isset($masterPayment->discount_value) && $masterPayment->discount_value > 0 && count($paymentPaidSerialNo) == 0) {
            $discount = app(SplitPaymentService::class)->calculateDiscount($totalSplitPayments, $masterPayment->discount_value);
        }

        foreach ($masterPayment->payment_splits as $splitPayment) {
            $serialNo = $splitPayment['sr_no'];
            if (in_array($serialNo, $paymentPaidSerialNo)) {
                continue;
            }

            if (isset($splitPayment['payment_method']) && $splitPayment['payment_method'] != null) {

                if ($request->isPaymentLocked) { // Check if payment is locked to update specific fields
                    $splitPaymentInformation = [
                        'payment_method' => $splitPayment['payment_method'],
                    ];
                } else {
                    $splitPaymentInformation = [
                        'code' => $request->paymentCode,
                        'sr_no' => $serialNo,
                        'payment_method' => $splitPayment['payment_method'],
                        'check_detail' => isset($splitPayment['check_detail']) ? $splitPayment['check_detail'] : null,
                        'payment_amount' => $splitPayment['payment_amount'],
                        'payment_status_id' => PaymentStatusEnum::NEW, //reset status to 'NEW
                        'due_date' => $splitPayment['due_date'],
                        'discount_value' => $discount,
                    ];
                }

                $paymentSplitRecord = PaymentSplits::where(['code' => $request->paymentCode, 'sr_no' => $serialNo])->first();
                if (! $paymentSplitRecord) {
                    $paymentSplitRecord = PaymentSplits::create($splitPaymentInformation);
                } else {
                    $paymentSplitRecord->update($splitPaymentInformation);
                }
                //add document references
                if (
                    isset($splitPayment['document_detail'])
                    && $paymentSplitRecord
                    && count($splitPayment['document_detail'])
                ) {
                    foreach ($splitPayment['document_detail'] as $document) {
                        $quoteDocumentRec = QuoteDocument::find($document['id']);
                        if ($quoteDocumentRec) {
                            $quoteDocumentRec->payment_split_id = $paymentSplitRecord->id;
                            $quoteDocumentRec->save();
                        }
                    }
                }
                if ($paymentSplitRecord) {
                    $childPaymentStatus = app(SplitPaymentService::class)->getChildPaymentStatus($paymentSplitRecord);
                    $paymentSplitRecord->update(['payment_status_id' => $childPaymentStatus]);
                }
            }
        }
        $payment = Payment::where('code', $request->paymentCode)->first();
        $this->setMasterPaymentStatus($payment);
        app(SplitPaymentService::class)->uploadDiscountDocuments($masterPayment->payment_splits[0]['discount_documents'], $request->paymentCode);
    }

    public function fetchUpdateSplitPaymentsApprove($request)
    {
        if ($request->is_declined) {
            if ($request->send_update_id > 0) {
                $quoteModel = SendUpdateLogRepository::getLogById($request->send_update_id);
            } else {
                $quoteModel = $this->getQuoteObject($request->modelType, $request->quote_id);
            }
            $firstPayment = $quoteModel->payments()->where('code', $request->payment_code)->first();
            $firstPayment->update([
                'decline_reason_id' => $request->declined_reason,
                'decline_custom_reason' => $request->declined_custom_reason,
                'updated_by' => Auth::user()->id,
            ]);
            if ($request->send_update_id > 0) {
                $quoteModel->status = SendUpdateLogStatusEnum::TRANSACTION_DECLINE;
            } else {
                $quoteModel->quote_status_id = QuoteStatusEnum::TransactionDeclined;
            }
            $quoteModel->save();
            $successMessage = 'Transaction declined';
        } else {
            if ($request->is_capture) { //update collected amount in childs
                foreach ($request->collection_amount as $key => $splitAmount) {
                    $paymentSplit = PaymentSplits::where(['code' => $request->payment_code, 'sr_no' => $key])->first();
                    if ($paymentSplit && $paymentSplit->payment_status_id != PaymentStatusEnum::PAID) {
                        // process split payment approve
                        app(SplitPaymentService::class)->processSplitPaymentApprove($request->modelType, $request->quote_id, $paymentSplit->id, $splitAmount);
                    }
                }
            }
            // process master payment approve
            $successMessage = app(SplitPaymentService::class)->processMasterPaymentApprove($request->modelType, $request->quote_id, $request->send_update_id);
        }

        return $successMessage;
    }

    //migrate payments
    public function fetchMigratePayments($request)
    {
        $quoteModel = $this->getQuoteObject(request()->model_type, request()->quote_id);
        $oldPayment = $quoteModel->payments()->where('code', $request->payment_code)->first();
        $paymentMigrated = app(SplitPaymentService::class)->migratePayments($oldPayment, $request->model_type);
        if ($paymentMigrated) {
            return response()->json(['message' => 'Payment Migrated Successfully']);
        }

        return response()->json(['error' => 'Payment Migration Failed']);
    }

    //update total price
    public function fetchUpdateTotalPrice($request)
    {
        $quoteModel = $this->getQuoteObject(request()->model_type, request()->quote_id);
        $payment = $quoteModel->payments()->where('code', $request->payment_code)->first();
        if ($payment) {
            $payment->total_price = $request->total_price;
            $payment->is_approved = 0;
            $payment->payment_status_id = PaymentStatusEnum::PARTIAL_CAPTURED;
            $payment->save();
            $this->updateLeadStatus($payment); //update lead status

            return response()->json(['message' => 'Total Price Updated Successfully']);
        }

        return response()->json(['error' => 'Total Price Update Failed']);
    }

    public function fetchUpdatePaymentStatus($request)
    {
        $successMessage = 'Payment Verified';
        $splitPayment = PaymentSplits::find($request->splitPaymentId);
        $masterPayment = $splitPayment->payment;
        if ($request->is_approved && $splitPayment->payment_status_id != PaymentStatusEnum::PAID) {
            $paymentInformation = [
                'collection_amount' => $request->collection_amount,
                'bank_reference_number' => $request->bank_reference_number,
                'payment_status_id' => PaymentStatusEnum::CAPTURED,
                'payment_allocation_status' => PaymentAllocationStatus::NOT_ALLOCATED,
                'updated_by' => $request->user()->id,
                'verified_at' => now(),
                'verified_by' => $request->user()->id,
            ];

            //associate approved documents with payment split
            if (
                isset($request->approved_document_model[$splitPayment->sr_no])
                && count($request->approved_document_model[$splitPayment->sr_no]) > 0
            ) {
                foreach ($request->approved_document_model[$splitPayment->sr_no] as $document) {
                    $quoteDocumentRec = QuoteDocument::find($document['id'] ?? '');
                    if ($quoteDocumentRec) {
                        if (empty($document['payment_split_id'])) {
                            $quoteDocumentRec->payment_split_id = $splitPayment->id;
                        } else {
                            $quoteDocumentRec->document_type_code = $this->mapToReciept($quoteDocumentRec->document_type_code);
                            $quoteDocumentRec->document_type_text = DocumentTypeEnum::RECEIPT;
                        }
                        $quoteDocumentRec->save();
                    }
                }
            }

            //create sage reciept
            $isSageEnabled = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::SAGE_ENABLED);

            if ($isSageEnabled) {
                $sageResponse = app(SplitPaymentService::class)->createSageRecipt($request, $splitPayment);
                if ($sageResponse['status'] == 'success') {
                    $paymentInformation['sage_reciept_id'] = $sageResponse['response'];
                    $splitPayment->update($paymentInformation);
                    if ($masterPayment) {
                        $masterPayment->update(
                            [
                                'captured_amount' => ($masterPayment->captured_amount + $request->collection_amount),
                                'payment_allocation_status' => PaymentAllocationStatus::NOT_ALLOCATED,
                            ]
                        );
                    }
                } else {
                    $failMessage = $sageResponse['response'];
                    vAbort($failMessage);
                }
            } else {
                $splitPayment->update($paymentInformation);

                if ($masterPayment) {
                    $masterCapturedAmount = $masterPayment->captured_amount + $request->collection_amount;
                    $masterPayment->update(
                        [
                            'captured_amount' => $masterCapturedAmount,
                            'payment_allocation_status' => PaymentAllocationStatus::NOT_ALLOCATED,
                        ]
                    );
                }
            }
            /* Create payment receipt for broker*/
            if ($masterPayment->collection_type == CollectionTypeEnum::BROKER) {
                app(SplitPaymentService::class)->createReceipt($request->modelType, $request->quote_id, $splitPayment, $request?->send_update_id);
            }
        } elseif ($request->is_declined && $splitPayment->payment_status_id != PaymentStatusEnum::PAID) {
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
        $this->setMasterPaymentStatus($masterPayment);

        return $successMessage;
    }

    //map document type to reciept
    public function mapToReciept($documentTypeCode)
    {
        $map = [
            DocumentTypeCode::CPD => DocumentTypeCode::CPD_RECEIPT,
            DocumentTypeCode::BPD => DocumentTypeCode::BPD_RECEIPT,
            DocumentTypeCode::TPD => DocumentTypeCode::TPD_RECEIPT,
            DocumentTypeCode::HPD => DocumentTypeCode::HPD_RECEIPT,
            DocumentTypeCode::LPD => DocumentTypeCode::LPD_RECEIPT,
            DocumentTypeCode::HOMPD => DocumentTypeCode::HOMPD_RECEIPT,
            DocumentTypeCode::CYCPD => DocumentTypeCode::CYCPD_RECEIPT,
            DocumentTypeCode::CLPD => DocumentTypeCode::CLPD_RECEIPT,
            DocumentTypeCode::GMQPD => DocumentTypeCode::GMQPD_RECEIPT,
            DocumentTypeCode::PPD => DocumentTypeCode::PPD_RECEIPT,
            DocumentTypeCode::YPD => DocumentTypeCode::YPD_RECEIPT,
        ];

        return $map[$documentTypeCode] ?? $documentTypeCode;
    }

    public function setMasterPaymentStatus($payment)
    {
        if ($payment) {
            if ($payment->frequency == 'upfront') {
                if ($payment->paymentSplits[0]->payment_status_id == PaymentStatusEnum::PAID) {
                    $payment->update(
                        ['payment_status_id' => PaymentStatusEnum::CAPTURED]
                    );
                } elseif ($payment->paymentSplits[0]->payment_status_id == PaymentStatusEnum::PARTIALLY_PAID) {
                    $payment->update(
                        ['payment_status_id' => PaymentStatusEnum::PARTIAL_CAPTURED]
                    );
                } else {
                    $payment->update(
                        ['payment_status_id' => $payment->paymentSplits[0]->payment_status_id]
                    );
                }
            } else {
                $totalPaidPayments = PaymentSplits::whereIn('payment_status_id', [
                    PaymentStatusEnum::PAID,
                    PaymentStatusEnum::CAPTURED,
                    PaymentStatusEnum::PARTIAL_CAPTURED,
                    PaymentStatusEnum::PARTIALLY_PAID,
                ])
                    ->where('code', $payment->code)
                    ->count();

                if (
                    $totalPaidPayments == $payment->total_payments
                    && $payment->captured_amount >= ($payment->total_price - $payment->discount_value)
                ) {
                    $payment->update(
                        ['payment_status_id' => PaymentStatusEnum::CAPTURED]
                    );
                } elseif ($totalPaidPayments > 0) {
                    $payment->update(
                        ['payment_status_id' => PaymentStatusEnum::PARTIAL_CAPTURED]
                    );
                } else {
                    //verify credit approved status
                    $totalCreditPayments = PaymentSplits::whereIn('payment_status_id', [
                        PaymentStatusEnum::CREDIT_APPROVED,
                    ])->where('code', $payment->code)->count();
                    if ($totalCreditPayments > 0) {
                        $payment->update(
                            ['payment_status_id' => PaymentStatusEnum::CREDIT_APPROVED]
                        );
                    } else {
                        $payment->update(
                            ['payment_status_id' => PaymentStatusEnum::NEW]
                        );
                    }
                }
            }
            $this->updateLeadStatus($payment); //update lead status
        }
    }

    // Update lead status for ecomm quotes
    private function updateLeadStatus($payment)
    {
        $quoteType = '';
        if ($payment->paymentable_type == CarQuote::class) {
            $quoteType = quoteTypeCode::Car;
        } elseif ($payment->paymentable_type == HealthQuote::class) {
            $quoteType = quoteTypeCode::Health;
        } elseif ($payment->paymentable_type == TravelQuote::class) {
            $quoteType = quoteTypeCode::Travel;
        }
        // If a quote type is found, get the corresponding quote object
        if ($quoteType !== '') {
            $quoteModel = $this->getQuoteObject($quoteType, $payment->paymentable_id);
            if ($quoteModel) {
                $quoteModel->payment_status_id = $payment->payment_status_id;
                if ($payment->payment_status_id == PaymentStatusEnum::PAID) {
                    $quoteModel->payment_paid_at = now();
                }
                $quoteModel->save();
            }
        }
    }

    public function getPaymentsCountByLeadCode($quoteCode)
    {
        return $this->where('code', 'LIKE', "%{$quoteCode}%")->count();
    }

    public function fetchGetPaymentByInsurerInvoiceNumber($quote, $invoiceNumber)
    {
        return $quote->payments()->where('insurer_tax_number', $invoiceNumber)->first();
    }

    public function updatePriceVatApplicableAndVat($quote, $modelType)
    {
        /* Start - Temporarily adding for correcting historic data  */
        info('Start - Temporarily adding for correcting historic data'.$quote->uuid);
        /* calculate price and vat for payments for old payment data  where price_vat_applicable is not available */
        $quotePayment = Payment::where('code', $quote->code)->mainLeadPayment()->with('paymentSplits')->first();
        if ($quotePayment) {
            if (! $quotePayment->price_vat_applicable) {
                [$priceWithoutVat, $vat] = app(SplitPaymentService::class)->calculateMasterPriceAndVat($quotePayment->frequency, $quotePayment->total_price, $modelType, $quote->id);
                $quotePayment->update([
                    'price_vat_applicable' => $priceWithoutVat,
                    'price_vat' => $vat,
                ]);
            }

            /* calculate price and vat for split payments for old split payment data where price_vat_applicable is not available */
            $paymentSplits = $quotePayment->paymentSplits;
            if ($paymentSplits->whereNull('price_vat_applicable')->count()) {
                foreach ($quotePayment->paymentSplits as $splitPayment) {
                    if (isset($splitPayment->payment_method) && $splitPayment->payment_method != null) {
                        [$priceWithoutVat, $vat] = app(SplitPaymentService::class)->calculatePriceAndVat($quotePayment->frequency, $quotePayment->total_price, $splitPayment->sr_no, $splitPayment->payment_amount, $modelType, $quote->id, count($paymentSplits));
                        $splitPayment->update([
                            'price_vat_applicable' => $priceWithoutVat,
                            'price_vat' => $vat,
                        ]);
                    }
                }
            }
        }

        info('End - Temporarily adding for correcting historic data '.$quote->uuid);
        /* End - Temporarily adding for correcting historic data  */

    }
}
