<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Exceptions\HighRiskBirdNotificationFailedException;
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
     * @param  array{refId?: string, scoreProfile?: string, customerEmail?: string|null, customerName?: string|null, riskScoreDoc?: string|null, riskScore?: int}  $payload
     */
    public function __construct(private readonly array $payload) {}

    public function handle(BirdService $birdService): void
    {
        $birdUrl = getAppStorageValueByKey(ApplicationStorageEnums::BIRD_HIGH_RISK_AML_SCORE_NOTIFICATION_WORKFLOW_URL, false, true);
        if ($birdUrl === false || $birdUrl === null || $birdUrl === '') {
            LoggerService::warning('NotifyHighRiskScoreBirdJob: Bird workflow URL not configured — skipping', context: [
                'quote_code' => $this->payload['refId'] ?? null,
                'risk_score' => $this->payload['riskScore'] ?? null,
            ]);

            return;
        }

        $response = $birdService->triggerWebHookRequest($birdUrl, $this->payload);

        if ($response?->status_code !== Response::HTTP_OK) {
            $statusCode = $response?->status_code ?? 0;

            LoggerService::error('NotifyHighRiskScoreBirdJob: Bird request did not return 200', context: [
                'quote_code' => $this->payload['refId'] ?? null,
                'risk_score' => $this->payload['riskScore'] ?? null,
                'status_code' => $statusCode,
                'body' => isset($response->body) ? (string) $response->body : null,
            ]);

            $exceptionCode = is_numeric($statusCode) ? (int) $statusCode : 0;

            throw new HighRiskBirdNotificationFailedException(
                'Bird high-risk AML workflow request did not return 200',
                $exceptionCode
            );
        }

        LoggerService::info('NotifyHighRiskScoreBirdJob: Bird workflow triggered', context: [
            'quote_code' => $this->payload['refId'] ?? null,
            'risk_score' => $this->payload['riskScore'] ?? null,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        LoggerService::error(
            'NotifyHighRiskScoreBirdJob: job failed after retries',
            exception: $exception,
            context: [
                'quote_code' => $this->payload['refId'] ?? null,
                'risk_score' => $this->payload['riskScore'] ?? null,
            ]
        );
    }
}
