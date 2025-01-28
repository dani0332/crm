<?php

namespace App\Repositories;

use App\Enums\CollectionTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\DocumentTypeEnum;
use App\Enums\PaymentAllocationStatus;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Interfaces\PaymentRepositoryInterface;
use App\Models\BrokerInvoiceNumber;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\PaymentStatusLog;
use App\Models\QuoteDocument;
use App\Models\User;
use App\Services\CentralService;
use App\Services\PaymentLinkService;
use App\Services\SageApiService;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;
use App\Traits\HandlesDeadlockRetries;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
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
            info('Starting payment creation process for Quote: '.$quoteModel->code);
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
                'total_amount' => $masterPayment->total_amount, // amount after discount
                'collection_date' => $masterPayment->collection_date,
                'discount_value' => $masterPayment->discount_value,
                'payment_methods_code' => $masterPayment->payment_methods,
                'payment_status_id' => $masterPaymentStatus,
                'plan_id' => ! empty($request->plan_id) ? $request->plan_id : null,
                'insurance_provider_id' => ! empty($request->insurance_provider_id) ? $request->insurance_provider_id : null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ];

            $count = $quoteModel->payments->count();
            $paymentInformation['code'] = ($count > 0) ? $quoteModel->code.'-'.$count : $quoteModel->code;

            if ($request->send_update_id || ! empty($quoteModel->parent_duplicate_quote_id)) {
                // Payment follow-up count is now iterative (uuid-(nth+1)) and not dependent on the count of payments in the quote
                // Count will be iterative for each payment added through the send update or Child lead
                $mainLeadCode = implode('-', array_slice(explode('-', $quoteModel->code), 0, 2));
                $paymentCount = $this->getPaymentsCountByLeadCode($mainLeadCode);
                $paymentInformation['code'] = ($paymentCount > 0) ? $mainLeadCode.'-'.$paymentCount : $mainLeadCode;

                if (! empty(request()->send_update_id)) {
                    $paymentInformation['send_update_log_id'] = $request->send_update_id;
                    $quoteModel = SendUpdateLogRepository::getLogById($request->send_update_id);
                }
            }

            if ($masterPayment->reference) {
                $paymentInformation['reference'] = $masterPayment->reference;
            }
            if ($masterPayment->payment_methods != PaymentMethodsEnum::CreditCard && $masterPayment->payment_methods != PaymentMethodsEnum::InsureNowPayLater) {
                $paymentInformation['authorized_at'] = now();
            }
            $quoteModel->payments()->create($paymentInformation);
            info('Payment created with Code: '.$paymentInformation['code']);
            // Add split payments start
            $this->addPaymentSplits($request, $paymentInformation['code']);
            // Add split payments ends
            info('Payment splits added for Payment Code: '.$paymentInformation['code']);

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
            info('Payment creation process completed successfully for Payment Code: '.$paymentInformation['code']);

            return ['status' => 'success', 'message' => 'Payment Added'];
        } catch (Exception $exception) {
            DB::rollBack(); // Rollback changes if any error occurred
            info('Error occurred during payment creation: '.$exception->getMessage());

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
                info('Payment does not exist for Payment Code: '.$request->paymentCode);

                return ['status' => 'error', 'message' => 'Payment record not found'];
            }

            if ($request->isPaymentLocked) { // Check if payment is locked to update specific fields
                $paymentInformation = [
                    'notes' => ! empty($masterPayment->notes) ? $masterPayment->notes : null,
                    'custom_reason' => ! empty($masterPayment->custom_reason) ? $masterPayment->custom_reason : null,
                    'credit_approval' => $masterPayment->credit_approval,
                    'updated_by' => $request->user()->id,
                ];

                if ($this->shouldUpdateParentPaymentMethod($payment, $masterPayment)) {
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
                    'total_amount' => $masterPayment->total_amount, // amount after discount
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
            // Log payment update
            info('Payment updated successfully for Payment Code: '.$request->paymentCode);

            // Update split payments start
            if (! empty($request->trashedFilesModal)) {
                QuoteDocument::whereIn('id', $request->trashedFilesModal)->delete();
            }
            $this->updatePaymentSplits($request);

            return ['status' => 'success', 'message' => 'Payment Updated'];
        }, $maxRetries);
    }

    /**
     * Determine if the parent payment method should be updated.
     */
    private function shouldUpdateParentPaymentMethod($payment, $masterPayment): bool
    {
        $isProformaPaymentNewParentPaymentMethod = $masterPayment->payment_methods == PaymentMethodsEnum::ProformaPaymentRequest;
        $isProformaPaymentOldParentPaymentMethod = $payment->payment_methods_code == PaymentMethodsEnum::ProformaPaymentRequest;
        $isParentPaymentFrequencyUpfront = $payment->frequency == PaymentFrequency::UPFRONT;
        $isCreditApprovalRemoved = $payment->credit_approval !== $masterPayment->credit_approval;

        return $isCreditApprovalRemoved || ($isParentPaymentFrequencyUpfront && ($isProformaPaymentNewParentPaymentMethod || $isProformaPaymentOldParentPaymentMethod));
    }

    // Add split payments
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
                    // add document references
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
        // Update parent payment status
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
        // Skipping paid payments and deleting extra payments
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
            // update payment amount for paid payments
            if (isset($request->isPaidEditable) && $request->isPaidEditable && count($paymentPaidSerialNo) === $totalSplitPayments) {
                $paymentSplit = PaymentSplits::where(['code' => $request->paymentCode, 'sr_no' => $serialNo])->first();
                if ($paymentSplit) {
                    $paymentSplit->update(['payment_amount' => $splitPayment['payment_amount']]);
                }

                continue;
            }

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
                        'payment_status_id' => PaymentStatusEnum::NEW, // reset status to 'NEW
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
                // add document references
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

    // Will move this code to helper or some where else later
    private function getQuoteModel($modelType, $quoteId, $sendUpdateId = 0)
    {
        if ($sendUpdateId > 0) {
            return SendUpdateLogRepository::getLogById($sendUpdateId);
        } else {
            return $this->getQuoteObject($modelType, $quoteId);
        }
    }

    /**
     * This method processes the decline of a payment by updating the payment record with the decline reason,
     * updating the status of the quote model, and logging the transaction decline.
     *
     * @param  \Illuminate\Http\Request  $request  The request object containing payment details.
     * @return string The result of the transaction processing.
     */
    private function handlePaymentDecline($request)
    {
        $quoteModel = $this->getQuoteModel($request->modelType, $request->quote_id, $request->send_update_id);
        $firstPayment = $quoteModel->payments()->where('code', $request->payment_code)->first();
        $firstPayment->update([
            'decline_reason_id' => $request->declined_reason,
            'decline_custom_reason' => $request->declined_custom_reason,
            'updated_by' => Auth::user()->id,
        ]);
        if ($request->send_update_id > 0) {
            app(CentralService::class)->updateSendUpdateStatusLogs($quoteModel->id, $quoteModel->status, SendUpdateLogStatusEnum::TRANSACTION_DECLINE);
            $quoteModel->status = SendUpdateLogStatusEnum::TRANSACTION_DECLINE;
        } else {
            $quoteModel->quote_status_id = QuoteStatusEnum::TransactionDeclined;
        }
        $quoteModel->save();
        info('Master payment code: '.$request->payment_code.' Transaction declined');

        return 'Transaction declined';
    }

    /**
     * This method processes the approval of a payment by updating the collected amount for split payments,
     * logging the approval process, and calling the appropriate service to handle the approval.
     *
     * @param  \Illuminate\Http\Request  $request  The request object containing payment details.
     * @return mixed The result of the master payment approval process.
     */
    private function handlePaymentApprove($request)
    {
        if ($request->is_capture) { // update collected amount in childs
            foreach ($request->collection_amount as $key => $splitAmount) {
                $paymentSplit = PaymentSplits::where(['code' => $request->payment_code, 'sr_no' => $key])->first();
                if ($paymentSplit && $paymentSplit->payment_status_id != PaymentStatusEnum::PAID) {

                    // Log the split payment approval process
                    info('Child payment code: '.$paymentSplit->code.' with serial no: '.$paymentSplit->sr_no.' approving process started');

                    // process split payment approve
                    app(SplitPaymentService::class)->processSplitPaymentApprove($request->modelType, $request->quote_id, $paymentSplit->id, $splitAmount);
                }
            }
        }
        // Log the master payment approval process
        info('Master payment code: '.$request->payment_code.' processing master payment approval');

        // process master payment approve
        return app(SplitPaymentService::class)->processMasterPaymentApprove($request->modelType, $request->quote_id, $request->send_update_id, false, 0, $request->payment_code);

    }

    // This method handles the approval or decline of split payments based on the request.
    public function fetchUpdateSplitPaymentsApprove($request)
    {
        return $request->is_declined ? $this->handlePaymentDecline($request) : $this->handlePaymentApprove($request);
    }

    // migrate payments
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

    // update total price
    public function fetchUpdateTotalPrice($request)
    {
        $quoteModel = $this->getQuoteObject(request()->model_type, request()->quote_id);
        $payment = $quoteModel->payments()->where('code', $request->payment_code)->first();
        if ($payment) {
            $payment->total_price = $request->total_price;
            $payment->is_approved = 0;
            $payment->payment_status_id = PaymentStatusEnum::PARTIAL_CAPTURED;
            $payment->save();
            app(SplitPaymentService::class)->updateLeadStatus($payment); // update lead status

            return response()->json(['message' => 'Total Price Updated Successfully']);
        }

        return response()->json(['error' => 'Total Price Update Failed']);
    }

    public function fetchUpdatePaymentStatus($request)
    {
        $maxRetries = 2;

        return $this->handleWithDeadlockRetries(function () use ($request) {
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

                // associate approved documents with payment split
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

                // create sage receipt
                if ((new SageApiService)->isSageEnabled()) {
                    $sageResponse = app(SplitPaymentService::class)->createSageRecipt($request, $splitPayment);
                    if ($sageResponse['status'] == 'success') {
                        $paymentInformation['sage_reciept_id'] = $sageResponse['response'];
                        $splitPayment->update($paymentInformation);
                        if ($masterPayment) {
                            $masterPayment->update(
                                [
                                    'captured_amount' => ($masterPayment->captured_amount + $request->collection_amount),
                                    'payment_allocation_status' => PaymentAllocationStatus::NOT_ALLOCATED,
                                ],
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
                            ],
                        );
                    }
                }
                /* Create payment receipt for broker */
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
            // Update parent payment status
            $this->setMasterPaymentStatus($masterPayment);

            return $successMessage;
        }, $maxRetries);
    }

    // map document type to reciept
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

    /**
     * This method updates the payment status for payments with an upfront frequency.
     *
     * @param  \App\Models\Payment  $payment  The payment object to update.
     * @return void
     */
    private function updateUpfrontStatus($payment)
    {
        if ($payment->frequency !== PaymentFrequency::UPFRONT) {
            return;
        }

        $capturedAmount = $payment->captured_amount;
        $totalAmount = $payment->total_amount;

        if ($capturedAmount > 0 && $totalAmount > $capturedAmount) {
            $payment->update(['payment_status_id' => PaymentStatusEnum::PARTIALLY_PAID]);
        } else {
            $firstSplitStatus = $payment->paymentSplits[0]->payment_status_id;
            switch ($firstSplitStatus) {
                case PaymentStatusEnum::PAID:
                    $payment->update(['payment_status_id' => PaymentStatusEnum::CAPTURED]);
                    break;
                case PaymentStatusEnum::PARTIALLY_PAID:
                    $payment->update(['payment_status_id' => PaymentStatusEnum::PARTIAL_CAPTURED]);
                    break;
                default:
                    $payment->update(['payment_status_id' => $firstSplitStatus]);
                    break;
            }
        }
    }

    /**
     * This method updates the payment status for payments that do not have an upfront frequency.
     *
     * @param  \App\Models\Payment  $payment  The payment object to update.
     * @return void
     */
    private function updateNonUpfrontStatus($payment)
    {
        $paymentSplits = PaymentSplits::where('code', $payment->code)->get();
        $creditApprovalSplitsCount = $paymentSplits->where('payment_method', PaymentMethodsEnum::CreditApproval)->count();
        $paidOrAuthorisedSplits = $this->getPaidOrAuthorisedSplits($paymentSplits);

        // Update payment method for credit approval payments when credit approval is present
        if (! empty($payment->credit_approval) && $paidOrAuthorisedSplits) {
            $this->updateCreditApprovalMethod($payment);
        }

        if ($creditApprovalSplitsCount > 0) {
            // Update payment status for credit approval payments
            $this->updateCreditApprovalStatus($payment, $paymentSplits, $creditApprovalSplitsCount);
        } else {
            // Update payment status for non-credit approval payments
            $this->updateNonCreditStatus($payment, $paymentSplits);
        }
    }

    private function getPaidOrAuthorisedSplits($paymentSplits)
    {
        $paidOrAuthorisedStatuses = [
            PaymentStatusEnum::PAID,
            PaymentStatusEnum::AUTHORISED,
            PaymentStatusEnum::CAPTURED,
        ];

        return $paymentSplits->contains(function ($split) use ($paidOrAuthorisedStatuses) {
            return in_array($split->payment_status_id, $paidOrAuthorisedStatuses);
        });
    }

    private function updateCreditApprovalMethod($payment)
    {
        info('Master payment code: '.$payment->code.' with credit approval & paid/authorised child payments');
        // Define the frequencies that should result in a PARTIAL_PAYMENT status
        $partialPaymentFrequencies = [
            PaymentFrequency::CUSTOM,
            PaymentFrequency::SEMI_ANNUAL,
            PaymentFrequency::QUARTERLY,
            PaymentFrequency::MONTHLY,
        ];

        // Update parent payment based on frequency
        if (in_array($payment->frequency, $partialPaymentFrequencies)) {
            info('Master payment code: '.$payment->code.' Updating payment method to Partial Payment');
            $payment->update(['payment_methods_code' => PaymentMethodsEnum::PartialPayment]);
        } elseif ($payment->frequency == PaymentFrequency::SPLIT_PAYMENTS) {
            info('Master payment code: '.$payment->code.' Updating payment method to Multiple Payment');
            $payment->update(['payment_methods_code' => PaymentMethodsEnum::MultiplePayment]);
        }
    }

    private function updateCreditApprovalStatus($payment, $paymentSplits, $creditApprovalSplitsCount)
    {
        $totalSplitPaymentCount = $paymentSplits->count();
        $authorisedPaymentCount = $paymentSplits->where('payment_status_id', PaymentStatusEnum::AUTHORISED)->count();
        $paidPaymentCount = $paymentSplits->where('payment_status_id', PaymentStatusEnum::PAID)->count();

        if ($authorisedPaymentCount > 0 && $creditApprovalSplitsCount + $authorisedPaymentCount == $totalSplitPaymentCount) {
            info('Master payment code: '.$payment->code.' Updating payment status to AUTHORISED');
            $payment->update(['payment_status_id' => PaymentStatusEnum::AUTHORISED]);
        } elseif ($paidPaymentCount > 0) {
            info('Master payment code: '.$payment->code.' Updating payment status to PARTIALLY_PAID');
            $payment->update(['payment_status_id' => PaymentStatusEnum::PARTIALLY_PAID]);
        } else {
            info('Master payment code: '.$payment->code.' Updating payment status to CREDIT_APPROVED');
            $payment->update(['payment_status_id' => PaymentStatusEnum::CREDIT_APPROVED]);
        }
    }

    private function updateNonCreditStatus($payment, $paymentSplits)
    {
        $totalPaidPayments = $this->getTotalPaidPayments($paymentSplits);

        info('Master payment code: '.$payment->code.' Total paid payments: '.$totalPaidPayments.' out of '.$payment->total_payments);

        if ($totalPaidPayments == $payment->total_payments && $payment->captured_amount >= ($payment->total_price - $payment->discount_value)) {
            info('Master payment code: '.$payment->code.' All payments are captured. Updating Payment status to CAPTURED');
            $payment->update(['payment_status_id' => PaymentStatusEnum::CAPTURED]);
        } elseif ($totalPaidPayments > 0) {
            info('Master payment code: '.$payment->code.' Some payments are captured. Updating Payment status to PARTIAL_CAPTURED');
            $payment->update(['payment_status_id' => PaymentStatusEnum::PARTIAL_CAPTURED]);
        } else {
            $this->updateStatusNoPaid($payment);
        }
    }

    private function getTotalPaidPayments($paymentSplits)
    {
        $paymentStatuses = [
            PaymentStatusEnum::PAID,
            PaymentStatusEnum::CAPTURED,
            PaymentStatusEnum::PARTIAL_CAPTURED,
            PaymentStatusEnum::PARTIALLY_PAID,
        ];

        $count = 0;

        // Iterate over the collection with each and manually count
        $paymentSplits->each(function ($split) use ($paymentStatuses, &$count) {
            if (in_array($split->payment_status_id, $paymentStatuses)) {
                $count++;
            }
        });

        return $count;
    }

    private function updateStatusNoPaid($payment)
    {
        $totalCreditPayments = $this->getTotalCreditPayments($payment);
        info('Master payment code: '.$payment->code.' Total credit approved payments: '.$totalCreditPayments);

        if ($totalCreditPayments > 0) {
            info('Master payment code: '.$payment->code.' Updating payment status to CREDIT_APPROVED');
            $payment->update(['payment_status_id' => PaymentStatusEnum::CREDIT_APPROVED]);
        } else {
            info('Master payment code: '.$payment->code.' Updating payment status to NEW');
            $payment->update(['payment_status_id' => PaymentStatusEnum::NEW]);
        }
    }

    private function getTotalCreditPayments($payment)
    {
        return PaymentSplits::whereIn('payment_status_id', [
            PaymentStatusEnum::CREDIT_APPROVED,
        ])
            ->where('code', $payment->code)
            ->count();
    }

    public function setMasterPaymentStatus($payment)
    {
        if ($payment) {
            if ($payment->frequency == 'upfront') {
                $this->updateUpfrontStatus($payment);
            } else {
                $this->updateNonUpfrontStatus($payment);
            }
            info('Master payment code: '.$payment->code.' Updating lead status');
            app(SplitPaymentService::class)->updateLeadStatus($payment); // update lead status
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

    public function generateAndStoreBrokerInvoiceNumber($quote, $payment, $quoteType, $attempts = 0)
    {
        info('fn:generateAndStoreBrokerInvoiceNumber Quote : '.$quote?->code.' Source : '.$quote?->source.' started');

        $response = ['status' => false, 'message' => ''];

        if (! $payment) {
            info('fn:generateAndStoreBrokerInvoiceNumber Quote : '.$quote?->code.' Source : '.$quote?->source.' payment not found.');
            $response['message'] = 'Payment not found.';

            return $response;
        }
        info('fn:generateAndStoreBrokerInvoiceNumber Payment : '.$payment->code);
        $maxRetries = 5;

        if ($payment->broker_invoice_number) {
            info('fn:generateAndStoreBrokerInvoiceNumber Payment  : '.$payment->code.' : Broker Invoice Number already exists - BIN : '.$payment->broker_invoice_number);
            $response['status'] = true;
            $response['message'] = 'Broker Invoice Number: '.$payment->broker_invoice_number;

            return $response;
        }
        try {
            $insuranceProvider = getInsuranceProvider($payment, $quoteType);

            if (! isNonSelfBillingEnabledForInsuranceProvider($insuranceProvider)) {
                info('fn:generateAndStoreBrokerInvoiceNumber Payment : '.$payment->code.' : Non-self Billing is not enabled for Insurance Provider ID : '.$insuranceProvider->id);
                $response['status'] = true;
                $response['message'] = 'Non-self Billing is not enabled for Insurance Provider';

                return $response;
            }

            $currentDate = Carbon::now();
            DB::transaction(function () use ($insuranceProvider, $currentDate, &$response, $payment) {
                $invoiceBrokerSequence = BrokerInvoiceNumber::where([
                    'insurance_provider_id' => $insuranceProvider->id,
                    'date' => $currentDate->format('Y-m'),
                ])
                    ->lockForUpdate()
                    ->first();

                if (! $invoiceBrokerSequence) {
                    info('fn:generateAndStoreBrokerInvoiceNumber Monthly sequence created for Insurance Provider ID '.$insuranceProvider->id);
                    $invoiceBrokerSequence = BrokerInvoiceNumber::create([
                        'insurance_provider_id' => $insuranceProvider->id,
                        'date' => $currentDate->format('Y-m'),
                        'sequence_number' => 1,
                    ]);
                }
                info('fn:generateAndStoreBrokerInvoiceNumber Payment : '.$payment->code.' : Insurer sequence number is : '.$invoiceBrokerSequence->sequence_number.' for Insurance Provider ID : '.$insuranceProvider->id);
                $currentSequence = $invoiceBrokerSequence->sequence_number;
                $insuranceProviderCode = $insuranceProvider?->code;
                $brokerInvoiceNumber = 'AFIA/'.$insuranceProviderCode.'/'.$currentDate->format('Y').'/'.$currentDate->format('m').'/'.$currentSequence;
                $payment->update([
                    'broker_invoice_number' => $brokerInvoiceNumber,
                ]);
                info('fn:generateAndStoreBrokerInvoiceNumber Payment  : '.$payment->code.' : Broker Invoice Number updated : '.$brokerInvoiceNumber);
                $invoiceBrokerSequence->increment('sequence_number');
                $response['status'] = true;
                $response['message'] = 'Broker Invoice Number: '.$brokerInvoiceNumber;
            });

            return $response;
        } catch (Exception $e) {
            $attempts++;
            if (in_array($e->getCode(), ['40001', '1213'])) {
                if ($attempts < $maxRetries) {
                    info('fn:generateAndStoreBrokerInvoiceNumber Payment  : '.$payment->code.' : table locked, trying again');
                    $this->generateAndStoreBrokerInvoiceNumber($quote, $payment, $quoteType, $attempts);
                } else {
                    info('fn:generateAndStoreBrokerInvoiceNumber Payment  : '.$payment->code.' : Error occurred while generating broker invoice number: Could not acquire lock after multiple attempts');
                    $response['message'] = 'Exception: Could not acquire lock after multiple attempts';

                    return $response;
                }
            } else {
                info('fn:generateAndStoreBrokerInvoiceNumber Payment  : '.$payment->code.' : Error occurred while generating broker invoice number: '.$e->getMessage());

                $response['message'] = 'Exception: '.$e->getMessage();

                return $response;
            }

        }
    }
    public function generateInvoiceDescription($payment, $quoteType, $record): string
    {
        $insuranceProvider = getInsuranceProvider($payment, $quoteType);

        $insuranceProviderCode = $insuranceProvider?->code;

        return substr($insuranceProviderCode.'-'.ucfirst($quoteType).'-'.$record->policy_number, 0, 60);
    }

    public function updatePriceVatApplicableAndVat($quote, $modelType)
    {
        /* Start - Temporarily adding for correcting historic data */
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
                        $splitAmount = $splitPayment->payment_amount;
                        if ($splitPayment->sr_no === 1) {
                            $splitAmount = $splitPayment->payment_amount + $quotePayment->discount_value;
                        }
                        [$priceWithoutVat, $vat] = app(SplitPaymentService::class)->calculatePriceAndVat($quotePayment->frequency, $quotePayment->total_price, $splitPayment->sr_no, $splitAmount, $modelType, $quote->id, count($paymentSplits));
                        $splitPayment->update([
                            'price_vat_applicable' => $priceWithoutVat,
                            'price_vat' => $vat,
                        ]);
                    }
                }
            }
        }

        info('End - Temporarily adding for correcting historic data '.$quote->uuid);
        /* End - Temporarily adding for correcting historic data */

    }

    public function getAuthorisePaymentCount($userId = null)
    {
        if (! Auth::check() && $userId == null) {
            return 0;
        }
        $userId = $userId != null ? $userId : Auth::user()->id;
        $user = User::where('id', $userId)->first();
        $userTeams = $user->getUserTeams($userId);

        $personalCount = DB::table('payments')
            ->distinct()
            ->Join('personal_quotes as pq', 'pq.code', '=', 'payments.code')
            ->join('users', 'users.id', 'pq.advisor_id')
            ->join('user_team', 'user_team.user_id', 'users.id')
            ->join('teams', 'teams.id', '=', 'user_team.team_id')
            ->where('payments.payment_status_id', PaymentStatusEnum::AUTHORISED);

        if ($user->hasAnyRole([RolesEnum::CarManager, RolesEnum::HealthManager, RolesEnum::TravelManager, RolesEnum::LifeManager, RolesEnum::HomeManager, RolesEnum::PetManager, RolesEnum::BikeManager, RolesEnum::CycleManager, RolesEnum::YachtManager, RolesEnum::JetskiManager, RolesEnum::BusinessManager])) {
            $personalCount = $personalCount->whereIn('teams.name', $userTeams)->count('payments.id');

        } else {
            $personalCount = $personalCount->where('pq.advisor_id', $userId)->count('payments.id');

        }

        return $personalCount;
    }

    public function fetchMainQuotePayment($quote)
    {
        $isDuplicateOrCIRLead = ! empty($quote->parent_duplicate_quote_id);
        $payment = $this->where('code', $quote->code)->mainLeadPayment()->first();

        if ($isDuplicateOrCIRLead && empty($payment)) {
            $payment = $this->where([
                'paymentable_id' => $quote->id, 'paymentable_type' => $quote->getMorphClass(),
            ])->mainLeadPayment()->first();
        }

        return $payment;
    }
}
