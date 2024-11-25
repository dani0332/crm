<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\TravelRenewalService;

class TravelRenewalLeadCreationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $tries = 3;
    public $timeout = 60;
    public $backoff = 300;
    private $travelQuote;

    public function __construct($travelQuote)
    {
        $this->travelQuote = $travelQuote;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        info('TravelRenewalLeadCreationJob - Creating Travel Renewal Lead for quote: '.$this->travelQuote->uuid);
        app(TravelRenewalService::class)->createTravelRenewalLead($this->travelQuote);
        info('TravelRenewalLeadCreationJob - Completed creating Travel Renewal Lead for quote: '.$this->travelQuote->uuid);
    }
}
