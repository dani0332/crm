<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Activitylog\Facades\LogBatch;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogBatchMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Automatically starts and ends Spatie's LogBatch for each HTTP request.
     * All activities created during the request will share the same batch_uuid.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Start Spatie's LogBatch - all activities will share the same batch_uuid
        LogBatch::startBatch();

        try {
            $response = $next($request);
        } finally {
            // Always end batch when request completes (even if exception occurs)
            // This ensures all activities are properly saved with the batch_uuid
            LogBatch::endBatch();
        }

        return $response;
    }
}

