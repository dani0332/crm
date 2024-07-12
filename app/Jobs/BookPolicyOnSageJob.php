<?php

namespace App\Jobs;

use App\Enums\PaymentFrequency;
use App\Enums\PaymentStatusEnum;
use App\Factories\SagePayloadFactory;
use App\Services\SageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BookPolicyOnSageJob /*implements ShouldQueue*/
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

    /**
     * Create a new job instance.
     */
    public function __construct($sageRequest, $quote, $payment, $paymentSplits)
    {
        $this->sageRequest = $sageRequest;
        $this->quote = $quote;
        $this->payment = $payment;
        $this->paymentSplits = $paymentSplits;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        (new SageApiService())->bookPolicyOnSage($this->sageRequest, $this->quote, $this->payment, $this->paymentSplits);
    }


    public function middleware()
    {
        return [(new WithoutOverlapping($this->quote->uuid))->dontRelease()];
    }


}
