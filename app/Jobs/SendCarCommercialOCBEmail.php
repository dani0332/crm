<?php

namespace App\Jobs;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
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
        LoggerService::startQuoteLogging(QuoteTypes::CAR->refId($this->quoteUuid));
        try {
            $carLead = CarQuote::where('uuid', $this->quoteUuid)->first();
            if (! $carLead) {
                LoggerService::info(self::class.' - Car Lead Not Found');

                return;
            }
            LoggerService::info(self::class.' - Sending car company commercial ocb email', ['lead_status_id' => $carLead->quote_status_id]);
            $carEmailService->sendCarCompanyCommercialOCB($carLead);
            if ($carLead->quote_status_id == QuoteStatusEnum::NewLead) {
                $carLead->quote_status_id = QuoteStatusEnum::Quoted;
                $carLead->save();
            }

        } catch (\Exception $exception) {
            LoggerService::error(self::class.' - Exception encountered: ', exception: $exception);
        }
    }
}
