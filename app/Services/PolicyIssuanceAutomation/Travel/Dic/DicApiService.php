<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Enums\PolicyIssuanceEnum;
use App\Facades\DicHttpFacade;
use App\Models\PolicyIssuance;
use App\Models\TravelQuote;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;

class DicApiService
{
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
        $url = DicHttpFacade::buildUrl($path);
        $payload = $this->requestBuilder->buildIssuePolicyPayload($quote);

        LoggerService::info('DIC Travel IssuePolicy request', [
            'quote_code' => $quote->code,
            'url' => $url,
        ]);

        if ($payload === [] || ! isset($payload['policy_id'])) {
            return $this->responseHandler->issuePolicyInvalidPayloadResponse();
        }

        $httpResponse = DicHttpFacade::authenticatedRequest('POST', $url, $payload);
        if ($httpResponse === null) {
            return $this->responseHandler->issuePolicyAuthFailureResponse();
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

        $result = $this->responseHandler->issuePolicyResultFromHttp($httpResponse, $responseBody);

        if ($result['status'] === true && is_array($result['data'] ?? null)) {
            $this->applyIssuePolicyResponseToQuote($quote, $result['data']);
        }

        return $result;
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
        $url = DicHttpFacade::buildUrl($path);

        $httpResponse = DicHttpFacade::authenticatedRequest('GET', $url);
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
        $url = DicHttpFacade::buildUrl($path);

        $httpResponse = DicHttpFacade::authenticatedRequest('GET', $url);
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
        $url = null;

        if (is_array($responseData)) {
            $raw = $responseData['url'] ?? $responseData['Url'] ?? null;
            if (is_string($raw)) {
                $trimmed = trim($raw);
                if ($trimmed !== '' && (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://'))) {
                    $url = $trimmed;
                }
            }
        }

        return $url;
    }
}
