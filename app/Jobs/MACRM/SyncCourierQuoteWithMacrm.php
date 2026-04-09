<?php

namespace App\Jobs\MACRM;

use App\Services\Logger\LoggerService;
use App\Services\MACRMService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SyncCourierQuoteWithMacrm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public $quote, public $quoteTypeId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            LoggerService::info(self::class." - Syncing Courier Quote with MACRM for UUID: {$this->quote->uuid} and QuoteTypeId: {$this->quoteTypeId}");
            MACRMService::syncCourierQuote($this->quote, $this->quoteTypeId);
        } catch (Exception $e) {
            LoggerService::error(self::class.' - Error while syncing Courier Quote with MACRM', exception: $e);
        }
    }

    public function uniqueId()
    {
        return $this->quote->id.now()->format('YmdHis');
    }

    public function middleware()
    {
        return [
            new WithoutOverlapping($this->uniqueId()),
        ];
    }
}
