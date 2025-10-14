<?php

namespace App\Jobs;

use App\Enums\ApplicationStorageEnums;
use App\Services\Logger\LoggerService;
use App\Services\MetLife\MetLifeApiService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\Skip;
use Illuminate\Queue\SerializesModels;

class LifeSyncHealthQuestionnaireJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = 300;
    private array $requestData;

    public function __construct(array $requestData)
    {
        $this->requestData = $requestData;
    }

    public function handle(MetLifeApiService $metLifeService): void
    {
        LoggerService::info('Processing Health Questionnaire sync job.', ['quote_uuid' => $this->requestData['quote_uuid']]);

        try {
            $result = $metLifeService->syncHealthQuestionnaire($this->requestData);

            if (is_array($result) && ($result['success'] ?? true) === false) {
                throw new Exception('Health questionnaire sync failed: '.($result['message'] ?? 'Unknown error'));
            }

            LoggerService::info('Health Questionnaire sync completed successfully.', ['quote_uuid' => $this->requestData['quote_uuid']]);
        } catch (Exception $e) {
            LoggerService::error('Health Questionnaire sync job failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'quote_uuid' => $this->requestData['quote_uuid'],
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        LoggerService::error('LifeSyncHealthQuestionnaireJob failed for quote_uuid: '.($this->requestData['quote_uuid'] ?? 'N/A'), [
            'error' => $exception->getMessage(),
        ]);
    }

    public function middleware()
    {
        $isMetLifeEnabled = getAppStorageValueByKey(ApplicationStorageEnums::ENABLE_METLIFE, useCache: true) == '1';

        if (! $isMetLifeEnabled) {
            return [Skip::when(fn () => true)];
        }

        return [];
    }
}
