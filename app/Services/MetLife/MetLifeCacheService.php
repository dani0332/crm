<?php

declare(strict_types=1);

namespace App\Services\MetLife;

use Illuminate\Support\Facades\Cache;

class MetLifeCacheService
{
    private const SESSION_CACHE_KEY = 'metlife_session_id';
    private const CSRF_TOKEN_CACHE_KEY = 'metlife_csrf_token';
    private const SESSION_CREATED_AT_CACHE_KEY = 'metlife_session_created_at';
    private const CSRF_TOKEN_CREATED_AT_CACHE_KEY = 'metlife_csrf_token_created_at';

    public function loadSession(): array
    {
        return [
            'session_id' => Cache::get(self::SESSION_CACHE_KEY),
            'csrf_token' => Cache::get(self::CSRF_TOKEN_CACHE_KEY),
            'session_created_at' => Cache::get(self::SESSION_CREATED_AT_CACHE_KEY),
            'csrf_token_created_at' => Cache::get(self::CSRF_TOKEN_CREATED_AT_CACHE_KEY)
        ];
    }

    public function cacheSession(string $sessionId, int $sessionCreatedAt, int $timeout): void
    {
        Cache::put(self::SESSION_CACHE_KEY, $sessionId, $timeout);
        Cache::put(self::SESSION_CREATED_AT_CACHE_KEY, $sessionCreatedAt, $timeout);
    }

    public function cacheTokens(string $csrfToken, int $csrfTokenCreatedAt, int $timeout): void
    {
        Cache::put(self::CSRF_TOKEN_CACHE_KEY, $csrfToken, $timeout);
        Cache::put(self::CSRF_TOKEN_CREATED_AT_CACHE_KEY, $csrfTokenCreatedAt, $timeout);
    }

    public function loadCachedSessionData(): array
    {
        $cachedData = $this->loadSession();
        
        return [
            'session_id' => $cachedData['session_id'],
            'csrf_token' => $cachedData['csrf_token'],
            'session_created_at' => (int) $cachedData['session_created_at'],
            'csrf_token_created_at' => (int) $cachedData['csrf_token_created_at']
        ];
    }

    public function clearCache(): void
    {
        Cache::forget(self::SESSION_CACHE_KEY);
        Cache::forget(self::CSRF_TOKEN_CACHE_KEY);
        Cache::forget(self::SESSION_CREATED_AT_CACHE_KEY);
        Cache::forget(self::CSRF_TOKEN_CREATED_AT_CACHE_KEY);
    }
}
