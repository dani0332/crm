<?php

namespace App\Jobs\OCB;

use App\Enums\QuoteTypes;
use App\Services\EmailServices\CarEmailService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SendAIAdvisorOCBJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = 60;

    public function __construct(private QuoteTypes $quoteType, private string $quoteUuid, private bool $isReAssignment = false) {}

    /**
     * Execute the job.
     */
    public function handle(CarEmailService $carEmailService): void
    {
        LoggerService::startQuoteLogging($this->quoteType->refId($this->quoteUuid));

        $lead = $this->quoteType->model()->where('uuid', $this->quoteUuid)->first();

        if (! $lead) {
            LoggerService::info(self::class.' - Lead Not Found');

            return;
        }

        if ($this->quoteType === QuoteTypes::CAR) {
            $carEmailService->sendCarAIAdvisorOCB($lead, $this->isReAssignment);
        }
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->quoteUuid))->dontRelease()];
    }
}
