<?php

declare(strict_types=1);

namespace App\Jobs\EP;

use App\Enums\Logger\LoggerFeatureEnum;
use App\Enums\QuoteTypeId;
use App\Models\CarQuote;
use App\Models\PersonalQuote;
use App\Services\EmbeddedTransactionService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RetargetEpReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 60;
    public $backoff = 120;

    public function __construct(
        protected string $quoteUuid,
        protected int $quoteTypeId
    ) {
        $this->afterCommit();
    }

    public function handle(EmbeddedTransactionService $embeddedTransactionService): void
    {
        LoggerService::startQuoteLogging($this->quoteUuid, LoggerFeatureEnum::EP_RETARGET_REMINDER);

        if (! $embeddedTransactionService->isRetargetingEpReminderEnabled()) {
            LoggerService::info('RetargetEpReminderJob - retargeting EP reminder is disabled', ['uuid' => $this->quoteUuid]);

            return;
        }

        $lead = match ($this->quoteTypeId) {
            QuoteTypeId::Car => CarQuote::where('uuid', $this->quoteUuid)->first(),
            QuoteTypeId::Bike => PersonalQuote::where('uuid', $this->quoteUuid)->first(),
            default => null,
        };
        if (! $lead) {
            LoggerService::info('RetargetEpReminderJob - quote not found', ['uuid' => $this->quoteUuid]);

            return;
        }

        $response = $embeddedTransactionService->retargetEpReminder($lead, $this->quoteTypeId);
        LoggerService::info('RetargetEpReminderJob - retarget ep reminder triggered', ['uuid' => $this->quoteUuid, 'response' => $response]);
    }

    public function middleware(): array
    {
        $lockKey = "retarget-ep-reminder-{$this->quoteUuid}-{$this->quoteTypeId}";

        return [
            (new WithoutOverlapping($lockKey))
                ->dontRelease()
                ->expireAfter($this->timeout),
        ];
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::startQuoteLogging($this->quoteUuid, LoggerFeatureEnum::EP_RETARGET_REMINDER);
        LoggerService::error('RetargetEpReminderJob - job failed after all retries', [
            'uuid' => $this->quoteUuid,
            'quote_type_id' => $this->quoteTypeId,
            'attempts' => $this->attempts(),
            'max_tries' => $this->tries,
        ], exception: $exception);
    }
}
