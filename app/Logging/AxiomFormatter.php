<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

class AxiomFormatter extends JsonFormatter implements FormatterInterface
{
    public function format(LogRecord $record): string
    {
        $formatted = [
            'context' => $record->context,
            'environment' => env('APP_ENV'),
            'level' => $record->level->getName(),
            'message' => $record->message,
            'service' => 'IMCRM', // Assuming this is a constant for your use case
            'timestamp' => $record->datetime->format('c'),
        ];

        return json_encode($formatted);
    }
} 