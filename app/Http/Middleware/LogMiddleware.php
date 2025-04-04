<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        Log::withContext([
            'request_id' => uniqid(),
            'request_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'request_path' => $request->path(),
            'request_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
        ]);

        $response = $next($request);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        Log::withContext([
            'response_status' => $response->getStatusCode(),
            'execution_time_ms' => round($executionTime, 2),
        ]);

        return $response;
    }
}
