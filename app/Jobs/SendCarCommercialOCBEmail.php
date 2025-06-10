<?php

namespace App\Jobs;

use App\Enums\QuoteStatusEnum;
use App\Models\CarQuote;
use App\Services\EmailServices\CarEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCarCommercialOCBEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = 60;
    private $quoteUuid;

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
                LoggerService::info(self::class." - Car Lead Not Found - Ref ID: {$this->quoteUuid} | Time: ".now());

                return;
            }
            LoggerService::info(self::class." - Sending car company commercial ocb email for Ref-ID: {$carLead->uuid}, Lead Status ID: {$carLead->quote_status_id} | Time: ".now());
            $carEmailService->sendCarCompanyCommercialOCB($carLead);
            if ($carLead->quote_status_id == QuoteStatusEnum::NewLead) {
                $carLead->quote_status_id = QuoteStatusEnum::Quoted;
                $carLead->save();
            }
        } catch (\Throwable $th) {
            LoggerService::error(self::class." - Exception encountered: '{$th->getMessage()} | Line: {$th->getLine()} | File: {$th->getFile()} ' - Ref ID: {$this->quoteUuid} | Time: ".now());
        }
    }
}
