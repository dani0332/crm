<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Services\BirdService;
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
     * @param  array<string, mixed>  $payload
     */
    public function __construct(private readonly array $payload) {}

    public function handle(BirdService $birdService): void
    {
        $birdUrl = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_HIGH_RISK_AML_SCORE_NOTIFICATION_WORKFLOW_URL, false, true);
        if ($birdUrl === false || $birdUrl === null || $birdUrl === '') {
            LoggerService::warning('NotifyHighRiskScoreBirdJob: Bird workflow URL not configured — skipping', context: [
                'quote_uuid' => $this->payload['refId'] ?? null,
            ]);

            return;
        }

        $response = $birdService->triggerWebHookRequest($birdUrl, $this->payload);

        if ($response?->status_code !== Response::HTTP_OK) {
            LoggerService::error('NotifyHighRiskScoreBirdJob: Bird request did not return 200', context: [
                'quote_uuid' => $this->payload['refId'] ?? null,
                'status_code' => $response?->status_code ?? null,
                'body' => isset($response->body) ? (string) $response->body : null,
            ]);
        } else {
            LoggerService::info('NotifyHighRiskScoreBirdJob: Bird workflow triggered', context: [
                'quote_uuid' => $this->payload['refId'] ?? null,
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::error(
            'NotifyHighRiskScoreBirdJob: job failed after retries',
            exception: $exception,
            context: [
                'quote_uuid' => $this->payload['refId'] ?? null,
            ]
        );
    }
}
