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

    public static function error(string $message, array $context = [])
    {
        Log::error($message, $context);
    }

    public static function warning(string $message, array $context = [])
    {
        Log::warning($message, $context);
    }

    public static function info(string $message, array $context = [])
    {
        Log::info($message, $context);
    }

    public static function debug(string $message, array $context = [])
    {
        Log::debug($message, $context);
    }

    public static function notice(string $message, array $context = [])
    {
        Log::notice($message, $context);
    }

    public static function alert(string $message, array $context = [])
    {
        Log::alert($message, $context);
    }

    public static function critical(string $message, array $context = [])
    {
        Log::critical($message, $context);
    }

    public static function emergency(string $message, array $context = [])
    {
        Log::emergency($message, $context);
    }



}
