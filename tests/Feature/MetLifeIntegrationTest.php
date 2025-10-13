<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ApplicationStorageEnums;
use App\Services\MetLife\MetLifeApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetLifeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private MetLifeApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MetLifeApiService;
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_complete_metlife_workflow_success()
    {
        $this->enableMetLifeIntegration();

        Http::fake([
            'https://api.metlife.com/api/v3/init/' => Http::response([
                'csrftoken' => 'test_csrf_token_123',
                'session_id' => 'test_session_123',
                'session_expiry' => 7200,
                'logintype' => '',
                'roles' => [],
                'org' => [],
                'country' => null,
                'date_format' => '%d/%m/%Y',
                'theme_overrides' => [],
            ], 200),
            'https://api.metlife.com/api/v3/login/' => Http::response([
                'success' => true,
                'session_id' => 'test_session_123',
                'roles' => ['admin'],
                'org' => ['id' => 1, 'name' => 'Test Org'],
            ], 200),
        ]);

        $result = $this->service->checkConnectionStatus();

        $this->assertTrue($result['status']);
        $this->assertEquals('MetLife API connection successful', $result['message']);
        $this->assertArrayHasKey('session_id', $result);
        $this->assertArrayHasKey('csrf_token', $result);
    }

    public function test_metlife_workflow_with_cached_session()
    {
        $this->enableMetLifeIntegration();

        Cache::put('metlife_session_id', 'cached_session_123', 3600);
        Cache::put('metlife_csrf_token', 'cached_csrf_token', 3600);
        Cache::put('metlife_session_created_at', time() - 1800, 3600);
        Cache::put('metlife_csrf_token_created_at', time() - 900, 3600);

        Http::fake([
            'https://api.metlife.com/api/v3/test/' => Http::response([
                'success' => true,
                'data' => ['test' => 'cached_session_used'],
            ], 200),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '/test/', 'GET', []);

        $this->assertTrue($result['status']);
    }

    public function test_metlife_workflow_with_expired_session()
    {
        $this->enableMetLifeIntegration();

        Cache::put('metlife_session_id', 'expired_session', 3600);
        Cache::put('metlife_csrf_token', 'expired_csrf_token', 3600);
        Cache::put('metlife_session_created_at', time() - 8000, 3600);
        Cache::put('metlife_csrf_token_created_at', time() - 4000, 3600);

        Http::fake([
            'https://api.metlife.com/api/v3/init/' => Http::response([
                'csrftoken' => 'new_csrf_token',
                'session_id' => 'new_session_id',
                'session_expiry' => 7200,
            ], 200),
            'https://api.metlife.com/api/v3/login/' => Http::response([
                'success' => true,
                'session_id' => 'new_session_id',
                'roles' => ['user'],
            ], 200),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '/test/', 'GET', []);

        $this->assertTrue($result['status']);
    }

    public function test_metlife_workflow_when_disabled()
    {
        $this->disableMetLifeIntegration();

        $result = $this->service->checkConnectionStatus();

        $this->assertFalse($result['status']);
        $this->assertEquals('MetLife integration is disabled', $result['message']);
        $this->assertEquals('INTEGRATION_DISABLED', $result['error']);
    }

    public function test_metlife_workflow_with_api_failure()
    {
        $this->enableMetLifeIntegration();

        Http::fake([
            'https://api.metlife.com/api/v3/init/' => Http::response([
                'error' => 'Service unavailable',
            ], 503),
        ]);

        $result = $this->service->checkConnectionStatus();

        $this->assertFalse($result['status']);
    }

    public function test_metlife_workflow_with_network_timeout()
    {
        $this->enableMetLifeIntegration();

        Http::fake(function () {
            throw new \Exception('Connection timeout');
        });

        $result = $this->service->checkConnectionStatus();

        $this->assertFalse($result['status']);
        $this->assertStringContainsString('Connection timeout', $result['error']);
    }

    public function test_metlife_workflow_with_invalid_credentials()
    {
        $this->enableMetLifeIntegration();

        Http::fake([
            'https://api.metlife.com/api/v3/init/' => Http::response([
                'csrftoken' => 'test_csrf_token',
                'session_id' => 'test_session_id',
                'session_expiry' => 7200,
            ], 200),
            'https://api.metlife.com/api/v3/login/' => Http::response([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401),
        ]);

        $result = $this->service->checkConnectionStatus();

        $this->assertFalse($result['status']);
    }

    public function test_metlife_workflow_with_malformed_response()
    {
        $this->enableMetLifeIntegration();

        Http::fake([
            'https://api.metlife.com/api/v3/init/' => Http::response('invalid json', 200),
        ]);

        $result = $this->service->initialize();

        $this->assertFalse($result['status']);
    }

    public function test_metlife_workflow_with_partial_failure()
    {
        $this->enableMetLifeIntegration();

        Http::fake([
            'https://api.metlife.com/api/v3/init/' => Http::response([
                'csrftoken' => 'test_csrf_token',
                'session_id' => 'test_session_id',
                'session_expiry' => 7200,
            ], 200),
            'https://api.metlife.com/api/v3/login/' => Http::response([
                'success' => false,
                'message' => 'Authentication failed',
            ], 200),
        ]);

        $result = $this->service->checkConnectionStatus();

        $this->assertFalse($result['status']);
    }

    public function test_metlife_workflow_with_cache_clear()
    {
        $this->enableMetLifeIntegration();

        Cache::put('metlife_session_id', 'test_session', 3600);
        Cache::put('metlife_csrf_token', 'test_token', 3600);

        $this->assertNotNull(Cache::get('metlife_session_id'));

        $reflection = new \ReflectionClass($this->service);
        $cacheProperty = $reflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cacheService = $cacheProperty->getValue($this->service);

        $cacheService->clearCache();

        $this->assertNull(Cache::get('metlife_session_id'));
        $this->assertNull(Cache::get('metlife_csrf_token'));
    }

    private function enableMetLifeIntegration(): void
    {
        DB::table('application_storage')->insert([
            'key_name' => ApplicationStorageEnums::ENABLE_METLIFE,
            'value' => '1',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function disableMetLifeIntegration(): void
    {
        DB::table('application_storage')->insert([
            'key_name' => ApplicationStorageEnums::ENABLE_METLIFE,
            'value' => '0',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
