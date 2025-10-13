<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MetLife;

use App\Services\MetLife\MetLifeRequestService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetLifeRequestServiceTest extends TestCase
{
    private MetLifeRequestService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MetLifeRequestService(
            'https://api.metlife.com',
            30
        );
    }

    public function test_make_request_get_success()
    {
        Http::fake([
            'https://api.metlife.com/test' => Http::response([
                'test' => 'value',
            ], 200),
        ]);

        $result = $this->service->makeRequest('/test', 'GET');

        $this->assertTrue($result['success']);
        $this->assertEquals('Request successful', $result['message']);
        $this->assertEquals(200, $result['data']['status_code']);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_make_request_post_success()
    {
        Http::fake([
            'https://api.metlife.com/test' => Http::response([
                'created' => true,
            ], 201),
        ]);

        $data = ['name' => 'test'];
        $result = $this->service->makeRequest('/test', 'POST', $data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Request successful', $result['message']);
        $this->assertEquals(201, $result['data']['status_code']);
    }

    public function test_make_request_failure()
    {
        Http::fake([
            'https://api.metlife.com/test' => Http::response([
                'error' => 'Not found',
            ], 404),
        ]);

        $result = $this->service->makeRequest('/test', 'GET');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Request failed', $result['message']);
        $this->assertEquals(404, $result['data']['status_code']);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_build_headers_with_auth()
    {
        $sessionId = 'test_session_id';
        $csrfToken = 'test_csrf_token';

        $headers = $this->service->buildHeaders($sessionId, $csrfToken);

        $this->assertEquals($sessionId, $headers['x-session-id']);
        $this->assertEquals($csrfToken, $headers['X-CSRFToken']);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertEquals('application/json', $headers['Accept']);
    }

    public function test_build_headers_without_auth()
    {
        $headers = $this->service->buildHeaders();

        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertEquals('application/json', $headers['Accept']);
        $this->assertCount(2, $headers);
        $this->assertArrayNotHasKey('x-session-id', $headers);
        $this->assertArrayNotHasKey('X-CSRFToken', $headers);
    }

    public function test_exception_handling()
    {
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $result = $this->service->makeRequest('/test', 'GET');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('MetLife operation failed', $result['message']);
        $this->assertArrayHasKey('data', $result);
    }
}
