<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Logger\LoggerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestTimingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Detect if this is a deferred prop request (Inertia sends X-Inertia-Only header)
        $isDeferredRequest = $request->header('X-Inertia-Only') !== null ||
                             $request->has('only') ||
                             $request->header('X-Inertia-Partial-Data') !== null;

        // Record request start
        //        LoggerService::info('RequestTiming START '.$request->method().': '.$request->path() , [
        //            'method' => $request->method(),
        //            'path' => $request->path(),
        //            'full_url' => $request->fullUrl(),
        //            'memory_start_mb' => round($startMemory / 1024 / 1024, 2),
        //            'is_deferred_request' => $isDeferredRequest,
        //            'inertia_only' => $request->header('X-Inertia-Only'),
        //            'only_params' => $request->input('only'),
        //        ]);

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        $turnaroundTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB
        $peakMemoryMb = $peakMemory / 1024 / 1024; // Convert to MB

        // Log full turnaround time
        LoggerService::info('RequestTiming Ends '.$request->method().': '.$request->path(), [
            'turnaround_time_ms' => round($turnaroundTime, 2),
            'turnaround_time_sec' => round(($endTime - $startTime), 2),
            'response_status' => $response->getStatusCode(),
            'memory_used_mb' => round($memoryUsed, 2),
            'peak_memory_mb' => round($peakMemoryMb, 2),
            'is_deferred_request' => $isDeferredRequest,
            'request_type' => $isDeferredRequest ? 'DEFERRED_PROP' : 'FULL_PAGE',
        ]);

        // Add timing headers to response
        $response->headers->set('X-Request-Turnaround-Time-Ms', (string) round($turnaroundTime, 2));
        $response->headers->set('X-Request-Turnaround-Time-Sec', (string) round(($endTime - $startTime), 2));
        $response->headers->set('X-Memory-Used-Mb', (string) round($memoryUsed, 2));
        $response->headers->set('X-Peak-Memory-Mb', (string) round($peakMemoryMb, 2));

        return $response;
    }
}
