<?php

namespace App\Jobs;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentProcessJobEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTagEnums;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Models\CcPaymentProcess;
use App\Models\PaymentSplits;
use App\Models\QuoteTag;
use App\Models\SendUpdateLog;
use App\Services\Logger\LoggerService;
use App\Services\QuoteTagService;
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
    private $splitPaymentCode;

    /**
     * Create a new job instance.
     *
     * @param  int  $ccPaymentProcessId
     * @return void
     */
    public function __construct($ccPaymentProcessId, $splitPaymentCode)
    {
        $this->ccPaymentProcessId = $ccPaymentProcessId;
        $this->splitPaymentCode = $splitPaymentCode;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $splitPaymentCode = $this->splitPaymentCode;

        LoggerService::startQuoteLogging($splitPaymentCode, LoggerFeatureEnum::CC_PAYMENT_PROCESS);

        LoggerService::info("Processing CC Payment Job: {$this->ccPaymentProcessId}, Payment Split Code: {$splitPaymentCode}");

        $ccPaymentProcess = CcPaymentProcess::find($this->ccPaymentProcessId);

        // CC payment process only once status queued if not then skip
        if ($ccPaymentProcess->status !== PaymentProcessJobEnum::QUEUED) {
            LoggerService::info("Skipping CC Payment Job: {$this->ccPaymentProcessId}, Already Processed Current Status: {$ccPaymentProcess->status}, Payment Split Code: {$splitPaymentCode}");

            return;
        }

        // Update status to IN_PROCESS
        $ccPaymentProcess->update(['status' => PaymentProcessJobEnum::IN_PROCESS]);
        LoggerService::info("Status changed to IN_PROCESS for Payment ID: {$this->ccPaymentProcessId}, Payment Split Code: {$splitPaymentCode}");

        // Process the split payment approval
        $isProcessComplete = app(SplitPaymentService::class)->processSplitPaymentApprove(
            $ccPaymentProcess->quote_type,
            $ccPaymentProcess->quoteable_id,
            $ccPaymentProcess->payment_splits_id,
            $ccPaymentProcess->amount_captured,
            true
        );

        // If sage receipt is not generated or any of process is break we are not processing further
        if (! $isProcessComplete) {
            LoggerService::info("Process not completed for Payment Split: {$this->splitPaymentCode} Aborting further processing, Payment Split Code: {$splitPaymentCode}");

            return;
        }

        $paymentSplit = PaymentSplits::find($ccPaymentProcess->payment_splits_id);
        if (! $paymentSplit || ! $paymentSplit->payment) {
            LoggerService::info("Payment Split or Payment record not found for Split ID: {$ccPaymentProcess->payment_splits_id}, Payment Split Code: {$splitPaymentCode}");

            return;
        }

        $payment = $paymentSplit->payment;
        $isInsurerPayment = $payment->isInsurerPayment();
        $isPaymentCapture = in_array($payment->payment_status_id, [PaymentStatusEnum::CAPTURED, PaymentStatusEnum::PAID]);

        $hasAnyCCPayment = $payment->paymentSplits()
            ->where('payment_method', PaymentMethodsEnum::CreditCard)
            ->select('id')
            ->limit(1)
            ->exists();

        // If conditions not met then skip the job like payment is not insurer & no cc payment and payemnt is not piad
        LoggerService::info("CC Payment Job - Payment Details: Status ID: {$payment->payment_status_id}, Has CC Payment: ".($hasAnyCCPayment ? 'Yes' : 'No').' has Insurer Payment: '.($isInsurerPayment ? 'Yes' : 'No').", Payment Split Code: {$splitPaymentCode} is Payment Capture: ".($isPaymentCapture ? 'Yes' : 'No'));
        if (! $isPaymentCapture || ! $isInsurerPayment || ! $hasAnyCCPayment) {
            LoggerService::info("CC Payment Job skipped: Payment conditions not met for Payment ID: {$this->ccPaymentProcessId} Payment Split Code: {$splitPaymentCode} ");

            return;
        }

        $quote = $this->getQuoteObject($ccPaymentProcess->quote_type, $ccPaymentProcess->quoteable_id);
        LoggerService::startQuoteLogging($quote);
        $isPaymentGatewayTap = $payment->isPaymentGatewayTap();

        // For personal LOB we can get value from personal quote  quote_type_id
        if ($ccPaymentProcess->quote_type == QuoteTypes::PERSONAL->value) {
            $quoteTypeId = $quote->quote_type_id;
        } else {
            $quoteTypeId = QuoteTypes::getIdFromValue($ccPaymentProcess->quote_type);
        }
        LoggerService::info("Quote ID: {$quote->id}, Send Update Log ID: {$payment->send_update_log_id}, Payment Split Code: {$splitPaymentCode} Quote Type ID: {$quoteTypeId}");
        $isCapturePaymentStarted = app(QuoteTagService::class)->isCapturePaymentStarted($quote->uuid, $quoteTypeId, $payment->send_update_log_id);

        // We are not relaying on insurance provider we are only relying on quote tag if found entry with 1 then we are good to go
        LoggerService::info("Payment Gateway TAP: {$isPaymentGatewayTap}, Is quote tag(TPCPS) entry found: ".($isCapturePaymentStarted ? 'Yes' : 'No').", Payment Split Code: {$splitPaymentCode}");
        if ($quote && $isCapturePaymentStarted) {
            LoggerService::info('CC Payment Job - Is Send Update Exists: '.! empty($payment->send_update_log_id).' - Send Update Log Id: '.$payment->send_update_log_id ?? '');
            if (! empty($payment->send_update_log_id) && $isPaymentGatewayTap) {
                $sendUpdateLog = SendUpdateLog::where('id', $payment->send_update_log_id)->first();
                LoggerService::info("CC Payment Job: Executing Send update case - Child Payment Code: '.$splitPaymentCode.' SendUpdateCode:".$sendUpdateLog->code);
                QuoteTag::where([
                    'quote_uuid' => $quote->uuid,
                    'send_update_log_id' => $payment->send_update_log_id,
                    'name' => QuoteTagEnums::TAP_PAYMENT_CAPTURE_PROCESS_SU_START.'-'.$payment->send_update_log_id,
                ])->update(['value' => 0]);

                $sendUpdateRequest = (object) [
                    'quoteType' => $ccPaymentProcess->quote_type,
                    'quoteRefId' => $quote->id,
                    'quoteUuid' => $quote->uuid,
                    'sendUpdateId' => $payment->send_update_log_id,
                    'inslyMigrated' => $quote->insly_migrated,
                    'reversalInvoice' => $sendUpdateLog->reversal_invoice ?? '',
                    'isEmailSent' => $sendUpdateLog->is_email_sent,
                    'throughCCPayment' => true,
                    'quoteCode' => $quote->code,
                ];

                LoggerService::info('CC Payment Job: Executing Endorsement Booking Process - Child Payment Code: '.$splitPaymentCode.' SendUpdateCode:'.$sendUpdateLog->code.' - Payload: '.json_encode((array) $sendUpdateRequest), extra: (array) $sendUpdateRequest);

                return app(SendUpdateLogService::class)->preparedDataForEndorsement($sendUpdateRequest);
            } else {
                LoggerService::info("CC Payment Job: Executing Booking Process Payment Split Code: {$splitPaymentCode}");

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
            LoggerService::info("Booking policy or update not triggered for Payment Split Code: {$splitPaymentCode} - Quote tag (TPCPS) entry found: ".($isCapturePaymentStarted ? 'Yes' : 'No'));
        }

        LoggerService::info("CC Payment Job Ended: Child payment code: {$splitPaymentCode}, Split ID: {$ccPaymentProcess->payment_splits_id}");

    }

    public function failed(\Throwable $exception): void
    {
        LoggerService::error("Payment Split Code: {$this->splitPaymentCode} CC Payment Job failed for: {$this->ccPaymentProcessId} ", exception: $exception);
        $ccPaymentProcess = CcPaymentProcess::find($this->ccPaymentProcessId);
        $ccPaymentProcess->update(['status' => PaymentProcessJobEnum::FAILED, 'message' => $exception->getMessage()]);
    }

    public function uniqueId(): string
    {
        return 'cc-payment-process-id-'.$this->ccPaymentProcessId;
    }
}
