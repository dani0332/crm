<?php

namespace App\Jobs\MACRM;

use App\Services\MACRMService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCourierQuoteWithMacrm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public $quote, public $quoteTypeId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            MACRMService::syncCourierQuote($this->quote, $this->quoteTypeId);
        } catch (Exception $e) {
            Log::error(self::class." - Error: {$e->getMessage()}");
        }
    }
}
