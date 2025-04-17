<?php

namespace App\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use GuzzleHttp\Client;
use Monolog\LogRecord;
use App\Logging\AxiomFormatter;

class AxiomBatchHandler extends AbstractProcessingHandler
{
    protected $apiToken;
    protected $dataset;
    protected $batch = [];

    public function __construct($apiToken, $dataset, $level = Logger::DEBUG, bool $bubble = true)
    {
        $this->apiToken = $apiToken;
        $this->dataset = $dataset;
        parent::__construct($level, $bubble);

        $this->setFormatter(new AxiomFormatter());

        register_shutdown_function([$this, 'sendBatch']);
    }

    protected function write(LogRecord $record): void
    {
        $this->batch[] = $record->formatted;

        // Send batch if it reaches a larger size
        if (count($this->batch) >= 100) { // Example larger batch size
            $this->sendBatch();
        }
    }

    protected function sendBatch()
    {
        if (empty($this->batch)) {
            return;
        }

        $client = new Client();
        $response = $client->post('https://api.axiom.co/v1/datasets/' . $this->dataset . '/ingest', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ],
            'json' => $this->batch,
        ]);

        if ($response->getStatusCode() !== 200) {
            // Handle error
            throw new \Exception('Failed to send logs to Axiom');
        }

        // Clear the batch after successful sending
        $this->batch = [];
    }
} 