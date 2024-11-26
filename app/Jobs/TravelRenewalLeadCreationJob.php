<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\TravelRenewalService;
use App\Enums\QuoteStatusEnum;
use App\Enums\PaymentStatusEnum;

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
        $quoteData = [
            'destination' => trim($this->travelQuote->destination),
            'first_name' => trim($this->travelQuote->first_name),
            'last_name' => trim($this->travelQuote->last_name),
            'source' => trim($this->travelQuote->source),
            'customer_id' => $this->travelQuote->customer_id,
            'payment_status_id' => PaymentStatusEnum::DRAFT,
            'quote_status_id' => QuoteStatusEnum::NewLead,
            'code' => trim($this->travelQuote->code),
            'uuid' => trim($this->travelQuote->uuid),
            'direction_code' => trim($this->travelQuote->direction_code),
            'nationality_id' => $this->travelQuote->nationality_id,
            'is_ecommerce' => $this->travelQuote->is_ecommerce,
            'renewal_batch' => $this->travelQuote->renewal_batch,
            'policy_expiry_date' => $this->travelQuote->policy_expiry_date,
            'email' => $this->travelQuote->email,
            'mobile_no' => $this->travelQuote->mobile_no,
            'coverage_code' => $this->travelQuote->coverage_code,
            'start_date' => $this->travelQuote->start_date,
            'renewal_batch_id' => $this->travelQuote->renewal_batch_id,
            'region_cover_for_id' => $this->travelQuote->region_cover_for_id,
        ];

        app(TravelRenewalService::class)->createTravelRenewalLead($quoteData);
        info('TravelRenewalLeadCreationJob - Completed creating Travel Renewal Lead for quote: '.$this->travelQuote->uuid);
    }
}
