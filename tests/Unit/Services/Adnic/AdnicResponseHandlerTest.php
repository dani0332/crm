<?php

declare(strict_types=1);

use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicResponseHandler;
use Illuminate\Http\Client\Response;

beforeEach(function () {
    $this->handler = new AdnicResponseHandler;
});

afterEach(function () {
    Mockery::close();
});

test('build step response creates correct structure', function () {
    $step = 'TestStep';
    $status = true;
    $message = 'Test message';
    $error = null;
    $data = ['test' => 'data'];

    $result = $this->handler->buildStepResponse($step, $status, $message, $error, $data);

    expect($result)
        ->toHaveKeys(['status', 'completed_step', 'message', 'error', 'data'])
        ->and($result['status'])->toBe($status)
        ->and($result['completed_step'])->toBe($step)
        ->and($result['message'])->toBe($message)
        ->and($result['error'])->toBe($error)
        ->and($result['data'])->toBe($data);
});

test('build step response with default values', function () {
    $step = 'TestStep';

    $result = $this->handler->buildStepResponse($step);

    expect($result['status'])->toBeFalse()
        ->and($result['message'])->toBeNull()
        ->and($result['error'])->toBeNull()
        ->and($result['data'])->toBeNull();
});

test('parse http response handles successful response', function () {
    $responseData = (object) [
        'policyInfo' => (object) [
            'policyNo' => 'POL123',
            'policyStartDate' => '2024-01-01',
        ],
    ];

    $responseMock = Mockery::mock(Response::class);
    $responseMock->shouldReceive('successful')->once()->andReturn(true);
    $responseMock->shouldReceive('object')->once()->andReturn($responseData);

    $result = $this->handler->parseHttpResponse($responseMock, 'TestAPI');

    expect($result['status'])->toBeTrue()
        ->and($result['error'])->toBeNull()
        ->and($result['data'])->not->toBeNull()
        ->and($result['data'])->toBe($responseData);
});

test('parse http response handles api error with error list', function () {
    $responseData = (object) [
        'ErrorInfo' => [
            (object) ['ErrorMsg' => 'API validation failed'],
        ],
    ];

    $responseMock = Mockery::mock(Response::class);
    $responseMock->shouldReceive('successful')->once()->andReturn(true);
    $responseMock->shouldReceive('object')->once()->andReturn($responseData);

    $result = $this->handler->parseHttpResponse($responseMock, 'TestAPI');

    expect($result['status'])->toBeFalse()
        ->and($result['error'])->not->toBeNull();
});

test('parse http response handles null response object', function () {
    $responseMock = Mockery::mock(Response::class);
    $responseMock->shouldReceive('successful')->once()->andReturn(true);
    $responseMock->shouldReceive('object')->once()->andReturn(null);

    $result = $this->handler->parseHttpResponse($responseMock, 'TestAPI');

    expect($result['status'])->toBeFalse()
        ->and($result['error'])->not->toBeNull()
        ->and($result['error'])->toContain('TestAPI');
});

test('parse http response handles is success n', function () {
    $responseData = (object) [
        'ErrorInfo' => [
            (object) ['ErrorMsg' => 'Operation failed'],
        ],
    ];

    $responseMock = Mockery::mock(Response::class);
    $responseMock->shouldReceive('successful')->once()->andReturn(true);
    $responseMock->shouldReceive('object')->once()->andReturn($responseData);

    $result = $this->handler->parseHttpResponse($responseMock, 'TestAPI');

    expect($result['status'])->toBeFalse()
        ->and($result['message'])->not->toBeNull();
});

test('parse http response handles 404 not found', function () {
    $responseMock = Mockery::mock(Response::class);
    $responseMock->shouldReceive('successful')->once()->andReturn(false);
    $responseMock->shouldReceive('object')->once()->andReturn(null);
    $responseMock->shouldReceive('status')->once()->andReturn(404);

    $result = $this->handler->parseHttpResponse($responseMock, 'TestAPI');

    expect($result['status'])->toBeFalse()
        ->and($result['error'])->toBe('404 Not Found')
        ->and($result['message'])->toBe('404 Not Found');
});

test('parse http response handles generic http error', function () {
    $responseMock = Mockery::mock(Response::class);
    $responseMock->shouldReceive('successful')->once()->andReturn(false);
    $responseMock->shouldReceive('object')->once()->andReturn(null);
    $responseMock->shouldReceive('status')->once()->andReturn(500);

    $result = $this->handler->parseHttpResponse($responseMock, 'TestAPI');

    expect($result['status'])->toBeFalse()
        ->and($result['error'])->toContain('API Failed');
});

test('parse http response extracts error from message field', function () {
    $responseData = (object) [
        'ErrorInfo' => [
            (object) ['ErrorMsg' => 'Custom error message'],
        ],
    ];

    $responseMock = Mockery::mock(Response::class);
    $responseMock->shouldReceive('successful')->once()->andReturn(true);
    $responseMock->shouldReceive('object')->once()->andReturn($responseData);

    $result = $this->handler->parseHttpResponse($responseMock, 'TestAPI');

    expect($result['status'])->toBeFalse()
        ->and($result['message'])->not->toBeNull();
});

test('build step response with error only', function () {
    $step = 'TestStep';
    $error = 'Test error';

    $result = $this->handler->buildStepResponse($step, false, null, $error);

    expect($result['status'])->toBeFalse()
        ->and($result['error'])->toBe($error)
        ->and($result['message'])->toBeNull();
});

test('parse http response returns consistent structure', function () {
    $responseMock = Mockery::mock(Response::class);
    $responseMock->shouldReceive('successful')->once()->andReturn(false);
    $responseMock->shouldReceive('object')->once()->andReturn(null);
    $responseMock->shouldReceive('status')->once()->andReturn(500);

    $result = $this->handler->parseHttpResponse($responseMock, 'TestAPI');

    // Verify all required keys exist
    expect($result)
        ->toHaveKeys(['status', 'error', 'message', 'data', 'completed_step']);
});

test('parse http response successful response has data', function () {
    $responseData = (object) ['test' => 'value'];

    $responseMock = Mockery::mock(Response::class);
    $responseMock->shouldReceive('successful')->once()->andReturn(true);
    $responseMock->shouldReceive('object')->once()->andReturn($responseData);

    $result = $this->handler->parseHttpResponse($responseMock, 'TestAPI');

    expect($result['status'])->toBeTrue()
        ->and($result['data'])->toBe($responseData)
        ->and($result['error'])->toBeNull();
});

test('build step response preserves all provided values', function () {
    $step = 'ComplexStep';
    $status = true;
    $message = 'Complex operation completed';
    $error = null;
    $data = ['key1' => 'value1', 'key2' => ['nested' => 'data']];

    $result = $this->handler->buildStepResponse($step, $status, $message, $error, $data);

    expect($result['status'])->toBe($status)
        ->and($result['completed_step'])->toBe($step)
        ->and($result['message'])->toBe($message)
        ->and($result['error'])->toBe($error)
        ->and($result['data'])->toBe($data)
        ->and($result['data']['key2']['nested'])->toBe('data');
});

test('parse http response handles error info array from adnic api', function () {
    $responseData = (object) [
        'ErrorInfo' => [
            (object) [
                'ErrorCode' => 'E001',
                'ErrorMsg' => 'Invalid document type',
            ],
        ],
    ];

    $responseMock = Mockery::mock(Response::class);
    $responseMock->shouldReceive('successful')->once()->andReturn(true);
    $responseMock->shouldReceive('object')->once()->andReturn($responseData);

    $result = $this->handler->parseHttpResponse($responseMock, 'TestAPI');

    expect($result['status'])->toBeFalse()
        ->and($result['message'])->toContain('Invalid document type');
});

test('parse http response handles document info error array', function () {
    $responseData = (object) [
        'DocumentInfo' => (object) [
            'ErrorInfo' => [
                (object) [
                    'ErrorCode' => 'E002',
                    'ErrorMsg' => 'Document content invalid',
                ],
            ],
        ],
    ];

    $responseMock = Mockery::mock(Response::class);
    $responseMock->shouldReceive('successful')->once()->andReturn(true);
    $responseMock->shouldReceive('object')->once()->andReturn($responseData);

    $result = $this->handler->parseHttpResponse($responseMock, 'TestAPI');

    expect($result['status'])->toBeFalse()
        ->and($result['message'])->toContain('Document content invalid');
});
