<?php

namespace App\Services;

use App\Enums\EnvEnum;
use App\Services\Logger\LoggerService;
use InvalidArgumentException;
use Throwable;

class ServerEnvironmentGuard
{
    /**
     * Determine if the current environment is allowed.
     *
     * @param  array<string>  $allowedEnvironments
     */
    public static function isAllowed(array $allowedEnvironments = []): bool
    {
        return self::handleValidation(function () use ($allowedEnvironments): bool {
            $defaultEnvironments = [
                EnvEnum::PRODUCTION,
                EnvEnum::STAGING,
                EnvEnum::LOCAL,
            ];
            $baseEnvironments = array_map('strtolower', $defaultEnvironments);

            $normalizedRequested = array_map('strtolower', array_filter($allowedEnvironments));
            self::validateEnvironments($normalizedRequested);
            $allowed = array_unique(array_merge($baseEnvironments, $normalizedRequested));

            $current = strtolower(app()->environment());
            $isAllowed = in_array($current, $allowed, true);

            if ($isAllowed) {
                LoggerService::info('Server environment guard allowed execution', [
                    'current_environment' => $current,
                    'allowed_environments' => $allowed,
                ]);
            } else {
                LoggerService::error('Server environment guard blocked execution', [
                    'current_environment' => $current,
                    'allowed_environments' => $allowed,
                ]);
            }

            return $isAllowed;
        });
    }

    /**
     * Ensure every requested environment exists on EnvEnum.
     *
     * @param  array<string>  $environments
     */
    private static function validateEnvironments(array $environments): void
    {
        if (empty($environments)) {
            return;
        }

        $available = array_map('strtolower', EnvEnum::getValues());
        $invalid = array_diff($environments, $available);

        if (! empty($invalid)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid environment value(s): %s. Allowed values are: %s',
                implode(', ', $invalid),
                implode(', ', $available)
            ));
        }
    }

    /**
     * Wraps execute to log failures.
     */
    private static function handleValidation(callable $callback): bool
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            LoggerService::error('Server environment guard failed validation', [
                'environment' => app()->environment(),
            ], $e);

            throw $e;
        }
    }
}
