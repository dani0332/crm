<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use Illuminate\Http\Client\Response;

/**
 * Maps EnsuredIT / DIC embed HTTP errors to operator-facing messages and log-friendly details
 * (see internal "API Error Handling & Resolution Guide" — Issuance + Policy Stores patterns).
 */
final class DicEnsuredItErrorHandler
{
    public const CODE_AUTH_ERROR = 'AUTH_ERROR';
    public const CODE_VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const CODE_INTERNAL_ERROR = 'INTERNAL_ERROR';

    /**
     * @return array{message: string, error: string}
     */
    public static function map(Response $response): array
    {
        $status = $response->status();
        $body = $response->body();
        $decoded = $response->json();
        $code = is_array($decoded) && isset($decoded['code']) && is_string($decoded['code'])
            ? $decoded['code'] : null;
        $apiMessage = is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])
            ? $decoded['message'] : null;

        $error = self::formatErrorDetail($code, $apiMessage, $body, $status);
        $message = self::buildUserMessage($status, $code, $apiMessage, $decoded);

        return ['message' => $message, 'error' => $error];
    }

    private static function formatErrorDetail(?string $code, ?string $apiMessage, string $body, int $status): string
    {
        if ($code !== null && $apiMessage !== null) {
            return "{$code}: {$apiMessage}";
        }

        if ($apiMessage !== null) {
            return $apiMessage;
        }

        return $body !== '' ? $body : 'HTTP '.$status;
    }

    /**
     * @param  array<string, mixed>|null  $decoded
     */
    private static function buildUserMessage(int $status, ?string $code, ?string $apiMessage, mixed $decoded): string
    {
        $message = self::messageIfPolicyAlreadySold($status, $apiMessage, $decoded)
            ?? self::messageIfAuthError($status, $code, $apiMessage)
            ?? self::messageIfValidationError($status, $code, $apiMessage)
            ?? self::messageIfInternalError($status, $code, $apiMessage)
            ?? self::messageIfGenericApiMessage($status, $apiMessage);

        return $message ?? 'DIC request failed (HTTP '.$status.').';
    }

    private static function messageIfPolicyAlreadySold(int $status, ?string $apiMessage, mixed $decoded): ?string
    {
        if ($status !== 400 || ! is_array($decoded)) {
            return null;
        }

        $policyStatus = $decoded['policyStatus'] ?? null;
        $msg = is_string($apiMessage) ? $apiMessage : '';
        $sold = (is_string($policyStatus) && strtoupper($policyStatus) === 'SOLD')
            || ($msg !== '' && stripos($msg, 'already sold') !== false);

        return $sold
            ? 'Policy is already sold. Verify policy status in EnsuredIT before attempting purchase again.'
            : null;
    }

    private static function messageIfAuthError(int $status, ?string $code, ?string $apiMessage): ?string
    {
        if ($status !== 401 || $code !== self::CODE_AUTH_ERROR || ! is_string($apiMessage)) {
            return null;
        }

        $out = null;
        if (stripos($apiMessage, 'Token Expired') !== false) {
            $out = 'EnsuredIT rejected the request: token expired. Generate a new access token, update the cached bearer token, and retry.';
        } elseif (stripos($apiMessage, 'Invalid Auth Token') !== false) {
            $out = 'EnsuredIT rejected the request: invalid auth token. Pass a valid bearer token (Authorization) with the request.';
        }

        return $out;
    }

    private static function messageIfValidationError(int $status, ?string $code, ?string $apiMessage): ?string
    {
        if ($status !== 400 || $code !== self::CODE_VALIDATION_ERROR || ! is_string($apiMessage)) {
            return null;
        }

        return "EnsuredIT validation error: {$apiMessage} Ensure field values match the API specification.";
    }

    private static function messageIfInternalError(int $status, ?string $code, ?string $apiMessage): ?string
    {
        if ($status < 500 || $code !== self::CODE_INTERNAL_ERROR || ! is_string($apiMessage)) {
            return null;
        }

        return "EnsuredIT server error: {$apiMessage} Verify identifiers (e.g. policy UUID) and required fields.";
    }

    private static function messageIfGenericApiMessage(int $status, ?string $apiMessage): ?string
    {
        if (! is_string($apiMessage) || $apiMessage === '') {
            return null;
        }

        return "EnsuredIT error (HTTP {$status}): {$apiMessage}";
    }
}
