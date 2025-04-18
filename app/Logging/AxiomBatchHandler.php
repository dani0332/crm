<?php

namespace App\Logging;

use App\Models\ApplicationStorage;
use GuzzleHttp\Client;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\LogRecord;

class AxiomBatchHandler extends AbstractProcessingHandler
{
    protected $apiToken;
    protected $dataset;
    protected $batch = [];
    protected $batchSize;
    protected $singleHandler;
    protected $dailyHandler;

    public function __construct($level = Logger::DEBUG, bool $bubble = true)
    {
        $this->apiToken = env('AXIOM_API_TOKEN');
        $this->dataset = env('AXIOM_DATASET');
        $this->batchSize = cache()->remember('axiom_batch_size', 3600, function () {
            $storage = ApplicationStorage::where('key_name', 'AXIOM_BATCH_SIZE')->first();

            return $storage ? (int) $storage->value : 100;
        });

        if (empty($this->apiToken) || empty($this->dataset)) {
            throw new \InvalidArgumentException('AXIOM_API_TOKEN and AXIOM_DATASET environment variables are required');
        }

        parent::__construct($level, $bubble);
        $this->setFormatter(new AxiomFormatter);

        // Initialize handlers for Laravel's default logging
        $this->singleHandler = new StreamHandler(
            storage_path('logs/laravel.log'),
            $level,
            $bubble
        );

        $this->dailyHandler = new RotatingFileHandler(
            storage_path('logs/laravel.log'),
            14, // Keep logs for 14 days
            $level,
            $bubble
        );

        // Use Laravel's default log format for file handlers
        $fileFormatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );

        $this->singleHandler->setFormatter($fileFormatter);
        $this->dailyHandler->setFormatter($fileFormatter);

        // Ensure batch is sent on shutdown
        register_shutdown_function([$this, 'sendBatch']);
    }

    protected function write(LogRecord $record): void
    {
        try {
            // Create a new record for file logging to ensure clean format
            $fileRecord = new LogRecord(
                $record->datetime,
                $record->channel,
                $record->level,
                $record->message,
                $record->context,
                $record->extra
            );

            // Write to Laravel's default log files with original format
            $this->singleHandler->handle($fileRecord);
            $this->dailyHandler->handle($fileRecord);

            // Add to Axiom batch with original Axiom format
            $this->batch[] = $this->formatRecord($record);

            if (count($this->batch) >= $this->batchSize) {
                $this->sendBatch();
            }
        } catch (\Exception $e) {
            error_log('Error writing to Axiom batch: '.$e->getMessage());
            // Don't throw to prevent breaking the application
        }
    }

    protected function formatRecord(LogRecord $record): array
    {
        return [
            'message' => $record->message,
            'context' => $record->context,
            'level' => strtoupper($record->level->getName()),
            'extra' => $record->extra,
            'timestamp' => $record->datetime->format('c'),
            'environment' => app()->environment(),
            'service' => 'IMCRM',
        ];
    }

    protected function sendBatch()
    {
        if (empty($this->batch)) {
            return;
        }

        try {
            $client = new Client;
            $response = $client->post('https://api.axiom.co/v1/datasets/'.$this->dataset.'/ingest', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $this->batch,
                'timeout' => 5, // 5 second timeout
            ]);

            if ($response->getStatusCode() === 200) {
                error_log(sprintf(
                    'Successfully sent batch of %d records to Axiom',
                    count($this->batch)
                ));
                $this->batch = [];
            } else {
                error_log(sprintf(
                    'Failed to send logs to Axiom. Status code: %d, Response: %s',
                    $response->getStatusCode(),
                    $response->getBody()->getContents()
                ));
            }
        } catch (\Exception $e) {
            error_log('Error sending batch to Axiom: '.$e->getMessage());
        }
    }
}
