<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EmbeddedTransactionEnum;
use App\Models\InsurerRequestResponse;
use App\Models\EmbeddedTransaction;
use App\Services\Logger\LoggerService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Throwable;

class EpExcessCashbackService
{
    private string $quoteId;
    private int $quoteTypeId;
    private int $etId;

    private EmbeddedTransaction $embeddedTransaction;
    private string $quoteUUID;
    private int $providerId;

    private string $logPrefix = 'EpExcessCashback - Service:';
    private array $logExtra = [];

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
    private array $policyDocuments = [];

    // Cache Keys
    private const TOKEN_CACHE_KEY = 'tpa_client_api_token';
    private const TOKEN_CACHE_DURATION = 3600; // 1 hour

    /**
     * Create a new class instance.
     */
    public function __construct(string $quoteId, int $quoteTypeId, int $etId)
    {
        $this->quoteId = $quoteId;
        $this->quoteTypeId = $quoteTypeId;
        $this->etId = $etId;

        $this->logExtra = [
            'quoteId' => $this->quoteId,
            'quoteTypeId' => $this->quoteTypeId,
            'etId' => $this->etId,
        ];

        // Load API configuration
        $this->loadApiConfiguration();

        // Load embedded transaction
        $this->embeddedTransaction = EmbeddedTransaction::findOrFail($this->etId);
        $this->quoteUUID = $this->embeddedTransaction->quoteRequest->uuid ?? '';

        // TODO::Need to make it dynamic
        $this->providerId = 10;

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
     * Main entry point for processing the purchase flow
     * Throws exceptions on failure so the job retry mechanism can handle them
     */
    public function processPurchaseFlow(): void
    {
        LoggerService::info($this->logPrefix . ' Starting purchase flow', extra: [
            ...$this->logExtra,
            'current_status' => $this->embeddedTransaction->status
        ]);

        // Execute workflow with conditional step execution
        // Any exceptions will bubble up to the job for automatic retry handling
        $this->executeWorkflowFromStep();

        LoggerService::info($this->logPrefix . ' Purchase flow completed successfully', extra: $this->logExtra);
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

        // Step 4: Get Policy Documents
        if (!$this->shouldSkipStep('get_documents', $currentStatus)) {
            LoggerService::info($this->logPrefix . " Executing step: GetPolicyDocuments", extra: $this->logExtra);

            $this->executeGetDocuments();

            LoggerService::info($this->logPrefix . " Step completed: GetPolicyDocuments", extra: $this->logExtra);
        } else {
            LoggerService::info($this->logPrefix . " Skipping step: GetPolicyDocuments - already completed", extra: $this->logExtra);
        }

        // All steps completed successfully
        $this->updateTransactionStatus(EmbeddedTransactionEnum::STATUS_BOOKED);
        LoggerService::info($this->logPrefix . ' Purchase flow completed successfully', extra: $this->logExtra);
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
                EmbeddedTransactionEnum::STATUS_BOOKED
            ]),
            'create_policy' => in_array($currentStatus, [
                EmbeddedTransactionEnum::STATUS_PAYMENT_SUCCEED,
                EmbeddedTransactionEnum::STATUS_BOOKED
            ]),
            'get_documents' => in_array($currentStatus, [
                EmbeddedTransactionEnum::STATUS_BOOKED
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
        $this->embeddedTransaction->update([
            'policy_status' => EmbeddedTransactionEnum::STATUS_QUOTED,
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
        $this->embeddedTransaction->update([
            'policy_status' => EmbeddedTransactionEnum::STATUS_PAYMENT_SUCCEED,
            'certificate_number' => $this->policyNumber
        ]);

        LoggerService::info($this->logPrefix . ' Policy created successfully', extra: [
            ...$this->logExtra,
            'certificate_number' => $this->policyNumber
        ]);
    }

    /**
     * Step 4: Get policy documents
     */
    private function executeGetDocuments(): void
    {
        if (!$this->bearerToken) {
            throw new Exception('No bearer token available for GetDocuments');
        }

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

        $this->policyDocuments = collect($response['data'])
            ->only('policy_certificate_url', 'premium_inv_doc_url', 'commision_inv_doc_url')
            ->toArray();

        LoggerService::info($this->logPrefix . ' Policy documents retrieved successfully', extra: [
            ...$this->logExtra,
            'policy_number' => $this->policyNumber,
            'documents_count' => is_array($this->policyDocuments) ? count($this->policyDocuments) : 0
        ]);
    }

    /**
     * Make API call with comprehensive logging and error handling
     */
    private function makeApiCall(string $method, string $endpoint, array $data, array $headers, string $operation): array
    {
        $url = $this->baseUrl . $endpoint;
        $startTime = microtime(true);

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
            $responseData = $httpResponse->object();
            $statusCode = $httpResponse->status();
            $isSuccess = $responseData->isSuccess ?? false;

            // Log the API call with request and response
            $this->logApiRequest($operation, $url, $data, $httpResponse);

            if ($httpResponse->successful() && $isSuccess) {
                return [
                    'success' => $isSuccess,
                    'errorCode' => $responseData->errorCode ?? '',
                    'statusMessage' => $responseData->statusMessage ?? 'Success',
                    'data' => $responseData,
                    'status_code' => $statusCode,
                    'response_time' => $responseTime
                ];
            } else {

                return [
                    'success' => $isSuccess,
                    'errorCode' => $responseData->errorCode ?? '-',
                    'statusMessage' => $responseData->statusMessage ?? 'Unknown error',
                    'status_code' => $statusCode,
                    'response_time' => $responseTime
                ];
            }
        } catch (Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log the failed API call with the actual exception details
            $this->logApiRequest($operation, $url, $data, null, $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => 0,
                'response_time' => $responseTime
            ];
        }
    }

    /**
     * Log API requests and responses using InsurerRequestResponse model
     */
    private function logApiRequest(
        string $operation,
        string $url,
        $payload,
        $httpResponse = null,
        ?Throwable $exception = null
    ): void {
        try {

            if (!empty($payload->access_token)) {
                $payload->access_token = substr($payload->access_token, 0, 50) . "...";
            }

            // Handle different scenarios: successful response, HTTP error, or exception
            if ($httpResponse) {
                // Normal HTTP response (successful or error status)
                $statusCode = $httpResponse->status();
                $responseData = $httpResponse->object();
                $isSuccessful = $httpResponse->successful() && ($responseData->isSuccess ?? false);
            } elseif ($exception) {
                // Exception occurred (network timeout, connection error, etc.)
                $statusCode = 0;
                $responseData = [
                    'error' => $exception->getMessage(),
                    // 'exception_type' => get_class($exception),
                    // 'file' => $exception->getFile(),
                    // 'line' => $exception->getLine()
                ];
                $isSuccessful = false;
            } else {
                // Fallback case
                $statusCode = 0;
                $responseData = ['error' => 'Unknown error occurred'];
                $isSuccessful = false;
            }

            InsurerRequestResponse::create([
                'quote_uuid' => $this->quoteUUID,
                'provider_id' => $this->providerId, // You may want to set this based on your provider mapping
                'call_type' => "EP-ECB",
                'request' => json_encode($payload),
                'response' => json_encode($responseData),
                'status' => $isSuccessful ? 'passed' : 'failed',
                'execution_method' => $operation
            ]);

            // Also log using LoggerService for additional tracking
            if ($exception) {
                LoggerService::error($this->logPrefix . " API {$operation} failed with exception", extra: [
                    ...$this->logExtra,
                    'operation' => $operation,
                    'url' => $url,
                    'payload' => json_encode($payload),
                    'status_code' => $statusCode,
                    'error' => $exception->getMessage(),
                    'exception_type' => get_class($exception)
                ]);
            } else {
                LoggerService::info($this->logPrefix . " API {$operation} logged", extra: [
                    ...$this->logExtra,
                    'operation' => $operation,
                    'url' => $url,
                    'payload' => json_encode($payload),
                    'response' => json_encode($httpResponse->object()),
                    'status_code' => $statusCode,
                    'status' => $isSuccessful ? 'passed' : 'failed'
                ]);
            }
        } catch (Throwable $e) {
            LoggerService::error($this->logPrefix . ' Failed to log API request', extra: [
                ...$this->logExtra,
                'operation' => $operation,
                'url' => $url,
                'logging_error' => $e->getMessage(),
                'original_exception' => $exception ? $exception->getMessage() : 'None'
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
    private function updateTransactionStatus(string $status): void
    {
        try {
            $this->embeddedTransaction->update(['policy_status' => $status]);

            LoggerService::info($this->logPrefix . ' Transaction status updated', extra: [
                ...$this->logExtra,
                'new_status' => $status
            ]);
        } catch (Throwable $e) {
            LoggerService::error($this->logPrefix . ' Failed to update transaction status', extra: [
                ...$this->logExtra,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Build quote payload based on business requirements
     */
    private function buildQuotePayload(): array
    {
        // TODO::Need to make it dynamic
        return [
            'client_reference_number' => "",
            'transaction_country' => "UAE",
            'transaction_currency' => "AED",
            'product_info' => [
                'policy_product' => "EXW"
            ],
            'customer_info' => [
                'customer_type' => ""
            ],
            'vehicle_info' => [
                'vehicle_type' => null,
                'vehicle_spec' => null,
                'vehicle_make' => "Toyota",
                'vehicle_model' => "Camry",
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
                'vehicle_first_regn_date' => "2025-01-01",
                'vehicle_invoiced_date' => null,
                'vehicle_delivery_date' => null,
                'vehicle_model_year' => 2024,
                'vehicle_current_km' => null,
                'vehicle_purchase_price' => null,
                'vehicle_current_value' => null,
                'vehicle_pwi_date' => null,
                'vehicle_pwi_km' => null
            ]
        ];
    }

    /**
     * Build policy creation payload
     */
    private function buildPolicyPayload(): array
    {
        // TODO::Need to make it dynamic
        return [
            'client_reference_number' => null,
            'quote_reference_number' => $this->quoteReferenceNumber,
            'transaction_country' => "UAE",
            'sales_info' => [
                'policy_sold_date' => Carbon::now()->format('Y-m-d'),
                'policy_sold_location' => null,
                'policy_sold_salesman' => null
            ],
            'customer_info' => [
                'customer_fname' => "John",
                'customer_lname' => "Doe",
                'customer_mobile_no' => null,
                'customer_whatsapp_no' => null,
                'customer_email_id' => null,
                'customer_id_type' => "EID",
                'customer_id_no' => "784-234234234-0",
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
                'vehicle_chassis_no' => "VIN11000000000002",
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
                'mi_policy_number' => "PS243243",
                'mi_policy_issuer' => 3,
                'mi_start_date' => "2025-01-01",
                'mi_end_date' => "2026-01-01",
                'mi_coverage_area' => "UAE & OMAN",
                'mi_sum_insured' => 100000,
                'mi_policy_excess' => 100
            ],
            'document_info' => [
                [
                    'document_type' => "VH_REGN",
                    'document_name' => "regn card.pdf",
                    'document_url' => "https://devwp.waypoint-systems.com:446/TPANew/Web/assets/images/logo.png"
                ],
                [
                    'document_type' => "CUST_ID",
                    'document_name' => "iddoc.png",
                    'document_url' => "https://devwp.waypoint-systems.com:446/TPANew/Web/assets/images/logo.png"
                ]
            ]
        ];
    }
}
