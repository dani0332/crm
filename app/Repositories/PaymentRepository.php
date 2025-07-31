<?php

namespace App\Repositories;

use App\Enums\CollectionTypeEnum;
use App\Enums\DocumentTypeCode;
use App\Enums\DocumentTypeEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PaymentAllocationStatus;
use App\Enums\PaymentFrequency;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Interfaces\PaymentRepositoryInterface;
use App\Jobs\SendFTCEmailJob;
use App\Models\BrokerInvoiceNumber;
use App\Models\Payment;
use App\Models\PaymentSplits;
use App\Models\PaymentStatusLog;
use App\Models\QuoteDocument;
use App\Models\SendUpdateLog;
use App\Services\CentralService;
use App\Services\Logger\LoggerService;
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

    // Creates a new payment record along with associated split payments for a quote
    public function fetchCreateNewPayment($request)
    {
        try {
            // Initialize payment source and get quote model
            $paymentSource = 'Main Lead';
            $quoteModel = $this->getQuoteObject($request->modelType, $request->quote_id);
            LoggerService::startQuoteLogging($quoteModel, LoggerFeatureEnum::CREATE_PAYMENT);
            $quoteCode = $quoteModel->code;
            LoggerService::info("Starting manual payment creation process for quote code: {$quoteCode}");
            $masterPayment = (object) $request->payment;

            // Determine initial payment status
            $masterPaymentStatus = PaymentStatusEnum::NEW;
            if ($masterPayment->payment_methods == PaymentMethodsEnum::CreditApproval) {
                $masterPaymentStatus = PaymentStatusEnum::CREDIT_APPROVED;
            }

            // Build payment information array with all required fields
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
                'created_by' => $request->user()->id ?? null,
                'updated_by' => $request->user()->id ?? null,
                'payment_gateway_id' => ! empty($request->payment_gateway_id) ? $request->payment_gateway_id : null,
            ];

            // Generate payment code based on quote code and existing payment count
            $count = $quoteModel->payments->count();
            $paymentInformation['code'] = ($count > 0) ? "{$quoteCode}-{$count}" : $quoteCode;
            LoggerService::info("Generated payment code {$paymentInformation['code']} for quote {$quoteCode} (payment #{$count} for this quote)");

            // Handle special case for send update or child lead payments
            if ($request->send_update_id || ! empty($quoteModel->parent_duplicate_quote_id)) {
                // Payment follow-up count is now iterative (uuid-(nth+1)) and not dependent on the count of payments in the quote
                // Count will be iterative for each payment added through the send update or Child lead
                $mainLeadCode = implode('-', array_slice(explode('-', $quoteCode), 0, 2));
                $paymentCount = $this->getPaymentsCountByLeadCode($mainLeadCode);
                $paymentInformation['code'] = ($paymentCount > 0) ? "{$mainLeadCode}-{$paymentCount}" : $mainLeadCode;
                LoggerService::info("Payment code updated for send update/child lead: {$paymentInformation['code']}");

                // Update payment source if it's from a send update log
                if (! empty(request()->send_update_id)) {
                    $paymentInformation['send_update_log_id'] = $request->send_update_id;
                    $quoteModel = SendUpdateLogRepository::getLogById($request->send_update_id);
                    $paymentSource = 'Send Update Log';
                    LoggerService::info("Payment source changed to Send Update Log with ID: {$request->send_update_id} for code {$paymentInformation['code']}");
                }
            }

            // Add reference if provided
            if ($masterPayment->reference) {
                $paymentInformation['reference'] = $masterPayment->reference;
            }

            // Pre-authorize payment for certain payment methods
            if (! in_array($masterPayment->payment_methods, [PaymentMethodsEnum::CreditCard, PaymentMethodsEnum::InsureNowPayLater])) {
                $paymentInformation['authorized_at'] = now();
                LoggerService::info("Payment pre-authorized for payment method: {$masterPayment->payment_methods}");
            }
        } catch (Exception $exception) {
            LoggerService::error("Error occurred during payment creation pre-processing: {$exception->getMessage()} for code {$paymentInformation['code']}", exception: $exception);

            return ['status' => 'error', 'message' => $exception->getMessage()];
        }

        // Start database transaction
        DB::beginTransaction();
        try {
            // Create the main payment record
            $payment = $quoteModel->payments()->create($paymentInformation);
            LoggerService::info("{$paymentSource} Payment created with Code: {$paymentInformation['code']}");

            // Get quote UUID and add payment splits
            $quoteUUID = $quoteModel instanceof SendUpdateLog ? null : $quoteModel->uuid;
            LoggerService::info("Starting to add payment splits for payment code: {$paymentInformation['code']}");
            $this->addPaymentSplits($request, $payment, $quoteUUID);
            LoggerService::info("Payment splits successfully added for payment code: {$paymentInformation['code']}");

            // Create payment status log entry
            $paymentLog = new PaymentStatusLog([
                'current_payment_status_id' => PaymentStatusEnum::NEW,
                'payment_code' => $paymentInformation['code'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $paymentLog->save();
            LoggerService::info("Payment status log created for payment code: {$paymentInformation['code']}");

            // Update quote status if necessary
            if (! $request->send_update_id && $masterPayment->payment_methods !== PaymentMethodsEnum::InsurerPaymentLink) {
                $quoteModel->quote_status_id = QuoteStatusEnum::PaymentPending;
                LoggerService::info("Quote status updated to Payment Pending for quote code: {$quoteCode}");
            }

            // Save quote and commit transaction
            $quoteModel->save();
            DB::commit();
            LoggerService::info("Payment creation transaction committed successfully for payment code: {$paymentInformation['code']}");

            return ['status' => 'success', 'message' => 'Payment Added'];
        } catch (Exception $exception) {
            // Rollback changes if any error occurred
            DB::rollBack();
            LoggerService::error("Error occurred during payment creation transaction: {$exception->getMessage()} for code {$paymentInformation['code']}", exception: $exception);

            return ['status' => 'error', 'message' => $exception->getMessage()];
        }
    }

    public function fetchUpdateNewPayment($request)
    {
        $masterPayment = (object) $request->payment;
        $payment = Payment::where('code', $request->paymentCode)->first();
        if (! $payment) {
            LoggerService::warning("Payment does not exist for Payment Code: {$request->paymentCode}");

            return ['status' => 'error', 'message' => 'Payment record not found'];
        }

        LoggerService::info("Starting payment update process for code: {$request->paymentCode} is payment locked: {$request->isPaymentLocked}");

        // Check if payment is locked we will update only specific fields
        if ($request->isPaymentLocked) {
            LoggerService::info("Processing locked payment update with limited fields for code: {$request->paymentCode}");
            $paymentInformation = [
                'notes' => ! empty($masterPayment->notes) ? $masterPayment->notes : null,
                'custom_reason' => ! empty($masterPayment->custom_reason) ? $masterPayment->custom_reason : null,
                'credit_approval' => $masterPayment->credit_approval,
                'updated_by' => $request->user()->id ?? null,
            ];

            if ($this->shouldUpdateParentPaymentMethod($payment, $masterPayment)) {
                LoggerService::info("Updating parent payment method from {$payment->payment_methods_code} to {$masterPayment->payment_methods} for locked payment: {$request->paymentCode}");
                $paymentInformation['payment_methods_code'] = $masterPayment->payment_methods;
            }
        } else {
            LoggerService::info("Processing full payment update for code: {$request->paymentCode}");
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
                'plan_id' => ! empty($request->plan_id) ? $request->plan_id : null,
                'updated_by' => $request->user()->id ?? null,
                'payment_gateway_id' => ! empty($request->payment_gateway_id) ? $request->payment_gateway_id : null,
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

        $maxRetries = 2;

        return $this->handleWithDeadlockRetries(function () use ($request, $payment, $paymentInformation) {
            $quoteModel = $this->getQuoteObject($request->modelType, $request->quote_id);
            $payment->update($paymentInformation);
            LoggerService::info("Payment updated successfully for Payment Code: {$request->paymentCode}");

            // Update split payments start
            // Delete trashed files if any of file is deleted from quote document
            if (! empty($request->trashedFilesModal)) {
                LoggerService::info("Deleting trashed files for payment code: {$request->paymentCode}");
                QuoteDocument::whereIn('id', $request->trashedFilesModal)->delete();
            }
            $quoteUUID = $quoteModel instanceof SendUpdateLog || $payment->send_update_log_id != null ? null : $quoteModel->uuid;
            $isRenewalLead = $quoteModel instanceof SendUpdateLog || $payment->send_update_log_id != null ? false : $quoteModel->source == LeadSourceEnum::RENEWAL_UPLOAD;

            $this->updatePaymentSplits($request, $payment, $quoteUUID, $isRenewalLead);

            LoggerService::info("Payment update process completed for code: {$request->paymentCode}");

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

    // Add split payments for a parent payment and creates related payment splits
    public function addPaymentSplits($request, $payment, $quoteUUID)
    {
        $quoteID = $payment->code;
        LoggerService::info("Starting split payment creation for payment code: {$quoteID}");

        $masterPayment = (object) $request->payment;
        $isInsurerPaymentLink = collect($masterPayment->payment_splits)->contains('payment_method', PaymentMethodsEnum::InsurerPaymentLink);
        $isPaymentLinkNotNull = collect($masterPayment->payment_splits)->filter(function ($split) {
            return isset($split['insurer_payment_link']) && $split['insurer_payment_link'] !== null;
        })->isNotEmpty();
        $sendFTCEmail = $isInsurerPaymentLink && $isPaymentLinkNotNull && $request->sendFTCEmail;
        $totalSplitPayments = count($masterPayment->payment_splits);

        // Calculate discount if applicable
        $discount = 0;
        if (isset($masterPayment->discount_value) && $masterPayment->discount_value > 0) {
            $discount = app(SplitPaymentService::class)->calculateDiscount($totalSplitPayments, $masterPayment->discount_value);
            LoggerService::info("Discount calculated for payment code {$quoteID}: amount {$discount}");
        }

        $firstPaymentSplit = null;

        foreach ($masterPayment->payment_splits as $splitPayment) {
            if (isset($splitPayment['payment_method']) && $splitPayment['payment_method'] != null) {
                // Create payment split record with required information
                $splitPaymentInformation = [
                    'code' => $quoteID,
                    'sr_no' => $splitPayment['sr_no'],
                    'insurer_payment_link' => $splitPayment['insurer_payment_link'] ?? null,
                    'payment_method' => $splitPayment['payment_method'],
                    'check_detail' => isset($splitPayment['check_detail']) ? $splitPayment['check_detail'] : null,
                    'payment_amount' => $splitPayment['payment_amount'],
                    'due_date' => $splitPayment['due_date'],
                    'payment_status_id' => PaymentStatusEnum::NEW,
                    'discount_value' => $discount,
                    'payment_gateway_id' => ! empty($request->payment_gateway_id) ? $request->payment_gateway_id : null,
                    'cc_payment_gateway' => ! empty($request->cc_payment_gateway) ? $request->cc_payment_gateway : null,
                ];
                $paymentSplitRecord = PaymentSplits::create($splitPaymentInformation);
                LoggerService::info("Created payment split #{$splitPayment['sr_no']} for payment {$quoteID} with method {$splitPayment['payment_method']}");

                if ($splitPayment['sr_no'] == 1) {
                    $firstPaymentSplit = $paymentSplitRecord;
                }

                if ($paymentSplitRecord) {
                    // Associate documents with payment split if available
                    if (isset($splitPayment['document_detail']) && count($splitPayment['document_detail'])) {
                        foreach ($splitPayment['document_detail'] as $document) {
                            $quoteDocumentRec = QuoteDocument::find($document['id'] ?? '');
                            if ($quoteDocumentRec) {
                                $quoteDocumentRec->payment_split_id = $paymentSplitRecord->id;
                                $quoteDocumentRec->save();
                                LoggerService::info("Associated document ID: {$document['id']} with payment split for {$quoteID}");
                            }
                        }
                    }
                    // Update child payment status based on payment method
                    $childPaymentStatus = app(SplitPaymentService::class)->getChildPaymentStatus($paymentSplitRecord);
                    $paymentSplitRecord->update(['payment_status_id' => $childPaymentStatus]);
                    LoggerService::info("Updated payment split #{$splitPayment['sr_no']} status to {$childPaymentStatus} for {$quoteID}");
                }
            }
        }

        // Update parent payment status based on child payments
        $this->setMasterPaymentStatus($payment);
        LoggerService::info("Updated master payment status completed for {$quoteID}");

        // Dispatch FTC email job if applicable
        // Only for insurer payment link payment method
        if ($sendFTCEmail && $quoteUUID != null) {
            $modelType = $request->modelType;
            $quoteType = QuoteTypes::from($modelType);
            SendFTCEmailJob::dispatch($quoteUUID, $quoteType, true)->delay(now()->addSeconds(5));
            LoggerService::info("Dispatched FTC email job for payment {$quoteID}");
        }

        // Process discount documents if available
        $discountDocuments = $masterPayment->payment_splits[0]['discount_documents'];
        if ($discountDocuments && count($discountDocuments)) {
            $firstPaymentSplit = $firstPaymentSplit ?? PaymentSplits::where(['code' => $quoteID])->first();
            app(SplitPaymentService::class)->uploadDiscountDocuments($discountDocuments, $firstPaymentSplit);
            LoggerService::info("Uploaded discount documents for payment {$quoteID}");
        }

        LoggerService::info("Completed split payment creation for {$quoteID}");
    }

    public function updatePaymentSplits($request, $payment, $quoteUUID, $isRenewalLead = false)
    {
        LoggerService::info("Starting update payment splits for payment code: {$request->paymentCode}");
        $masterPayment = (object) $request->payment;
        $isInsurerPaymentLink = collect($masterPayment->payment_splits)->contains('payment_method', PaymentMethodsEnum::InsurerPaymentLink);
        $insurerPaymentLinkIndex = collect($masterPayment->payment_splits)->search(function ($item) {
            return isset($item['insurer_payment_link']) && $item['insurer_payment_link'] !== null;
        });
        $sendFTCEmail = $isInsurerPaymentLink && $insurerPaymentLinkIndex !== false && $request->sendFTCEmail;
        $paymentSplits = PaymentSplits::with('documents')->where(['code' => $request->paymentCode])->get();
        $paymentPaidSerialNo = [];
        $splitPaymentDocumentIds = [];
        // Skipping paid payments and deleting extra payments
        if ($paymentSplits) {
            foreach ($paymentSplits as $paymentSplit) {
                // We are not deleting certian payment statuses payment add backend validation
                // This will handle once we are updating frequecy types and deleting extra payments
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
                    LoggerService::info("Deleting payment split #{$paymentSplit->sr_no} and documents for payment {$request->paymentCode}");
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
            LoggerService::info("Discount calculated for payment code {$request->paymentCode}: amount {$discount}");
        }

        $firstPaymentSplit = null;

        foreach ($masterPayment->payment_splits as $index => $splitPayment) {
            $serialNo = $splitPayment['sr_no'];
            // update payment amount for paid payments
            if (isset($request->isPaidEditable) && $request->isPaidEditable && count($paymentPaidSerialNo) === $totalSplitPayments) {
                $paymentSplit = PaymentSplits::where(['code' => $request->paymentCode, 'sr_no' => $serialNo])->first();
                if ($paymentSplit) {
                    LoggerService::info("Updating payment split #{$serialNo} for payment {$request->paymentCode} with amount {$splitPayment['payment_amount']}");
                    $paymentSplit->update(['payment_amount' => $splitPayment['payment_amount']]);
                }

                continue;
            }

            if (in_array($serialNo, $paymentPaidSerialNo)) {
                continue;
            }

            if (isset($splitPayment['payment_method']) && $splitPayment['payment_method'] != null) {
                LoggerService::info("Processing payment split #{$serialNo} for payment {$request->paymentCode} with method {$splitPayment['payment_method']}");
                if ($request->isPaymentLocked) { // Check if payment is locked to update specific fields
                    LoggerService::info("Processing locked payment split update with limited fields for code: {$request->paymentCode}");
                    $splitPaymentInformation = [
                        'payment_method' => $splitPayment['payment_method'],
                    ];
                } else {
                    LoggerService::info("Processing full payment split update for code: {$request->paymentCode}");
                    $splitPaymentInformation = [
                        'code' => $request->paymentCode,
                        'sr_no' => $serialNo,
                        'payment_method' => $splitPayment['payment_method'],
                        'check_detail' => isset($splitPayment['check_detail']) ? $splitPayment['check_detail'] : null,
                        'payment_amount' => $splitPayment['payment_amount'],
                        'payment_status_id' => PaymentStatusEnum::NEW, // reset status to 'NEW
                        'due_date' => $splitPayment['due_date'],
                        'discount_value' => $discount,
                        'insurer_payment_link' => $splitPayment['insurer_payment_link'] ?? null,
                        'payment_gateway_id' => ! empty($request->payment_gateway_id) ? $request->payment_gateway_id : null,
                        'cc_payment_gateway' => ! empty($request->cc_payment_gateway) ? $request->cc_payment_gateway : null,
                    ];
                }

                $paymentSplitRecord = PaymentSplits::where(['code' => $request->paymentCode, 'sr_no' => $serialNo])->first();
                if (! $paymentSplitRecord) {
                    LoggerService::info("Creating new payment split #{$serialNo} for payment {$request->paymentCode}");
                    $paymentSplitRecord = PaymentSplits::create($splitPaymentInformation);
                } else {
                    if ($index == $insurerPaymentLinkIndex) {
                        $sendFTCEmail = ($splitPaymentInformation['payment_method'] == PaymentMethodsEnum::InsurerPaymentLink
                            && (
                                $isRenewalLead // Renewal leads with InsurerPaymentLink always send FTC email
                                ||
                                ($request->sendFTCEmail && ($splitPayment['insurer_payment_link'] != $paymentSplitRecord->insurer_payment_link)) // Regular leads need both conditions
                            ));
                    }
                    $paymentSplitRecord->update($splitPaymentInformation);
                }
                // add document references
                if (
                    isset($splitPayment['document_detail'])
                    && $paymentSplitRecord
                    && count($splitPayment['document_detail'])
                ) {
                    LoggerService::info("Adding document references for payment split #{$serialNo} for payment {$request->paymentCode}");
                    foreach ($splitPayment['document_detail'] as $document) {
                        $quoteDocumentRec = QuoteDocument::find($document['id']);
                        if ($quoteDocumentRec) {
                            LoggerService::info("Associating document ID: {$document['id']} with payment split for {$request->paymentCode}");
                            $quoteDocumentRec->payment_split_id = $paymentSplitRecord->id;
                            $quoteDocumentRec->save();
                        }
                    }
                }
                if ($paymentSplitRecord) {
                    $childPaymentStatus = app(SplitPaymentService::class)->getChildPaymentStatus($paymentSplitRecord);
                    LoggerService::info("Updating payment split #{$serialNo} status to {$childPaymentStatus} for payment {$request->paymentCode}");
                    $paymentSplitRecord->update(['payment_status_id' => $childPaymentStatus]);
                }
                if ($serialNo == 1) {
                    $firstPaymentSplit = $paymentSplitRecord;
                }
            }
        }
        // Dispatch FTC email job if sendFTCEmail is true and quoteUUID is not null
        // sendFTCEmail is passing from frontend
        ($sendFTCEmail && $quoteUUID != null) && SendFTCEmailJob::dispatch($quoteUUID, QuoteTypes::from($request->modelType), true)->delay(now()->addSeconds(5));
        $payment = Payment::where('code', $request->paymentCode)->first();
        LoggerService::info("Setting master payment status for payment code: {$request->paymentCode}");
        $this->setMasterPaymentStatus($payment);
        $discountDocuments = $masterPayment->payment_splits[0]['discount_documents'];
        if ($discountDocuments && count($discountDocuments)) {
            LoggerService::info("Uploading discount documents for payment code: {$request->paymentCode}");
            $firstPaymentSplit = $firstPaymentSplit ?? PaymentSplits::where(['code' => $request->paymentCode])->first();
            app(SplitPaymentService::class)->uploadDiscountDocuments($discountDocuments, $firstPaymentSplit);
        }
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
        $paymentCode = $request->payment_code;
        LoggerService::info("Processing payment decline for {$paymentCode}");

        $quoteModel = $this->getQuoteModel($request->modelType, $request->quote_id, $request->send_update_id);
        LoggerService::startQuoteLogging($quoteModel, LoggerFeatureEnum::DECLINE_PARENT_PAYMENT);
        $firstPayment = $quoteModel->payments()->where('code', $request->payment_code)->first();
        LoggerService::info("Updating payment decline details for payment code: {$paymentCode}");
        $firstPayment->update([
            'decline_reason_id' => $request->declined_reason,
            'decline_custom_reason' => $request->declined_custom_reason,
            'updated_by' => Auth::user()->id,
        ]);
        if ($request->send_update_id > 0) {
            LoggerService::info("Updating send update status logs for quote ID: {$quoteModel->id}");
            app(CentralService::class)->updateSendUpdateStatusLogs($quoteModel->id, $quoteModel->status, SendUpdateLogStatusEnum::TRANSACTION_DECLINE);
            $quoteModel->status = SendUpdateLogStatusEnum::TRANSACTION_DECLINE;
        } else {
            LoggerService::info("Updating quote status to TransactionDeclined for main lead quote ID: {$quoteModel->id}");
            $quoteModel->quote_status_id = QuoteStatusEnum::TransactionDeclined;
        }
        $quoteModel->save();
        LoggerService::info("Transaction declined process complete: {$paymentCode}");

        return 'Transaction declined';
    }

    /**
     * This method processes the approval of a payment by updating the collected amount for split payments,
     * logging the approval process, and calling the appropriate service to handle the approval.
     *
     * @param  \Illuminate\Http\Request  $request  The request object containing payment details.
     * @return mixed The result of the master payment approval process.
     */
    public function handlePaymentApprove($request)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::APPROVE_PARENT_PAYMENT);
        $paymentCode = $request->payment_code;
        LoggerService::info("Payment approval process initiated: {$paymentCode}, Capture Mode: ".($request->is_capture ? 'Yes' : 'No'));
        if ($request->is_capture) { // update collected amount in childs
            foreach ($request->collection_amount as $key => $splitAmount) {
                if ($key == null || $key < 0) {
                    LoggerService::info("Child payment code: {$paymentCode} Invalid split number: {$key}");

                    continue;
                }
                $paymentSplit = PaymentSplits::where(['code' => $paymentCode, 'sr_no' => $key])->first();

                if ($paymentSplit && $paymentSplit->payment_status_id != PaymentStatusEnum::PAID) {

                    // Log the split payment approval process
                    LoggerService::info("Child payment code: {$paymentSplit->code} with serial no: {$paymentSplit->sr_no} approving process started with amount: {$splitAmount}");

                    // process split payment approve
                    app(SplitPaymentService::class)->processSplitPaymentApprove($request->modelType, $request->quote_id, $paymentSplit->id, $splitAmount);
                } else {
                    LoggerService::info("Payment split not found or already paid: {$paymentCode}, Split No: {$key}");
                }
            }
        }
        // Log the master payment approval process
        LoggerService::info('Master payment code: '.$paymentCode.' processing master payment approval called');

        // process master payment approve
        return app(SplitPaymentService::class)->processMasterPaymentApprove($request->modelType, $request->quote_id, $request->send_update_id, false, 0, $paymentCode);

    }

    // This method handles the approval or decline of split payments based on the request.
    public function fetchMasterPaymentApproveCapture($request)
    {
        LoggerService::info("Processing split payment request for {$request->payment_code} - Action: ".($request->is_declined ? 'Decline' : 'Approve'));

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

    public function fetchSplitPaymentApproveDecline($request)
    {
        LoggerService::startFeatureLogging(LoggerFeatureEnum::APPROVE_DECLINE_CHILD_PAYMENT);
        $maxRetries = 2;
        $successMessage = 'Payment Verified';

        // Move fetching data outside transaction
        $splitPayment = PaymentSplits::find($request->splitPaymentId);
        $splitPaymentCode = $splitPayment->code;
        $srNo = $splitPayment->sr_no;
        $masterPayment = $splitPayment->payment;

        $quote = $masterPayment?->paymentable;
        $sageResponseStatus = false;

        /* Handle NRA case where payment is approved after policy/send update is booked */
        $shouldCreatePrepaymentPremiumReceipt = (new SageApiService)->shouldCreateAndSchedulePostPrepayment($quote, $splitPayment);
        info(self::class.' fn:'.__FUNCTION__.' Child payment code: '.$splitPayment->code.' with serial no: '.$splitPayment->sr_no.' trigger creation of Premium Sage receipt  : ', ['$shouldCreatePrepaymentPremiumReceipt' => $shouldCreatePrepaymentPremiumReceipt]);

        // Process Sage API call outside transaction if needed
        if ($request->is_approved && $splitPayment->payment_status_id != PaymentStatusEnum::PAID && (new SageApiService)->isSageEnabled() && $shouldCreatePrepaymentPremiumReceipt) {
            $sageRequest = $request->safe();
            $sageRequest->userId = auth()->id();
            $sageRequest->advisor_id = $quote->advisor_id;
            $sageRequest->collection_amount = $request->collection_amount;
            $sageRequest->insurerReceiptNumber = $request->insurer_receipt_number;
            if(!$sageRequest->quoteType){
                $sageRequest->quoteType = $request->modelType;
            }

            /* Handle NRA case where payment is approved after policy/send update is booked */
            $sageResponse = (new SageApiService)->createPrepaymentPremiumReceipt($sageRequest, $quote, $masterPayment, $splitPayment, $request->collection_amount);

            if (! $sageResponse['status']) {
                vAbort($sageResponse['message']);
            }

            $sageResponseStatus = $sageResponse['status'];
        }

        // Now handle database operations within transaction
        return $this->handleWithDeadlockRetries(function () use ($request, $splitPayment, $masterPayment, $successMessage, $splitPaymentCode, $srNo) {
            if ($request->is_approved && $splitPayment->payment_status_id != PaymentStatusEnum::PAID) {
                $paymentStatusId = PaymentStatusEnum::CAPTURED;
                if ($request->actual_amount && $request->actual_amount > $request->collection_amount) {
                    $paymentStatusId = PaymentStatusEnum::PARTIAL_CAPTURED;
                }
                LoggerService::info("Split payment approval started for code: {$splitPaymentCode}, SR No: {$srNo}");
                $paymentInformation = [
                    'collection_amount' => $request->collection_amount,
                    'bank_reference_number' => $request->bank_reference_number,
                    'payment_status_id' => $paymentStatusId,
                    'payment_allocation_status' => PaymentAllocationStatus::NOT_ALLOCATED,
                    'updated_by' => $request->user()->id,
                    'verified_at' => now(),
                    'verified_by' => $request->user()->id,
                    'insurer_receipt_number' => $request->insurer_receipt_number,
                ];

                // associate approved documents with payment split
                if (isset($request->approved_document_model[$splitPayment->sr_no]) && count($request->approved_document_model[$splitPayment->sr_no]) > 0) {
                    LoggerService::info("Processing approved documents for split payment - code: {$splitPaymentCode}, SR No: {$srNo}, document count: ".count($request->approved_document_model[$splitPayment->sr_no]));
                    foreach ($request->approved_document_model[$splitPayment->sr_no] as $document) {
                        $quoteDocumentRec = QuoteDocument::find($document['id'] ?? '');
                        if ($quoteDocumentRec) {
                            LoggerService::info("Processing document ID: {$document['id']} for split payment - code: {$splitPaymentCode}, SR No: {$srNo}");
                            if (empty($document['payment_split_id'])) {
                                $quoteDocumentRec->payment_split_id = $splitPayment->id;
                                LoggerService::info("Assigned document to payment split - code: {$splitPaymentCode}, SR No: {$srNo}, document ID: {$document['id']}");
                            } else {
                                $quoteDocumentRec->document_type_code = $this->mapToReciept($quoteDocumentRec->document_type_code);
                                $quoteDocumentRec->document_type_text = DocumentTypeEnum::RECEIPT;
                                LoggerService::info("Updated document to receipt type - code: {$splitPaymentCode}, SR No: {$srNo}, document ID: {$document['id']}");
                            }
                            $quoteDocumentRec->save();
                        }
                    }
                }

                $splitPayment->update($paymentInformation);
                if ($masterPayment) {
                    $masterPayment->update([
                        'captured_amount' => ($masterPayment->captured_amount + $request->collection_amount),
                        'payment_allocation_status' => PaymentAllocationStatus::NOT_ALLOCATED,
                    ]);
                }

                /* Create payment receipt for broker */
                if ($masterPayment->collection_type == CollectionTypeEnum::BROKER) {
                    LoggerService::info("Creating broker receipt for - code: {$splitPaymentCode}, SR No: {$srNo}, collection type: {$masterPayment->collection_type}");
                    app(SplitPaymentService::class)->createReceipt($request->modelType, $request->quote_id, $splitPayment, $request?->send_update_id);
                }
                LoggerService::info("Split payment approval completed successfully - code: {$splitPaymentCode}, SR No: {$srNo}");
            } elseif ($request->is_declined && $splitPayment->payment_status_id != PaymentStatusEnum::PAID) {
                LoggerService::info("Split payment decline started for code: {$splitPaymentCode}, SR No: {$srNo}");
                $paymentInformation = [
                    'decline_reason_id' => $request->declined_reason,
                    'decline_custom_reason' => $request->declined_custom_reason,
                    'payment_status_id' => PaymentStatusEnum::DECLINED,
                    'updated_by' => $request->user()->id,
                ];
                $splitPayment->update($paymentInformation);
                $successMessage = 'Payment Declined';
                LoggerService::info("Split payment decline completed successfully - code: {$splitPaymentCode}, SR No: {$srNo}");
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
            $oldPaymentStatus = $payment->payment_status_id;
            info("Master payment code: {$payment->code} - setMasterPaymentStatus called");
            if ($payment->frequency == 'upfront') {
                $this->updateUpfrontStatus($payment);
            } else {
                $this->updateNonUpfrontStatus($payment);
            }
            $newPaymentStatus = $payment->payment_status_id;
            LoggerService::info("Master payment code: {$payment->code} - Payment status updated from {$oldPaymentStatus} to {$newPaymentStatus}");
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
        info('Start - Temporarily adding for correcting historic data: '.$quote->code);
        /* calculate price and vat for payments for old payment data  where price_vat_applicable is not available */
        $quotePayment = Payment::where('code', $quote->code)->mainLeadPayment()->with('paymentSplits')->first();
        if ($quotePayment) {
            if (! $quotePayment->price_vat_applicable) {
                [$priceWithoutVat, $vat] = app(SplitPaymentService::class)->calculateMasterPriceAndVat($quotePayment->total_price, $modelType, $quote->id, $quotePayment->code);
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
                        [$priceWithoutVat, $vat] = app(SplitPaymentService::class)->calculatePriceAndVat($quotePayment->frequency, $quotePayment->total_price, $splitPayment->sr_no, $splitAmount, $modelType, $quote->id, count($paymentSplits), $splitPayment->code);
                        $splitPayment->update([
                            'price_vat_applicable' => $priceWithoutVat,
                            'price_vat' => $vat,
                        ]);
                    }
                }
            }
        }

        info('End - Temporarily adding for correcting historic data: '.$quote->code);
        /* End - Temporarily adding for correcting historic data */

    }

    public function getAuthorisePaymentCount($user = null, $teamIds = null)
    {
        $user = $user ?: Auth::user();
        if (! $user) {
            return 0;
        }

        $userTeamIds = $teamIds ?: $user->getUserTeamIds();

        $personalCount = DB::table('payments')
            ->distinct()
            ->Join('personal_quotes as pq', 'pq.code', '=', 'payments.code')
            ->join('user_team', 'user_team.user_id', 'pq.advisor_id')
            ->where('payments.payment_status_id', PaymentStatusEnum::AUTHORISED);

        if ($user->hasAnyRole([RolesEnum::CarManager, RolesEnum::HealthManager, RolesEnum::TravelManager, RolesEnum::LifeManager, RolesEnum::HomeManager, RolesEnum::PetManager, RolesEnum::BikeManager, RolesEnum::CycleManager, RolesEnum::YachtManager, RolesEnum::JetskiManager, RolesEnum::BusinessManager])) {
            $personalCount = $personalCount->whereIn('user_team.team_id', $userTeamIds);
        } else {
            $personalCount = $personalCount->where('pq.advisor_id', $user->id);
        }

        return $personalCount->count('payments.id');
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
