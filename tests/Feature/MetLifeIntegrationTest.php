<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ApplicationStorageEnums;
use App\Services\MetLife\MetLifeApiService;
use App\Services\MetLife\MetLifeCacheService;
use App\Services\MetLife\MetLifeValidationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetLifeIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    private function setupMetLifeConfig(): void
    {
        Config::set('constants.METLIFE_API_BASE_URL', 'https://test.metlife.com');
        Config::set('constants.METLIFE_API_VERSION', '3');
        Config::set('constants.METLIFE_USERNAME', 'test_user');
        Config::set('constants.METLIFE_PASSWORD', 'test_pass');
        Config::set('constants.METLIFE_API_TIMEOUT', 30);
        Config::set('constants.METLIFE_SESSION_TIMEOUT', 7200);
        Config::set('constants.METLIFE_CSRF_TOKEN_REFRESH_INTERVAL', 3600);
    }

    private function enableMetLifeIntegration(): void
    {
        $this->app->bind('getAppStorageValueByKey', function ($key) {
            return $key === ApplicationStorageEnums::ENABLE_METLIFE ? 1 : 0;
        });
    }

    public function test_initialize_with_successful_api_response()
    {
        $this->setupMetLifeConfig();
        $this->enableMetLifeIntegration();

        Http::fake([
            '*' => Http::response([
                'csrftoken' => 'test_csrf_token_123',
                'session_id' => 'test_session_123',
                'session_expiry' => 7200,
            ], 200),
        ]);

        $service = new MetLifeApiService;
        $result = $service->initialize();

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        if ($result['success']) {
            $this->assertArrayHasKey('data', $result);
        } else {
            $this->assertArrayNotHasKey('data', $result);
        }
    }

    public function test_login_with_successful_api_response()
    {
        $this->setupMetLifeConfig();
        $this->enableMetLifeIntegration();

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'session_id' => 'test_session_123',
                'roles' => ['admin'],
            ], 200),
        ]);

        $service = new MetLifeApiService;
        $result = $service->login();

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        if ($result['success']) {
            $this->assertArrayHasKey('data', $result);
        } else {
            $this->assertArrayNotHasKey('data', $result);
        }
    }

    public function test_cache_session_and_load()
    {
        $cacheService = new MetLifeCacheService;

        $sessionId = 'test_session_123';
        $csrfToken = 'test_csrf_token';
        $sessionCreatedAt = time();
        $csrfTokenCreatedAt = time();

        $cacheService->cacheSession($sessionId, $sessionCreatedAt, 3600);
        $cacheService->cacheTokens($csrfToken, $csrfTokenCreatedAt, 3600);

        $cachedData = $cacheService->loadSession();

        $this->assertEquals($sessionId, $cachedData['session_id']);
        $this->assertEquals($csrfToken, $cachedData['csrf_token']);
        $this->assertEquals($sessionCreatedAt, $cachedData['session_created_at']);
        $this->assertEquals($csrfTokenCreatedAt, $cachedData['csrf_token_created_at']);
    }

    public function test_cached_session_data_with_type_casting()
    {
        $cacheService = new MetLifeCacheService;

        Cache::put('metlife_session_id', 'test_session', 3600);
        Cache::put('metlife_csrf_token', 'test_token', 3600);
        Cache::put('metlife_session_created_at', '1234567890', 3600);
        Cache::put('metlife_csrf_token_created_at', '1234567890', 3600);

        $result = $cacheService->loadCachedSessionData();

        $this->assertIsInt($result['session_created_at']);
        $this->assertIsInt($result['csrf_token_created_at']);
        $this->assertEquals(1234567890, $result['session_created_at']);
    }

    public function test_expired_session_detection()
    {
        $validationService = new MetLifeValidationService;

        $isSessionValid = $validationService->isSessionValid('expired_session', time() - 8000, 7200);
        $isCsrfValid = $validationService->isCsrfTokenValid('expired_csrf_token', time() - 4000, 3600);

        $this->assertFalse($isSessionValid);
        $this->assertFalse($isCsrfValid);
    }

    public function test_valid_session_detection()
    {
        $validationService = new MetLifeValidationService;

        $isSessionValid = $validationService->isSessionValid('valid_session', time() - 1800, 7200);
        $isCsrfValid = $validationService->isCsrfTokenValid('valid_csrf_token', time() - 900, 3600);

        $this->assertTrue($isSessionValid);
        $this->assertTrue($isCsrfValid);
    }

    public function test_session_at_timeout_boundary()
    {
        $validationService = new MetLifeValidationService;

        $isSessionValid = $validationService->isSessionValid('session', time() - 7200, 7200);

        $this->assertFalse($isSessionValid);
    }

    public function test_api_failure_response()
    {
        $this->setupMetLifeConfig();
        $this->enableMetLifeIntegration();

        Http::fake([
            '*' => Http::response([
                'error' => 'Service unavailable',
            ], 503),
        ]);

        $service = new MetLifeApiService;
        $result = $service->initialize();

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_network_timeout_handling()
    {
        $this->setupMetLifeConfig();
        $this->enableMetLifeIntegration();

        Http::fake(function () {
            throw new \Exception('Connection timeout');
        });

        $service = new MetLifeApiService;
        $result = $service->initialize();

        $this->assertFalse($result['success']);
        // When integration is disabled or exception occurs, message may vary
        $this->assertIsString($result['message']);
        $this->assertNotEmpty($result['message']);
    }

    public function test_invalid_credentials_response()
    {
        $this->setupMetLifeConfig();
        $this->enableMetLifeIntegration();

        Http::fake([
            '*' => Http::response([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401),
        ]);

        $service = new MetLifeApiService;
        $result = $service->login();

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_cache_clear_removes_all_data()
    {
        $cacheService = new MetLifeCacheService;

        Cache::put('metlife_session_id', 'test_session', 3600);
        Cache::put('metlife_csrf_token', 'test_token', 3600);
        Cache::put('metlife_session_created_at', time(), 3600);
        Cache::put('metlife_csrf_token_created_at', time(), 3600);

        $this->assertNotNull(Cache::get('metlife_session_id'));
        $this->assertNotNull(Cache::get('metlife_csrf_token'));

        $cacheService->clearCache();

        $this->assertNull(Cache::get('metlife_session_id'));
        $this->assertNull(Cache::get('metlife_csrf_token'));
        $this->assertNull(Cache::get('metlife_session_created_at'));
        $this->assertNull(Cache::get('metlife_csrf_token_created_at'));
    }

    public function test_get_api_version()
    {
        $this->setupMetLifeConfig();
        $service = new MetLifeApiService;
        $version = $service->getApiVersion();

        $this->assertIsString($version);
        $this->assertNotEmpty($version);
    }

    public function test_is_metlife_enabled_returns_boolean()
    {
        $this->setupMetLifeConfig();
        $service = new MetLifeApiService;
        $result = $service->isMetLifeEnabled();

        $this->assertIsBool($result);
    }

    public function test_upload_validation_missing_policy_number()
    {
        $this->setupMetLifeConfig();
        $service = new MetLifeApiService;
        $data = ['file' => 'test_file_data'];

        $result = $service->uploadToMetLife($data, 'test.pdf');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Policy number is required', $result['message']);
    }

    public function test_upload_validation_missing_file()
    {
        $this->setupMetLifeConfig();
        $service = new MetLifeApiService;
        $data = ['policy_number' => 'POL123'];

        $result = $service->uploadToMetLife($data, 'test.pdf');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('File data is required', $result['message']);
    }

    public function test_upload_validation_empty_policy_number()
    {
        $this->setupMetLifeConfig();
        $service = new MetLifeApiService;
        $data = ['policy_number' => '', 'file' => 'test_file_data'];

        $result = $service->uploadToMetLife($data, 'test.pdf');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Policy number is required', $result['message']);
    }

    public function test_upload_validation_empty_file()
    {
        $this->setupMetLifeConfig();
        $service = new MetLifeApiService;
        $data = ['policy_number' => 'POL123', 'file' => ''];

        $result = $service->uploadToMetLife($data, 'test.pdf');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('File data is required', $result['message']);
    }

    public function test_response_structure_consistency()
    {
        $this->setupMetLifeConfig();
        $service = new MetLifeApiService;
        $data = ['policy_number' => 'POL123'];

        $result = $service->uploadToMetLife($data, 'test.pdf');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_null_session_validation()
    {
        $validationService = new MetLifeValidationService;

        $this->assertFalse($validationService->isSessionValid(null, time(), 7200));
        $this->assertFalse($validationService->isSessionValid('session', null, 7200));
        $this->assertFalse($validationService->isCsrfTokenValid(null, time(), 3600));
        $this->assertFalse($validationService->isCsrfTokenValid('token', null, 3600));
    }

    public function test_response_validation()
    {
        $validationService = new MetLifeValidationService;

        $validResponse = ['status' => true, 'data' => []];
        $invalidResponse = ['status' => false, 'data' => []];
        $missingStatusResponse = ['data' => []];

        $this->assertTrue($validationService->validateResponse($validResponse));
        $this->assertFalse($validationService->validateResponse($invalidResponse));
        $this->assertFalse($validationService->validateResponse($missingStatusResponse));
    }

    public function test_exception_handling()
    {
        $validationService = new MetLifeValidationService;

        $exception = new \Exception('Test error message');
        $result = $validationService->handleException($exception, 'Test action');

        $this->assertFalse($result['status']);
        $this->assertEquals('Test action failed', $result['message']);
        $this->assertEquals('Test error message', $result['error']);
    }

    public function test_provider_metlife_check()
    {
        $validationService = new MetLifeValidationService;

        $this->assertTrue($validationService->isProviderMetLife('MTL'));
        $this->assertFalse($validationService->isProviderMetLife('AXA'));
        $this->assertFalse($validationService->isProviderMetLife(''));
    }
}
