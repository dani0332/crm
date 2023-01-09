<?php

namespace App\Jobs;

use App\Enums\QuoteTypeShortCode;
use App\Services\HealthQuoteService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GetQuotePlansJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, GenericQueriesAllLobs;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 120;
    private $lead,
    $healthQuoteService;

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
        if (! $this->lead) {
            return false;
        }
        $quoteTypeCode = $this->getQuoteCodeType($this->lead);
        if (! $quoteTypeCode) {
            return false;
        }

        switch ($quoteTypeCode) {
            case QuoteTypeShortCode::HEA:
                $healthQuoteService->getQuotePlans($this->lead->uuid);
                $this->lead->quote_updated_at = Carbon::now();
                $this->lead->save();
                break;
            default:
                break;
        }
    }
}
