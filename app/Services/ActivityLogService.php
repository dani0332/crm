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
use Symfony\Component\HttpFoundation\Response;

class ActivityLogService extends BaseService
{
    /**
     * Get paginated activity logs with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActivityLogs(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ActivityLog::with(['causer:id,name,email', 'subject'])
            ->orderBy('created_at', 'desc');

        // Filter by user (causer) - required
        if (!empty($filters['user_id'])) {
            $query->where('causer_id', $filters['user_id'])
                ->where('causer_type', User::class);
        }

        // Filter by date range - required
        if (!empty($filters['date_from'])) {
            $dateFrom = Carbon::parse($filters['date_from'])->startOfDay();
            $query->where('created_at', '>=', $dateFrom);
        }

        if (!empty($filters['date_to'])) {
            $dateTo = Carbon::parse($filters['date_to'])->endOfDay();
            $query->where('created_at', '<=', $dateTo);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Log HTTP request details using Spatie activity log
     */
    public function logHttpRequest(Request $request, ?Response $response, string $traceId, float $startTime): void
    {
        try {
            // Skip if activity logger is disabled
            if (!config('activitylog.enabled', true)) {
                return;
            }

            // Skip if route should be excluded from logging
            if ($this->shouldSkipLogging($request)) {
                return;
            }

            // Skip if user is not authenticated
            if (!$request->user()) {
                return;
            }

            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);

            $user = $request->user();
            $method = $request->method();
            $path = $request->path();
            $route = $request->route();
            $routeName = $route?->getName();

            // Use default log name
            $logName = config('activitylog.default_log_name', 'default');

            // Extract resource name and identifier
            $resourceName = $this->extractResourceName($request, $route, $path);
            $identifier = $this->extractIdentifier($request, $route, $path);

            // Build description using HTTP method
            $description = $this->generateDescription($user, $method, $resourceName, $identifier, $path);

            // Collect request data
            $requestData = $this->collectRequestData($request, $traceId, $executionTime, $response);

            // Prepare properties
            $properties = [
                'method' => $method,
                'path' => $path,
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status_code' => $response?->getStatusCode() ?? 0,
                'execution_time_ms' => $executionTime,
                'trace_id' => $traceId,
            ];

            // Add request payload if available
            if (!empty($requestData['payload'])) {
                $properties['request_payload'] = $this->sanitizePayload($requestData['payload']);
            }

            // Add query parameters
            if (!empty($requestData['query_params'])) {
                $properties['query_params'] = $requestData['query_params'];
            }

            // Add file info if available
            if (!empty($requestData['files'])) {
                $properties['files'] = $requestData['files'];
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
                ->event($this->determineEvent($method))
                ->tap(function ($activity) use ($request) {
                    // Get feature and code from Context (set by LoggerService::startFeatureLogging)
                    $feature = Context::get('feature');
                    $code = Context::get('code');
                    
                    // Set custom fields
                    $activity->url = $request->getRequestUri();
                    $activity->ip_address = $request->ip();
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
     * Extract resource name from route and path
     */
    private function extractResourceName(Request $request, $route, string $path): ?string
    {
        $pathSegments = array_filter(explode('/', $path));
        
        $resourceMap = [
            'quotes' => 'quote',
            'personal-quotes' => 'personal quote',
            'payments' => 'payment',
            'customers' => 'customer',
            'users' => 'user',
            'leads' => 'lead',
            'documents' => 'document',
            'invoices' => 'invoice',
            'policies' => 'policy',
        ];
        
        $firstSegment = $pathSegments[0] ?? null;
        if ($firstSegment && isset($resourceMap[$firstSegment])) {
            return $resourceMap[$firstSegment];
        }
        
        if ($route) {
            $parameters = $route->parameters();
            foreach (['quote', 'payment', 'customer', 'user'] as $key) {
                if (isset($parameters[$key])) {
                    return $resourceMap[$key . 's'] ?? $key;
                }
            }
        }
        
        return null;
    }

    /**
     * Extract identifier (quote code, payment code, etc.) from request
     */
    private function extractIdentifier(Request $request, $route, string $path): ?string
    {
        if ($route) {
            $parameters = $route->parameters();
            foreach (['uuid', 'code', 'id'] as $key) {
                if (isset($parameters[$key])) {
                    $value = $parameters[$key];
                    if (is_object($value)) {
                        return $value->code ?? $value->id ?? null;
                    }
                    if (preg_match('/^[A-Z]{3}-[A-Z0-9]+$/', strtoupper($value))) {
                        return strtoupper($value);
                    }
                    return $value;
                }
            }
        }
        
        $pathSegments = array_filter(explode('/', $path));
        foreach ($pathSegments as $segment) {
            if (preg_match('/^[A-Z]{3}-[A-Z0-9]+$/', strtoupper($segment))) {
                return strtoupper($segment);
            }
        }
        
        $payload = $request->all();
        foreach (['code', 'payment_code', 'quote_code', 'quote_uuid', 'uuid'] as $key) {
            if (isset($payload[$key])) {
                return $payload[$key];
            }
        }
        
        return null;
    }

    /**
     * Generate description for activity log
     */
    private function generateDescription($user, string $method, ?string $resourceName, ?string $identifier, string $path): string
    {
        $userName = $user?->name ?? 'System';
        $methodUpper = strtoupper($method);
        
        if ($identifier && $resourceName) {
            return sprintf(
                '%s %s %s %s',
                $userName,
                $methodUpper,
                $resourceName,
                $identifier
            );
        } elseif ($resourceName) {
            return sprintf(
                '%s %s %s',
                $userName,
                $methodUpper,
                $resourceName
            );
        } else {
            return sprintf(
                '%s %s %s',
                $userName,
                $methodUpper,
                $path
            );
        }
    }

    /**
     * Determine event type from request method
     */
    private function determineEvent(string $method): string
    {
        return match (strtoupper($method)) {
            'GET' => 'viewed',
            'POST' => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            default => 'performed',
        };
    }

    /**
     * Collect request data (payload, query params, files, etc.)
     */
    private function collectRequestData(Request $request, string $traceId, float $executionTime, ?Response $response): array
    {
        $method = $request->method();
        $contentType = $request->header('Content-Type', '');
        
        $requestData = [
            'trace_id' => $traceId,
            'execution_time_ms' => $executionTime,
        ];
        
        // Extract payload based on method and content type
        $payload = $this->extractPayload($request, $method, $contentType);
        if (!empty($payload)) {
            $requestData['payload'] = $payload;
        }
        
        // Extract file info if present
        $fileInfo = $this->extractFileInfo($request);
        if (!empty($fileInfo)) {
            $requestData['files'] = $fileInfo;
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
        } elseif (str_contains($contentType, 'multipart/form-data') || str_contains($contentType, 'application/x-www-form-urlencoded')) {
            $allData = $request->all();
            $files = $request->allFiles();
            
            // Remove file keys from payload
            foreach (array_keys($files) as $fileKey) {
                unset($allData[$fileKey]);
            }
            
            $payload = $allData;
        } else {
            $payload = $request->except(array_keys($request->allFiles()));
        }
        
        return $payload;
    }

    /**
     * Extract file information from request (metadata only, not file objects)
     * Handles both single files and arrays of files
     */
    private function extractFileInfo(Request $request): array
    {
        $fileInfo = [];
        
        foreach ($request->allFiles() as $fieldName => $file) {
            if (is_array($file)) {
                // Handle multiple files (e.g., files[] or files[0], files[1])
                $fileInfo[$fieldName] = $this->extractMultipleFilesMetadata($file);
            } elseif ($file instanceof \Illuminate\Http\UploadedFile) {
                // Handle single file
                $fileInfo[$fieldName] = $this->extractFileMetadata($file);
            }
        }
        
        return $fileInfo;
    }

    /**
     * Extract metadata from a single uploaded file
     */
    private function extractFileMetadata(\Illuminate\Http\UploadedFile $file): array
    {
        return [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extension' => $file->getClientOriginalExtension(),
        ];
    }

    /**
     * Extract metadata from multiple uploaded files
     */
    private function extractMultipleFilesMetadata(array $files): array
    {
        $metadata = [];
        
        foreach ($files as $index => $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $metadata[$index] = $this->extractFileMetadata($file);
            }
        }
        
        return $metadata;
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
        ];

        $path = $request->path();
        $routeName = $request->route()?->getName();

        // Check excluded paths
        foreach ($excludedPaths as $excludedPath) {
            if (str_starts_with($path, ltrim($excludedPath, '/'))) {
                return true;
            }
        }

        // Check route name patterns
        if ($routeName) {
            $internalRoutePatterns = [
                'activity',
                'horizon',
                'telescope',
                'queue',
                'job',
            ];

            foreach ($internalRoutePatterns as $pattern) {
                if (stripos($routeName, $pattern) !== false) {
                    return true;
                }
            }
        }

        return false;
    }
}

