<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Enums\PolicyIssuanceEnum;
use App\Facades\DicHttpFacade;
use App\Models\PolicyIssuance;
use App\Models\TravelQuote;
use App\Services\Logger\LoggerService;
use App\Services\PolicyIssuanceAutomation\PolicyIssuanceService;
use Carbon\Carbon;

class DicApiService
{
    public function __construct(
        private DicResponseHandler $responseHandler,
        private DicRequestBuilder $requestBuilder,
        private PolicyIssuanceService $policyIssuanceService,
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

        $policyId = $payload['policy_id'] ?? null;
        if (! is_string($policyId) || trim($policyId) === '') {
            $result = $this->responseHandler->issuePolicyInvalidPayloadResponse();
            $this->policyIssuanceService->storePolicyIssuanceLog(
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
            $this->policyIssuanceService->storePolicyIssuanceLog(
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

        $this->policyIssuanceService->storePolicyIssuanceLog(
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
        $quote->price_vat_applicable = $body['amount'];
        $quote->policy_issuance_date = Carbon::parse(data_get($body, 'additionalDetails.premium_issuing_date', null))->format('Y-m-d');
        $quote->save();
    }

    /**
     * @return array<string, mixed>
     */
    public function getPolicyDoc(TravelQuote $quote, PolicyIssuance $policyIssuance): array
    {
        $policyId = $quote->insurer_quote_number;
        if ($policyId === null || $policyId === '') {
            return $this->missingInsurerQuoteNumberFailure(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
                $quote,
                $policyIssuance,
                'dic-get-policy-doc/missing-insurer-quote-number',
            );
        }

        return $this->runAuthenticatedDicGetStep(
            $quote,
            $policyIssuance,
            'policy-stores/'.$policyId.'/certificate:download',
            PolicyIssuanceEnum::DIC_TRAVEL_GET_POLICY_DOC,
            'DIC GetPolicyDoc completed',
            true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getBrokerInvoice(TravelQuote $quote, PolicyIssuance $policyIssuance): array
    {
        $policyId = $quote->insurer_quote_number;
        if ($policyId === null || $policyId === '') {
            return $this->missingInsurerQuoteNumberFailure(
                PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
                $quote,
                $policyIssuance,
                'dic-get-broker-invoice/missing-insurer-quote-number',
            );
        }

        return $this->runAuthenticatedDicGetStep(
            $quote,
            $policyIssuance,
            'policy-stores/invoice/'.$policyId.':download',
            PolicyIssuanceEnum::DIC_TRAVEL_GET_BROKER_INVOICE,
            'DIC GetBrokerInvoice completed',
            false,
        );
    }

    /**
     * Shared EnsuredIT GET (authenticated) flow for document / invoice download steps.
     *
     * @param  string  $step  {@see PolicyIssuanceEnum} step constant
     * @return array<string, mixed>
     */
    private function runAuthenticatedDicGetStep(
        TravelQuote $quote,
        PolicyIssuance $policyIssuance,
        string $path,
        string $step,
        string $successMessage,
        bool $coerceSuccessPayloadToArray,
    ): array {
        $url = DicHttpFacade::buildUrl($path);
        $httpResponse = DicHttpFacade::authenticatedRequest('GET', $url);
        if ($httpResponse === null) {
            $result = $this->responseHandler->authTokenUnavailableStepResponse($step);
            $this->policyIssuanceService->storePolicyIssuanceLog(
                $quote,
                [],
                ['error' => $result['error'] ?? $result['message']],
                $url,
                $step,
                PolicyIssuanceEnum::FAILED_STATUS,
                $policyIssuance,
            );
        } else {
            $responseBody = $httpResponse->json() ?? [];
            $normalizedForLog = is_array($responseBody) ? $responseBody : [];

            if ($httpResponse->failed()) {
                $result = $this->responseHandler->buildStepResponseFromEnsuredItFailure(
                    $step,
                    $httpResponse,
                );
                $this->policyIssuanceService->storePolicyIssuanceLog(
                    $quote,
                    [],
                    $normalizedForLog,
                    $url,
                    $step,
                    PolicyIssuanceEnum::FAILED_STATUS,
                    $policyIssuance,
                );
            } else {
                $this->policyIssuanceService->storePolicyIssuanceLog(
                    $quote,
                    [],
                    $normalizedForLog,
                    $url,
                    $step,
                    PolicyIssuanceEnum::SUCCESS_STATUS,
                    $policyIssuance,
                );
                $successPayload = $coerceSuccessPayloadToArray ? $normalizedForLog : $responseBody;
                $result = $this->responseHandler->buildStepResponse(
                    $step,
                    true,
                    $successMessage,
                    null,
                    $successPayload,
                );
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function missingInsurerQuoteNumberFailure(
        string $step,
        TravelQuote $quote,
        PolicyIssuance $policyIssuance,
        string $logEndpoint,
    ): array {
        $result = $this->responseHandler->buildStepResponse(
            $step,
            false,
            'DIC step requires insurer_quote_number (EnsuredIT policy id) on the quote.',
            'DIC: missing insurer_quote_number',
        );
        $this->policyIssuanceService->storePolicyIssuanceLog(
            $quote,
            [],
            ['error' => $result['error'] ?? $result['message']],
            $logEndpoint,
            $step,
            PolicyIssuanceEnum::FAILED_STATUS,
            $policyIssuance,
        );

        return $result;
    }

    /**
     * Extract the certificate / policy schedule URL from EnsuredIT JSON (top-level `url`).
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

    /**
     * Broker / buyer invoice number from GetBrokerInvoice JSON, e.g. `{ "url": "...", "invoiceNumber": "INV-AE-..." }`.
     */
    public function extractBrokerInvoiceNumberFromResponse(mixed $responseData): ?string
    {
        if (! is_array($responseData)) {
            return null;
        }

        $raw = $responseData['invoiceNumber'] ?? $responseData['InvoiceNumber'] ?? null;
        if (is_string($raw)) {
            $trimmed = trim($raw);

            return $trimmed !== '' ? $trimmed : null;
        }

        if (is_int($raw)) {
            return (string) $raw;
        }

        return null;
    }

    /**
     * Tax invoice URL from GetPolicyDoc: `additionalDetails.documents[]` where `documentName` is `TAX_INVOICE`.
     */
    public function extractTaxInvoiceUrlFromGetPolicyDocResponse(mixed $responseData): ?string
    {
        $document = $this->findTaxInvoiceDocumentInGetPolicyDocResponse($responseData);
        if ($document === null) {
            return null;
        }

        $raw = $document['url'] ?? $document['Url'] ?? null;
        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed !== '' && (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://'))) {
                return $trimmed;
            }
        }

        return null;
    }

    /**
     * Tax invoice `documentNumber` from GetPolicyDoc (same document entry as {@see extractTaxInvoiceUrlFromGetPolicyDocResponse}).
     */
    public function extractTaxInvoiceDocumentNumberFromGetPolicyDocResponse(mixed $responseData): ?string
    {
        $document = $this->findTaxInvoiceDocumentInGetPolicyDocResponse($responseData);
        if ($document === null) {
            return null;
        }

        $num = $document['documentNumber'] ?? $document['document_number'] ?? null;
        if (is_string($num)) {
            $trimmed = trim($num);

            return $trimmed !== '' ? $trimmed : null;
        }

        if (is_int($num)) {
            return (string) $num;
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findTaxInvoiceDocumentInGetPolicyDocResponse(mixed $responseData): ?array
    {
        if (! is_array($responseData)) {
            return null;
        }

        $additionalDetails = $responseData['additionalDetails'] ?? $responseData['additional_details'] ?? null;
        if (! is_array($additionalDetails)) {
            return null;
        }

        $documents = $additionalDetails['documents'] ?? null;
        if (! is_array($documents)) {
            return null;
        }

        foreach ($documents as $document) {
            if (! is_array($document)) {
                continue;
            }

            $documentName = $document['documentName'] ?? $document['document_name'] ?? null;
            if (! is_string($documentName) || strtoupper(trim($documentName)) !== 'TAX_INVOICE') {
                continue;
            }

            return $document;
        }

        return null;
    }
}
