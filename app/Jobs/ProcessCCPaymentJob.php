<?php

namespace App\Jobs;

use App\Enums\PaymentCollectionTypeEnum;
use App\Enums\PaymentMethodsEnum;
use App\Enums\PaymentProcessJobEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\QuoteTagEnums;
use App\Enums\SendPolicyTypeEnum;
use App\Models\CcPaymentProcess;
use App\Models\PaymentSplits;
use App\Models\QuoteTag;
use App\Services\SageApiService;
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

    protected $paymentRecord;
    public $tries = 1;
    public $timeout = 120; // 2 minutes
    public $uniqueFor = 125;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(CcPaymentProcess $paymentRecord)
    {
        $this->paymentRecord = $paymentRecord;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $quoteInfo = $this->paymentRecord->quote_type.'-'.$this->paymentRecord->quoteable_id;
        info("CC Payments Job Started For Payment {$quoteInfo} Split ID: {$this->paymentRecord->payment_splits_id}");
        try {
            $this->paymentRecord->update(['status' => PaymentProcessJobEnum::INPROCESS]);
            app(SplitPaymentService::class)->processSplitPaymentApprove(
                $this->paymentRecord->quote_type,
                $this->paymentRecord->quoteable_id,
                $this->paymentRecord->payment_splits_id,
                $this->paymentRecord->amount_captured,
                true
            );
            $payment = PaymentSplits::find($this->paymentRecord->payment_splits_id)->payment;
            if (! $payment) {
                info("CC Payments Job Failed for Payment {$quoteInfo} Split ID: {$this->paymentRecord->payment_splits_id} - Error: Payment not found");

                return;
            }
            $splitPayments = $payment->paymentSplits;
            $hasAnyCCPayment = $splitPayments->where('payment_method', PaymentMethodsEnum::CreditCard)->count() > 0 ? true : false;

            if (in_array($payment->payment_status_id, [PaymentStatusEnum::PAID, PaymentStatusEnum::CAPTURED]) && $payment->collection_type == PaymentCollectionTypeEnum::INSURER && $hasAnyCCPayment) {
                $quote = $this->getQuoteObject($this->paymentRecord->quote_type, $this->paymentRecord->quoteable_id);
                // We can trigger sage & book policy entry from here
                if ($quote) {
                    QuoteTag::where('quote_uuid', $quote->uuid)
                        ->where('name', QuoteTagEnums::TAP_PAYMENT_CAPTURE_PROCESS_START)
                        ->update(['value' => 0]);

                    $request = new \stdClass;
                    $request->quote_id = $quote->id; // TODO :  Lead ID
                    $request->modelType = $this->paymentRecord->quote_type;
                    $request->model_type = $this->paymentRecord->quote_type;
                    $request->is_send_policy = false;
                    $request->send_policy_type = SendPolicyTypeEnum::SAGE;
                    $request->transaction_payment_status = null;

                    return (new SageApiService)->postBookPolicyToSage($request, $quote);
                } else {
                    info("CC Payments Job Failed for Payment {$quoteInfo} Split ID: {$this->paymentRecord->payment_splits_id} - Error: Quote not found");
                }
            }

            info("CC Payments Job Ended For Payment {$quoteInfo} Split ID: {$this->paymentRecord->payment_splits_id}");

        } catch (\Exception $exception) {
            // Handle the exception here
            info("CC Payments Job Failed for Payment {$quoteInfo} Split ID: {$this->paymentRecord->payment_splits_id} - Error: ".$exception->getMessage());
        }
    }

    public function uniqueId(): string
    {
        return $this->paymentRecord->payment_splits_id;
    }
}
