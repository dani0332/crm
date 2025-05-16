<?php

namespace App\Jobs;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\CarQuote;
use App\Services\EmailServices\CarEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPCPFollowupsJob implements ShouldQueue
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
    public function handle(CarEmailService $carEmailService): void
    {
        try {
            $carLead = CarQuote::where('uuid', $this->quoteUuid)->first();
            if (! $carLead) {
                LoggerService::info(self::class." -SendPCPFollowupsJob - CAR Lead Not Found - Ref ID: {$this->quoteUuid} | Time: ".now());

                return;
            }
            if (empty($carLead->pcp_flow_executed_at)) {
                LoggerService::info(self::class." - Sending PCP Motor follow-ups for Ref-ID: {$carLead->uuid}, Lead Status ID: {$carLead->quote_status_id} | Time: ".now());
                $leadSources = [LeadSourceEnum::REVIVAL, LeadSourceEnum::REVIVAL_PAID, LeadSourceEnum::REVIVAL_REPLIED];
                $eligibleStatuses = [QuoteStatusEnum::Quoted, QuoteStatusEnum::NewLead];
                if (in_array($carLead->quote_status_id, $eligibleStatuses) && ! in_array($carLead->source, $leadSources)) {
                    LoggerService::info(self::class." - Sending PCP Motor follow-ups for Ref-ID: {$carLead->uuid}, Lead Status ID: {$carLead->quote_status_id} | Time: ".now());
                    $carEmailService->sendPCPFollowups($carLead);
                    $carLead->quote_status_id = QuoteStatusEnum::FollowedUp;
                    $carLead->pcp_flow_executed_at = now();
                    $carLead->save();
                } else {
                    LoggerService::info(self::class." - CAR Lead did not trigger PCP WorkFlow due to ineligible status (Status ID: {$carLead->quote_status_id}) - Ref ID: {$carLead->uuid} | Time: ".now());
                }
            } else {
                LoggerService::info(self::class." - PCP Motor follow-ups already sent for Ref-ID: {$carLead->uuid}, Lead Status ID: {$carLead->quote_status_id} | Time: ".now());
            }

        } catch (\Throwable $th) {
            LoggerService::info(self::class." - Exception encountered: '{$th->getMessage()}' - Ref ID: {$this->quoteUuid} | Time: ".now());
            throw $th;
        }
    }
}
