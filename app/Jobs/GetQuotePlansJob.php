<?php

namespace App\Jobs;

use App\Enums\QuoteTypeShortCode;
use App\Services\HealthQuoteService;
use App\Traits\GenericQueriesAllLobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Sammyjo20\LaravelHaystack\Concerns\Stackable;
use Sammyjo20\LaravelHaystack\Contracts\StackableJob;

class GetQuotePlansJob implements ShouldQueue, StackableJob
{
    use Dispatchable, GenericQueriesAllLobs, InteractsWithQueue, Queueable, Stackable;

    public $tries = 3;
    public $timeout = 30;
    public $backoff = 300;
    private $lead;
    private $healthQuoteService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($lead)
    {
        $this->lead = $lead;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(HealthQuoteService $healthQuoteService)
    {
        if ($this->lead && $this->getQuoteCodeType($this->lead)) {
            $quoteTypeCode = $this->getQuoteCodeType($this->lead);

            switch ($quoteTypeCode) {
                case QuoteTypeShortCode::HEA:
                    $statusCode = $healthQuoteService->getQuotePlans($this->lead->uuid);
                    if (! isset($statusCode)) {
                        info('GetQuotePlansJob - '.$this->lead->code.' - Failed - No Response from KEN');

                        return false;
                    } elseif (is_string($statusCode)) {
                        info('GetQuotePlansJob - '.$this->lead->code.' - Failed - '.$statusCode);

                        return false;
                    }
                    break;
                default:
                    break;
            }
        }
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->lead->id))->dontRelease()];
    }
}
