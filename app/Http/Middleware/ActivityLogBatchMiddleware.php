<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogBatchMiddleware
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Logs HTTP request details for each request via ActivityLogService.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } finally {
            $this->activityLogService->logHttpRequest($request);
        }

        return $response ?? response('', 500);
    }
}
