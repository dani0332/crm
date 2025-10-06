<?php

declare(strict_types=1);

namespace App\Services\MetLife;

use App\Services\BaseService;
use App\Services\Logger\LoggerService;
use Exception;

class MetLifeApiService extends BaseService
{
    private string $baseUrl;
    private string $apiVersion;
    private string $username;
    private string $password;
    private int $timeout;
    private int $sessionTimeout;
    private int $csrfTokenRefreshInterval;
    
    private ?string $sessionId = null;
    private ?string $csrfToken = null;
    private ?int $sessionCreatedAt = null;
    private ?int $csrfTokenCreatedAt = null;

    private MetLifeRequestService $request;
    private MetLifeCacheService $cache;
    private MetLifeValidationService $validator;

    public function __construct()
    {
        $this->baseUrl = config('constants.METLIFE_API_BASE_URL');
        $this->apiVersion = config('constants.METLIFE_API_VERSION', '3');
        $this->username = config('constants.METLIFE_USERNAME');
        $this->password = config('constants.METLIFE_PASSWORD');
        $this->timeout = (int) config('constants.METLIFE_API_TIMEOUT', 30);
        $this->sessionTimeout = (int) config('constants.METLIFE_SESSION_TIMEOUT', 7200);
        $this->csrfTokenRefreshInterval = (int) config('constants.METLIFE_CSRF_TOKEN_REFRESH_INTERVAL', 3600);
        
        $this->request = new MetLifeRequestService($this->baseUrl, $this->apiVersion, $this->timeout);
        $this->cache = new MetLifeCacheService();
        $this->validator = new MetLifeValidationService();
        
        $this->loadCachedSession();
    }

    public function checkConnectionStatus(): array
    {
        if (!$this->isMetLifeEnabled()) {
            return [
                'status' => false,
                'message' => 'MetLife integration is disabled',
                'error' => 'INTEGRATION_DISABLED'
            ];
        }

        try {
            LoggerService::info('MetLife API - Checking connection status');
            $initResult = $this->initialize();
            if (!$initResult['status']) {
                return $initResult;
            }

            $loginResult = $this->login();
            if (!$loginResult['status']) {
                return $loginResult;
            }

            return [
                'status' => true,
                'message' => 'MetLife API connection successful',
                'session_id' => $this->sessionId,
                'csrf_token' => substr($this->csrfToken, 0, 10) . '...'
            ];

        } catch (Exception $e) {
            return $this->validator->handleException($e, 'Connection check');
        }
    }

    public function initialize(): array
    {
        if (!$this->isMetLifeEnabled()) {
            return [
                'status' => false,
                'message' => 'MetLife integration is disabled'
            ];
        }

        try {
            LoggerService::info('MetLife API - Initializing session');

            $response = $this->request->makeRequest('/init/', 'GET');

            if ($response['status']) {
                $data = $response['data'];
                
                $this->csrfToken = $data['csrftoken'] ?? null;
                $this->sessionId = $data['session_id'] ?? null;
                $this->csrfTokenCreatedAt = time();
                $this->cache->cacheTokens($this->csrfToken, $this->csrfTokenCreatedAt, $this->csrfTokenRefreshInterval);

                LoggerService::info('MetLife API - Session initialized successfully', [
                    'session_id' => substr($this->sessionId, 0, 10) . '...',
                    'csrf_token' => substr($this->csrfToken, 0, 10) . '...',
                    'session_expiry' => $data['session_expiry'] ?? null
                ]);

                return [
                    'status' => true,
                    'message' => 'Session initialized successfully',
                    'data' => $data
                ];
            }

            return $response;

        } catch (Exception $e) {
            return $this->validator->handleException($e, 'Session initialization');
        }
    }

    public function login(): array
    {
        if (!$this->isMetLifeEnabled()) {
            return [
                'status' => false,
                'message' => 'MetLife integration is disabled'
            ];
        }

        try {
            LoggerService::info('MetLife API - Attempting login');

            $data = [
                'username' => $this->username,
                'password' => $this->password
            ];

            $headers = $this->request->buildAuthHeaders($this->sessionId, $this->csrfToken);
            $response = $this->request->makeRequest('/login/', 'POST', $data, $headers);

            if ($response['status']) {
                $responseData = $response['data'];
                
                if ($responseData['success'] ?? false) {
                    $this->sessionId = $responseData['session_id'] ?? $this->sessionId;
                    $this->sessionCreatedAt = time();
                    $this->cache->cacheSession($this->sessionId, $this->sessionCreatedAt, $this->sessionTimeout);

                    LoggerService::info('MetLife API - Login successful', [
                        'session_id' => substr($this->sessionId, 0, 10) . '...',
                        'roles' => $responseData['roles'] ?? [],
                        'org' => $responseData['org'] ?? null
                    ]);

                    return [
                        'status' => true,
                        'message' => 'Login successful',
                        'data' => $responseData
                    ];
                }

                return [
                    'status' => false,
                    'message' => 'Login failed',
                    'error' => $responseData['message'] ?? 'Unknown error'
                ];
            }

            return $response;

        } catch (Exception $e) {
            return $this->validator->handleException($e, 'Login');
        }
    }

    private function isMetLifeEnabled(): bool
    {
        return $this->validator->isIntegrationEnabled();
    }

    private function loadCachedSession(): void
    {
        $cachedData = $this->cache->loadCachedSessionData();
        $this->sessionId = $cachedData['session_id'];
        $this->csrfToken = $cachedData['csrf_token'];
        $this->sessionCreatedAt = $cachedData['session_created_at'];
        $this->csrfTokenCreatedAt = $cachedData['csrf_token_created_at'];
    }

    private function ensureValidSession(): bool
    {
        if (!$this->isMetLifeEnabled()) {
            return false;
        }

        if (!$this->validator->isCsrfTokenValid($this->csrfToken, $this->csrfTokenCreatedAt, $this->csrfTokenRefreshInterval)) {
            LoggerService::info('MetLife API - CSRF token expired, refreshing');
            $initResult = $this->initialize();
            if (!$initResult['status']) {
                return false;
            }
        }

        if (!$this->validator->isSessionValid($this->sessionId, $this->sessionCreatedAt, $this->sessionTimeout)) {
            LoggerService::info('MetLife API - Session expired, re-authenticating');
            $loginResult = $this->login();
            if (!$loginResult['status']) {
                return false;
            }
        }

        return true;
    }

    protected function makeRequest(string $endpoint, string $method = 'GET', array $data = []): array
    {
        if (!$this->ensureValidSession()) {
            return [
                'status' => false,
                'message' => 'Unable to establish valid session'
            ];
        }

        $headers = [
            'x-session-id' => $this->sessionId,
            'X-CSRFToken' => $this->csrfToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        return $this->request->makeRequest($endpoint, $method, $data, $headers);
    }
}