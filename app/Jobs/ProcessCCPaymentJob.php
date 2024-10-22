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

class ProcessCCPaymentJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
