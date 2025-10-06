<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MetLife;

use App\Services\MetLife\MetLifeValidationService;
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
}