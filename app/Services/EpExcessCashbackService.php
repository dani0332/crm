<?php

namespace App\Services;

use App\DTO\EpBookingContext;
use App\Enums\EmbeddedTransactionEnum;
use App\Enums\InsuranceProviderEnum;
use App\Enums\QuoteDocumentsEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\SendPolicyTypeEnum;
use App\Models\DocumentType;
use App\Jobs\EpPurchaseFlowJob;
use App\Jobs\EpWatermarkDocumentJob;
use App\Jobs\EpSendDocumentJob;
use App\Mail\EpFailureNotification;
use App\Models\InsurerRequestResponse;
use App\Models\EmbeddedTransaction;
use App\Models\InsuranceProvider;
use App\Models\QuoteDocument;
use App\Services\Logger\LoggerService;
use App\Services\SageApiService;
use App\Services\SageApiEmbeddedProductService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Bus;
use Carbon\Carbon;
use Error;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EpExcessCashbackService extends EpBookingService
{
    public mixed $quote = null;
    private int $providerId;
    private EmbeddedTransaction $embeddedTransaction;

    // API Configuration
    private string $baseUrl;
    private string $clientCode;
    private string $clientId;
    private string $clientSecret;
    private int $timeout;

    // Process State
    private ?string $bearerToken = null;
    private ?string $quoteReferenceNumber = null;
    private ?string $policyNumber = null;
    public array $reqDocTypeCodes = [];

    // Cache Keys
    private const TOKEN_CACHE_KEY = 'tpa_client_api_token';
    private const TOKEN_CACHE_DURATION = 3600; // 1 hour

    private string $transactionCountry = 'UAE';
    private string $transactionCurrency = 'AED';
    private string $policyProduct = 'EXW';

    /**
     * Create a new class instance.
     */
    public function __construct(
        EpBookingContext $context
    ) {
        parent::__construct('EpEcb', $context);
        $this->reqDocTypeCodes = $this->getRequiredDocTypeCodes();

        $quoteType = QuoteTypes::getName($this->context->quoteTypeId)->value;
        $this->quote = $this->getQuoteObject($quoteType, $this->context->quoteId);
    }

    public function init(): void
    {
        $this->logExtra = [
            'etId' => $this->context->etId,
            'quoteId' => $this->context->quoteId,
            'quoteTypeId' => $this->context->quoteTypeId,
            'quoteUUID' => $this->context->quoteUUID,
        ];

        // Load API configuration
        $this->loadApiConfiguration();

        // Load embedded transaction
        $this->embeddedTransaction = EmbeddedTransaction::findOrFail($this->context->etId);

        $this->providerId = InsuranceProvider::where('code', InsuranceProviderEnum::NGI->value)->value('id');

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
        if (!empty($this->embeddedTransaction->quote_policy)) {
            $this->quoteReferenceNumber = $this->embeddedTransaction->quote_policy;
        }

        // Restore policy_number from certificate_number field
        if (!empty($this->embeddedTransaction->certificate_number)) {
            $this->policyNumber = $this->embeddedTransaction->certificate_number;
        }

        // Try to restore bearer token from cache if available
        $cachedToken = Cache::get(self::TOKEN_CACHE_KEY);
        if ($cachedToken) {
            $this->bearerToken = $cachedToken;
        }

        LoggerService::info($this->logPrefix . ' Workflow state restoration completed', extra: [
            ...$this->logExtra,
            'has_bearer_token' => !empty($this->bearerToken),
            'has_quote_reference' => !empty($this->quoteReferenceNumber),
            'has_policy_number' => !empty($this->policyNumber),
            'current_status' => $this->embeddedTransaction->status
        ]);
    }

    /**
     * Load API configuration from config/services.php
     */
    private function loadApiConfiguration(): void
    {
        $config = config('services.tpa_client_api');

        $this->baseUrl = $config['base_url'];
        $this->clientCode = $config['client_code'];
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->timeout = $config['timeout'];
    }

    /**
     * Process all EP Excess Cashback workflow using job chain
     */
    public static function epEcbWorkflow(EpBookingContext $context): void
    {
        $logExtra = [
            'etId' => $context->etId,
            'quoteId' => $context->quoteId,
            'quoteTypeId' => $context->quoteTypeId,
            'quoteUUID' => $context->quoteUUID
        ];

        LoggerService::info('EpEcbService: Starting EP ExcessCashback workflow', extra: $logExtra);

        Bus::chain([
            new EpPurchaseFlowJob($context),
            new EpWatermarkDocumentJob($context),
            new EpSendDocumentJob($context)
        ])
            // Chain will stop on first failure by default
            ->catch(function (Throwable $e) use ($logExtra) {
                LoggerService::error('EpEcbService: Workflow chain failed', extra: [
                    ...$logExtra,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                Mail::send(new EpFailureNotification($this->context->quoteId, $this->context->quoteTypeId, $this->context->etId));
                LoggerService::info("{$this->logPrefix} - Send EP failure notification email successfully");
            })
            ->dispatch();

        LoggerService::info('EpEcbService: Workflow chain dispatched successfully', extra: $logExtra);
    }

    /**
     * Main entry point for processing the purchase flow
     * Throws exceptions on failure so the job retry mechanism can handle them
     */
    public function executeSteps(): void
    {
        try {
            LoggerService::info($this->logPrefix . ' Starting purchase flow', extra: [
                ...$this->logExtra,
                'current_status' => $this->embeddedTransaction->status
            ]);

            // Execute workflow with conditional step execution
            // Any exceptions will bubble up to the job for automatic retry handling
            $this->executeWorkflowFromStep();

            LoggerService::info($this->logPrefix . ' Purchase flow completed successfully', extra: $this->logExtra);
        } catch (Exception $e) {
            LoggerService::error($this->logPrefix . ' Purchase flow failed', extra: [
                ...$this->logExtra,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'exceptionType' => get_class($e),
            ]);
            // throw $e;
        }
    }

    /**
     * Execute the workflow sequentially with clear step-by-step logic
     */
    private function executeWorkflowFromStep(): void
    {
        $currentStatus = $this->embeddedTransaction->status ?? '';

        // Step 1: Get Token (Always required first)
        if (!$this->shouldSkipStep('get_token', $currentStatus)) {
            LoggerService::info($this->logPrefix . " Executing step: GetToken", extra: $this->logExtra);

            $this->executeGetToken();

            LoggerService::info($this->logPrefix . " Step completed: GetToken", extra: $this->logExtra);
        } else {
            LoggerService::info($this->logPrefix . " Skipping step: GetToken", extra: $this->logExtra);
        }

        // Step 2: Get Quote
        if (!$this->shouldSkipStep('get_quote', $currentStatus)) {
            LoggerService::info($this->logPrefix . " Executing step: GetQuote", extra: $this->logExtra);

            $this->executeGetQuote();

            LoggerService::info($this->logPrefix . " Step completed: GetQuote", extra: $this->logExtra);
        } else {
            LoggerService::info($this->logPrefix . " Skipping step: GetQuote - already have quote", extra: $this->logExtra);
        }

        // Step 3: Create Policy From Quote
        if (!$this->shouldSkipStep('create_policy', $currentStatus)) {
            LoggerService::info($this->logPrefix . " Executing step: CreatePolicyFromQuote", extra: $this->logExtra);

            $this->executeCreatePolicy();

            LoggerService::info($this->logPrefix . " Step completed: CreatePolicyFromQuote", extra: $this->logExtra);
        } else {
            LoggerService::info($this->logPrefix . " Skipping step: CreatePolicyFromQuote - already have policy", extra: $this->logExtra);
        }
    }

    /**
     * Sync policy documents (called by EpExcessCashbackSyncDocumentJob)
     */
    public function syncPolicyDocuments(): void
    {
        LoggerService::info($this->logPrefix . ' Starting document sync process', extra: $this->logExtra);

        $currentStatus = $this->embeddedTransaction->status ?? '';

        // Step 1: Get policy documents
        if (!$this->shouldSkipStep('get_documents', $currentStatus)) {
            LoggerService::info($this->logPrefix . " Executing step: GetPolicyDocuments", extra: $this->logExtra);

            // Get policy documents
            $getPolicyDocumentsResponse = (array) $this->executeGetPolicyDocuments();

            // Download, Upload & Save policy documents to DB
            $this->executeSyncDocuments($getPolicyDocumentsResponse);

            LoggerService::info($this->logPrefix . " Step completed: GetPolicyDocuments", extra: $this->logExtra);
        } else {
            LoggerService::info($this->logPrefix . " Skipping step: GetPolicyDocuments - already completed", extra: $this->logExtra);
        }

        // Step 2: Update commission if needed
        if (!$this->shouldSkipStep('prepare_for_sage', $currentStatus)) {
            $this->executeUpdateCommission($getPolicyDocumentsResponse ?? []);
        }

        LoggerService::info($this->logPrefix . ' Document sync completed successfully', extra: $this->logExtra);
    }

    /**
     * Determine if a step should be skipped based on current transaction status
     */
    private function shouldSkipStep(string $step, string $currentStatus): bool
    {
        return match ($step) {
            'get_token' => false, // Always need token first
            'get_quote' => in_array($currentStatus, [
                EmbeddedTransactionEnum::STATUS_QUOTED,
                EmbeddedTransactionEnum::STATUS_PAYMENT_SUCCEED,
                EmbeddedTransactionEnum::STATUS_BOOKED,
                EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE
            ]),
            'create_policy' => in_array($currentStatus, [
                EmbeddedTransactionEnum::STATUS_PAYMENT_SUCCEED,
                EmbeddedTransactionEnum::STATUS_BOOKED,
                EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE
            ]),
            'get_documents' => in_array($currentStatus, [
                EmbeddedTransactionEnum::STATUS_BOOKED,
                EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE
            ]),
            'prepare_for_sage' => in_array($currentStatus, [
                EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE
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
            LoggerService::info($this->logPrefix . ' Using cached token', extra: $this->logExtra);
            return;
        }

        $payload = [
            'client_code' => $this->clientCode,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ];

        $response = $this->makeApiCall(
            'POST',
            '/api/Auth/GetToken',
            $payload,
            ['client-code' => $this->clientCode],
            'GetToken'
        );

        if (!$response['success']) {
            throw new Exception("GetToken API call failed: " . ($response['error'] ?? 'Unknown error'));
        }

        $responseData = $response['data'];

        if (!isset($responseData->access_token)) {
            throw new Exception('Token not found in GetToken response');
        }

        $this->bearerToken = $responseData->access_token;

        // Cache the token
        Cache::put(self::TOKEN_CACHE_KEY, $this->bearerToken, self::TOKEN_CACHE_DURATION);

        LoggerService::info($this->logPrefix . ' Token retrieved successfully', extra: $this->logExtra);
    }

    /**
     * Step 2: Get quote
     */
    private function executeGetQuote(): void
    {
        if (!$this->bearerToken) {
            throw new Exception('No bearer token available for GetQuote');
        }

        // Check if we already have a restored quote reference number
        if (!empty($this->quoteReferenceNumber)) {
            LoggerService::info($this->logPrefix . ' Using restored quote_policy, skipping GetQuote API call', extra: [
                ...$this->logExtra,
                'restored_quote_policy' => $this->quoteReferenceNumber
            ]);
            return;
        }

        // Build quote request payload based on your business requirements
        $payload = $this->buildQuotePayload();

        $response = $this->makeApiCall(
            'POST',
            '/api/Quote/GetQuote',
            $payload,
            [
                'client-code' => $this->clientCode,
                'Authorization' => 'Bearer ' . $this->bearerToken
            ],
            'GetQuote'
        );

        if (!$response['success']) {
            throw new Exception("GetQuote API call failed: " . ($response['error'] ?? 'Unknown error'));
        }

        $responseQuote = $response['data']->quotes[0] ?? null;
        if (empty($responseQuote->quote_reference_no ?? null)) {
            throw new Exception('Quote reference number not found in GetQuote response');
        }

        $this->quoteReferenceNumber = $responseQuote->quote_reference_no;

        // Update transaction status and save quote_reference_number to quote_policy field
        $this->updateTransactionStatus(EmbeddedTransactionEnum::STATUS_QUOTED, [
            'quote_policy' => $this->quoteReferenceNumber
        ]);

        LoggerService::info($this->logPrefix . ' Quote retrieved successfully', extra: [
            ...$this->logExtra,
            'quote_policy' => $this->quoteReferenceNumber
        ]);
    }

    /**
     * Step 3: Create policy from quote
     */
    private function executeCreatePolicy(): void
    {
        if (!$this->bearerToken) {
            throw new Exception('No bearer token available for CreatePolicy');
        }

        if (!$this->quoteReferenceNumber) {
            throw new Exception('No quote reference number available for CreatePolicy');
        }

        // Check if we already have a restored policy number
        if (!empty($this->policyNumber)) {
            LoggerService::info($this->logPrefix . ' Using restored certificate_number, skipping CreatePolicyFromQuote API call', extra: [
                ...$this->logExtra,
                'restored_certificate_number' => $this->policyNumber
            ]);
            return;
        }

        // Build policy creation payload
        $payload = $this->buildPolicyPayload();

        $response = $this->makeApiCall(
            'POST',
            '/api/Policy/CreatePolicyFromQuote',
            $payload,
            [
                'client-code' => $this->clientCode,
                'Authorization' => 'Bearer ' . $this->bearerToken
            ],
            'CreatePolicyFromQuote'
        );

        if (!$response['success']) {
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
            'certificate_number' => $this->policyNumber
        ]);

        LoggerService::info($this->logPrefix . ' Policy created successfully', extra: [
            ...$this->logExtra,
            'certificate_number' => $this->policyNumber
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

            if (!$saveDocumentResponse['success']) {
                $docStatus['skipped'][] = "{$docKey}-{$docCode}";
                continue;
            }

            $docStatus['created'][] = "{$docKey}-{$docCode}";
        }

        $fetchedDocumentsCount = count($fetchedPolicyDocuments);
        $savedDocumentsCount = count($docStatus['created'] ?? []);
        LoggerService::info("{$this->logPrefix} Sync Policy documents: {$savedDocumentsCount} out of {$fetchedDocumentsCount}", extra: [
            ...$this->logExtra,
            'certificate_number' => $this->policyNumber,
            'docs' => $docStatus
        ]);

        $savedDocumentDocTypes = $this->embeddedTransaction?->documents?->pluck('document_type_code')->toArray();
        $missingDocumentDocTypes = array_diff($this->reqDocTypeCodes, $savedDocumentDocTypes);

        if (empty($missingDocumentDocTypes)) {
            // Update transaction status
            $this->updateTransactionStatus(EmbeddedTransactionEnum::STATUS_BOOKED);
        }
    }

    private function executeUpdateCommission($policyDetailResponse): void
    {
        $policyPrice = floatval($policyDetailResponse['policy_premium_with_tax'] ?? 0);
        $policyDetails = [
            'tax_invoice_no' => $policyDetailResponse['premium_inv_no'] ?? '',
            'tax_invoice_buyer_no' => $policyDetailResponse['commision_inv_no'] ?? '',
            'policy_price' => $policyPrice,
            'commission_with_vat' => $policyDetailResponse['policy_commision_with_tax'] ?? 0,
            'commission_without_vat' => $policyDetailResponse['policy_commision_without_tax'] ?? 0,
            'credit_note_buyer_no' => $policyDetailResponse['credit_note_buyer_no'] ?? '',
            'credit_note_no' => $policyDetailResponse['credit_note_no'] ?? '',
        ];

        $isPolicyBooked = $this->embeddedTransaction?->policy_status == EmbeddedTransactionEnum::STATUS_BOOKED;
        if ($isPolicyBooked && $policyPrice > 0) {
            $policyDetails = ['policy_status' => EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE, ...$policyDetails];
        }

        // Update transaction status and commissions
        $this->embeddedTransaction->update($policyDetails);

        LoggerService::info($this->logPrefix . ' Transaction status updated', extra: [
            ...$this->logExtra,
            'policy_status' => $this->embeddedTransaction?->policy_status
        ]);
    }

    // Step 4.1: Get policy documents
    private function executeGetPolicyDocuments(): array
    {
        if (!$this->policyNumber) {
            throw new Exception('No policy number available for GetDocuments');
        }

        $response = $this->makeApiCall(
            'GET',
            '/api/Policy/GetPolicyDocuments',
            ['policyNumber' => $this->policyNumber],
            [
                'client-code' => $this->clientCode,
                'Authorization' => 'Bearer ' . $this->bearerToken
            ],
            'GetPolicyDocuments'
        );

        if (!$response['success']) {
            $responseErrorCode = $response['errorCode'] ?? '-';
            $responseStatusMessage = $response['statusMessage'] ?? 'Unknown error';
            throw new Exception("GetPolicyDocuments API call failed: ($responseErrorCode) - $responseStatusMessage");
        }

        return (array) $response['data'] ?? [];
    }

    /**
     * Download document from API endpoint
     */
    private function makeDownloadApiCall(string $url, array $headers, string $operation): array
    {
        $startTime = microtime(true);
        $statusCode = 0;
        $response = [];

        try {
            $httpClient = Http::withHeaders($headers)->timeout($this->timeout);
            $httpResponse = $httpClient->get($url);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $statusCode = $httpResponse->status();
            $content = $httpResponse->body();
            $contentType = $httpResponse->header('Content-Type') ?? '';

            if (!$httpResponse->successful() || str_contains($contentType, 'text/html')) {
                $errorMessage = !$httpResponse->successful()
                    ? "HTTP {$statusCode}: Failed to download document"
                    : "Document not found or server returned HTML error page";

                throw new Error($errorMessage);
            }

            if (empty($content)) {
                throw new Error('Document content is empty');
            }

            $response = [
                'success' => true,
                'filename' => $this->extractFilename($url),
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
                'error' => "Download failed: " . $e->getMessage(),
                'statusCode' => $statusCode,
                'responseTime' => $responseTime
            ];
        } finally {
            $responseLog = collect($response)->except('content')->toArray();

            // Log the API call
            $this->logApiRequest($operation, $url, [], $responseLog);
            return $response;
        }
    }

    /**
     * Make API call with comprehensive logging and error handling
     */
    private function makeApiCall(string $method, string $endpoint, array $data, array $headers, string $operation): array
    {
        $url = $this->baseUrl . $endpoint;
        $startTime = microtime(true);
        $response = [];
        $httpResponse = null;

        try {
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
                'statusMessage' => $responseData->statusMessage ?? ($isSuccess ? 'Success' : 'Unknown error'),
                'data' => $responseData
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
                $responseLog['data']->access_token = substr($responseLog['data']->access_token, 0, 50) . '********';
            }

            // Log the API call
            $this->logApiRequest($operation, $url, $data, $responseLog);
            return $response;
        }
    }

    /**
     * Log API requests and responses using InsurerRequestResponse model
     */
    private function logApiRequest(
        string $operation,
        string $url = '',
        array $payload = [],
        array $responseLog = [],
        bool $isSavedInDB = true
    ): void {

        $status = !empty($responseLog['success']) ? 'passed' : 'failed';
        $basicLogs = collect($responseLog)
            ->only('success', 'statusCode', 'errorCode', 'statusMessage', 'responseTime', 'error', 'exceptionType');

        $logData = [
            ...$this->logExtra,
            'operation' => $operation,
            ...$basicLogs,
            'url' => $url,
            'payload' => json_encode($payload),
            ...$responseLog,
        ];

        try {

            if ($isSavedInDB) {
                // Store API request and response in database
                InsurerRequestResponse::create([
                    'quote_uuid' => $this->context->quoteUUID,
                    'provider_id' => $this->providerId, // You may want to set this based on your provider mapping
                    'call_type' => "EpEcb",
                    'request' => json_encode($payload),
                    'response' => json_encode($responseLog['data'] ?? []),
                    'status' => $status,
                    'execution_method' => $operation
                ]);
            }

            // Also log using LoggerService for additional tracking
            LoggerService::info($this->logPrefix . " API {$operation} logged ($status)", extra: $logData);
        } catch (Throwable $e) {
            LoggerService::error($this->logPrefix . " Failed to log API {$operation} request ($status)", extra: [
                ...$logData,
                'logging_error' => $e->getMessage()
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
        return !empty($token);
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

            LoggerService::info($this->logPrefix . ' Transaction status updated', extra: [
                ...$this->logExtra,
                'policy_status' => $this->embeddedTransaction?->policy_status
            ]);
        } catch (Throwable $e) {
            LoggerService::error($this->logPrefix . ' Failed to update transaction status', extra: [
                ...$this->logExtra,
                'policy_status' => $this->embeddedTransaction?->policy_status,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function executeSavePolicyDocument(string $docUrl, DocumentType $documentType): array
    {
        $dir = 'documents/' . $documentType->folder_path;

        // Step: Download & Upload policy document
        $documentData = $this->executeDownloadAndUploadDocument($docUrl, $dir);
        if (!$documentData['success']) {
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

        $this->embeddedTransaction->documents()->create($documentData);

        return ['success' => true, 'data' => $documentData];
    }

    /**
     * Download policy documents
     */
    private function executeDownloadAndUploadDocument($docUrl, $dir) // string $docUrl, DocumentType $documentType
    {
        try {
            $downloadDocResponse = $this->makeDownloadApiCall(
                $docUrl,
                ['Authorization' => 'Bearer ' . $this->bearerToken],
                'DownloadPolicyDocument'
            );

            $fileName = "{$this->context->quoteUUID}_{$this->policyNumber}-{$downloadDocResponse['filename']}";
            $fileContent = $downloadDocResponse['content'];

            if (!$downloadDocResponse['success']) {
                throw new Error($downloadDocResponse['error'] ?? "DownloadPolicyDocument API call Failed, doc_name: {$fileName}");
            }

            $response = $this->uploadDocument($fileName, $fileContent, $dir);

            if (!$response['success']) {
                throw new Error($response['error'] ?? "UploadDocument Process Failed, doc_name: {$fileName}");
            }

            return [
                'success' => $response['success'],
                'file_name' => $fileName,
                'document_name' => $response['data']['doc_name'] ?? null,
                'document_url' => $response['data']['doc_url'] ?? null
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
        $vehicleFirstRegnDate = $this->quote?->year_of_first_registration;
        $vehicleFirstRegnDate = $this->formatDate(!empty($vehicleFirstRegnDate) ? $vehicleFirstRegnDate . '-01-01' : '');

        return [
            'client_reference_number' => "",
            'transaction_country' => $this->transactionCountry,
            'transaction_currency' => $this->transactionCurrency,
            'product_info' => [
                'policy_product' => $this->policyProduct
            ],
            'customer_info' => [
                'customer_type' => ""
            ],
            'vehicle_info' => [
                'vehicle_type' => null,
                'vehicle_spec' => null,
                'vehicle_make' => $this->quote?->carMake?->text ?? null,
                'vehicle_model' => $this->quote?->carModel?->text ?? null,
                'vehicle_variant' => null,
                'vehicle_cc' => null,
                'vehicle_no_cyl' => null,
                'vehicle_aspiration' => null,
                'vehicle_drive_type' => null,
                'vehicle_transmission' => null,
                'vehicle_body_type' => null,
                'vehicle_fuel_type' => null,
                'vehicle_is_electric' => null,
                'vehicle_is_hybrid' => null,
                'vehicle_hybrid_type' => null,
                'vehicle_first_regn_date' => $vehicleFirstRegnDate,
                'vehicle_invoiced_date' => null,
                'vehicle_delivery_date' => null,
                'vehicle_model_year' => $this->quote?->year_of_manufacture ?? null,
                'vehicle_current_km' => null,
                'vehicle_purchase_price' => null,
                'vehicle_current_value' => null,
                'vehicle_pwi_date' => null,
                'vehicle_pwi_km' => null
            ]
        ];
    }

    public function handleJobSuccess()
    {
        $response = [];

        $quoteStatusId = $this->quote?->quote_status_id;
        $epPolicyStatus = $this->embeddedTransaction?->policy_status;

        LoggerService::info("{$this->logPrefix} Begin handleJobSuccess: QuoteStatusId: {$quoteStatusId}, EpPolicyStatus: {$epPolicyStatus}");

        if ($epPolicyStatus == EmbeddedTransactionEnum::STATUS_READY_FOR_SAGE) {
            $response = match ($quoteStatusId) {
                QuoteStatusEnum::PolicyIssued => $this->callSageBookingProcess(),
                QuoteStatusEnum::PolicyBooked => $this->scheduleSageBookingForSukoonEp(),
                default => ['status' => true, 'message' => 'Sage booking is not called'],
            };
        }

        LoggerService::info("{$this->logPrefix} Finish handleJobSuccess: QuoteStatusId: {$quoteStatusId}, EpPolicyStatus: {$epPolicyStatus}", extra: ['response' => $response]);

        return $response;
    }

    private function callSageBookingProcess()
    {
        $quoteType = QuoteTypes::getName($this->context->quoteTypeId)->value;

        $sageApiService = (new SageApiService);
        $sageApiService->updateAndLogQuoteStatus($this->quote, $this->context?->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_QUEUED, null);

        $request = new \stdClass;
        $request->quote_id = $this->quote?->id;
        $request->modelType = $quoteType;
        $request->model_type = $quoteType;
        $request->is_send_policy = false;
        $request->send_policy_type = SendPolicyTypeEnum::SAGE;
        $request->transaction_payment_status = null;

        $createSageProcessResponse = $sageApiService->postBookPolicyToSage($request, $this->quote);

        if (! $createSageProcessResponse['status']) {
            $sageApiService->updateAndLogQuoteStatus($this->quote, $this->context?->quoteTypeId, QuoteStatusEnum::POLICY_BOOKING_FAILED, null);
        }

        return $createSageProcessResponse;
    }

    private function scheduleSageBookingForSukoonEp()
    {
        $quoteType = QuoteTypes::getName($this->context->quoteTypeId)->value;

        $request = [
            'epTransactionId' => $this->embeddedTransaction->id, // embedded_transaction_id
            'insuranceProviderId' => $this->providerId, // embedded_product's provider_id
            'modelType' => $quoteType, // main-lead quote_type
            'quoteId' => $this->quote?->id, // main-lead quote_id
        ];

        $scheduledBookingResponse = (new SageApiEmbeddedProductService)->scheduleBookingOfEmbeddedProduct($request);

        return $scheduledBookingResponse;
    }

    /**
     * Build policy creation payload
     */
    private function buildPolicyPayload(): array
    {
        $latestInsuredData = $this->quote?->latestInsured;
        $insuredKyc = $latestInsuredData?->insuredKyc;

        $emirateIdNumber = str_replace('-', '', $insuredKyc?->id_type == 'emiratesId' ? $insuredKyc?->id_number : '');

        if ((! empty($emirateIdNumber)) && strlen($emirateIdNumber) == 15) {
            $emirateIdNumber = substr($emirateIdNumber, 0, 3) . '-' . substr($emirateIdNumber, 3, 4)
                . '-' . substr($emirateIdNumber, 7, 7) . '-' . substr($emirateIdNumber, 14, 1);
        }

        $storageBaseUrl = config('constants.AZURE_IM_STORAGE_URL') . config('constants.AZURE_IM_STORAGE_CONTAINER') . '/';
        $mulkiyaDocuments = $this->quote?->documents()->where('document_type_code', QuoteDocumentsEnum::CAR_MULKIY)
            ->select('document_type_code as document_type', 'doc_name as document_name', 'doc_url')
            ->get()
            ->map(function ($document) use ($storageBaseUrl) {
                // Add storage base URL prefix if doc_url is not empty
                if (!empty($document->doc_url)) {
                    $document->document_url = $storageBaseUrl . $document->doc_url;
                } else {
                    $document->document_url = '';
                }
                // Remove the original doc_url field
                unset($document->doc_url);
                return $document;
            });

        $policyStartDate = $this->formatDate($this->quote?->policy_start_date ?? '');

        $policyEndDate = $this->formatDate($this->quote->policy_expiry_date ?? '');

        return [
            'client_reference_number' => null,
            'quote_reference_number' => $this->quoteReferenceNumber,
            'transaction_country' => $this->transactionCountry,
            'sales_info' => [
                'policy_sold_date' => $policyStartDate,
                'policy_sold_location' => null,
                'policy_sold_salesman' => null
            ],
            'customer_info' => [
                'customer_fname' => $this->quote?->first_name,
                'customer_lname' => $this->quote?->last_name,
                'customer_mobile_no' => null,
                'customer_whatsapp_no' => null,
                'customer_email_id' => null,
                'customer_id_type' => "EID",
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
                'co_buyer_id_expiry_date' => null
            ],
            'vehicle_info' => [
                'vehicle_chassis_no' => $this->quote?->carQuoteRequestDetail?->chassis_number,
                'vehicle_engine_no' => null,
                'vehicle_plate_no' => null,
                'vehicle_purchase_price' => null,
                'vehicle_current_value' => null,
                'vehicle_mw_start_date' => null,
                'vehicle_mw_end_date' => null,
                'vehicle_mw_start_km' => null,
                'vehicle_mw_end_km' => null,
                'vehicle_pwi_date' => null,
                'vehicle_pwi_km' => null
            ],
            'motor_insurance_info' => [
                'mi_policy_number' => "NA",
                'mi_policy_issuer' => $this->quote?->insuranceProviderDetails?->ecb_insurer_id ?? 3,
                'mi_start_date' => $policyStartDate,
                'mi_end_date' => $policyEndDate,
                'mi_coverage_area' => "NA", // "UAE & OMAN",
                'mi_sum_insured' => $this->quote?->car_value,
                'mi_policy_excess' => 100
            ],
            'document_info' => $mulkiyaDocuments
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
        return !empty($date) ? date('Y-m-d', strtotime($date)) : null;
    }
}
