<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Context;

class ActivityLogService extends BaseService
{
    /**
     * HTTP methods that use query parameters as payload
     */
    private const QUERY_PARAM_METHODS = ['GET', 'DELETE'];

    /**
     * JSON content type
     */
    private const JSON_CONTENT_TYPE = 'application/json';

    /**
     * Get paginated activity logs with optional filters
     *
     * @param  array<string, mixed>  $filters  Optional filters: user_id, date_from, date_to, event
     */
    public function getActivityLogs(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ActivityLog::with(['causer:id,name,email', 'subject']);

        // Filter by user (causer) - optional
        $query->when(
            ! empty($filters['user_id']),
            fn ($q) => $q->where('causer_id', $filters['user_id'])
                ->where('causer_type', User::class)
        );

        // Filter by event - optional
        $query->when(
            ! empty($filters['event']),
            fn ($q) => $q->where('event', $filters['event'])
        );

        // Filter by date from
        $query->when(
            ! empty($filters['date_from']),
            function ($q) use ($filters) {
                try {
                    $dateFrom = Carbon::parse($filters['date_from'])->startOfDay();
                    $q->where('created_at', '>=', $dateFrom);
                } catch (InvalidFormatException $e) {
                    LoggerService::warning('ActivityLogService: Invalid date_from format', [
                        'date_from' => $filters['date_from'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        );

        // Filter by date to
        $query->when(
            ! empty($filters['date_to']),
            function ($q) use ($filters) {
                try {
                    $dateTo = Carbon::parse($filters['date_to'])->endOfDay();
                    $q->where('created_at', '<=', $dateTo);
                } catch (InvalidFormatException $e) {
                    LoggerService::warning('ActivityLogService: Invalid date_to format', [
                        'date_to' => $filters['date_to'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        );

        $query->orderBy('id', 'desc');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Log HTTP request details using Spatie activity log
     */
    public function logHttpRequest(Request $request): void
    {
        try {
            if (! $this->shouldLogRequest($request)) {
                return;
            }

            $user = $request->user();
            $path = $request->path();
            $route = $request->route();

            // Extract controller name and method name from route action
            $logName = $this->extractLogName($route);

            // Build description: "User Name Sent Request to URL"
            $description = $this->buildDescription($user?->name, $path);

            // Collect request data
            $requestData = $this->collectRequestData($request);

            // Prepare properties
            $properties = $this->buildProperties($request, $requestData, $route);

            // Log using Spatie's activity helper
            // This will automatically get the batch_uuid from LogBatch context
            $this->createActivityLog($logName, $user, $properties, $description, $request);

        } catch (\Throwable $e) {
            // Log error but don't break the request
            // Only pass Exception to LoggerService::error(), not Error types
            $exception = $e instanceof \Exception ? $e : null;

            LoggerService::error('ActivityLogService: Failed to log HTTP request', [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'path' => $request->path(),
                'method' => $request->method(),
            ], $exception);
        }
    }

    /**
     * Build activity log description
     */
    private function buildDescription(?string $userName, string $path): string
    {
        return sprintf('%s Sent Request to %s', $userName ?? 'Unknown User', $path);
    }

    /**
     * Build properties array for activity log
     *
     * @param  array<string, mixed>  $requestData
     * @return array<string, mixed>
     */
    private function buildProperties(Request $request, array $requestData, ?Route $route): array
    {
        $properties = [
            'method' => $request->method(),
            'path' => $request->path(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Add request payload if available
        if (! empty($requestData['payload'])) {
            $properties['request_payload'] = $this->sanitizePayload($requestData['payload']);
        }

        // Add query parameters
        if (! empty($requestData['query_params'])) {
            $properties['query_params'] = $requestData['query_params'];
        }

        // Add route information
        $routeName = $route?->getName();
        if ($routeName) {
            $properties['route_name'] = $routeName;
        }

        return $properties;
    }

    /**
     * Create activity log entry
     */
    private function createActivityLog(
        string $logName,
        ?Authenticatable $user,
        array $properties,
        string $description,
        Request $request
    ): void {
        // Get feature and code from Context (set by LoggerService::startFeatureLogging)
        $feature = Context::get('feature');
        $code = Context::get('code');

        activity($logName)
            ->causedBy($user)
            ->withProperties($properties)
            ->event(config('activitylog.default_event'))
            ->tap(function ($activity) use ($request, $feature, $code) {
                // Set custom fields (avoid duplication - these are already in properties)
                $activity->url = $request->getRequestUri();
                $activity->ip_address = $request->ip();
                $activity->user_agent = $request->userAgent();
                $activity->feature = $feature;
                $activity->code = $code;
            })
            ->log($description);
    }

    /**
     * Extract log name from route action (ControllerName@methodName)
     *
     * @param  Route|null  $route  The route instance, which may be null for unregistered routes
     * @return string The log name in format "Controller@method" or default log name
     */
    private function extractLogName(?Route $route): string
    {
        if ($route === null) {
            return config('activitylog.default_log_name', 'default');
        }

        // Safe to call getActionName() here since we've verified $route is not null
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
        if (! empty($payload)) {
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
     *
     * @return array<string, mixed>
     */
    private function extractPayload(Request $request, string $method, string $contentType): array
    {
        $methodUpper = strtoupper($method);
        $payload = [];

        // For GET and DELETE, use query parameters as payload
        if (in_array($methodUpper, self::QUERY_PARAM_METHODS, true)) {
            if ($request->query->count() > 0) {
                $payload = $request->query->all();
            }
        } elseif (str_contains($contentType, self::JSON_CONTENT_TYPE)) {
            // For POST, PUT, PATCH - extract based on content type (JSON)
            $jsonContent = $request->getContent();
            if (! empty($jsonContent)) {
                $decoded = json_decode($jsonContent, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $payload = $decoded;
                }
            }
        } else {
            // For form data
            $payload = $request->except(array_keys($request->allFiles()));
        }

        return $payload;
    }

    /**
     * Sanitize sensitive fields in payload (recursively handles nested arrays)
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $data): array
    {
        $sensitiveKeys = config('activitylog.sensitive_keys', [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'api_key',
            'secret',
        ]);

        foreach ($data as $key => $value) {
            // Check if key is sensitive (case-insensitive)
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (strcasecmp((string) $key, (string) $sensitiveKey) === 0) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $data[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                // Recursively sanitize nested arrays
                $data[$key] = $this->sanitizePayload($value);
            }
        }

        return $data;
    }

    /**
     * Check if the request should be logged
     */
    private function shouldLogRequest(Request $request): bool
    {
        // Skip logging in testing environment or if disabled
        if (app()->environment('testing') || ! config('activitylog.enabled', true)) {
            return false;
        }

        // Skip if route should be excluded or user is not authenticated
        if ($this->shouldSkipLogging($request) || ! $request->user()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if HTTP request logging should be skipped for this request
     */
    private function shouldSkipLogging(Request $request): bool
    {
        $excludedPaths = config('activitylog.excluded_paths', []);

        if (empty($excludedPaths)) {
            return false;
        }

        $path = $request->path();

        foreach ($excludedPaths as $excludedPath) {
            $normalizedExcluded = ltrim($excludedPath, '/');

            if (str_starts_with($path, $normalizedExcluded)) {
                return true;
            }
        }

        return false;
    }
}
