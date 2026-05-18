<?php

namespace App\Http\Middleware;

use App\Events\Axiom\FlushAxiomBatch;
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
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $traceId = $request->header('X-Trace-Id', (string) Str::uuid()) ?: (string) Str::uuid();

        Log::withContext([
            'trace_id' => $traceId,
            'request_path' => $request->method().' '.$request->path(),
            'request_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'execution_time_ms' => null,
        ]);

        $response = $next($request);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        $executionTime = round($executionTime, 2);

        Log::withContext([
            'response_status' => $response->getStatusCode(),
            'execution_time_ms' => $executionTime,
        ]);

        $response->headers->set('X-Trace-Id', $traceId);
        $response->headers->set('X-Execution-Time', $executionTime);

        FlushAxiomBatch::dispatch();

        return $response;
    }
}
