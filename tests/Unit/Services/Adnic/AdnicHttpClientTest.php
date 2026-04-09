<?php

declare(strict_types=1);

use App\Enums\ApplicationStorageEnums;
use App\Services\ApplicationStorageService;
use App\Services\PolicyIssuanceAutomation\Health\Adnic\AdnicHttpClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $mockService = Mockery::mock(ApplicationStorageService::class);
    $mockService->shouldReceive('getValueByKey')
        ->with(ApplicationStorageEnums::ADNIC_HEALTH_AUTOMATION_API_TIMEOUT)
        ->andReturn(30);

    app()->instance(ApplicationStorageService::class, $mockService);

    // Non-production test doubles (RFC 2606 host); real AdnicHttpClient is wrapped below.
    config([
        'constants.ADNIC_PARTNER_ID' => 'unit-test-partner-id',
        'constants.ADNIC_PARTNER_REFERENCE_NO' => 'unit-test-partner-ref',
        'constants.ADNIC_API_BASE_URL' => 'https://example.test',
        'constants.ADNIC_AUTHORIZATION_TOKEN' => 'Bearer unit-test-token',
        'constants.ADNIC_SUBSCRIPTION_KEY' => 'unit-test-subscription-key',
    ]);

    $adnicHttpClient = new AdnicHttpClient;

    $this->client = Mockery::mock(AdnicHttpClient::class);
    $this->client->shouldReceive('post')->andReturnUsing(function (...$args) use ($adnicHttpClient) {
        return $adnicHttpClient->post(...$args);
    });
    $this->client->shouldReceive('getBaseUrl')->andReturnUsing(function () use ($adnicHttpClient) {
        return $adnicHttpClient->getBaseUrl();
    });
    $this->client->shouldReceive('getPartnerId')->andReturnUsing(function () use ($adnicHttpClient) {
        return $adnicHttpClient->getPartnerId();
    });
    $this->client->shouldReceive('getPartnerReferenceNo')->andReturnUsing(function () use ($adnicHttpClient) {
        return $adnicHttpClient->getPartnerReferenceNo();
    });
});

afterEach(function () {
    Mockery::close();
});

test('http client makes successful post request', function () {
    Http::fake([
        '*' => Http::response([
            'isSuccess' => 'Y',
            'message' => 'Success',
            'data' => ['test' => 'value'],
        ], 200),
    ]);

    $response = $this->client->post('/test-endpoint', ['key' => 'value']);

    expect($response->successful())->toBeTrue()
        ->and($response->json('isSuccess'))->toBe('Y')
        ->and($response->json('data'))->toBe(['test' => 'value']);
});

test('http client handles server error 500', function () {
    Http::fake([
        '*' => Http::response([
            'isSuccess' => 'N',
            'message' => 'Internal Server Error',
        ], 500),
    ]);

    $response = $this->client->post('/test-endpoint', ['key' => 'value']);

    expect($response->serverError())->toBeTrue()
        ->and($response->status())->toBe(500);
});

test('http client handles server error 502', function () {
    Http::fake([
        '*' => Http::response('Bad Gateway', 502),
    ]);

    $response = $this->client->post('/test-endpoint', ['key' => 'value']);

    expect($response->serverError())->toBeTrue()
        ->and($response->status())->toBe(502);
});

test('http client handles server error 503', function () {
    Http::fake([
        '*' => Http::response('Service Unavailable', 503),
    ]);

    $response = $this->client->post('/test-endpoint', ['key' => 'value']);

    expect($response->serverError())->toBeTrue()
        ->and($response->status())->toBe(503);
});

test('http client handles server error 504', function () {
    Http::fake([
        '*' => Http::response('Gateway Timeout', 504),
    ]);

    $response = $this->client->post('/test-endpoint', ['key' => 'value']);

    expect($response->serverError())->toBeTrue()
        ->and($response->status())->toBe(504);
});

test('http client retries on connection timeout', function () {
    $attemptCount = 0;

    Http::fake(function () use (&$attemptCount) {
        $attemptCount++;

        // Fail first 3 attempts, succeed on 4th (after 3 retries = 4 total attempts)
        if ($attemptCount < 4) {
            throw new ConnectionException('Connection timeout');
        }

        return Http::response([
            'isSuccess' => 'Y',
            'message' => 'Success after retries',
        ], 200);
    });

    $response = $this->client->post('/test-endpoint', ['key' => 'value']);

    expect($attemptCount)->toBe(4) // 1 initial + 3 retries
        ->and($response->successful())->toBeTrue()
        ->and($response->json('message'))->toBe('Success after retries');
});

test('http client does not retry on server errors', function () {
    $attemptCount = 0;

    Http::fake(function () use (&$attemptCount) {
        $attemptCount++;

        return Http::response([
            'isSuccess' => 'N',
            'message' => 'Server error - no retry',
        ], 503);
    });

    $response = $this->client->post('/test-endpoint', ['key' => 'value']);

    // Should not retry on server errors, only on connection exceptions
    expect($attemptCount)->toBe(1)
        ->and($response->serverError())->toBeTrue()
        ->and($response->json('message'))->toBe('Server error - no retry');
});

test('http client exhausts retries and throws exception on persistent connection error', function () {
    Http::fake(function () {
        throw new ConnectionException('Persistent connection error');
    });

    // Should throw after exhausting all retries (1 initial + 5 retries = 6 total attempts)
    expect(fn () => $this->client->post('/test-endpoint', ['key' => 'value']))
        ->toThrow(ConnectionException::class);
});

test('http client sends correct headers', function () {
    Http::fake();

    $this->client->post('/test-endpoint', ['key' => 'value']);

    Http::assertSent(function ($request) {
        return $request->hasHeader('Content-Type', 'application/json')
            && $request->hasHeader('Accept', 'application/json')
            && $request->hasHeader('Ocp-Apim-Subscription-Key', 'unit-test-subscription-key')
            && $request->hasHeader('Authorization', 'Bearer unit-test-token');
    });
});

test('http client builds correct url', function () {
    Http::fake();

    $this->client->post('/TestEndpoint', ['key' => 'value']);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'https://example.test/MedicalProductAPI/MedicalAPI.svc/API/Medical/TestEndpoint');
    });
});

test('http client handles 4xx errors without retry', function () {
    $attemptCount = 0;

    Http::fake(function () use (&$attemptCount) {
        $attemptCount++;

        return Http::response([
            'isSuccess' => 'N',
            'message' => 'Bad Request',
        ], 400);
    });

    $response = $this->client->post('/test-endpoint', ['key' => 'value']);

    // Should not retry on 4xx errors
    expect($attemptCount)->toBe(1)
        ->and($response->clientError())->toBeTrue()
        ->and($response->status())->toBe(400);
});

test('http client handles 404 errors without retry', function () {
    $attemptCount = 0;

    Http::fake(function () use (&$attemptCount) {
        $attemptCount++;

        return Http::response([
            'isSuccess' => 'N',
            'message' => 'Not Found',
        ], 404);
    });

    $response = $this->client->post('/test-endpoint', ['key' => 'value']);

    // Should not retry on 404 errors
    expect($attemptCount)->toBe(1)
        ->and($response->clientError())->toBeTrue()
        ->and($response->status())->toBe(404);
});

test('get base url returns correct value', function () {
    expect($this->client->getBaseUrl())
        ->toBe('https://example.test/MedicalProductAPI/MedicalAPI.svc/API/Medical');
});

test('get partner id returns correct value', function () {
    expect($this->client->getPartnerId())->toBe('unit-test-partner-id');
});

test('get partner reference no returns correct value', function () {
    expect($this->client->getPartnerReferenceNo())->toBe('unit-test-partner-ref');
});

test('http client applies custom headers', function () {
    Http::fake();

    $this->client->post('/test-endpoint', ['key' => 'value'], ['X-Custom-Header' => 'custom-value']);

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Custom-Header', 'custom-value')
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->hasHeader('Authorization', 'Bearer unit-test-token');
    });
});

test('http client sends payload as json', function () {
    Http::fake();

    $payload = [
        'partnerId' => 'TEST123',
        'memberInfo' => [
            'name' => 'John Doe',
            'age' => 30,
        ],
    ];

    $this->client->post('/test-endpoint', $payload);

    Http::assertSent(function ($request) use ($payload) {
        return $request->isJson()
            && $request->data() === $payload;
    });
});
