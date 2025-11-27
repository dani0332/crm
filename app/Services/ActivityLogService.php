<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Context;

class ActivityLogService extends BaseService
{
    /**
     * Get paginated activity logs with optional filters
     *
     * @param array $filters Optional filters: user_id, date_from, date_to
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActivityLogs(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ActivityLog::with(['causer:id,name,email', 'subject']);

        // Filter by user (causer) - optional
        if (!empty($filters['user_id'])) {
            $query->where('causer_id', $filters['user_id'])
                ->where('causer_type', User::class);
        }

        if (!empty($filters['date_from'])) {
            $dateFrom = Carbon::parse($filters['date_from'])->startOfDay();
            $query->where('created_at', '>=', $dateFrom);
        }

        if (!empty($filters['date_to'])) {
            $dateTo = Carbon::parse($filters['date_to'])->endOfDay();
            $query->where('created_at', '<=', $dateTo);
        }

        $query->orderBy('id', 'desc');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Log HTTP request details using Spatie activity log
     */
    public function logHttpRequest(Request $request): void
    {
        try {
            if (!$this->shouldLogRequest($request)) {
                return;
            }

            $user = $request->user();
            $method = $request->method();
            $path = $request->path();
            $route = $request->route();
            $routeName = $route?->getName();

            // Extract controller name and method name from route action
            $logName = $this->extractLogName($route);
            
            // Build description: "User Name Sent Request to URL"
            $userName = $user?->name;
            $path = request()->path(); 
            $description = sprintf('%s Sent Request to %s', $userName, $path);

            // Collect request data
            $requestData = $this->collectRequestData($request);

            // Prepare properties
            $properties = [
                'method' => $method,
                'path' => $path,
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            // Add request payload if available
            if (!empty($requestData['payload'])) {
                $properties['request_payload'] = $this->sanitizePayload($requestData['payload']);
            }

            // Add query parameters
            if (!empty($requestData['query_params'])) {
                $properties['query_params'] = $requestData['query_params'];
            }

            // Add route information
            if ($routeName) {
                $properties['route_name'] = $routeName;
            }

            // Log using Spatie's activity helper
            // This will automatically get the batch_uuid from LogBatch context
            activity($logName)
                ->causedBy($user)
                ->withProperties($properties)
                ->event("Accessed")
                ->tap(function ($activity) use ($request) {
                    // Get feature and code from Context (set by LoggerService::startFeatureLogging)
                    $feature = Context::get('feature');
                    $code = Context::get('code');
                    
                    // Set custom fields
                    $activity->url = $request->getRequestUri();
                    $activity->ip_address = $request->ip();
                    $activity->user_agent = $request->userAgent();
                    $activity->feature = $feature;
                    $activity->code = $code;
                })
                ->log($description);

        } catch (\Throwable $e) {
            // Log error but don't break the request
            LoggerService::error('ActivityLogService: Failed to log HTTP request', [
                'error' => $e->getMessage(),
                'path' => $request->path(),
                'method' => $request->method(),
            ], $e);
        }
    }

    /**
     * Extract log name from route action (ControllerName@methodName)
     */
    private function extractLogName($route): string
    {
        $actionName = $route->getActionName();
        
        if ($actionName && is_string($actionName) && str_contains($actionName, '@')) {
            $parts = explode('@', $actionName);
            if (count($parts) === 2) {
                $controller = class_basename($parts[0]);
                $method = $parts[1];
                return sprintf('%s@%s', $controller, $method);
            }
        }
        
        return config('activitylog.default_log_name', 'default');
    }

    /**
     * Collect request data (payload, query params, etc.)
     */
    private function collectRequestData(Request $request): array
    {
        $method = $request->method();
        $contentType = $request->header('Content-Type', '');
        
        $requestData = [];
        
        // Extract payload based on method and content type
        $payload = $this->extractPayload($request, $method, $contentType);
        if (!empty($payload)) {
            $requestData['payload'] = $payload;
        }
        
        // Collect query parameters
        if ($request->query->count() > 0) {
            $requestData['query_params'] = $request->query->all();
        }
        
        return $requestData;
    }

    /**
     * Extract payload based on HTTP method and content type
     */
    private function extractPayload(Request $request, string $method, string $contentType): array
    {
        $payload = [];
        
        // For GET and DELETE, use query parameters as payload
        if (in_array(strtoupper($method), ['GET', 'DELETE'])) {
            if ($request->query->count() > 0) {
                $payload = $request->query->all();
            }
            return $payload;
        }
        
        // For POST, PUT, PATCH - extract based on content type
        if (str_contains($contentType, 'application/json')) {
            $jsonContent = $request->getContent();
            if (!empty($jsonContent)) {
                $decoded = json_decode($jsonContent, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $payload = $decoded;
                }
            }
        }  else {
            $payload = $request->except(array_keys($request->allFiles()));
        }
        
        return $payload;
    }

    /**
     * Sanitize sensitive fields in payload
     */
    private function sanitizePayload(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'current_password', 'token', 'api_key', 'secret'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***REDACTED***';
            }
        }
        
        return $data;
    }

    /**
     * Check if the request should be logged
     */
    private function shouldLogRequest(Request $request): bool
    {
        // Skip if activity logger is disabled
        if (!config('activitylog.enabled', true)) {
            return false;
        }

        // Skip if route should be excluded from logging
        if ($this->shouldSkipLogging($request)) {
            return false;
        }

        // Skip if user is not authenticated
        if (!$request->user()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if HTTP request logging should be skipped for this request
     */
    private function shouldSkipLogging(Request $request): bool
    {
        $excludedPaths = [
            '/health',
            '/horizon',
            '/telescope',
            '/activity-log',
            '/activity-logs',
            '/admin/activity',
            '/google/callback',
        ];

        $path = $request->path(); 
        $routeName = $request->route()?->getName();

        foreach ($excludedPaths as $excludedPath) {
            $normalizedExcluded = ltrim($excludedPath, '/');
            
            if (str_starts_with($path, $normalizedExcluded)) {
                return true;
            }
        }

        return false;
    }
}
