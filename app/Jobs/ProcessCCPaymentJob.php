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
     * @param int $ccPaymentProcessId
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
        $ccPaymentProcess = CcPaymentProcess::find($this->ccPaymentProcessId);

        if ($ccPaymentProcess->status === PaymentProcessJobEnum::IN_PROCESS) {
            $splitPaymentCode = $ccPaymentProcess->splitPayment->code;
            info("CC Payment Job Started: Child payment code: {$splitPaymentCode}, Split ID: {$ccPaymentProcess->payment_splits_id}");

            try {
                app(SplitPaymentService::class)->processSplitPaymentApprove(
                    $ccPaymentProcess->quote_type,
                    $ccPaymentProcess->quoteable_id,
                    $ccPaymentProcess->payment_splits_id,
                    $ccPaymentProcess->amount_captured,
                    true
                );

                info("CC Payment Job Ended: Child payment code: {$splitPaymentCode}, Split ID: {$ccPaymentProcess->payment_splits_id}");
            } catch (\Exception $exception) {
                info("CC Payment Job Failed: Child payment code: {$splitPaymentCode}, Split ID: {$ccPaymentProcess->payment_splits_id}, Error: {$exception->getMessage()}");
            }
        } else {
            info("CC Payment Job Not In Process: Child payment code: {$ccPaymentProcess->splitPayment->code}, Status: {$ccPaymentProcess->status}");
        }
    }

    /**
     * Get the unique ID for the job.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return "cc-payment-process-id-" .$this->ccPaymentProcessId;
    }
}