<?php

namespace App\Jobs;

use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\HealthQuote;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\Skip;

class CourtesyEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = 360;
    private $quoteData;

    /**
     * Create a new job instance.
     */
    public function __construct($quoteData)
    {
        $this->quoteData = $quoteData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (isset($this->quoteData['quoteUID'])) {
            LoggerService::startQuoteLogging($this->quoteData['quoteUID']);
        }

        $response = Capi::request('/api/v1-trigger-courtesy-email-sib-workflow', 'post', $this->quoteData);

        info('Courtesy Email CAPI - Payload  : '.json_encode($this->quoteData).' - Response - : '.json_encode($response));
    }

    public function middleware()
    {
        $isAUHLead = false;
        
        if (isset($this->quoteData['quoteTypeId']) && $this->quoteData['quoteTypeId'] === QuoteTypes::HEALTH->id()) {
            $isAUHLead = HealthQuote::where('uuid', $this->quoteData['quoteUID'])->first()?->isAUHLead() ?? false;
        }

        if ($isAUHLead) {
            LoggerService::info(self::class." - Skipping Courtesy Email because lead is from AUH for UUID: {$this->quoteData['quoteUID']}");
        }

        return [
            Skip::when(fn () => $isAUHLead),
        ];
    }
}
