<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Enums\PolicyIssuanceEnum;
use Illuminate\Http\Client\Response;

class DicResponseHandler
{
    /** When Redis/cache has no dic-token before calling EnsuredIT. */
    public const MESSAGE_AUTH_TOKEN_UNAVAILABLE = 'Could not obtain EnsuredIT access token. Generate a token and store it under the configured Redis key (dic-token), then retry.';

    /**
     * @param  mixed  $error
     * @param  mixed  $data
     * @return array<string, mixed>
     */
    public function buildStepResponse(string $step, bool $status = false, ?string $message = null, $error = null, $data = null): array
    {
        return [
            'status' => $status,
            'completed_step' => $step,
            'message' => $message,
            'error' => $error,
            'data' => $data,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function issuePolicyInvalidPayloadResponse(): array
    {
        return $this->buildStepResponse(
            PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
            false,
            'DIC IssuePolicy requires policy_id (set insurer_quote_number for EnsuredIT embed).',
            'DIC IssuePolicy: missing policy_id for this quote',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function issuePolicyAuthFailureResponse(): array
    {
        return $this->authTokenUnavailableStepResponse(PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY);
    }

    /**
     * @return array<string, mixed>
     */
    public function authTokenUnavailableStepResponse(string $step): array
    {
        return $this->buildStepResponse(
            $step,
            false,
            self::MESSAGE_AUTH_TOKEN_UNAVAILABLE,
            self::MESSAGE_AUTH_TOKEN_UNAVAILABLE,
        );
    }

    /**
     * Failed EnsuredIT HTTP response mapped per API error guide (AUTH_ERROR / VALIDATION_ERROR / INTERNAL_ERROR).
     *
     * @return array<string, mixed>
     */
    public function buildStepResponseFromEnsuredItFailure(string $step, Response $httpResponse): array
    {
        $mapped = DicEnsuredItErrorHandler::map($httpResponse);

        return $this->buildStepResponse($step, false, $mapped['message'], $mapped['error']);
    }

    /**
     * Map HTTP outcome for DIC IssuePolicy to a step response (success body must be a JSON object/array).
     *
     * @param  mixed  $responseBody  Value from {@see Response::json()} or `?? []`
     * @return array<string, mixed>
     */
    public function issuePolicyResultFromHttp(Response $httpResponse, mixed $responseBody): array
    {
        if ($httpResponse->failed()) {
            return $this->buildStepResponseFromEnsuredItFailure(
                PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
                $httpResponse,
            );
        }

        if (! is_array($responseBody)) {
            return $this->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
                false,
                'DIC IssuePolicy: success response missing',
                'DIC IssuePolicy: success response missing',
            );
        }

        return $this->buildStepResponse(
            PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
            true,
            'DIC IssuePolicy completed',
            null,
            $responseBody,
        );
    }
}
