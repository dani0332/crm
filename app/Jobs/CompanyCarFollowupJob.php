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

class CompanyCarFollowupJob implements ShouldQueue
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
            $eligibleStatuses = [QuoteStatusEnum::Quoted, QuoteStatusEnum::NewLead];
            $leadSources = [LeadSourceEnum::RENEWAL_UPLOAD];
            if (in_array($carLead->quote_status_id, $eligibleStatuses) && ! in_array($carLead->source, $leadSources)) {
                info("Sending company car  email follow-ups for Ref-ID: {$carLead->uuid}, Lead Status ID: {$carLead->quote_status_id} | Time: ".now());
                $carEmailService->sendCompanyCarWorkFlow($carLead);
                $carLead->quote_status_id = QuoteStatusEnum::FollowedUp;
                $carLead->save();
            } else {
                info(self::class." - Car Lead did not trigger company car motor WorkFlow due to ineligible source (Source: {$carLead->source}) - Ref ID: {$carLead->uuid} | Time: ".now());
                info(self::class." - Car Lead did not trigger company car WorkFlow due to ineligible status (Status ID: {$carLead->quote_status_id}) - Ref ID: {$carLead->uuid} | Time: ".now());
            }
        } catch (\Throwable $th) {
            info(self::class." - Exception encountered: '{$th->getMessage()}' - Ref ID: {$this->quoteUuid} | Time: ".now());
        }
    }

}
