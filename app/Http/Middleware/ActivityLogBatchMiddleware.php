<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogBatchHandler;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogBatchMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Start batch at the beginning of request
        ActivityLogBatchHandler::startBatch();

        try {
            $response = $next($request);
        } finally {
            // Always end batch when request completes (even if exception occurs)
            ActivityLogBatchHandler::endBatch();
        }

        return $response;
    }
}

