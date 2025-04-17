<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

class AxiomFormatter extends JsonFormatter implements FormatterInterface
{
    public function format(LogRecord $record): string
    {
        // Parse the Monolog format to extract the message and context
        $message = $record->message;
        $context = $record->context;
        
        // Create a clean event object
        $event = [
            'timestamp' => $record->datetime->format('c'),
            'level' => $record->level->getName(),
            'message' => $message,
            'context' => $context,
            'environment' => env('APP_ENV'),
            'service' => 'IMCRM',
            'channel' => $record->channel
        ];

        // Use the parent's toJson method to ensure proper JSON encoding
        return $this->toJson($event, true);
    }
} 