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
            '3',
            30
        );
    }

    public function test_make_request_get_success()
    {
        Http::fake([
            'https://api.metlife.com/api/v3/test' => Http::response([
                'success' => true,
                'data' => ['test' => 'value']
            ], 200)
        ]);

        $result = $this->service->makeRequest('/test', 'GET');

        $this->assertTrue($result['status']);
        $this->assertEquals('Request successful', $result['message']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_make_request_post_success()
    {
        Http::fake([
            'https://api.metlife.com/api/v3/test' => Http::response([
                'success' => true,
                'data' => ['created' => true]
            ], 201)
        ]);

        $data = ['name' => 'test'];
        $result = $this->service->makeRequest('/test', 'POST', $data);

        $this->assertTrue($result['status']);
        $this->assertEquals('Request successful', $result['message']);
        $this->assertEquals(201, $result['status_code']);
    }

    public function test_make_request_failure()
    {
        Http::fake([
            'https://api.metlife.com/api/v3/test' => Http::response([
                'error' => 'Not found'
            ], 404)
        ]);

        $result = $this->service->makeRequest('/test', 'GET');

        $this->assertFalse($result['status']);
        $this->assertEquals('Request failed', $result['message']);
        $this->assertEquals(404, $result['status_code']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_build_auth_headers()
    {
        $sessionId = 'test_session_id';
        $csrfToken = 'test_csrf_token';

        $headers = $this->service->buildAuthHeaders($sessionId, $csrfToken);

        $this->assertEquals($sessionId, $headers['x-session-id']);
        $this->assertEquals($csrfToken, $headers['X-CSRFToken']);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertEquals('application/json', $headers['Accept']);
    }

    public function test_build_standard_headers()
    {
        $headers = $this->service->buildStandardHeaders();

        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertEquals('application/json', $headers['Accept']);
        $this->assertCount(2, $headers);
    }

    public function test_check_connectivity()
    {
        Http::fake([
            'https://api.metlife.com/api/v3/init/' => Http::response([
                'success' => true
            ], 200)
        ]);

        $result = $this->service->checkConnectivity();

        $this->assertTrue($result['status']);
        $this->assertEquals('Request successful', $result['message']);
    }
}