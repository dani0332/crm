<?php

namespace App\Jobs;

use App\Services\AMLService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InsurerAMLScreeningJob implements ShouldQueue
{
    use Queueable;

    public string|int $quoteTypeID;
    public object $quoteDetails;
    public string $customerType;
    public $request;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteTypeID, $quoteDetails, $customerType, $request)
    {
        $this->quoteTypeID = $quoteTypeID;
        $this->quoteDetails = $quoteDetails;
        $this->customerType = $customerType;
        $this->request = $request;
    }

    /**
     * Execute the job.
     */
    public function handle(AMLService $amlService): void
    {
        info('Insurer AML Screening Job Started. Ref-ID: '.$this->quoteDetails['code']);
        $amlService->amlScreeningGIG($this->request, $this->quoteTypeID, $this->quoteDetails, $this->customerType);
        info('Insurer AML Screening Job Ended. Ref-ID: '.$this->quoteDetails['code']);
    }
}
