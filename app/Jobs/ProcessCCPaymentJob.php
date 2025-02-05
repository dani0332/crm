<?php

namespace App\Jobs;

use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentProcessJobEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTagEnums;
use App\Enums\SendPolicyTypeEnum;
use App\Models\CcPaymentProcess;
use App\Models\PaymentSplits;
use App\Models\QuoteTag;
use App\Services\SageApiService;
use App\Services\SendUpdateLogService;
use App\Services\SplitPaymentService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCCPaymentJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, SerializesModels;

    protected $ccPaymentProcess;
    private $ccPaymentProcessId;
    public $tries = 1;
    public $timeout = 120; // 2 minutes
    public $uniqueFor = 125;

    /**
     * Create a new job instance.
     *
     * @param  int  $ccPaymentProcessId
     * @return void
     */
    public function __construct($ccPaymentProcessId)
    {
        $this->ccPaymentProcessId = $ccPaymentProcessId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        info("CC Payment Job Started: {$this->ccPaymentProcessId}");
        $ccPaymentProcess = CcPaymentProcess::find($this->ccPaymentProcessId);

        if ($ccPaymentProcess->status === PaymentProcessJobEnum::QUEUED) {
            $splitPaymentCode = $ccPaymentProcess->splitPayment->code;
            $previousStatus = $ccPaymentProcess->status;

            // Update status to IN_PROCESS
            $ccPaymentProcess->update(['status' => PaymentProcessJobEnum::IN_PROCESS]);
            info("Status changed from {$previousStatus} to {$ccPaymentProcess->status}: for Child payment code: {$splitPaymentCode}, Split ID: {$ccPaymentProcess->payment_splits_id}");

            // Process the split payment approval
            app(SplitPaymentService::class)->processSplitPaymentApprove(
                $ccPaymentProcess->quote_type,
                $ccPaymentProcess->quoteable_id,
                $ccPaymentProcess->payment_splits_id,
                $ccPaymentProcess->amount_captured,
                true
            );

            $paymentSplit = PaymentSplits::find($ccPaymentProcess->payment_splits_id);
            if (! $paymentSplit || ! $paymentSplit->payment) {
                info("CC Payments Job Failed for Payment Split {$splitPaymentCode} - Error: Payment not found");

                return;
            }
            $payment = $paymentSplit->payment;
            $splitPayments = $payment->paymentSplits;
            $hasAnyCCPayment = $splitPayments->where('payment_method', PaymentMethodsEnum::CreditCard)->count() > 0;

            if (in_array($payment->payment_status_id, [PaymentStatusEnum::CAPTURED]) && $payment->isInsurerPayment() && $hasAnyCCPayment) {
                $quote = $this->getQuoteObject($ccPaymentProcess->quote_type, $ccPaymentProcess->quoteable_id);
                if ($quote && ! $payment->isGIGInsurer($ccPaymentProcess->quote_type, $quote)) {
                    if (! empty($payment->send_update_log_id)) {
                        QuoteTag::where([
                            'quote_uuid' => $quote->uuid,
                            'send_update_log_id' => $payment->send_update_log_id,
                            'name' => QuoteTagEnums::TAP_PAYMENT_CAPTURE_PROCESS_SU_START.'-'.$payment->send_update_log_id,
                        ])->update(['value', 0]);

                        $sendUpdateRequest = new \stdClass;
                        $sendUpdateRequest->quoteType = $ccPaymentProcess->quote_type;
                        $sendUpdateRequest->quoteRefId = $quote->id;
                        $sendUpdateRequest->quoteUuid = $quote->uuid;
                        $sendUpdateRequest->sendUpdateId = $payment->send_update_log_id;
                        $sendUpdateRequest->inslyMigrated = $quote->insly_migrated;

                        return app(SendUpdateLogService::class)->preparedDataForEndorsement($sendUpdateRequest);
                    } else {
                        QuoteTag::where('quote_uuid', $quote->uuid)
                            ->where('name', QuoteTagEnums::TAP_PAYMENT_CAPTURE_PROCESS_START)
                            ->update(['value' => 0]);

                        $request = new \stdClass;
                        $request->quote_id = $quote->id; // TODO :  Lead ID
                        $request->modelType = $ccPaymentProcess->quote_type;
                        $request->model_type = $ccPaymentProcess->quote_type;
                        $request->is_send_policy = false;
                        $request->send_policy_type = SendPolicyTypeEnum::SAGE;
                        $request->transaction_payment_status = null;

                        return (new SageApiService)->postBookPolicyToSage($request, $quote);
                    }
                } else {
                    info("CC Payments Job Failed for Payment Split : {$splitPaymentCode} - Error: Quote not found");
                }
            } else {
                info("CC Payments Job did not meet conditions for Payment Split : {$splitPaymentCode}");
            }

            info("CC Payment Job Ended: Child payment code: {$splitPaymentCode}, Split ID: {$ccPaymentProcess->payment_splits_id}");

        } else {
            info("Skipping CC Payment Job: Not in QUEUED status. Current status: {$ccPaymentProcess->status}, Child payment code: {$ccPaymentProcess->splitPayment->code}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        info("CC Payment Job failed for: {$this->ccPaymentProcessId}, Error: {$exception->getMessage()}");
        $ccPaymentProcess = CcPaymentProcess::find($this->ccPaymentProcessId);
        $ccPaymentProcess->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => $exception->getMessage()]);
    }

    public function uniqueId(): string
    {
        return 'cc-payment-process-id-'.$this->ccPaymentProcessId;
    }
}
