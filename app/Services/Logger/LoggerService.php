<?php

namespace App\Services\Logger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class LoggerService
{
    public static function startQuoteLogging(Model|string $lead, array $extra = [])
    {
        $refID = null;

        if (is_string($lead)) {
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
