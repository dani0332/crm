<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $requestId = (string) Str::uuid();

        Log::withContext([
            'request_id' => $requestId,
            'request_path' => $request->method().' '.$request->path(),
            'request_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
        ]);

        $response = $next($request);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        $executionTime = round($executionTime, 2);

        Log::withContext([
            'response_status' => $response->getStatusCode(),
            'execution_time_ms' => $executionTime,
        ]);

        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('X-Execution-Time', $executionTime);

        return $response;
    }
}
