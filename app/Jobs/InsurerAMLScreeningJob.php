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
    public array $memberDetails;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteTypeID, $quoteDetails, $customerType, $memberDetails)
    {
        $this->quoteTypeID = $quoteTypeID;
        $this->quoteDetails = $quoteDetails;
        $this->customerType = $customerType;
        $this->memberDetails = $memberDetails;
    }

    /**
     * Execute the job.
     */
    public function handle(AMLService $amlService): void
    {
        try {
            info('Insurer AML Screening Job Started. Ref-ID: '.$this->quoteDetails['code']);
            $amlService->amlScreeningGIG(
                $this->quoteTypeID,
                $this->quoteDetails,
                $this->customerType,
                $this->memberDetails,
            );
        } catch (\Exception $exception) {
            info('Insurer AML Screening Job Failed. Ref-ID: '.$this->quoteDetails['code'].' - Error: '.$exception->getMessage());
        }
    }
}
