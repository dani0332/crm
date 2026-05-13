<?php

namespace App\Services\Logger;

use App\Enums\Logger\LoggerFeatureEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoggerService
{
    public static function startFeatureLogging(LoggerFeatureEnum $feature, $code = null)
    {
        Log::withContext(['feature' => $feature->value]);

        // Add feature to context for activity log
        Context::add('feature', $feature->value);

        if ($code) {
            Context::add('code', $code);
        }
    }

    public static function startQuoteLogging(Model|string|null $lead, ?LoggerFeatureEnum $feature = null, $code = null)
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

        if ($feature) {
            self::startFeatureLogging($feature, $code);
        }
    }

    public static function endLogging()
    {
        Log::withoutContext();

        // Clear Context facade values to prevent leaking into subsequent activity logs
        Context::forget('feature');
        Context::forget('code');
    }

    private static function addExtra(array|string $extra = [])
    {
        if (! empty($extra)) {
            $extraData = is_array($extra) ? json_encode($extra) : $extra;

            if (config('app.env') === 'local') {
                Context::add('__extra', $extraData);
            } else {
                Context::addHidden('__extra', $extraData);
            }
        }
    }

    private static function getExceptionData(Throwable $exception): array
    {
        return [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'code' => $exception->getCode(),
        ];
    }

    public static function error(string $message, array|string $extra = [], ?Throwable $exception = null, array $context = [])
    {

        if ($exception) {
            $context['exception'] = self::getExceptionData($exception);
        }

        self::addExtra($extra);

        Log::error($message, $context);
    }

    public static function warning(string $message, array|string $extra = [], ?Throwable $exception = null, array $context = [])
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

    public static function debug(string $message, array|string $extra = [], ?Throwable $exception = null, array $context = [])
    {
        if ($exception) {
            $context['exception'] = self::getExceptionData($exception);
        }

        self::addExtra($extra);

        Log::debug($message, $context);
    }

    public static function notice(string $message, array|string $extra = [], ?Throwable $exception = null, array $context = [])
    {
        if ($exception) {
            $context['exception'] = self::getExceptionData($exception);
        }

        self::addExtra($extra);

        Log::notice($message, $context);
    }

    public static function alert(string $message, array|string $extra = [], ?Throwable $exception = null, array $context = [])
    {
        if ($exception) {
            $context['exception'] = self::getExceptionData($exception);
        }

        self::addExtra($extra);

        Log::alert($message, $context);
    }

    public static function critical(string $message, array|string $extra = [], ?Throwable $exception = null, array $context = [])
    {
        if ($exception) {
            $context['exception'] = self::getExceptionData($exception);
        }

        self::addExtra($extra);

        Log::critical($message, $context);
    }

    public static function emergency(string $message, array|string $extra = [], ?Throwable $exception = null, array $context = [])
    {
        if ($exception) {
            $context['exception'] = self::getExceptionData($exception);
        }

        self::addExtra($extra);

        Log::emergency($message, $context);
    }

    public static function sql(string $title, $queryInstance)
    {
        $sql = $queryInstance->toRawSql();

        self::debug("{$title} Query", ['sql' => $sql]);
    }
}
