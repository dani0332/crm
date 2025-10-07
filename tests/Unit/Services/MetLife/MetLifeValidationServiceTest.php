<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MetLife;

use App\Enums\ApplicationStorageEnums;
use App\Enums\InsuranceProviderEnum;
use App\Services\MetLife\MetLifeValidationService;
use Illuminate\Http\Request;
use Tests\TestCase;

class MetLifeValidationServiceTest extends TestCase
{
    private MetLifeValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MetLifeValidationService();
    }

    public function test_is_session_valid_with_valid_session()
    {
        $sessionId = 'valid_session_id';
        $sessionCreatedAt = time() - 3600;
        $sessionTimeout = 7200;

        $result = $this->service->isSessionValid($sessionId, $sessionCreatedAt, $sessionTimeout);

        $this->assertTrue($result);
    }

    public function test_is_session_valid_with_expired_session()
    {
        $sessionId = 'expired_session_id';
        $sessionCreatedAt = time() - 8000;
        $sessionTimeout = 7200;

        $result = $this->service->isSessionValid($sessionId, $sessionCreatedAt, $sessionTimeout);

        $this->assertFalse($result);
    }

    public function test_is_session_valid_with_null_session_id()
    {
        $sessionId = null;
        $sessionCreatedAt = time() - 3600;
        $sessionTimeout = 7200;

        $result = $this->service->isSessionValid($sessionId, $sessionCreatedAt, $sessionTimeout);

        $this->assertFalse($result);
    }

    public function test_is_csrf_token_valid_with_valid_token()
    {
        $csrfToken = 'valid_csrf_token';
        $csrfTokenCreatedAt = time() - 1800;
        $csrfTokenRefreshInterval = 3600;

        $result = $this->service->isCsrfTokenValid($csrfToken, $csrfTokenCreatedAt, $csrfTokenRefreshInterval);

        $this->assertTrue($result);
    }

    public function test_is_csrf_token_valid_with_expired_token()
    {
        $csrfToken = 'expired_csrf_token';
        $csrfTokenCreatedAt = time() - 4000;
        $csrfTokenRefreshInterval = 3600;

        $result = $this->service->isCsrfTokenValid($csrfToken, $csrfTokenCreatedAt, $csrfTokenRefreshInterval);

        $this->assertFalse($result);
    }

    public function test_validate_response_with_valid_response()
    {
        $response = [
            'status' => true,
            'data' => ['test' => 'value']
        ];

        $result = $this->service->validateResponse($response);

        $this->assertTrue($result);
    }

    public function test_validate_response_with_invalid_response()
    {
        $response = [
            'status' => false,
            'data' => ['test' => 'value']
        ];

        $result = $this->service->validateResponse($response);

        $this->assertFalse($result);
    }

    public function test_handle_exception_returns_correct_format()
    {
        $exception = new \Exception('Test error message');
        $action = 'Test action';

        $result = $this->service->handleException($exception, $action);

        $this->assertFalse($result['status']);
        $this->assertEquals('Test action failed', $result['message']);
        $this->assertEquals('Test error message', $result['error']);
    }

    public function test_should_validate_payment_logic_coverage()
    {
        // Test the logic directly without complex mocking
        // This tests the core logic: $providerCode !== InsuranceProviderEnum::MTL->value || !$isMetLifeEnabled
        
        // Test case 1: MetLife provider with integration enabled (should return false)
        $this->app->instance('request', Request::create('/', 'POST', ['providerCode' => InsuranceProviderEnum::MTL->value]));
        
        // Mock the integration as enabled
        $this->app->bind('getAppStorageValueByKey', function ($key) {
            return $key === ApplicationStorageEnums::ENABLE_METLIFE ? 1 : 0;
        });
        
        $result = $this->service->shouldValidatePayment();
        $this->assertFalse($result, 'MetLife with integration enabled should skip payment validation');
        
        // Test case 2: Other provider (should return true)
        $this->app->instance('request', Request::create('/', 'POST', ['providerCode' => 'AXA']));
        
        $result = $this->service->shouldValidatePayment();
        $this->assertTrue($result, 'Other providers should always validate payment');
        
        // Test case 3: Null provider code (should return true)
        $this->app->instance('request', Request::create('/', 'POST', ['providerCode' => null]));
        
        $result = $this->service->shouldValidatePayment();
        $this->assertTrue($result, 'Null provider code should validate payment');
        
        // Test case 4: Empty provider code (should return true)
        $this->app->instance('request', Request::create('/', 'POST', ['providerCode' => '']));
        
        $result = $this->service->shouldValidatePayment();
        $this->assertTrue($result, 'Empty provider code should validate payment');
    }
}