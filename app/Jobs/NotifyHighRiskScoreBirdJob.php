<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\WorkflowTypeEnum;
use App\Services\EmailServices\WebEngageService;
use App\Services\Logger\LoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Sends a Bird workflow notification when AML risk score reaches the "High Risk" band.
 * Risk bands match {@see resources/views/pdf/risk_score_document.blade.php} (>= 35 = High Risk).
 */
class NotifyHighRiskScoreBirdJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    /**
     * @param  array{refId?: string, scoreProfile?: string, customerEmail?: string|null, customerName?: string|null, riskScoreDoc?: string|null, riskScore?: int}  $payload
     */
    public function __construct(private readonly array $payload) {}

    public function handle(WebEngageService $webEngageService): void
    {
        $response = $webEngageService->sendEvent(WorkflowTypeEnum::HIGH_RISK_NOTIFICATION, (array) $this->payload);

        if ($response?->status_code !== Response::HTTP_CREATED) {
            LoggerService::warning('NotifyHighRiskScoreBirdJob: WebEngage workflow failed', [
                'quote_code' => $this->payload['refId'] ?? null,
                'risk_score' => $this->payload['riskScore'] ?? null,
            ]);
        }

        LoggerService::info('NotifyHighRiskScoreBirdJob: WebEngage workflow triggered', [
            'quote_code' => $this->payload['refId'] ?? null,
            'risk_score' => $this->payload['riskScore'] ?? null,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::warning(
            'NotifyHighRiskScoreBirdJob: job failed after retries',
            exception: $exception,
            context: [
                'quote_code' => $this->payload['refId'] ?? null,
                'risk_score' => $this->payload['riskScore'] ?? null,
            ]
        );
    }
}
