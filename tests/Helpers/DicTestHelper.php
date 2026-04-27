<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;

/**
 * Build a synchronous Laravel HTTP client response for DIC/EnsuredIT unit tests (no real HTTP).
 *
 * @param  array<string, mixed>|string  $body
 */
function dicTestClientResponse(array|string $body, int $status = 200): Response
{
    $raw = is_array($body) ? (string) json_encode($body) : $body;

    return new Response(new Psr7Response($status, [], $raw));
}
