<?php

namespace App\Jobs;

use App\Enums\PaymentProcessJobEnum;
use App\Models\CcPaymentProcess;
use App\Services\SplitPaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCCPaymentJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
