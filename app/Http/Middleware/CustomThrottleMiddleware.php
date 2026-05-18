<?php

namespace App\Http\Middleware;

use App\Services\Logger\LoggerService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class CustomThrottleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $urlPath = $request->path();

        // Use user ID if authenticated, otherwise use IP address
        $userId = optional($request->user())->id;
        $identifier = $userId ? 'user:'.$userId : 'ip:'.$request->ip();

        // Create a unique key combining identifier and route
        $key = $identifier.':urlPath:'.$urlPath;

        // Default Laravel throttle limit
        $limit = 30;
        $decay = 60; // seconds (1 minute)

        // Increment the counter but don't block
        $hits = RateLimiter::hit($key, $decay);

        // If over limit, log it
        if ($hits > $limit) {

            LoggerService::info('Rate limit exceeded , Identifier : '.$identifier, extra: [
                'userId' => $userId,
                'identifier' => $identifier,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'numberOfHits' => $hits,
                'limit' => $limit,
                'timeWindow' => $decay.'s',
                'timestamp' => now()->toDateTimeString(),
            ]);
        }

        // Always allow the request through
        return $next($request);
    }
}
