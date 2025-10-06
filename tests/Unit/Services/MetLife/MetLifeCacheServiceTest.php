<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MetLife;

use App\Services\MetLife\MetLifeCacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MetLifeCacheServiceTest extends TestCase
{
    private MetLifeCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MetLifeCacheService();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_load_session_returns_cached_data()
    {
        Cache::put('metlife_session_id', 'test_session_123', 3600);
        Cache::put('metlife_csrf_token', 'test_csrf_token', 3600);
        Cache::put('metlife_session_created_at', 1234567890, 3600);
        Cache::put('metlife_csrf_token_created_at', 1234567890, 3600);

        $result = $this->service->loadSession();

        $this->assertEquals('test_session_123', $result['session_id']);
        $this->assertEquals('test_csrf_token', $result['csrf_token']);
        $this->assertEquals(1234567890, $result['session_created_at']);
        $this->assertEquals(1234567890, $result['csrf_token_created_at']);
    }

    public function test_cache_session_stores_data()
    {
        $sessionId = 'test_session_123';
        $sessionCreatedAt = 1234567890;
        $timeout = 3600;

        $this->service->cacheSession($sessionId, $sessionCreatedAt, $timeout);

        $this->assertEquals($sessionId, Cache::get('metlife_session_id'));
        $this->assertEquals($sessionCreatedAt, Cache::get('metlife_session_created_at'));
    }

    public function test_cache_tokens_stores_data()
    {
        $csrfToken = 'test_csrf_token';
        $csrfTokenCreatedAt = 1234567890;
        $timeout = 3600;

        $this->service->cacheTokens($csrfToken, $csrfTokenCreatedAt, $timeout);

        $this->assertEquals($csrfToken, Cache::get('metlife_csrf_token'));
        $this->assertEquals($csrfTokenCreatedAt, Cache::get('metlife_csrf_token_created_at'));
    }

    public function test_load_cached_session_data_with_type_casting()
    {
        Cache::put('metlife_session_id', 'test_session_123', 3600);
        Cache::put('metlife_csrf_token', 'test_csrf_token', 3600);
        Cache::put('metlife_session_created_at', '1234567890', 3600);
        Cache::put('metlife_csrf_token_created_at', '1234567890', 3600);

        $result = $this->service->loadCachedSessionData();

        $this->assertEquals('test_session_123', $result['session_id']);
        $this->assertEquals('test_csrf_token', $result['csrf_token']);
        $this->assertIsInt($result['session_created_at']);
        $this->assertIsInt($result['csrf_token_created_at']);
        $this->assertEquals(1234567890, $result['session_created_at']);
        $this->assertEquals(1234567890, $result['csrf_token_created_at']);
    }

    public function test_clear_cache_removes_all_data()
    {
        Cache::put('metlife_session_id', 'test_session', 3600);
        Cache::put('metlife_csrf_token', 'test_token', 3600);
        Cache::put('metlife_session_created_at', 1234567890, 3600);
        Cache::put('metlife_csrf_token_created_at', 1234567890, 3600);

        $this->assertNotNull(Cache::get('metlife_session_id'));
        $this->assertNotNull(Cache::get('metlife_csrf_token'));

        $this->service->clearCache();

        $this->assertNull(Cache::get('metlife_session_id'));
        $this->assertNull(Cache::get('metlife_csrf_token'));
        $this->assertNull(Cache::get('metlife_session_created_at'));
        $this->assertNull(Cache::get('metlife_csrf_token_created_at'));
    }
}