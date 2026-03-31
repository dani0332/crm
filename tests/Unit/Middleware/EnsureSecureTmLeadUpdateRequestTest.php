<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureSecureTmLeadUpdateRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

function buildSecureTmLeadUpdateRequest(array $server = [], string $method = 'PUT'): Request
{
    $contentType = $server['CONTENT_TYPE'] ?? 'application/json';
    $xsrfToken = $server['HTTP_X_XSRF_TOKEN'] ?? 'test-csrf-token';
    $origin = $server['HTTP_ORIGIN'] ?? 'https://imcrmuat.alfred.ae';
    $referer = $server['HTTP_REFERER'] ?? null;

    unset($server['CONTENT_TYPE'], $server['HTTP_X_XSRF_TOKEN'], $server['HTTP_ORIGIN'], $server['HTTP_REFERER']);

    $request = Request::create(
        'https://imcrmuat.alfred.ae/telemarketing/tmleads/18097/tmLeadUpdate',
        $method,
        [],
        [],
        [],
        $server
    );
    $request->headers->set('Content-Type', $contentType);
    $request->headers->set('X-XSRF-TOKEN', $xsrfToken);
    $request->headers->set('Origin', $origin);
    if ($referer !== null) {
        $request->headers->set('Referer', $referer);
    }

    $session = app('session.store');
    $session->start();
    $session->put('_token', 'test-csrf-token');
    $request->setLaravelSession($session);

    return $request;
}

test('allows secure put json request with valid xsrf token and same origin', function () {
    $middleware = new EnsureSecureTmLeadUpdateRequest;
    $request = buildSecureTmLeadUpdateRequest();

    $response = $middleware->handle($request, fn () => response('ok', 200));

    expect($response->getStatusCode())->toBe(200);
});

test('rejects non put request', function () {
    $middleware = new EnsureSecureTmLeadUpdateRequest;
    $request = buildSecureTmLeadUpdateRequest([], 'POST');

    expect(fn () => $middleware->handle($request, fn () => response('ok', 200)))
        ->toThrow(HttpException::class, 'Method Not Allowed');
});

test('rejects non json content type', function () {
    $middleware = new EnsureSecureTmLeadUpdateRequest;
    $request = buildSecureTmLeadUpdateRequest([
        'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
    ]);

    expect(fn () => $middleware->handle($request, fn () => response('ok', 200)))
        ->toThrow(HttpException::class, 'Unsupported Media Type');
});

test('rejects when xsrf token header is missing', function () {
    $middleware = new EnsureSecureTmLeadUpdateRequest;
    $request = buildSecureTmLeadUpdateRequest([
        'HTTP_X_XSRF_TOKEN' => '',
    ]);

    expect(fn () => $middleware->handle($request, fn () => response('ok', 200)))
        ->toThrow(HttpException::class, 'CSRF token mismatch.');
});

test('rejects when request origin and referer do not match host', function () {
    $middleware = new EnsureSecureTmLeadUpdateRequest;
    $request = buildSecureTmLeadUpdateRequest([
        'HTTP_ORIGIN' => 'https://attacker.example',
        'HTTP_REFERER' => 'https://attacker.example/telemarketing/tmleads/18097',
    ]);

    expect(fn () => $middleware->handle($request, fn () => response('ok', 200)))
        ->toThrow(HttpException::class, 'Invalid request origin.');
});
