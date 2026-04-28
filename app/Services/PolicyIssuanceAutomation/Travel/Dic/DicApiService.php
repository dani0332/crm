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
            $result = $this->responseHandler->issuePolicyInvalidPayloadResponse();
            app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
                $quote,
                $payload,
                ['error' => $result['error'] ?? $result['message'] ?? 'invalid_payload'],
                $url,
                PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
                PolicyIssuanceEnum::FAILED_STATUS,
                $policyIssuance,
            );

            return $result;
        }

        $httpResponse = DicHttpFacade::authenticatedRequest('POST', $url, $payload);
        if ($httpResponse === null) {
            $result = $this->responseHandler->issuePolicyAuthFailureResponse();
            app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
                $quote,
                $payload,
                ['error' => $result['error'] ?? $result['message']],
                $url,
                PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
                PolicyIssuanceEnum::FAILED_STATUS,
                $policyIssuance,
            );

            return $result;
        }

        $responseBody = $httpResponse->json() ?? [];

        $result = $this->responseHandler->issuePolicyResultFromHttp($httpResponse, $responseBody);

        $logStatus = ($result['status'] ?? false)
            ? PolicyIssuanceEnum::SUCCESS_STATUS
            : PolicyIssuanceEnum::FAILED_STATUS;

        app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
            $quote,
            $payload,
            is_array($responseBody) ? $responseBody : [],
            $url,
            PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
            $logStatus,
            $policyIssuance,
        );

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
            $result = $this->responseHandler->authTokenUnavailableStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
            );
            app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
                $quote,
                [],
                ['error' => $result['error'] ?? $result['message']],
                $url,
                PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
                PolicyIssuanceEnum::FAILED_STATUS,
                $policyIssuance,
            );

            return $result;
        }

        $responseBody = $httpResponse->json() ?? [];

        if ($httpResponse->failed()) {
            $result = $this->responseHandler->buildStepResponseFromEnsuredItFailure(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
                $httpResponse,
            );
            app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
                $quote,
                [],
                is_array($responseBody) ? $responseBody : [],
                $url,
                PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
                PolicyIssuanceEnum::FAILED_STATUS,
                $policyIssuance,
            );

            return $result;
        }

        app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
            $quote,
            [],
            is_array($responseBody) ? $responseBody : [],
            $url,
            PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
            PolicyIssuanceEnum::SUCCESS_STATUS,
            $policyIssuance,
        );

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
            $result = $this->responseHandler->authTokenUnavailableStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
            );
            app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
                $quote,
                [],
                ['error' => $result['error'] ?? $result['message']],
                $url,
                PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
                PolicyIssuanceEnum::FAILED_STATUS,
                $policyIssuance,
            );

            return $result;
        }

        $responseBody = $httpResponse->json() ?? [];

        if ($httpResponse->failed()) {
            $result = $this->responseHandler->buildStepResponseFromEnsuredItFailure(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
                $httpResponse,
            );
            app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
                $quote,
                [],
                is_array($responseBody) ? $responseBody : [],
                $url,
                PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
                PolicyIssuanceEnum::FAILED_STATUS,
                $policyIssuance,
            );

            return $result;
        }

        app(PolicyIssuanceService::class)->storePolicyIssuanceLog(
            $quote,
            [],
            is_array($responseBody) ? $responseBody : [],
            $url,
            PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
            PolicyIssuanceEnum::SUCCESS_STATUS,
            $policyIssuance,
        );

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
