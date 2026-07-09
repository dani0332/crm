<?php

declare(strict_types=1);

namespace App\Jobs\Revival;

use App\Enums\WorkflowTypeEnum;
use App\Services\CarRevivalService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class CarRevivalResendJob implements ShouldQueue
{
    use Batchable, Queueable;

    public $tries = 3;
    public $timeout = 90;
    public $backoff = 300;

    public function __construct(
        private readonly string $quoteUuid,
        private readonly int $dttRevivalId,
    ) {
        $this->onQueue('renewals');
    }

    public function handle(CarRevivalService $carRevivalService): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        try {
            $emailPayload = $carRevivalService->sendCarRevivalEmail($this->quoteUuid);
        } catch (Throwable $e) {
            LoggerService::warning(self::class.' - Error resending car revival OCB email', [
                'quote_uuid' => $this->quoteUuid,
                'dtt_revival_id' => $this->dttRevivalId,
            ], $e);

            return;
        }

        if ($emailPayload === null) {
            LoggerService::warning(self::class.' - Car revival quote not found for OCB resend', [
                'quote_uuid' => $this->quoteUuid,
                'dtt_revival_id' => $this->dttRevivalId,
            ]);

            return;
        }

        $emailPayload->workflowType = WorkflowTypeEnum::MOTOR_REVIVAL_FOLLOWUP;
        CarRevivalFollowUpEmailJob::dispatch($this->dttRevivalId, $emailPayload)->delay(now()->addMinutes(10));

        LoggerService::info(self::class.' - Car revival OCB resent and follow-up queued', [
            'quote_uuid' => $this->quoteUuid,
            'dtt_revival_id' => $this->dttRevivalId,
        ]);
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->quoteUuid))->dontRelease()];
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::warning(self::class.' - job failed', [
            'quote_uuid' => $this->quoteUuid,
            'dtt_revival_id' => $this->dttRevivalId,
        ], $exception);
    }
}
