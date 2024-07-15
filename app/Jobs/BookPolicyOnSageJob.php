<?php

namespace App\Jobs;

use App\Services\SageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class BookPolicyOnSageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;
    private $sageRequest;
    private $quote;
    private $payment;
    private $paymentSplits;
    private $isPaymentFrequencyUpfront;
    private $isPaymentFrequencySplitPayment;
    private $skipAPInvoicePatchAndPosting;
    private $aPInvoicePatchAndPostingOnly;

    /**
     * Create a new job instance.
     */
    public function __construct($sageRequest, $quote, $payment, $paymentSplits, $skipAPInvoicePatchAndPosting, $aPInvoicePatchAndPostingOnly)
    {
        $this->sageRequest = $sageRequest;
        $this->quote = $quote;
        $this->payment = $payment;
        $this->paymentSplits = $paymentSplits;
        $this->skipAPInvoicePatchAndPosting = $skipAPInvoicePatchAndPosting;
        $this->aPInvoicePatchAndPostingOnly = $aPInvoicePatchAndPostingOnly;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        (new SageApiService())->bookPolicyOnSage($this->sageRequest, $this->quote, $this->payment, $this->paymentSplits, $this->skipAPInvoicePatchAndPosting, $this->aPInvoicePatchAndPostingOnly);
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->quote->uuid))->dontRelease()];
    }

}
