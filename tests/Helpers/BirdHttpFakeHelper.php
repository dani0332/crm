<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Enums\ApplicationStorageEnums;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

final class BirdHttpFakeHelper
{
    private const AUTHORIZATION_HEADER = 'Authorization';
    private const ACCESS_KEY_PREFIX = 'AccessKey ';

    /**
     * Prevent accidental real HTTP requests in tests.
     */
    public static function preventStrayRequests(): void
    {
        Http::preventStrayRequests();
    }

    /**
     * Fake a single JSON response for a specific URL.
     *
     * @param  array<string, string>  $headers
     * @param  array<mixed>|string|null  $body
     */
    public static function fakeJsonResponse(string $url, int $status = 200, array $headers = [], array|string|null $body = []): void
    {
        Http::fake([
            $url => Http::response($body ?? '', $status, $headers),
        ]);
    }

    /**
     * Fake a thrown exception for a specific URL (e.g. timeout / DNS / network failure).
     */
    public static function fakeException(string $url, Throwable $exception): void
    {
        Http::fake(function (Request $request) use ($url, $exception) {
            if ($request->url() === $url) {
                throw $exception;
            }

            return Http::response('', 404);
        });
    }

    /**
     * Fake a sequence of responses for repeated calls to the same URL.
     *
     * Example:
     * BirdHttpFakeHelper::fakeSequence($url, [
     *   BirdHttpFakeHelper::step(500, [], ['message' => 'temporary']),
     *   BirdHttpFakeHelper::step(200, ['Run-Id' => 'run-2'], []),
     * ]);
     *
     * @param  array<int, array{status:int, headers:array<string,string>, body:array<mixed>|string|null}>  $steps
     */
    public static function fakeSequence(string $url, array $steps): void
    {
        $sequence = Http::fakeSequence($url);

        foreach ($steps as $step) {
            $sequence->push($step['body'] ?? '', $step['status'], $step['headers'] ?? []);
        }
    }

    /**
     * Convenience builder for fakeSequence() steps.
     *
     * @param  array<string, string>  $headers
     * @param  array<mixed>|string|null  $body
     * @return array{status:int, headers:array<string,string>, body:array<mixed>|string|null}
     */
    public static function step(int $status = 200, array $headers = [], array|string|null $body = []): array
    {
        return [
            'status' => $status,
            'headers' => $headers,
            'body' => $body,
        ];
    }

    /**
     * Assert an HTTP request was sent to the URL with JSON matching a predicate.
     *
     * @param  callable(array<string, mixed>):bool  $payloadAssert
     */
    public static function assertSentJson(string $url, callable $payloadAssert): void
    {
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
     * Assert a request was sent to the URL with Authorization: AccessKey {value}.
     */
    public static function assertAuthorizationAccessKey(string $url, string $accessKey): void
    {
        $expected = self::ACCESS_KEY_PREFIX.$accessKey;

        Http::assertSent(function (Request $request) use ($url, $expected) {
            if ($request->url() !== $url) {
                return false;
            }

            $headers = self::normalizeHeaders($request->headers());
            $authorization = $headers[strtolower(self::AUTHORIZATION_HEADER)] ?? null;

            if (is_array($authorization)) {
                return in_array($expected, $authorization, true);
            }

            return $authorization === $expected;
        });
    }

    public static function assertNothingSent(): void
    {
        Http::assertNothingSent();
    }

    public static function assertSentCount(int $count): void
    {
        Http::assertSentCount($count);
    }

    /**
     * Seed Bird AccessKey value in application_storage for tests that call Bird with $isAccessKey=true.
     */
    public static function seedAccessKey(string $accessKey, string $connection = 'sqlite'): void
    {
        DB::connection($connection)->table('application_storage')->updateOrInsert(
            ['key_name' => ApplicationStorageEnums::BIRD_ACCESS_KEY],
            ['value' => $accessKey, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Normalize headers array keys to lowercase to avoid case issues.
     *
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
