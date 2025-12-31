<?php

namespace App\Jobs;

use App\Enums\QuoteTypes;
use App\Models\HealthQuote;
use App\Services\CapiRequestService;
use App\Services\Logger\LoggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReEvaluatePecJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 15;
    public $backoff = 30;

    public function __construct(protected string $quoteUID) {}

    public function handle(): void
    {
        LoggerService::startQuoteLogging(QuoteTypes::HEALTH->refId($this->quoteUID));

        LoggerService::info('ReEvaluatePecJob - Starting job');

        CapiRequestService::sendCAPIRequest('/api/v1-evaluate-pec-marks', [
            'quoteUID' => $this->quoteUID,
        ], HealthQuote::class);

        LoggerService::info('ReEvaluatePecJob - Job completed');

        LoggerService::endLogging();
    }
}
