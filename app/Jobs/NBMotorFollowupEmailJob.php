<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CarQuote;
use App\Enums\QuoteStatusEnum;
use App\Services\EmailServices\CarEmailService;

class NBMotorFollowupEmailJob implements ShouldQueue
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
                info("NBMotorFollowupEmailJob - Car Lead Not Found - Ref ID: {$this->quoteUuid} | Time: ".now());

                return;
            }

            $eligibleStatuses = [QuoteStatusEnum::Quoted];
            if (in_array($carLead->quote_status_id, $eligibleStatuses)) {
                info("Sending NB motor email follow-ups for Ref-ID: {$carLead->uuid}, Lead Status ID: {$carLead->quote_status_id} | Time: ".now());
                $carEmailService->triggerNBMotorWorkFlow($carLead);
                $carLead->quote_status_id = QuoteStatusEnum::FollowedUp;
                $carLead->save();
            } else {
                info("NBMotorFollowupEmailJob - Car Lead did not trigger OCA WorkFlow due to ineligible status (Status ID: {$carLead->quote_status_id}) - Ref ID: {$carLead->uuid} | Time: ".now());
            }
        } catch (\Throwable $th) {
            info("NBMotorFollowupEmailJob - Exception encountered: '{$th->getMessage()}' - Ref ID: {$this->quoteUuid} | Time: ".now());
            throw $th;
        }
    }
}
