<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class OcrHttpFakeHelper
{
    /**
     * @var array<string, Response>
     */
    private static array $fakeMap = [];

    /**
     * Prevent accidental real HTTP requests in tests.
     */
    public static function preventStrayRequests(): void
    {
        self::$fakeMap = [];
        Http::preventStrayRequests();
    }

    public static function fakeHealthOk(): void
    {
        self::fakeJsonResponse(self::healthUrl(), 200, []);
    }

    public static function fakeHealthFail(int $status = 500, array|string|null $body = []): void
    {
        self::fakeJsonResponse(self::healthUrl(), $status, $body);
    }

    public static function fakeProcessDocumentOk(array $body): void
    {
        self::fakeJsonResponse(self::processDocumentUrl(), 200, $body);
    }

    public static function fakeProcessDocumentFail(int $status = 500, array|string|null $body = []): void
    {
        self::fakeJsonResponse(self::processDocumentUrl(), $status, $body);
    }

    /**
     * @param  array<mixed>|string|null  $body
     */
    private static function fakeJsonResponse(string $url, int $status = 200, array|string|null $body = []): void
    {
        self::$fakeMap[$url] = Http::response($body ?? '', $status);

        Http::fake(self::$fakeMap);
    }

    /**
     * Assert an OCR process-document request was sent with JSON.
     *
     * @param  callable(array<string, mixed>):bool  $payloadAssert
     */
    public static function assertSentProcessDocumentPayload(callable $payloadAssert): void
    {
        $url = self::processDocumentUrl();

        Http::assertSent(function (Request $request) use ($url, $payloadAssert) {
            if ($request->url() !== $url) {
                return false;
            }

            /** @var array<string, mixed> $json */
            $json = $request->data();

            return $payloadAssert($json) === true;
        });
    }

    /**
     * Assert the OCR process-document request includes `source` header.
     */
    public static function assertSentProcessDocumentSource(string $expectedSource): void
    {
        $url = self::processDocumentUrl();

        Http::assertSent(function (Request $request) use ($url, $expectedSource) {
            if ($request->url() !== $url) {
                return false;
            }

            $headers = self::normalizeHeaders($request->headers());
            $source = $headers['source'] ?? null;

            if (is_array($source)) {
                return in_array($expectedSource, $source, true);
            }

            return $source === $expectedSource;
        });
    }

    public static function assertNothingSent(): void
    {
        Http::assertNothingSent();
    }

    private static function healthUrl(): string
    {
        return self::baseUrl().'/health';
    }

    private static function processDocumentUrl(): string
    {
        return self::baseUrl().'/process-document';
    }

    private static function baseUrl(): string
    {
        return rtrim((string) config('constants.OCR_API_ENDPOINT'), '/');
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    private static function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            $normalized[strtolower((string) $key)] = $value;
        }

        return $normalized;
    }
}
