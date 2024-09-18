<?php

namespace App\Jobs;

use App\Enums\QuoteStatusEnum;
use App\Models\HealthQuote;
use App\Services\HealthEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OCAHealthFollowupEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public function handle(HealthEmailService $healthEmailService): void
    {
        try {
            $healthLead = HealthQuote::where('uuid', $this->quoteUuid)->first();
            if (! $healthLead) {
                info("OCAHealthFollowupEmailJob - Health Lead Not Found - Ref ID: {$this->quoteUuid} | Time: ".now());

                return;
            }

            $eligibleStatuses = [QuoteStatusEnum::Quoted, QuoteStatusEnum::ApplicationPending];
            if (in_array($healthLead->quote_status_id, $eligibleStatuses)) {
                info("Sending OCA health email follow-ups for Ref-ID: {$healthLead->uuid}, Lead Status ID: {$healthLead->quote_status_id} | Time: ".now());
                // Send the Health OCA email using the HealthEmailService
                $healthEmailService->sendOCAHealthWorkFlow($healthLead);
                if ($healthLead->quote_status_id != QuoteStatusEnum::ApplicationPending) {
                    $healthLead->quote_status_id = QuoteStatusEnum::FollowedUp;
                    $healthLead->save();
                }

            } else {
                info("OCAHealthFollowupEmailJob - Health Lead did not trigger OCA WorkFlow due to ineligible status (Status ID: {$healthLead->quote_status_id}) - Ref ID: {$healthLead->uuid} | Time: ".now());
            }
        } catch (\Throwable $th) {
            info("OCAHealthFollowupEmailJob - Exception encountered: '{$th->getMessage()}' - Ref ID: {$this->quoteUuid} | Time: ".now());
            throw $th;
        }
    }
}
