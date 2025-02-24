<?php

namespace App\Services\Logger;

use Illuminate\Support\Facades\Log;

class LoggerService
{
    public static function startQuoteLogging(string $uuid, array $extra = [])
    {
        self::endLogging();
        Log::withContext(['uuid' => $uuid, ...$extra]);
    }

    public static function endLogging()
    {
        Log::withoutContext();
    }
}
