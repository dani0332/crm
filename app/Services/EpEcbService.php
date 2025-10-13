<?php

namespace App\Services;

use App\DTO\EpBookingContext;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteStatusEnum;
use App\Jobs\EpWatermarkDocumentJob;
use App\Jobs\SyncEpDocumentsJob;
use App\Models\DocumentType;
use App\Models\InsurerRequestResponse;
use App\Models\QuoteDocument;
use App\Services\Logger\LoggerService;
use Error;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class EpEcbService extends EpBookingService
{
    // API Configuration
    private string $baseUrl = '';
    private string $clientCode = '';
    private string $clientId = '';
    private string $clientSecret = '';
    private int $timeout = 180;

    // Process State
    private ?string $bearerToken = null;
    private ?string $quoteReferenceNumber = null;
    private ?string $policyNumber = null;

    // Cache Keys
    private const TOKEN_CACHE_KEY = 'tpa_client_api_token';
    private const TOKEN_CACHE_DURATION = 3600; // 1 hour

    private string $transactionCountry = 'UAE';
    private string $transactionCurrency = 'AED';
    private string $policyProduct = 'EXW';

    const STEP_GET_TOKEN = 'GetToken';
    const STEP_GET_QUOTE = 'GetQuote';
    const STEP_CREATE_POLICY_FROM_QUOTE = 'CreatePolicyFromQuote';
    const STEP_CREATE_POLICY_WITHOUT_QUOTE = 'CreatePolicyWithoutQuote';
    const STEP_GET_POLICY_DOCUMENTS = 'GetPolicyDocuments';

    /**
     * Create a new class instance.
     */
    public function __construct(
        EpBookingContext $context
    ) {
        parent::__construct('EpEcb', $context);
    }

    public function init(): void
    {
        $this->logExtra = $this->context->logExtra;

        if (! $this->quote) {
            throw new Exception('Quote not found.');
        }

        if (! $this->embeddedTransaction) {
            throw new Exception("EmbeddedTransaction not found with ID: {$this->context->etId}");
        }

        // Load API configuration
        $this->loadApiConfiguration();

        // Restore workflow state from previous execution
        $this->restoreWorkflowState();
    }

    /**
     * Restore workflow state from embedded transaction
     * This allows the workflow to continue from where it left off
     */
    private function restoreWorkflowState(): void
    {
        // Restore quote_reference_number from quote_policy field
        if (! empty($this->embeddedTransaction->quote_policy)) {
            $this->quoteReferenceNumber = $this->embeddedTransaction->quote_policy;
        }

        // Restore policy_number from certificate_number field
        if (! empty($this->embeddedTransaction->certificate_number)) {
            $this->policyNumber = $this->embeddedTransaction->certificate_number;
        }

        // Try to restore bearer token from cache if available
        $cachedToken = Cache::get(self::TOKEN_CACHE_KEY);
        if ($cachedToken) {
            $this->bearerToken = $cachedToken;
        }

        LoggerService::info($this->logPrefix.' Workflow state restoration completed', extra: [
            ...$this->logExtra,
            'has_bearer_token' => ! empty($this->bearerToken),
            'has_quote_reference' => ! empty($this->quoteReferenceNumber),
            'has_policy_number' => ! empty($this->policyNumber),
            'quote_status_id' => $this->quote->quote_status_id,
            'policy_status' => $this->embeddedTransaction->policy_status,
        ]);
    }

    /**
     * Load API configuration from config/services.php
     */
    private function loadApiConfiguration(): void
    {
        $config = config('services.tpa_client_api', []);

        $this->baseUrl = $config['base_url'] ?? '';
        $this->clientCode = $config['client_code'] ?? '';
        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
        $this->timeout = $config['timeout'] ?? 180;
    }

    /**
     * Main entry point for processing the purchase flow
     * Throws exceptions on failure so the job retry mechanism can handle them
     */
    public function executeSteps(): void
    {
        try {
            LoggerService::info($this->logPrefix.' Starting purchase flow', extra: [
                ...$this->logExtra,
                'policy_status' => $this->embeddedTransaction->policy_status,
            ]);

            // Execute workflow with conditional step execution
            // Any exceptions will bubble up to the job for automatic retry handling
            $this->executeWorkflowFromStep();

            LoggerService::info($this->logPrefix.' Purchase flow completed successfully', extra: $this->logExtra);
        } catch (Exception $e) {
            LoggerService::error($this->logPrefix.' Purchase flow failed', extra: [
                ...$this->logExtra,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'exceptionType' => get_class($e),
            ]);
            throw $e;
        }
    }

    /**
     * Execute the workflow sequentially with clear step-by-step logic
     */
    private function executeWorkflowFromStep(): void
    {
        $currentStatus = $this->embeddedTransaction->policy_status ?? '';
        $executedSteps = [];

        // Step 1: Get Token (Always required first)
        if (! $this->shouldSkipStep('get_token', $currentStatus)) {
            $this->executeGetToken();
            $executedSteps[] = self::STEP_GET_TOKEN;
        }

        if ($this->quote->quote_status_id == QuoteStatusEnum::PolicyBooked) {

            // Step 2 & 3: Create Policy Without Quote STATUS_PAYMENT_SUCCEED
            if (! $this->shouldSkipStep('create_policy_without_quote', $currentStatus)) {
                $this->executeCreatePolicyWithoutQuote();
                $executedSteps[] = self::STEP_CREATE_POLICY_WITHOUT_QUOTE;
            }

        } else {

            // Step 2: Get Quote STATUS_QUOTED
            if (! $this->shouldSkipStep('get_quote', $currentStatus)) {
                $this->executeGetQuote();
                $executedSteps[] = self::STEP_GET_QUOTE;
            }

            // Step 3: Create Policy From Quote STATUS_PAYMENT_SUCCEED
            if (! $this->shouldSkipStep('create_policy_from_quote', $currentStatus)) {
                $this->executeCreatePolicyFromQuote();
                $executedSteps[] = self::STEP_CREATE_POLICY_FROM_QUOTE;
            }
        }

        // Log executed steps summary
        LoggerService::info($this->logPrefix.' Executed steps: '.implode(', ', $executedSteps), extra: [
            ...$this->logExtra,
            'quote_policy' => $this->quoteReferenceNumber,
            'certificate_number' => $this->policyNumber,
            'policy_status' => $this->embeddedTransaction->policy_status ?? '',
        ]);

        $isPaymentSucceed = $this->embeddedTransaction->policy_status == EmbeddedTransactionEnum::STATUS_PAYMENT_SUCCEED;
        if (! $this->shouldSkipStep('get_documents', $this->embeddedTransaction->policy_status) && $isPaymentSucceed) {
            // Delete existing documents
            $this->embeddedTransaction->documents()->whereIn('document_type_code', $this->reqDocTypeCodes)->delete();

            $executedCreatePolicyStep = array_values(array_intersect([self::STEP_CREATE_POLICY_WITHOUT_QUOTE, self::STEP_CREATE_POLICY_FROM_QUOTE], $executedSteps));
            if (empty($executedCreatePolicyStep)) {
                dispatch(new SyncEpDocumentsJob($this->context));
            } else {
                // Dispatch job with 2 minutes delay, because documents are available after 2 minutes of policy creation
                LoggerService::info($this->logPrefix.' Dispatch SyncEpDocumentsJob with 2 minutes delay', extra: $this->logExtra);
                dispatch(new SyncEpDocumentsJob($this->context))->delay(now()->addMinutes(1));
            }
        }

        $isDocumentsRetrieved = $this->embeddedTransaction->policy_status == EmbeddedTransactionEnum::STATUS_BOOKED;
        $missingReqDocTypeCodes = $this->getMissingDocumentDocTypes($this->reqDocTypeCodes);
        if (! $this->shouldSkipStep('prepare_for_sage', $this->embeddedTransaction->policy_status) && $isDocumentsRetrieved && empty($missingReqDocTypeCodes)) {

            // Dispatch job for watermark ep documents
            dispatch(new EpWatermarkDocumentJob($this->context));
        }
    }

    /**
     * Sync policy documents (called by EpExcessCashbackSyncDocumentJob)
     */
    public function syncPolicyDocuments(): void
    {
        $currentStatus = $this->embeddedTransaction->policy_status ?? '';
        $missingReqDocTypeCodes = $this->getMissingDocumentDocTypes($this->reqDocTypeCodes);

        // Step 1: Get policy documents
        if (! $this->shouldSkipStep('get_documents', $currentStatus) && ! empty($missingReqDocTypeCodes)) {
            // Get policy documents
            $getPolicyDocumentsResponse = (array) $this->executeGetPolicyDocuments();

            // Update commission if needed
            $this->executeUpdateCommission($getPolicyDocumentsResponse ?? []);

            // Download, Upload & Save policy documents to DB
            $this->executeSyncDocuments($getPolicyDocumentsResponse);

            LoggerService::info($this->logPrefix.' Step completed: GetPolicyDocuments', extra: $this->logExtra);
        } else {
            $extraLogs = [...$this->logExtra, 'missing_req_doc_type_codes' => $missingReqDocTypeCodes];
            LoggerService::info($this->logPrefix.' Skipping step: GetPolicyDocuments - already completed', extra: $extraLogs);
        }
    }

    /**
     * Determine if a step should be skipped based on current transaction status
     */
    private function shouldSkipStep(string $step, string $currentStatus = ''): bool
    {
        return match ($step) {
            'get_token' => false, // Always need token first
            'get_quote' => in_array($currentStatus, [
                EmbeddedTransactionEnum::STATUS_QUOTED,
                EmbeddedTransactionEnum::STATUS_PAYMENT_SUCCEED,
                EmbeddedTransactionEnum::STATUS_BOOKED,
                EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE,
            ]),
            'create_policy_from_quote', 'create_policy_without_quote' => in_array($currentStatus, [
                EmbeddedTransactionEnum::STATUS_PAYMENT_SUCCEED,
                EmbeddedTransactionEnum::STATUS_BOOKED,
                EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE,
            ]),
            'get_documents' => in_array($currentStatus, [
                EmbeddedTransactionEnum::STATUS_BOOKED,
                EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE,
            ]),
            'prepare_for_sage' => in_array($currentStatus, [
                EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE,
            ]),
            default => false
        };
    }

    /**
     * Step 1: Get authentication token
     */
    private function executeGetToken(): void
    {
        // Check if we have a cached valid token
        $cachedToken = Cache::get(self::TOKEN_CACHE_KEY);
        if ($cachedToken && $this->validateToken($cachedToken)) {
            $this->bearerToken = $cachedToken;

            return;
        }

        $payload = [
            'client_code' => $this->clientCode,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        $response = $this->makeApiCall(
            'POST',
            '/api/Auth/GetToken',
            $payload,
            false,
            self::STEP_GET_TOKEN
        );

        if (! $response['success']) {
            throw new Exception('GetToken API call failed: '.($response['error'] ?? 'Unknown error'));
        }

        $responseData = $response['data'];

        if (! isset($responseData->access_token)) {
            throw new Exception('Token not found in GetToken response');
        }

        $this->bearerToken = $responseData->access_token;

        // Cache the token
        Cache::put(self::TOKEN_CACHE_KEY, $this->bearerToken, self::TOKEN_CACHE_DURATION);
    }

    /**
     * Step 2: Get quote
     */
    private function executeGetQuote(): void
    {
        if (! $this->bearerToken) {
            throw new Exception('No bearer token available for GetQuote');
        }

        // Check if we already have a restored quote reference number
        if (! empty($this->quoteReferenceNumber)) {
            return;
        }

        // Build quote request payload based on your business requirements
        $payload = $this->buildQuotePayload();

        $response = $this->makeApiCall(
            'POST',
            '/api/Quote/GetQuote',
            $payload,
            true,
            self::STEP_GET_QUOTE
        );

        if (! $response['success']) {
            throw new Exception('GetQuote API call failed: '.($response['error'] ?? 'Unknown error'));
        }

        $responseQuote = $response['data']->quotes[0] ?? null;
        if (empty($responseQuote->quote_reference_no ?? null)) {
            throw new Exception('Quote reference number not found in GetQuote response');
        }

        $this->quoteReferenceNumber = $responseQuote->quote_reference_no;

        // Update transaction status and save quote_reference_number to quote_policy field
        $this->updateTransactionStatus(EmbeddedTransactionEnum::STATUS_QUOTED, [
            'quote_policy' => $this->quoteReferenceNumber,
        ]);
    }

    /**
     * Step 3: Create policy from quote
     */
    private function executeCreatePolicyFromQuote(): void
    {
        if (! $this->bearerToken) {
            throw new Exception('No bearer token available for CreatePolicyFromQuote');
        }

        if (! $this->quoteReferenceNumber) {
            throw new Exception('No quote reference number available for CreatePolicyFromQuote');
        }

        // Check if we already have a restored policy number
        if (! empty($this->policyNumber)) {
            return;
        }

        // Build policy creation payload
        $payload = $this->buildPolicyFromQuotePayload();

        $response = $this->makeApiCall(
            'POST',
            '/api/Policy/CreatePolicyFromQuote',
            $payload,
            true,
            self::STEP_CREATE_POLICY_FROM_QUOTE
        );

        if (! $response['success']) {
            $responseErrorCode = $response['errorCode'] ?? '-';
            $responseStatusMessage = $response['statusMessage'] ?? 'Unknown error';
            throw new Exception("CreatePolicyFromQuote API call failed: ($responseErrorCode) - $responseStatusMessage");
        }

        $responseData = $response['data'];
        if (empty($responseData->policy_no ?? null)) {
            throw new Exception('Policy number not found in CreatePolicyFromQuote response');
        }

        $this->policyNumber = $responseData->policy_no;

        // Update transaction status and save certificate_number to certificate_number field
        $this->updateTransactionStatus(EmbeddedTransactionEnum::STATUS_PAYMENT_SUCCEED, [
            'certificate_number' => $this->policyNumber,
        ]);
    }

    /**
     * Step 3: Create policy from quote
     */
    private function executeCreatePolicyWithoutQuote(): void
    {
        if (! $this->bearerToken) {
            throw new Exception('No bearer token available for CreatePolicyFromQuote');
        }

        // Check if we already have a restored policy number
        if (! empty($this->policyNumber)) {
            return;
        }

        // Build policy creation payload
        $payload = $this->buildPolicyWithoutQuotePayload();

        $response = $this->makeApiCall(
            'POST',
            '/api/Policy/CreatePolicy',
            $payload,
            true,
            self::STEP_CREATE_POLICY_WITHOUT_QUOTE
        );

        if (! $response['success']) {
            $responseErrorCode = $response['errorCode'] ?? '-';
            $responseStatusMessage = $response['statusMessage'] ?? 'Unknown error';
            throw new Exception("CreatePolicyWithoutQuote API call failed: ($responseErrorCode) - $responseStatusMessage");
        }

        $responseData = $response['data'];
        if (empty($responseData->policy_no ?? null)) {
            throw new Exception('Policy number not found in CreatePolicyWithoutQuote response');
        }

        $this->policyNumber = $responseData->policy_no;

        // Update transaction status and save certificate_number to certificate_number field
        $this->updateTransactionStatus(EmbeddedTransactionEnum::STATUS_PAYMENT_SUCCEED, [
            'certificate_number' => $this->policyNumber,
        ]);
    }

    /**
     * Step 4: Sync policy documents
     *  Step 4.1: Get policy documents
     */
    private function executeSyncDocuments(array $getPolicyDocumentsResponse): void
    {
        $fetchedPolicyDocuments = collect($getPolicyDocumentsResponse ?? [])
            ->only('policy_certificate_url', 'premium_inv_doc_url', 'commision_inv_doc_url')
            ->toArray();

        if (empty($fetchedPolicyDocuments)) {
            return;
        }

        $documentTypes = DocumentType::whereIn('code', $this->reqDocTypeCodes)
            ->where('quote_type_id', $this->context->quoteTypeId)->get();

        $docStatus = ['created' => [], 'skipped' => []];
        foreach ($fetchedPolicyDocuments as $docKey => $docUrl) {

            $docCode = match ($docKey) {
                'policy_certificate_url' => QuoteDocumentsEnum::POLICY_SCHEDULE, // Policy Schedule (GETPOLICYSCHEDULE)
                'premium_inv_doc_url' => QuoteDocumentsEnum::CAR_TAX_INVOICE, // Tax Invoice (GETPOLICYTAXINVOICE - DOCTYPE=1)
                'commision_inv_doc_url' => QuoteDocumentsEnum::CAR_TAX_INVOICE_RAISE_BY_BUYER, // Tax Invoice (GETPOLICYTAXINVOICE - DOCTYPE=2)
                default => null
            };

            $documentType = $documentTypes->firstWhere('code', $docCode);
            if (empty($docCode) || empty($documentType)) {
                $docStatus['skipped'][] = "{$docKey}-{$docCode}";

                continue;
            }

            $saveDocumentResponse = $this->executeSavePolicyDocument($docUrl, $documentType);

            if (! $saveDocumentResponse['success']) {
                $docStatus['skipped'][] = "{$docKey}-{$docCode}";

                continue;
            }

            $docStatus['created'][] = "{$docKey}-{$docCode}";
        }

        $fetchedDocumentsCount = count($fetchedPolicyDocuments);
        $savedDocumentsCount = count($docStatus['created'] ?? []);
        if ($savedDocumentsCount > 0) {
            LoggerService::info("{$this->logPrefix} Documents synced: {$savedDocumentsCount} out of {$fetchedDocumentsCount}", extra: [
                ...$this->logExtra,
                'docs' => $docStatus,
            ]);
        }

        $missingDocumentDocTypes = $this->getMissingDocumentDocTypes($this->reqDocTypeCodes);
        if (empty($missingDocumentDocTypes)) {
            // Update transaction status
            $this->updateTransactionStatus(EmbeddedTransactionEnum::STATUS_BOOKED);
            dispatch(new EpWatermarkDocumentJob($this->context));
        }
    }

    private function executeUpdateCommission($policyDetailResponse): void
    {
        $policyPrice = floatval($policyDetailResponse['policy_premium_with_tax'] ?? 0);

        if (empty($policyDetailResponse) || ! ($policyPrice > 0)) {
            return;
        }

        $policyDetails = [
            'tax_invoice_no' => $policyDetailResponse['premium_inv_no'] ?? '',
            'tax_invoice_buyer_no' => $policyDetailResponse['commision_inv_no'] ?? '',
            'policy_price' => $policyPrice,
            'commission_with_vat' => $policyDetailResponse['policy_commision_with_tax'] ?? 0,
            'commission_without_vat' => $policyDetailResponse['policy_commision_without_tax'] ?? 0,
            'credit_note_buyer_no' => $policyDetailResponse['credit_note_buyer_no'] ?? '',
            'credit_note_no' => $policyDetailResponse['credit_note_no'] ?? '',
        ];

        // Update transaction commissions and policy_details
        $this->embeddedTransaction->update($policyDetails);
    }

    // Step 4.1: Get policy documents
    private function executeGetPolicyDocuments(): array
    {
        if (! $this->policyNumber) {
            throw new Exception('No policy number available for GetDocuments');
        }

        $this->executeGetToken();
        $response = $this->makeApiCall(
            'GET',
            '/api/Policy/GetPolicyDocuments',
            ['policyNumber' => $this->policyNumber],
            true,
            self::STEP_GET_POLICY_DOCUMENTS
        );

        if (! $response['success']) {
            $responseErrorCode = $response['errorCode'] ?? '-';
            $responseStatusMessage = $response['statusMessage'] ?? 'Unknown error';
            throw new Exception("GetPolicyDocuments API call failed: ($responseErrorCode) - $responseStatusMessage");
        }

        return (array) $response['data'] ?? [];
    }

    /**
     * Download document from API endpoint
     */
    private function makeDownloadApiCall(string $url, bool $isAuth, string $operation): array
    {
        $startTime = microtime(true);
        $statusCode = 0;
        $response = [];
        $fileName = '';

        try {
            $headers = $isAuth ? ['Authorization' => 'Bearer '.$this->bearerToken] : [];
            $httpClient = Http::withHeaders($headers)->timeout($this->timeout);
            $httpResponse = $httpClient->get($url);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $statusCode = $httpResponse->status();
            $content = $httpResponse->body();
            $contentType = $httpResponse->header('Content-Type') ?? '';
            $fileName = $this->makeFileNameFromUrl($url);

            if (! $httpResponse->successful() || str_contains($contentType, 'text/html')) {
                $errorMessage = ! $httpResponse->successful()
                    ? "HTTP {$statusCode}: Failed to download document"
                    : 'Document not found or server returned HTML error page';

                throw new Error($errorMessage);
            }

            if (empty($content)) {
                throw new Error('Document content is empty');
            }

            $response = [
                'success' => true,
                'filename' => $fileName,
                'content_length' => strlen($content),
                'statusCode' => $statusCode,
                'responseTime' => $responseTime,
                'content_type' => $contentType,
                'content' => $content,
            ];
        } catch (Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $response = [
                'success' => false,
                'filename' => $fileName,
                'error' => 'Download failed: '.$e->getMessage(),
                'statusCode' => $statusCode,
                'responseTime' => $responseTime,
            ];
        } finally {
            $responseLog = collect($response)->except('content')->toArray();

            // Log the API call
            $this->logApiRequest($operation, [], $responseLog);

            return $response;
        }
    }

    /**
     * Make API call with comprehensive logging and error handling
     */
    private function makeApiCall(string $method, string $endpoint, array $data, bool $isAuth, string $operation): array
    {
        $url = $this->baseUrl.$endpoint;
        $startTime = microtime(true);
        $response = [];
        $httpResponse = null;

        try {
            $headers = ['client-code' => $this->clientCode];
            $isAuth && $headers['Authorization'] = 'Bearer '.$this->bearerToken;

            $httpClient = Http::withHeaders($headers)
                ->timeout($this->timeout);

            $httpResponse = match (strtoupper($method)) {
                'POST' => $httpClient->post($url, $data),
                'GET' => $httpClient->get($url, $data),
                'PUT' => $httpClient->put($url, $data),
                'PATCH' => $httpClient->patch($url, $data),
                'DELETE' => $httpClient->delete($url, $data),
                default => throw new Exception("Unsupported HTTP method: {$method}")
            };

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $isSuccess = $httpResponse->successful();
            $responseData = $httpResponse->object();

            $contentType = $httpResponse->header('Content-Type');
            if (str_contains($contentType, 'application/json')) {
                $isSuccess = $isSuccess && ($responseData->isSuccess ?? false);
            }

            $response = [
                'success' => $isSuccess,
                'statusCode' => $httpResponse->status(),
                'responseTime' => $responseTime,
                'errorCode' => $responseData->errorCode ?? ($isSuccess ? '' : '-'),
                'statusMessage' => $responseData->statusMessage ?? ($isSuccess ? 'Success' : 'Unknown message'),
                'data' => $responseData,
            ];
        } catch (Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $response = [
                'success' => false,
                'statusCode' => $httpResponse?->status() ?? 0,
                'responseTime' => $responseTime,
                'exceptionType' => get_class($e),
                'error' => $e->getMessage(),
            ];
        } finally {
            $responseLog = $response;
            if (isset($responseLog['data']->access_token)) {
                $responseLog['data'] = clone $responseLog['data'];
                unset($responseLog['data']->access_token);
            }

            // Log the API call
            $this->logApiRequest($operation, $data, $responseLog);

            return $response;
        }
    }

    /**
     * Log API requests and responses using InsurerRequestResponse model
     */
    private function logApiRequest(
        string $operation,
        array $payload = [],
        array $responseLog = [],
        bool $isSavedInDB = true
    ): void {

        $status = ! empty($responseLog['success']) ? 'passed' : 'failed';
        $basicLogs = collect($responseLog)
            ->only('success', 'statusCode', 'errorCode', 'statusMessage', 'responseTime', 'error', 'exceptionType');

        $logData = [
            ...$this->logExtra,
            'operation' => $operation,
            ...$basicLogs,
        ];

        try {

            if ($isSavedInDB) {
                // Store API request and response in database
                InsurerRequestResponse::create([
                    'quote_uuid' => $this->quote?->uuid,
                    'provider_id' => $this->context->insuranceProviderId, // You may want to set this based on your provider mapping
                    'call_type' => 'EpEcb',
                    'request' => json_encode($payload),
                    'response' => json_encode($responseLog['data'] ?? []),
                    'status' => $status,
                    'execution_method' => $operation,
                ]);
            }

            $logAction = $isSavedInDB ? 'API' : 'Process';
            // Also log using LoggerService for additional tracking
            LoggerService::info("{$this->logPrefix} {$logAction} {$operation} logged ($status)", extra: $logData);
        } catch (Throwable $e) {
            LoggerService::error("{$this->logPrefix} Failed to log API {$operation} request ($status)", extra: [
                ...$logData,
                'logging_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate if a token is still valid
     */
    private function validateToken(string $token): bool
    {
        // You can implement token validation logic here
        // For now, we'll assume cached tokens are valid
        return ! empty($token);
    }

    /**
     * Clear token cache
     */
    private function clearTokenCache(): void
    {
        Cache::forget(self::TOKEN_CACHE_KEY);
    }

    /**
     * Update transaction status
     */
    private function updateTransactionStatus(string $status, array $data = []): void
    {
        try {
            $policyInfo = collect($data)->only('quote_policy', 'certificate_number')->toArray();
            $this->embeddedTransaction->update(['policy_status' => $status, ...$policyInfo]);

            LoggerService::info($this->logPrefix.' Transaction status updated', extra: [
                ...$this->logExtra,
                'policy_status' => $this->embeddedTransaction?->policy_status,
            ]);
        } catch (Throwable $e) {
            LoggerService::error($this->logPrefix.' Failed to update transaction status', extra: [
                ...$this->logExtra,
                'policy_status' => $this->embeddedTransaction?->policy_status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function executeSavePolicyDocument(string $docUrl, DocumentType $documentType): array
    {
        $dir = 'documents/'.$documentType->folder_path;

        // Step: Download & Upload policy document
        $documentData = $this->executeDownloadAndUploadDocument($docUrl, $dir);
        if (! $documentData['success']) {
            $this->logApiRequest('DownloadAndUploadDocument', responseLog: ['error' => $documentData['error']], isSavedInDB: false);

            return ['success' => false, 'error' => $documentData['error']];
        }

        $docUuid = $this->generateUniqueUuid();
        $documentData = [
            'original_name' => $documentData['file_name'] ?? null,
            'doc_name' => $documentData['document_name'] ?? null,
            'doc_url' => $documentData['document_url'] ?? null,
            'doc_uuid' => $docUuid,
            'document_type_code' => $documentType->code,
            'document_type_text' => $documentType->text,
            'doc_mime_type' => 'application/pdf',
            'created_by_id' => null,
            'watermarked_doc_name' => null,
            'watermarked_doc_url' => null,
        ];

        $filterDocument = [
            'document_type_code' => $documentType->code,
            'quote_documentable_id' => $this->embeddedTransaction->id,
            'quote_documentable_type' => get_class($this->embeddedTransaction),
        ];
        $this->embeddedTransaction->documents()->updateOrCreate($filterDocument, $documentData);

        return ['success' => true, 'data' => $documentData];
    }

    /**
     * Download policy documents
     */
    private function executeDownloadAndUploadDocument($docUrl, $dir) // string $docUrl, DocumentType $documentType
    {
        try {
            $this->executeGetToken();
            $downloadDocResponse = $this->makeDownloadApiCall($docUrl, true, 'DownloadPolicyDocument');

            $fileName = $downloadDocResponse['filename'] ?? '';
            $modifiedfileName = "{$this->policyNumber}_{$fileName}.pdf";
            $fileIndentifier = empty($fileName) ? $this->extractFilename($docUrl) : $modifiedfileName;

            if (! $downloadDocResponse['success'] || empty($downloadDocResponse['content'] ?? null)) {
                throw new Error($downloadDocResponse['error'] ?? "DownloadPolicyDocument API call Failed, doc_identifier: {$fileIndentifier}");
            }

            $fileContent = $downloadDocResponse['content'];
            $response = $this->uploadDocument($modifiedfileName, $fileContent, $dir);

            if (! $response['success']) {
                throw new Error($response['error'] ?? "UploadDocument: Process Failed, doc_identifier: {$fileIndentifier}");
            }

            return [
                'success' => $response['success'],
                'file_name' => $modifiedfileName,
                'document_name' => $response['data']['doc_name'] ?? null,
                'document_url' => $response['data']['doc_url'] ?? null,
            ];
        } catch (Throwable $e) {

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'statusCode' => 0,
                'exceptionType' => get_class($e),
            ];
        }
    }

    /**
     * Build quote payload based on business requirements
     */
    private function buildQuotePayload(): array
    {
        if (! $this->quote) {
            throw new Exception('Quote not found for building quote payload');
        }

        $productInfo = $this->getProductInfo(self::STEP_GET_QUOTE);
        $customerInfo = $this->getCustomerInfo(self::STEP_GET_QUOTE);
        $vehicleInfo = $this->getVehicleInfo(self::STEP_GET_QUOTE);

        return [
            'client_reference_number' => '',
            'transaction_country' => $this->transactionCountry,
            'transaction_currency' => $this->transactionCurrency,
            'product_info' => $productInfo,
            'customer_info' => $customerInfo,
            'vehicle_info' => $vehicleInfo,
        ];
    }

    /**
     * Build policy creation payload
     */
    private function buildPolicyFromQuotePayload(): array
    {
        if (! $this->quote) {
            throw new Exception('Quote not found for building policy payload');
        }

        if (! $this->embeddedTransaction) {
            throw new Exception('EmbeddedTransaction not found for building policy payload');
        }

        $salesInfo = $this->getSalesInfo(self::STEP_CREATE_POLICY_FROM_QUOTE);
        $customerInfo = $this->getCustomerInfo(self::STEP_CREATE_POLICY_FROM_QUOTE);
        $vehicleInfo = $this->getVehicleInfo(self::STEP_CREATE_POLICY_FROM_QUOTE);
        $motorInsuranceInfo = $this->getMotorInsuranceInfo();
        $documentsInfo = $this->getDocumentsInfo();

        $paymentChargeId = $this->embeddedTransaction?->paymentCharges?->first()?->transaction_id;

        return [
            'client_reference_number' => null,
            'quote_reference_number' => $this->quoteReferenceNumber,
            'transaction_country' => $this->transactionCountry,
            'payment_reference_number' => $paymentChargeId,
            'sales_info' => $salesInfo,
            'customer_info' => $customerInfo,
            'vehicle_info' => $vehicleInfo,
            'motor_insurance_info' => $motorInsuranceInfo,
            'document_info' => $documentsInfo,
        ];
    }

    private function buildPolicyWithoutQuotePayload(): array
    {
        $salesInfo = $this->getSalesInfo(self::STEP_CREATE_POLICY_WITHOUT_QUOTE);
        $productInfo = $this->getProductInfo(self::STEP_CREATE_POLICY_WITHOUT_QUOTE);
        $customerInfo = $this->getCustomerInfo(self::STEP_CREATE_POLICY_WITHOUT_QUOTE);
        $vehicleInfo = $this->getVehicleInfo(self::STEP_CREATE_POLICY_WITHOUT_QUOTE);
        $motorInsuranceInfo = $this->getMotorInsuranceInfo();
        $documentsInfo = $this->getDocumentsInfo();

        $paymentChargeId = $this->embeddedTransaction?->paymentCharges?->first()?->transaction_id;

        return [
            'client_reference_number' => '',
            'transaction_country' => $this->transactionCountry,
            'payment_reference_number' => $paymentChargeId,
            'sales_info' => $salesInfo,
            'product_info' => $productInfo,
            'vehicle_info' => $vehicleInfo,
            'customer_info' => $customerInfo,
            'motor_insurance_info' => $motorInsuranceInfo,
            'document_info' => $documentsInfo,
        ];
    }

    private function getEmirateIdNumber(): string
    {
        $latestInsuredData = $this->quote?->latestInsured;
        $insuredKyc = $latestInsuredData?->insuredKyc;

        $emirateIdNumber = str_replace('-', '', $insuredKyc?->id_type == 'emiratesId' ? $insuredKyc?->id_number : '');

        if ((! empty($emirateIdNumber)) && strlen($emirateIdNumber) == 15) {
            $emirateIdNumber = substr($emirateIdNumber, 0, 3).'-'.substr($emirateIdNumber, 3, 4)
                .'-'.substr($emirateIdNumber, 7, 7).'-'.substr($emirateIdNumber, 14, 1);
        }

        return $emirateIdNumber;
    }

    private function getDocumentsInfo(): array
    {
        $documentsInfo = \App\Models\CarQuote::whereUuid('7JCUF5NR')->first()->documents()->whereIn('document_type_code', [QuoteDocumentsEnum::CAR_EMIRATE_ID, QuoteDocumentsEnum::CAR_MULKIY])
            ->select('document_type_code', 'doc_name', 'doc_url')
            ->get()
            ->map(function ($document) {
                return [
                    'document_type' => $document->document_type_code,
                    'document_name' => $document->doc_name,
                    'document_url' => $document->document_url,
                ];
            });

        return $documentsInfo->toArray();
    }

    private function getCustomerInfo(string $step): array
    {
        $emirateIdNumber = $this->getEmirateIdNumber();
        $customerDetails = [
            'customer_type' => null,
            'customer_fname' => $this->quote?->first_name,
            'customer_lname' => $this->quote?->last_name,
            'customer_mobile_no' => null,
            'customer_whatsapp_no' => null,
            'customer_email_id' => null,
            'customer_id_type' => 'EID',
            'customer_id_no' => $emirateIdNumber,
            'customer_id_expiry_date' => null,
            'customer_address' => null,
            'customer_address_city' => null,
            'customer_address_country' => null,
            'co_buyer_name' => null,
            'co_buyer_mobile_no' => null,
            'co_buyer_whatsapp_no' => null,
            'co_buyer_email_id' => null,
            'co_buyer_id_type' => null,
            'co_buyer_id_no' => null,
            'co_buyer_id_expiry_date' => null,
        ];

        return match ($step) {
            self::STEP_GET_QUOTE => collect($customerDetails)->only('customer_type')->toArray(),
            self::STEP_CREATE_POLICY_FROM_QUOTE => collect($customerDetails)->except('customer_type')->toArray(),
            self::STEP_CREATE_POLICY_WITHOUT_QUOTE => $customerDetails,
            default => []
        };
    }

    private function getProductInfo(string $step): array
    {
        $productDetails = [
            'policy_product' => $this->policyProduct,
            'policy_coverage_type' => $this->policyProduct,
            'policy_plan_type' => 'EXW-STANDARD',
        ];

        return match ($step) {
            self::STEP_GET_QUOTE => collect($productDetails)->only('policy_product')->toArray(),
            self::STEP_CREATE_POLICY_WITHOUT_QUOTE => $productDetails,
            default => []
        };
    }

    private function getSalesInfo(string $step): array
    {
        $salesDetails = [
            'policy_sold_date' => $this->formatDate(now()),
            'policy_sold_location' => null,
            'policy_sold_salesman' => null,
            'policy_currency' => $this->transactionCurrency,
        ];

        return match ($step) {
            self::STEP_CREATE_POLICY_FROM_QUOTE => collect($salesDetails)->except('policy_currency')->toArray(),
            self::STEP_CREATE_POLICY_WITHOUT_QUOTE => $salesDetails,
            default => []
        };
    }

    private function getMotorInsuranceInfo(): array
    {
        $policyStartDate = $this->formatDate($this->quote?->policy_start_date ?? '');
        $policyEndDate = $this->formatDate($this->quote->policy_expiry_date ?? '');

        return [
            'mi_policy_number' => 'NA',
            'mi_policy_issuer' => $this->quote?->insuranceProviderDetails?->ecb_insurer_id,
            'mi_start_date' => $policyStartDate,
            'mi_end_date' => $policyEndDate,
            'mi_coverage_area' => 'NA', // "UAE & OMAN",
            'mi_sum_insured' => $this->quote?->car_value,
            'mi_policy_excess' => $this->quote?->carQuotePlanDetail?->excess ?: 0,
        ];
    }

    private function getVehicleInfo(string $step): array
    {
        $vehiclePayloadFields = $this->getVehiclePayloadFields($step);
        $vehicleDetails = $this->getVehicleDetails();

        return collect($vehicleDetails)->only($vehiclePayloadFields)->toArray();
    }

    private function getVehiclePayloadFields(string $step): array
    {
        $getQuoteFields = [
            'vehicle_type',
            'vehicle_spec',
            'vehicle_make',
            'vehicle_model',
            'vehicle_variant',
            'vehicle_cc',
            'vehicle_no_cyl',
            'vehicle_aspiration',
            'vehicle_drive_type',
            'vehicle_transmission',
            'vehicle_body_type',
            'vehicle_fuel_type',
            'vehicle_is_electric',
            'vehicle_is_hybrid',
            'vehicle_hybrid_type',
            'vehicle_first_regn_date',
            'vehicle_invoiced_date',
            'vehicle_delivery_date',
            'vehicle_model_year',
            'vehicle_current_km',
            'vehicle_purchase_price',
            'vehicle_current_value',
            'vehicle_pwi_date',
            'vehicle_pwi_km',
        ];
        $policyFromQuoteFields = [
            'vehicle_chassis_no',
            'vehicle_engine_no',
            'vehicle_plate_no',
            'vehicle_purchase_price',
            'vehicle_current_value',
            'vehicle_mw_start_date',
            'vehicle_mw_end_date',
            'vehicle_mw_start_km',
            'vehicle_mw_end_km',
            'vehicle_pwi_date',
            'vehicle_pwi_km',
        ];
        $policyWithoutQuoteFields = [
            'vehicle_type',
            'vehicle_spec',
            'vehicle_make',
            'vehicle_model',
            'vehicle_variant',
            'vehicle_cc',
            'vehicle_no_cyl',
            'vehicle_aspiration',
            'vehicle_drive_type',
            'vehicle_transmission',
            'vehicle_body_type',
            'vehicle_fuel_type',
            'vehicle_is_electric',
            'vehicle_is_hybrid',
            'vehicle_hybrid_type',
            'vehicle_first_regn_date',
            'vehicle_invoiced_date',
            'vehicle_delivery_date',
            'vehicle_model_year',
            'vehicle_current_km',
            'vehicle_chassis_no',
            'vehicle_plate_no',
            'vehicle_purchase_price',
            'vehicle_current_value',
        ];

        return match ($step) {
            self::STEP_GET_QUOTE => $getQuoteFields,
            self::STEP_CREATE_POLICY_FROM_QUOTE => $policyFromQuoteFields,
            self::STEP_CREATE_POLICY_WITHOUT_QUOTE => $policyWithoutQuoteFields,
            default => []
        };
    }

    private function getVehicleDetails(): array
    {
        $vehicleFirstRegnDate = $this->quote?->year_of_first_registration;
        $vehicleFirstRegnDate = $this->formatDate(! empty($vehicleFirstRegnDate) ? $vehicleFirstRegnDate.'-01-01' : '');

        return [
            // Only for CreatePolicyFromQuote, CreatePolicyWithoutQuote
            'vehicle_chassis_no' => $this->quote?->carQuoteRequestDetail?->chassis_number,

            'vehicle_make' => $this->quote?->carMake?->text ?? null,
            'vehicle_model' => $this->quote?->carModel?->text ?? null,
            'vehicle_model_year' => $this->quote?->year_of_manufacture ?? null,
            'vehicle_first_regn_date' => $vehicleFirstRegnDate,

            'vehicle_aspiration' => null,
            'vehicle_body_type' => null,
            'vehicle_cc' => null,
            'vehicle_current_km' => null,
            'vehicle_current_value' => null,
            'vehicle_delivery_date' => null,
            'vehicle_drive_type' => null,
            'vehicle_fuel_type' => null,
            'vehicle_hybrid_type' => null,
            'vehicle_invoiced_date' => null,
            'vehicle_is_electric' => null,
            'vehicle_is_hybrid' => null,
            'vehicle_no_cyl' => null,
            'vehicle_purchase_price' => null,
            'vehicle_plate_no' => null,
            'vehicle_spec' => null,
            'vehicle_transmission' => null,
            'vehicle_type' => null,
            'vehicle_variant' => null,

            // Only for GetQuote, CreatePolicyFromQuote
            'vehicle_pwi_date' => null,
            'vehicle_pwi_km' => null,

            // Only for CreatePolicyFromQuote
            'vehicle_engine_no' => null,
            'vehicle_mw_end_date' => null,
            'vehicle_mw_end_km' => null,
            'vehicle_mw_start_date' => null,
            'vehicle_mw_start_km' => null,
        ];
    }

    /**
     * Generates a unique UUID for the given document.
     *
     * @return string The generated UUID.
     */
    private function generateUniqueUuid()
    {
        do {
            $uuid = uniqid();
        } while (QuoteDocument::where('doc_uuid', $uuid)->exists());

        return $uuid;
    }

    public function formatDate(string $date): ?string
    {
        return ! empty($date) ? date('Y-m-d', strtotime($date)) : null;
    }
}
