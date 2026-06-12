<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MetLife;

use App\Services\MetLife\MetLifeResponseService;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class MetLifeResponseServiceTest extends TestCase
{
    private MetLifeResponseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MetLifeResponseService;
    }

    public function test_create_response_success()
    {
        $data = ['test' => 'value'];
        $result = $this->service->createResponse(true, 'Success message', $data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Success message', $result['message']);
        $this->assertEquals($data, $result['data']);
    }

    public function test_create_response_failure()
    {
        $result = $this->service->createResponse(false, 'Error message');

        $this->assertFalse($result['success']);
        $this->assertEquals('Error message', $result['message']);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_handle_upload_response_success()
    {
        $response = [
            'file_reference' => 'test_file_ref_123',
        ];
        $policyNumber = 'POL123';
        $quoteUuid = 'QUOTE456';

        $result = $this->service->handleUploadResponse($response, $policyNumber, $quoteUuid);

        $this->assertTrue($result['success']);
        $this->assertEquals('Document uploaded successfully', $result['message']);
        $this->assertEquals('test_file_ref_123', $result['data']['file_reference']);
        $this->assertEquals($policyNumber, $result['data']['policy_number']);
    }

    public function test_handle_upload_response_nested_file_reference()
    {
        $response = [
            'data' => [
                'file_reference' => 'nested_file_ref_456',
            ],
        ];
        $policyNumber = 'POL789';

        $result = $this->service->handleUploadResponse($response, $policyNumber);

        $this->assertTrue($result['success']);
        $this->assertEquals('nested_file_ref_456', $result['data']['file_reference']);
    }

    public function test_handle_upload_response_failure()
    {
        $response = [
            'message' => 'Upload failed',
        ];
        $policyNumber = 'POL999';

        $result = $this->service->handleUploadResponse($response, $policyNumber);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Document upload failed', $result['message']);
        $this->assertEquals($policyNumber, $result['data']['policy_number']);
    }

    public function test_handle_http_response_success()
    {
        $mockResponse = new \Illuminate\Http\Client\Response(
            new Response(200, [], json_encode(['success' => true]))
        );

        $result = $this->service->handleHttpResponse($mockResponse, '/test/endpoint');

        $this->assertTrue($result['success']);
        $this->assertEquals('Request successful', $result['message']);
        $this->assertEquals(200, $result['data']['status_code']);
    }

    public function test_handle_http_response_failure()
    {
        $mockResponse = new \Illuminate\Http\Client\Response(
            new Response(404, [], json_encode(['error' => 'Not found']))
        );

        $result = $this->service->handleHttpResponse($mockResponse, '/test/endpoint');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Request failed', $result['message']);
        $this->assertEquals(404, $result['data']['status_code']);
    }

    public function test_handle_exception_response()
    {
        $exception = new \Exception('Test error message', 500);

        $result = $this->service->handleExceptionResponse($exception);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('MetLife operation failed', $result['message']);
        $this->assertEquals(500, $result['data']['exception_code']);
    }

    public function test_response_structure_consistency()
    {
        $result = $this->service->createResponse(true, 'Test', ['key' => 'value']);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(3, $result);
    }
}
