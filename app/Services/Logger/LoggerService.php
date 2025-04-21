<?php

namespace App\Services\Logger;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

class LoggerService
{
    public static function startQuoteLogging(Model|string $lead, array $extra = [])
    {
        $refID = null;

        if ($lead instanceof string) {
            $refID = $lead;
        } elseif ($lead instanceof Model) {
            $refID = $lead->code;
        }

        self::endLogging();
        Log::withContext(['ref_id' => $refID, ...$extra]);
    }

    public static function endLogging()
    {
        Log::withoutContext();
    }

    private static function addExtra(?string $extra = null)
    {
        if ($extra) {
            Context::addHidden('__extra', $extra);
        }
    }

    public static function error(string $message, array $context = [], ?string $extra = null, ?Exception $exception = null)
    {
        if ($exception) {
            $context['exception'] = [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'code' => $exception->getCode(),
            ];
        }

        self::addExtra($extra);

        Log::error($message, $context);
    }

    public static function warning(string $message, array $context = [], ?string $extra = null)
    {
        self::addExtra($extra);

        Log::warning($message, $context);
    }

    public static function info(string $message, array $context = [], ?string $extra = null)
    {
        self::addExtra($extra);

        Log::info($message, $context);
    }

    public static function debug(string $message, array $context = [], ?string $extra = null)
    {
        self::addExtra($extra);

        Log::debug($message, $context);
    }

    public static function notice(string $message, array $context = [], ?string $extra = null)
    {
        self::addExtra($extra);

        Log::notice($message, $context);
    }

    public static function alert(string $message, array $context = [], ?string $extra = null)
    {
        self::addExtra($extra);

        Log::alert($message, $context);
    }

    public static function critical(string $message, array $context = [], ?string $extra = null)
    {
        self::addExtra($extra);

        Log::critical($message, $context);
    }

    public static function emergency(string $message, array $context = [], ?string $extra = null)
    {
        self::addExtra($extra);

        Log::emergency($message, $context);
    }
}
