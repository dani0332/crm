<?php

namespace App\Jobs;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\CarQuote;
use App\Services\EmailServices\CarEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CompanyCarOCBJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    private $quoteUuid;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 60;

    public function __construct($quoteUuid)
    {
        $this->quoteUuid = $quoteUuid;
    }

    /**
     * Execute the job.
     */
    public function handle(CarEmailService $carEmailService): void
    {
        try {
            $carLead = CarQuote::where('uuid', $this->quoteUuid)->first();

            if (! $carLead) {
                info(self::class." - Car Lead Not Found - Ref ID: {$this->quoteUuid} | Time: ".now());

                return;
            }
            $leadSources = [LeadSourceEnum::RENEWAL_UPLOAD];
            if (! in_array($carLead->source, $leadSources)) {
                info("Sending company car ocb email for Ref-ID: {$carLead->uuid}, Lead Status ID: {$carLead->quote_status_id} | Time: ".now());
                $carEmailService->sendCompanyCarOCB($carLead);
                if ($carLead->quote_status_id == QuoteStatusEnum::NewLead) {
                    $carLead->quote_status_id = QuoteStatusEnum::Quoted;
                    $carLead->save();
                }

            } else {
                info(self::class." - Car Lead did not trigger company car ocb email WorkFlow due to ineligible source (Source: {$carLead->source}) - Ref ID: {$carLead->uuid} | Time: ".now());

            }
        } catch (\Throwable $th) {
            info(self::class." - Exception encountered: '{$th->getMessage()}' - Ref ID: {$this->quoteUuid} | Time: ".now());
        }
    }
}
