<?php

declare(strict_types=1);

use App\Services\PolicyIssuanceAutomation\Travel\Dic\DicHttpClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'cache.stores.redis' => [
            'driver' => 'array',
            'serialize' => false,
        ],
    ]);
    Cache::store('redis')->clear();
});

it('requests auth generate and caches accessToken when redis has no token', function (): void {
    config([
        'constants.DIC_API_BASE_URL' => 'https://dic-test.example',
        'constants.DIC_API_USERNAME' => 'insurancemarket',
        'constants.DIC_API_PASSWORD' => 'secret-pass',
        'constants.DIC_API_TIMEOUT' => 30,
    ]);

    Http::fake([
        'https://dic-test.example/auth/generate' => Http::response([
            'accessToken' => 'fresh-token-abc',
            'expiresIn' => 120,
            'refreshToken' => 'ignored',
        ], 200),
        'https://dic-test.example/v1/foo' => Http::response(['ok' => true], 200),
    ]);

    $client = new DicHttpClient;
    $response = $client->authenticatedRequest('GET', $client->buildUrl('v1/foo'));

    expect($response)->not->toBeNull()
        ->and($response->successful())->toBeTrue();

    expect(Cache::store('redis')->get(DicHttpClient::REDIS_TOKEN_KEY))->toBe('fresh-token-abc');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://dic-test.example/auth/generate'
            && $request->method() === 'POST'
            && $request['username'] === 'insurancemarket'
            && $request['password'] === 'secret-pass';
    });
});

it('does not call auth generate when token is already cached', function (): void {
    config([
        'constants.DIC_API_BASE_URL' => 'https://dic-test.example',
        'constants.DIC_API_USERNAME' => 'u',
        'constants.DIC_API_PASSWORD' => 'p',
    ]);

    Cache::store('redis')->put(DicHttpClient::REDIS_TOKEN_KEY, 'cached-only', 600);

    Http::fake([
        'https://dic-test.example/v1/bar' => Http::response([], 200),
    ]);

    $client = new DicHttpClient;
    $client->authenticatedRequest('GET', $client->buildUrl('v1/bar'));

    Http::assertNotSent(function ($request) {
        return str_contains($request->url(), 'auth/generate');
    });
});

it('clears cache and refetches token after 401', function (): void {
    config([
        'constants.DIC_API_BASE_URL' => 'https://dic-test.example',
        'constants.DIC_API_USERNAME' => 'u',
        'constants.DIC_API_PASSWORD' => 'p',
    ]);

    Cache::store('redis')->put(DicHttpClient::REDIS_TOKEN_KEY, 'stale-token', 600);

    Http::fake([
        'https://dic-test.example/auth/generate' => Http::response([
            'accessToken' => 'new-after-401',
            'expiresIn' => 60,
        ], 200),
        'https://dic-test.example/v1/secure' => Http::sequence()
            ->push(['err' => 'unauthorized'], 401)
            ->push(['ok' => true], 200),
    ]);

    $client = new DicHttpClient;
    $response = $client->authenticatedRequest('GET', $client->buildUrl('v1/secure'));

    expect($response)->not->toBeNull()
        ->and($response->successful())->toBeTrue()
        ->and(Cache::store('redis')->get(DicHttpClient::REDIS_TOKEN_KEY))->toBe('new-after-401');
});
