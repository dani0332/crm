<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Enums\PolicyIssuanceEnum;
use App\Models\PolicyIssuance;
use App\Models\TravelQuote;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class DicApiService
{
    /** Laravel Redis cache key — value must be set via e.g. `Cache::store('redis')->put('dic-token', $bearer, $ttl)`. */
    private const REDIS_TOKEN_KEY = 'dic-token';

    public function __construct(
        private DicResponseHandler $responseHandler,
        private DicRequestBuilder $requestBuilder,
    ) {}

    /**
     * EnsuredIT embed: POST `{base}/embed/v1/products/buy/client` with `{ "policy_id": "<uuid>" }`.
     * Success (200): `transactionId`, `amount`, `certificateNumber`, `insurerDelay`.
     *
     * @return array<string, mixed>
     */
    public function issuePolicy(TravelQuote $quote, PolicyIssuance $policyIssuance): array
    {
        $path = 'products/buy/client';
        $url = $this->buildUrl($path);
        $payload = $this->requestBuilder->buildIssuePolicyPayload($quote);

        LoggerService::info('DIC Travel IssuePolicy request', [
            'quote_code' => $quote->code,
            'url' => $url,
        ]);

        if ($payload === [] || ! isset($payload['policy_id'])) {
            return $this->responseHandler->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
                false,
                'DIC IssuePolicy requires policy_id (set insurer_quote_number for EnsuredIT embed).',
                'DIC IssuePolicy: missing policy_id for this quote',
            );
        }

        $httpResponse = $this->authenticatedRequest('POST', $url, $payload);
        if ($httpResponse === null) {
            return $this->responseHandler->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
                false,
                'DIC authentication failed — could not obtain access token',
                'DIC authentication failed — could not obtain access token',
            );
        }

        $responseBody = $httpResponse->json() ?? [];

        app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
            $quote,
            $payload,
            is_array($responseBody) ? $responseBody : [],
            $url,
            PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
            $httpResponse->successful() ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS,
            $policyIssuance,
        );

        if ($httpResponse->failed()) {
            return $this->responseHandler->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
                false,
                'DIC IssuePolicy request failed',
                $httpResponse->body() ?: 'HTTP '.$httpResponse->status(),
            );
        }

        if (! is_array($responseBody)) {
            return $this->responseHandler->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
                false,
                'DIC IssuePolicy: success response missing',
                'DIC IssuePolicy: success response missing',
            );
        }

        // $this->applyIssuePolicyResponseToQuote($quote, $responseBody);

        return $this->responseHandler->buildStepResponse(
            PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
            true,
            'DIC IssuePolicy completed',
            null,
            $responseBody,
        );
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function applyIssuePolicyResponseToQuote(TravelQuote $quote, array $body): void
    {
        if (! isset($body['certificateNumber']) || ! is_string($body['certificateNumber']) || $body['certificateNumber'] === '') {
            return;
        }

        $quote->policy_number = $body['certificateNumber'];
        $quote->save();
    }

    /**
     * @return array<string, mixed>
     */
    public function getPolicyDoc(TravelQuote $quote, PolicyIssuance $policyIssuance): array
    {
        $policyId = $quote->insurer_quote_number;

        $path = 'policy-stores/'.$policyId.'/certificate:download';
        $url = $this->buildUrl($path);

        $httpResponse = $this->authenticatedRequest('GET', $url);
        if ($httpResponse === null) {
            return $this->responseHandler->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
                false,
                'DIC authentication failed — could not obtain access token',
                'DIC authentication failed — could not obtain access token',
            );
        }

        $responseBody = $httpResponse->json() ?? [];
        app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
            $quote,
            [],
            is_array($responseBody) ? $responseBody : [],
            $url,
            PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
            $httpResponse->successful() ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS,
            $policyIssuance,
        );

        if ($httpResponse->failed()) {
            return $this->responseHandler->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
                false,
                'DIC GetPolicyDoc request failed',
                $httpResponse->body() ?: 'HTTP '.$httpResponse->status(),
            );
        }

        return $this->responseHandler->buildStepResponse(
            PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
            true,
            'DIC GetPolicyDoc completed',
            null,
            is_array($responseBody) ? $responseBody : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getBrokerInvoice(TravelQuote $quote, PolicyIssuance $policyIssuance): array
    {
        $policyId = $quote->insurer_quote_number;

        $path = 'policy-stores/invoice/'.$policyId.':download';
        $url = $this->buildUrl($path);

        $httpResponse = $this->authenticatedRequest('GET', $url);
        if ($httpResponse === null) {
            return $this->responseHandler->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
                false,
                'DIC authentication failed — could not obtain access token',
                'DIC authentication failed — could not obtain access token',
            );
        }

        $responseBody = $httpResponse->json() ?? [];

        app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
            $quote,
            [],
            $responseBody,
            $url,
            PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
            $httpResponse->successful() ? PolicyIssuanceEnum::SUCCESS_STATUS : PolicyIssuanceEnum::FAILED_STATUS,
            $policyIssuance,
        );

        if ($httpResponse->failed()) {
            return $this->responseHandler->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
                false,
                'DIC GetBrokerInvoice request failed',
                $httpResponse->body() ?: 'HTTP '.$httpResponse->status(),
            );
        }

        return $this->responseHandler->buildStepResponse(
            PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
            true,
            'DIC GetBrokerInvoice completed',
            null,
            $responseBody,
        );
    }

    /**
     * Extract a downloadable document URL from EnsuredIT JSON, e.g. `{ "url": "https://...s3.../file.pdf?...sig..." }`.
     */
    public function extractDocumentUrlFromResponse(mixed $responseData): ?string
    {
        if (! is_array($responseData)) {
            return null;
        }

        $raw = $responseData['url'] ?? $responseData['Url'] ?? null;
        if (! is_string($raw)) {
            return null;
        }

        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        return str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://') ? $raw : null;
    }

    private function buildUrl(string $path): string
    {
        $base = rtrim((string) config('constants.DIC_API_BASE_URL', ''), '/');
        $path = ltrim($path, '/');

        return $base !== '' && $path !== '' ? $base.'/'.$path : '';
    }

    private function httpTimeoutSeconds(): int
    {
        return (int) config('constants.DIC_API_TIMEOUT', 90);
    }

    /**
     * Bearer access token (single entry point; swap with Redis when not using a static token in dev).
     */
    private function getBearerToken(): ?string
    {
        $value = Cache::store('redis')->get(self::REDIS_TOKEN_KEY);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $data  Query string (GET) or JSON body (POST/PUT/PATCH)
     */
    private function sendWithBearer(string $method, string $fullUrl, string $token, array $data = []): Response
    {
        $client = Http::withToken($token)
            ->timeout($this->httpTimeoutSeconds())
            ->acceptJson();

        return match (strtoupper($method)) {
            'GET' => $client->get($fullUrl, $data),
            'POST' => $client->asJson()->post($fullUrl, $data),
            default => throw new InvalidArgumentException("Unsupported DIC HTTP method: {$method}"),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function authenticatedRequest(string $method, string $fullUrl, array $data = []): ?Response
    {
        $token = $this->getBearerToken();
        if ($token === null) {
            return null;
        }

        $response = $this->sendWithBearer($method, $fullUrl, $token, $data);

        if ($response->status() === 401) {
            $token = $this->getBearerToken();
            if ($token === null) {
                return null;
            }
            $response = $this->sendWithBearer($method, $fullUrl, $token, $data);
        }

        return $response;
    }
}
