<?php

declare(strict_types=1);

namespace App\Services\MetLife;

use App\Enums\QuoteTypes;
use App\Models\QuoteDocument;
use App\Services\BaseService;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Support\Facades\Storage;

class MetLifeApiService extends BaseService
{
    private const UNKNOWN_ERROR_MESSAGE = 'Unknown error';

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
    private MetLifeResponseService $responseService;
    private MetLifeValidationService $validator;
    private ?array $acceptedDocumentMimeTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/bmp',
        'image/tiff',
    ];

    public function __construct()
    {
        $this->baseUrl = config('constants.METLIFE_API_BASE_URL');
        $this->apiVersion = config('constants.METLIFE_API_VERSION', '3');
        $this->username = config('constants.METLIFE_USERNAME');
        $this->password = config('constants.METLIFE_PASSWORD');
        $this->timeout = (int) config('constants.METLIFE_API_TIMEOUT', 30);
        $this->sessionTimeout = (int) config('constants.METLIFE_SESSION_TIMEOUT', 7200);
        $this->csrfTokenRefreshInterval = (int) config('constants.METLIFE_CSRF_TOKEN_REFRESH_INTERVAL', 3600);

        $this->request = new MetLifeRequestService($this->baseUrl, $this->timeout);
        $this->cache = new MetLifeCacheService;
        $this->responseService = new MetLifeResponseService;
        $this->validator = new MetLifeValidationService;

        $this->loadCachedSession();
    }

    public function initialize(): array
    {
        if (! $this->isMetLifeEnabled()) {
            return ['success' => false, 'message' => 'MetLife integration is disabled'];
        }

        try {
            LoggerService::info('MetLife Auth: Initializing session');
            $endpoint = '/api/v'.$this->apiVersion.'/init/';
            $response = $this->request->makeRequest($endpoint, 'GET');

            if ($response['success']) {
                $actualResponse = $response['data']['data'] ?? $response['data'];

                $oldCsrfToken = $this->csrfToken;
                $oldSessionId = $this->sessionId;

                $this->csrfToken = $actualResponse['csrftoken'] ?? null;
                $this->sessionId = $actualResponse['session_id'] ?? null;
                $this->csrfTokenCreatedAt = time();

                LoggerService::info('MetLife Auth: Session initialized', [
                    'old_csrf_token' => $oldCsrfToken ? substr($oldCsrfToken, 0, 8).'...' : 'null',
                    'new_csrf_token' => $this->csrfToken ? substr($this->csrfToken, 0, 8).'...' : 'null',
                    'old_session_id' => $oldSessionId ? substr($oldSessionId, 0, 8).'...' : 'null',
                    'new_session_id' => $this->sessionId ? substr($this->sessionId, 0, 8).'...' : 'null',
                ]);

                if ($this->csrfToken) {
                    $this->cache->cacheTokens($this->csrfToken, $this->csrfTokenCreatedAt, $this->csrfTokenRefreshInterval);
                    LoggerService::info('MetLife Auth: CSRF token cached');
                }

                $response = $this->responseService->createResponse(true, 'Session initialized successfully', $actualResponse);
            } else {
                LoggerService::warning('MetLife Auth: Session initialization failed', [
                    'error' => $response['message'] ?? self::UNKNOWN_ERROR_MESSAGE,
                ]);
            }

            return $response;

        } catch (Exception $e) {
            return $this->validator->handleException($e, 'Session initialization');
        }
    }

    public function login(): array
    {
        if (! $this->isMetLifeEnabled()) {
            return ['success' => false, 'message' => 'MetLife integration is disabled'];
        }

        try {
            LoggerService::info('MetLife Auth: Attempting login', [
                'username' => $this->username,
                'current_session_id' => $this->sessionId ? substr($this->sessionId, 0, 8).'...' : 'null',
                'current_csrf_token' => $this->csrfToken ? substr($this->csrfToken, 0, 8).'...' : 'null',
            ]);

            $data = ['username' => $this->username, 'password' => $this->password];
            $headers = $this->request->buildHeaders($this->sessionId, $this->csrfToken);
            $response = $this->request->makeRequest('/api/v'.$this->apiVersion.'/login/', 'POST', $data, $headers);

            if ($response['success']) {
                $actualResponse = $response['data']['data'] ?? $response['data'];

                if ($actualResponse['success'] ?? false) {
                    $oldSessionId = $this->sessionId;
                    $this->sessionId = $actualResponse['session_id'] ?? $this->sessionId;
                    $this->sessionCreatedAt = time();

                    LoggerService::info('MetLife Auth: Login successful', [
                        'old_session_id' => $oldSessionId ? substr($oldSessionId, 0, 8).'...' : 'null',
                        'new_session_id' => $this->sessionId ? substr($this->sessionId, 0, 8).'...' : 'null',
                        'session_created_at' => $this->sessionCreatedAt,
                    ]);

                    $this->cache->cacheSession($this->sessionId, $this->sessionCreatedAt, $this->sessionTimeout);
                    LoggerService::info('MetLife Auth: Session cached');

                    $response = $this->responseService->createResponse(true, 'Login successful', $actualResponse);
                } else {
                    LoggerService::warning('MetLife Auth: Login failed - API returned success=false', [
                        'message' => $actualResponse['message'] ?? self::UNKNOWN_ERROR_MESSAGE,
                    ]);

                    $response = $this->responseService->createResponse(false, 'Login failed: '.($actualResponse['message'] ?? self::UNKNOWN_ERROR_MESSAGE), $actualResponse);
                }
            } else {
                LoggerService::warning('MetLife Auth: Login request failed', [
                    'error' => $response['message'] ?? self::UNKNOWN_ERROR_MESSAGE,
                ]);
            }

            return $response;

        } catch (Exception $e) {
            return $this->validator->handleException($e, 'Login');
        }
    }

    public function isMetLifeEnabled(): bool
    {
        return $this->validator->isIntegrationEnabled();
    }

    public function getApiVersion(): string
    {
        return $this->apiVersion;
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
        if (! $this->isMetLifeEnabled()) {
            LoggerService::warning('MetLife Auth: Integration disabled');

            return false;
        }

        LoggerService::info('MetLife Auth: Checking session validity', [
            'session_id' => $this->sessionId ? substr($this->sessionId, 0, 8).'...' : 'null',
            'csrf_token' => $this->csrfToken ? substr($this->csrfToken, 0, 8).'...' : 'null',
            'session_created_at' => $this->sessionCreatedAt,
            'csrf_token_created_at' => $this->csrfTokenCreatedAt,
        ]);

        $csrfValid = $this->validator->isCsrfTokenValid($this->csrfToken, $this->csrfTokenCreatedAt, $this->csrfTokenRefreshInterval);
        $sessionValid = $this->validator->isSessionValid($this->sessionId, $this->sessionCreatedAt, $this->sessionTimeout);

        LoggerService::info('MetLife Auth: Session validation results', [
            'csrf_valid' => $csrfValid,
            'session_valid' => $sessionValid,
            'csrf_age' => $this->csrfTokenCreatedAt ? (time() - $this->csrfTokenCreatedAt) : 'null',
            'session_age' => $this->sessionCreatedAt ? (time() - $this->sessionCreatedAt) : 'null',
        ]);

        if (! $csrfValid) {
            LoggerService::info('MetLife Auth: CSRF token invalid, refreshing...');
            $initResult = $this->initialize();
            if (! $initResult['success']) {
                LoggerService::warning('MetLife Auth: CSRF token refresh failed', [
                    'error' => $initResult['message'] ?? self::UNKNOWN_ERROR_MESSAGE,
                ]);

                return false;
            }
            LoggerService::info('MetLife Auth: CSRF token refreshed successfully', [
                'new_csrf_token' => $this->csrfToken ? substr($this->csrfToken, 0, 8).'...' : 'null',
            ]);
        }

        if (! $sessionValid) {
            LoggerService::info('MetLife Auth: Session invalid, logging in...');
            $loginResult = $this->login();
            if (! $loginResult['success']) {
                LoggerService::warning('MetLife Auth: Login failed', [
                    'error' => $loginResult['message'] ?? self::UNKNOWN_ERROR_MESSAGE,
                ]);

                return false;
            }
            LoggerService::info('MetLife Auth: Login successful', [
                'new_session_id' => $this->sessionId ? substr($this->sessionId, 0, 8).'...' : 'null',
            ]);
        }

        LoggerService::info('MetLife Auth: Session validation completed', [
            'final_session_id' => $this->sessionId ? substr($this->sessionId, 0, 8).'...' : 'null',
            'final_csrf_token' => $this->csrfToken ? substr($this->csrfToken, 0, 8).'...' : 'null',
        ]);

        return true;
    }

    public function makeRequest(string $endpoint, string $method = 'GET', array $data = []): array
    {
        LoggerService::info('MetLife Auth: Starting request', [
            'endpoint' => $endpoint,
            'method' => $method,
        ]);

        if (! $this->ensureValidSession()) {
            LoggerService::warning('MetLife Auth: Unable to establish valid session');

            return $this->responseService->createResponse(false, 'Unable to establish valid session');
        }

        // Reload from cache to ensure we have the latest session data
        $this->loadCachedSession();

        LoggerService::info('MetLife Auth: Building headers with current session data', [
            'session_id' => $this->sessionId ? substr($this->sessionId, 0, 8).'...' : 'null',
            'csrf_token' => $this->csrfToken ? substr($this->csrfToken, 0, 8).'...' : 'null',
        ]);

        $headers = $this->request->buildHeaders($this->sessionId, $this->csrfToken);

        LoggerService::info('MetLife Auth: Headers built, making request', [
            'has_session_header' => isset($headers['x-session-id']),
            'has_csrf_header' => isset($headers['X-CSRFToken']),
        ]);

        return $this->request->makeRequest($endpoint, $method, $data, $headers);
    }

    public function handleDocumentUpload(array $validatedData, $quote): array
    {
        LoggerService::startQuoteLogging(QuoteTypes::getName(QuoteTypes::LIFE->id())->refId($validatedData['quote_uuid']));

        $documents = QuoteDocument::where('quote_documentable_id', $quote->id)
            ->where('document_type_code', $validatedData['document_type_code'])
            ->get();

        if ($documents->count() !== 2) {
            return [
                'success' => false,
                'message' => 'Exactly 2 documents of type '.$validatedData['document_type_code'].' are required',
                'uploaded_count' => $documents->count(),
                'required_count' => 2,
            ];
        }

        $results = [];

        foreach ($documents as $document) {
            $fileData = $this->getDocumentBase64Content($document);
            $documentName = $document->doc_name ?: $document->original_name;

            LoggerService::info('Document details for MetLife upload', [
                'document_id' => $document->id,
                'original_name' => $document->original_name,
                'doc_name' => $document->doc_name,
                'final_document_name' => $documentName,
            ]);

            if (! $fileData) {
                $results[] = [
                    'document_name' => $documentName,
                    'success' => false,
                    'message' => 'Failed to read document content',
                ];

                continue;
            }

            $uploadData = [
                'quote_uuid' => $validatedData['quote_uuid'],
                'policy_number' => $validatedData['policy_number'],
                'provider_code' => $validatedData['provider_code'],
                'file' => $fileData,
                'file_name' => $documentName,
            ];

            $result = $this->uploadToMetLife($uploadData, $documentName);

            if ($result['success'] && isset($result['data']['file_reference'])) {
                $fileReference = $result['data']['file_reference'];
                $document->update(['insurer_document_link' => $fileReference]);

                LoggerService::info('Database updated with MetLife file reference', [
                    'document_id' => $document->id,
                    'file_reference' => $fileReference,
                    'updated' => true,
                ]);
            } else {
                LoggerService::warning('Failed to update database with MetLife file reference', [
                    'document_id' => $document->id,
                    'result_success' => $result['success'] ?? false,
                    'has_file_reference' => isset($result['data']['file_reference']),
                    'result_structure' => $result,
                ]);
            }

            $results[] = [
                'document_name' => $documentName,
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? 'Upload failed',
                'file_reference' => $result['data']['file_reference'] ?? null,
            ];
        }

        $successCount = collect($results)->where('success', true)->count();

        return [
            'success' => $successCount === count($results),
            'message' => $successCount === count($results) ? 'All documents uploaded successfully' : 'Some documents failed to upload',
            'results' => $results,
            'uploaded_count' => $successCount,
            'total_count' => count($results),
        ];
    }

    private function getDocumentBase64Content(QuoteDocument $document): ?string
    {
        try {
            $docPath = $document->watermarked_doc_url ?: $document->doc_url;

            if (empty($docPath)) {
                return null;
            }

            $fileContent = Storage::disk('azureIM')->get($docPath);

            if (! $fileContent) {
                return null;
            }

            $extension = pathinfo($docPath, PATHINFO_EXTENSION);
            $mimeType = $this->getMimeTypeFromExtension($extension);
            $base64Data = base64_encode($fileContent);

            return "data:{$mimeType};base64,{$base64Data}";

        } catch (Exception $e) {
            LoggerService::warning('Failed to get document content', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function getMimeTypeFromExtension(string $extension): string
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }

    public function uploadToMetLife(array $data, string $docName): array
    {
        try {
            if (! isset($data['policy_number']) || empty($data['policy_number'])) {
                return $this->responseService->createResponse(false, 'Policy number is required for MetLife upload', ['doc_name' => $docName]);
            }

            if (! isset($data['file']) || empty($data['file'])) {
                return $this->responseService->createResponse(false, 'File data is required for MetLife upload', ['doc_name' => $docName, 'policy_number' => $data['policy_number']]);
            }

            $payload = [
                'name' => $docName,
                'data' => $data['file'],
                'accepted_mime_types' => $this->acceptedDocumentMimeTypes,
            ];

            $endpoint = '/en/api/v'.$this->apiVersion.'/policy/'.$data['policy_number'].'/attachment/add/';
            $response = $this->makeRequest($endpoint, 'POST', $payload);

            return $this->responseService->handleUploadResponse(
                $response['data'] ?? $response,
                $data['policy_number'],
                $data['quote_uuid'] ?? null
            );

        } catch (Exception $e) {
            return $this->responseService->handleExceptionResponse($e);
        }
    }

    public function syncHealthQuestionnaire(array $requestData): array
    {
        try {
            LoggerService::info('MetLifeApiService: Starting health questionnaire sync', [
                'quote_uuid' => $requestData['quote_uuid'],
                'policy_number' => $requestData['policy_number'],
            ]);

            $healthQuestionnaireService = app(MTLHealthQuestionnaireService::class);
            $document = $healthQuestionnaireService->syncHealthQuestionnaire($requestData);

            LoggerService::info('MetLifeApiService: Health questionnaire sync completed successfully', [
                'quote_uuid' => $requestData['quote_uuid'],
                'document_id' => $document->id ?? 'N/A',
            ]);

            return $this->responseService->createResponse(true, 'Health questionnaire synced successfully', [
                'document_id' => $document->id ?? null,
                'quote_uuid' => $requestData['quote_uuid'],
            ]);

        } catch (Exception $e) {
            LoggerService::warning('MetLifeApiService: Exception in health questionnaire sync', [
                'quote_uuid' => $requestData['quote_uuid'] ?? 'N/A',
                'error' => $e->getMessage(),
            ]);

            return $this->responseService->handleExceptionResponse($e);
        }
    }

}
