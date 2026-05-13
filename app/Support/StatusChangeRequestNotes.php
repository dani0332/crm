<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Builds JSON for quote / send-update status log `notes` from request context.
 *
 * Queue workers and Artisan use a synthetic HTTP request (GET /, Symfony UA).
 * In that case we persist a `background` payload instead of misleading web metadata.
 */
final class StatusChangeRequestNotes
{
    public static function toJson(Request $request): string
    {
        if (self::shouldRecordAsBackgroundContext($request)) {
            return json_encode(self::backgroundPayload(), JSON_UNESCAPED_SLASHES);
        }

        return json_encode([
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ], JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, string>
     */
    private static function backgroundPayload(): array
    {
        $payload = [
            'source' => 'background',
        ];

        $argv = $_SERVER['argv'] ?? [];
        if (is_array($argv) && $argv !== []) {
            $payload['argv'] = implode(' ', array_slice($argv, 1));
        }

        return $payload;
    }

    private static function shouldRecordAsBackgroundContext(Request $request): bool
    {
        $userAgent = (string) $request->userAgent();

        return app()->runningInConsole()
            && $request->method() === 'GET'
            && $request->path() === '/'
            && ($userAgent === '' || str_contains($userAgent, 'Symfony'));
    }
}
