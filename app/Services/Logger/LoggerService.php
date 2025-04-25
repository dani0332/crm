<?php

namespace App\Services\Logger;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

class LoggerService
{
    public static function startQuoteLogging(Model|string|null $lead = null, array $extra = [])
    {
        if (empty($lead)) {
            self::alert('startQuoteLogging - Lead is empty');

            return;
        }

        $refID = null;

        if ($lead instanceof string || is_string($lead)) {
            $refID = $lead;
        } elseif ($lead instanceof Model) {
            $refID = $lead->code;
        }

        self::endLogging();
        Log::withContext(['ref_id' => $refID]);
    }

    public static function endLogging()
    {
        Log::withoutContext();
    }

    private static function addExtra(array|string $extra = [])
    {
        if (! empty($extra)) {
            $extraData = is_array($extra) ? json_encode($extra) : $extra;

            Context::addHidden('__extra', $extraData);
        }
    }

    private static function getExceptionData(Exception $exception): array
    {
        return [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'code' => $exception->getCode(),
        ];
    }

    public static function error(string $message, array|string $extra = [], ?Exception $exception = null, array $context = [])
    {
        if ($exception) {
            $context['exception'] = self::getExceptionData($exception);
        }

        self::addExtra($extra);

        Log::error($message, $context);
    }

    public static function warning(string $message, array|string $extra = [], ?Exception $exception = null, array $context = [])
    {
        if ($exception) {
            $context['exception'] = self::getExceptionData($exception);
        }

        self::addExtra($extra);

        Log::warning($message, $context);
    }

    public static function info(string $message, array|string $extra = [], array $context = [])
    {
        self::addExtra($extra);

        Log::info($message, $context);
    }

    public static function debug(string $message, array|string $extra = [], ?Exception $exception = null, array $context = [])
    {
        if ($exception) {
            $context['exception'] = self::getExceptionData($exception);
        }

        self::addExtra($extra);

        Log::debug($message, $context);
    }

    public static function notice(string $message, array|string $extra = [], ?Exception $exception = null, array $context = [])
    {
        if ($exception) {
            $context['exception'] = self::getExceptionData($exception);
        }

        self::addExtra($extra);

        Log::notice($message, $context);
    }

    public static function alert(string $message, array|string $extra = [], ?Exception $exception = null, array $context = [])
    {
        if ($exception) {
            $context['exception'] = self::getExceptionData($exception);
        }

        self::addExtra($extra);

        Log::alert($message, $context);
    }

    public static function critical(string $message, array|string $extra = [], ?Exception $exception = null, array $context = [])
    {
        if ($exception) {
            $context['exception'] = self::getExceptionData($exception);
        }

        self::addExtra($extra);

        Log::critical($message, $context);
    }

    public static function emergency(string $message, array|string $extra = [], ?Exception $exception = null, array $context = [])
    {
        if ($exception) {
            $context['exception'] = self::getExceptionData($exception);
        }

        self::addExtra($extra);

        Log::emergency($message, $context);
    }
}
