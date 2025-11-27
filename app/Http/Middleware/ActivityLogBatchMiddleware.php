<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Spatie\Activitylog\Facades\LogBatch;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogBatchMiddleware
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {
    }

    /**
     * Automatically starts and ends Spatie's LogBatch for each HTTP request.
     * All activities created during the request will share the same batch_uuid.     *
     */
    public function handle(Request $request, Closure $next): Response
    {
        LogBatch::startBatch();

        try {
            $response = $next($request);
        } finally {
            // This ensures all activities are properly saved with the batch_uuid
            LogBatch::endBatch();

            // Log HTTP request details after response is ready
            $this->activityLogService->logHttpRequest($request);
        }

        return $response ?? response('', 500);
    }
}