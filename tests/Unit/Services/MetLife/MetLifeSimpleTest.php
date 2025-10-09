<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MetLife;

use App\Services\MetLife\MetLifeRequestService;
use App\Services\MetLife\MetLifeCacheService;
use App\Services\MetLife\MetLifeValidationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetLifeSimpleTest extends TestCase
{
    public function test_request_service_http_calls()
    {
        Http::fake([
            'https://api.metlife.com/init/' => Http::response([
                'csrftoken' => 'test_csrf_token',
                'session_id' => 'test_session_id',
                'session_expiry' => 7200
            ], 200)
        ]);

        $requestService = new MetLifeRequestService(
            'https://api.metlife.com',
            30
        );

        $result = $requestService->makeRequest('/init/', 'GET');

        $this->assertTrue($result['success']);
        $this->assertEquals('Request successful', $result['message']);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_cache_service_operations()
    {
        $cacheService = new MetLifeCacheService();

        // Test caching
        $sessionId = 'test_session_123';
        $csrfToken = 'test_csrf_token';
        $sessionCreatedAt = time();
        $csrfTokenCreatedAt = time();

        $cacheService->cacheSession($sessionId, $sessionCreatedAt, 3600);
        $cacheService->cacheTokens($csrfToken, $csrfTokenCreatedAt, 3600);

        // Test loading
        $result = $cacheService->loadSession();

        $this->assertEquals($sessionId, $result['session_id']);
        $this->assertEquals($csrfToken, $result['csrf_token']);
        $this->assertEquals($sessionCreatedAt, $result['session_created_at']);
        $this->assertEquals($csrfTokenCreatedAt, $result['csrf_token_created_at']);
    }

    public function test_validation_service_logic()
    {
        $validationService = new MetLifeValidationService();

        // Test session validation
        $sessionId = 'valid_session';
        $sessionCreatedAt = time() - 3600; // 1 hour ago
        $sessionTimeout = 7200; // 2 hours

        $isValid = $validationService->isSessionValid($sessionId, $sessionCreatedAt, $sessionTimeout);
        $this->assertTrue($isValid);

        // Test expired session
        $expiredSessionCreatedAt = time() - 8000; // More than 2 hours ago
        $isExpired = $validationService->isSessionValid($sessionId, $expiredSessionCreatedAt, $sessionTimeout);
        $this->assertFalse($isExpired);

        // Test CSRF token validation
        $csrfToken = 'valid_token';
        $csrfTokenCreatedAt = time() - 1800; // 30 minutes ago
        $csrfTokenRefreshInterval = 3600; // 1 hour

        $isTokenValid = $validationService->isCsrfTokenValid($csrfToken, $csrfTokenCreatedAt, $csrfTokenRefreshInterval);
        $this->assertTrue($isTokenValid);
    }

    public function test_http_headers_building()
    {
        $requestService = new MetLifeRequestService(
            'https://api.metlife.com',
            30
        );

        $sessionId = 'test_session_123';
        $csrfToken = 'test_csrf_token';

        $headers = $requestService->buildHeaders($sessionId, $csrfToken);

        $this->assertEquals($sessionId, $headers['x-session-id']);
        $this->assertEquals($csrfToken, $headers['X-CSRFToken']);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertEquals('application/json', $headers['Accept']);
    }

    public function test_cache_clear_functionality()
    {
        $cacheService = new MetLifeCacheService();

        // Set some cache data
        Cache::put('metlife_session_id', 'test_session', 3600);
        Cache::put('metlife_csrf_token', 'test_token', 3600);

        $this->assertNotNull(Cache::get('metlife_session_id'));
        $this->assertNotNull(Cache::get('metlife_csrf_token'));

        // Clear cache
        $cacheService->clearCache();

        $this->assertNull(Cache::get('metlife_session_id'));
        $this->assertNull(Cache::get('metlife_csrf_token'));
    }

    public function test_response_validation()
    {
        $validationService = new MetLifeValidationService();

        // Valid response
        $validResponse = [
            'status' => true,
            'data' => ['test' => 'value']
        ];
        $this->assertTrue($validationService->validateResponse($validResponse));

        // Invalid response
        $invalidResponse = [
            'status' => false,
            'data' => ['test' => 'value']
        ];
        $this->assertFalse($validationService->validateResponse($invalidResponse));

        // Missing status
        $missingStatusResponse = [
            'data' => ['test' => 'value']
        ];
        $this->assertFalse($validationService->validateResponse($missingStatusResponse));
    }

    public function test_exception_handling()
    {
        $validationService = new MetLifeValidationService();

        $exception = new \Exception('Test error message');
        $action = 'Test action';

        $result = $validationService->handleException($exception, $action);

        $this->assertFalse($result['status']);
        $this->assertEquals('Test action failed', $result['message']);
        $this->assertEquals('Test error message', $result['error']);
    }
}
