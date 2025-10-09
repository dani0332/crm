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
        "application/pdf",
        "image/jpeg",
        "image/png",
        "image/bmp",
        "image/tiff"
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
        $this->cache = new MetLifeCacheService();
        $this->responseService = new MetLifeResponseService();
        $this->validator = new MetLifeValidationService();
        
        $this->loadCachedSession();
    }


    public function initialize(): array
    {
        if (!$this->isMetLifeEnabled()) {
            return ['success' => false, 'message' => 'MetLife integration is disabled'];
        }

        try {
            $endpoint = '/api/v' . $this->apiVersion . '/init/';
            $response = $this->request->makeRequest($endpoint, 'GET');

            if ($response['success']) {
                $actualResponse = $response['data']['data'] ?? $response['data'];
                
                $this->csrfToken = $actualResponse['csrftoken'] ?? null;
                $this->sessionId = $actualResponse['session_id'] ?? null;
                $this->csrfTokenCreatedAt = time();
                
                if ($this->csrfToken) {
                    $this->cache->cacheTokens($this->csrfToken, $this->csrfTokenCreatedAt, $this->csrfTokenRefreshInterval);
                }

                return $this->responseService->createResponse(true, 'Session initialized successfully', $actualResponse);
            }

            return $response;

        } catch (Exception $e) {
            return $this->validator->handleException($e, 'Session initialization');
        }
    }

    public function login(): array
    {
        if (!$this->isMetLifeEnabled()) {
            return ['success' => false, 'message' => 'MetLife integration is disabled'];
        }

        try {
            $data = ['username' => $this->username, 'password' => $this->password];
            $headers = $this->request->buildHeaders($this->sessionId, $this->csrfToken);
            $response = $this->request->makeRequest('/api/v' . $this->apiVersion . '/login/', 'POST', $data, $headers);

            if ($response['success']) {
                $actualResponse = $response['data']['data'] ?? $response['data'];
                
                if ($actualResponse['success'] ?? false) {
                    $this->sessionId = $actualResponse['session_id'] ?? $this->sessionId;
                    $this->sessionCreatedAt = time();
                    $this->cache->cacheSession($this->sessionId, $this->sessionCreatedAt, $this->sessionTimeout);

                    return $this->responseService->createResponse(true, 'Login successful', $actualResponse);
                }

                return $this->responseService->createResponse(false, 'Login failed: ' . ($actualResponse['message'] ?? 'Unknown error'), $actualResponse);
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

        $csrfValid = $this->validator->isCsrfTokenValid($this->csrfToken, $this->csrfTokenCreatedAt, $this->csrfTokenRefreshInterval);
        $sessionValid = $this->validator->isSessionValid($this->sessionId, $this->sessionCreatedAt, $this->sessionTimeout);

        if (!$csrfValid) {
            $initResult = $this->initialize();
            if (!$initResult['success']) {
                return false;
            }
        }

        if (!$sessionValid) {
            $loginResult = $this->login();
            if (!$loginResult['success']) {
                return false;
            }
        }

        return true;
    }

    protected function makeRequest(string $endpoint, string $method = 'GET', array $data = []): array
    {
        if (!$this->ensureValidSession()) {
            return $this->responseService->createResponse(false, 'Unable to establish valid session');
        }

        $headers = $this->request->buildHeaders($this->sessionId, $this->csrfToken);
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
                'message' => 'Exactly 2 documents of type ' . $validatedData['document_type_code'] . ' are required',
                'uploaded_count' => $documents->count(),
                'required_count' => 2
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
                'final_document_name' => $documentName
            ]);
            
            if (!$fileData) {
                $results[] = [
                    'document_name' => $documentName,
                    'success' => false,
                    'message' => 'Failed to read document content'
                ];
                continue;
            }
            
            $uploadData = [
                'quote_uuid' => $validatedData['quote_uuid'],
                'policy_number' => $validatedData['policy_number'],
                'provider_code' => $validatedData['provider_code'],
                'file' => $fileData,
                'file_name' => $documentName
            ];
            
            $result = $this->uploadToMetLife($uploadData, $documentName);
            
            if ($result['success'] && isset($result['data']['file_reference'])) {
                $fileReference = $result['data']['file_reference'];
                $document->update(['insurer_document_link' => $fileReference]);
                
                LoggerService::info('Database updated with MetLife file reference', [
                    'document_id' => $document->id,
                    'file_reference' => $fileReference,
                    'updated' => true
                ]);
            } else {
                LoggerService::warning('Failed to update database with MetLife file reference', [
                    'document_id' => $document->id,
                    'result_success' => $result['success'] ?? false,
                    'has_file_reference' => isset($result['data']['file_reference']),
                    'result_structure' => $result
                ]);
            }
            
            $results[] = [
                'document_name' => $documentName,
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? 'Upload failed',
                'file_reference' => $result['data']['file_reference'] ?? null
            ];
        }
        
        $successCount = collect($results)->where('success', true)->count();
        
        return [
            'success' => $successCount > 0,
            'message' => $successCount === count($results) ? 'All documents uploaded successfully' : 'Some documents failed to upload'
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
            
            if (!$fileContent) {
                return null;
            }
            
            $extension = pathinfo($docPath, PATHINFO_EXTENSION);
            $mimeType = $this->getMimeTypeFromExtension($extension);
            $base64Data = base64_encode($fileContent);
            
            return "data:{$mimeType};base64,{$base64Data}";
            
        } catch (Exception $e) {
            LoggerService::warning('Failed to get document content', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
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
            if (!isset($data['policy_number']) || empty($data['policy_number'])) {
                return $this->responseService->createResponse(false, 'Policy number is required for MetLife upload', ['doc_name' => $docName]);
            }

            if (!isset($data['file']) || empty($data['file'])) {
                return $this->responseService->createResponse(false, 'File data is required for MetLife upload', ['doc_name' => $docName, 'policy_number' => $data['policy_number']]);
            }

            $payload = [
                'name' => $docName,
                'data' => $data['file'],
                'accepted_mime_types' => $this->acceptedDocumentMimeTypes,
            ];

            $endpoint = '/en/api/v' . $this->apiVersion . '/policy/' . $data['policy_number'] . '/attachment/add/';
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
}