<?php

namespace App\Logging;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class AxiomHandler extends AbstractProcessingHandler
{
    private string $apiToken;
    private string $dataset;

    private const API_BASE_URL = 'https://api.axiom.co/v1/datasets/';

    public function __construct($level = Level::Debug, bool $bubble = true, ?string $apiToken = null, ?string $dataset = null)
    {
        parent::__construct($level, $bubble);

        $this->apiToken = $apiToken ?? config('logging.channels.axiom.with.apiToken');
        $this->dataset = $dataset ?? config('logging.channels.axiom.with.dataset');
    }

    protected function write(LogRecord $record): void
    {
        try {
            $data = [
                'message' => $record->message,
                'context' => $record->context,
                'level' => $record->level->getName(),
                'extra' => $record->extra,
                'timestamp' => $record->datetime->format('c'),
                'environment' => app()->environment(),
                'user_id' => Auth::id(),
                'service' => 'IMCRM',
            ];

            $response = Http::withToken($this->apiToken)
                ->timeout(5)
                ->retry(3, 100)
                ->acceptJson()
                ->post($this->getApiEndpoint(), [$data]);

            if (! $response->successful()) {
                $this->handleFailedRequest($response->status(), $response->body());
            }
        } catch (ConnectionException $e) {
            $this->handleConnectionException($e);
        } catch (\Exception $e) {
            $this->handleGenericException($e);
        }
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new JsonFormatter;
    }

    private function getApiEndpoint(): string
    {
        return self::API_BASE_URL.$this->dataset.'/ingest';
    }

    private function handleFailedRequest(int $statusCode, string $responseBody): void
    {
        error_log(sprintf(
            'Axiom logging request failed with status %d: %s',
            $statusCode,
            $responseBody
        ));
    }

    private function handleConnectionException(ConnectionException $e): void
    {
        error_log('Axiom logging connection failed: '.$e->getMessage());
    }

    private function handleGenericException(\Exception $e): void
    {
        error_log('Axiom logging exception: '.$e->getMessage());
    }
}
