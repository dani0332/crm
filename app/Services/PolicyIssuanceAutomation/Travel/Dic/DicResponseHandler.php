<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Enums\PolicyIssuanceEnum;
use Illuminate\Http\Client\Response;

class DicResponseHandler
{
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
        return $this->buildStepResponse(
            PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
            false,
            'DIC authentication failed — could not obtain access token',
            'DIC authentication failed — could not obtain access token',
        );
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
            return $this->buildStepResponse(
                PolicyIssuanceEnum::DIC_TRAVEL_ISSUE_POLICY,
                false,
                'DIC IssuePolicy request failed',
                $httpResponse->body() ?: 'HTTP '.$httpResponse->status(),
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
