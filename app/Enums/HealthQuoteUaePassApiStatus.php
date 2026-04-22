<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * UAE PASS API interaction status for reporting (IMCRM Health).
 *
 * Persisted on health_quote_request.uae_pass_api_status.
 */
final class HealthQuoteUaePassApiStatus extends Enum
{
    public const AUTHENTICATION_PENDING = 'authentication_pending';
    public const AUTHENTICATION_SUCCESS = 'authentication_success';
    public const AUTHENTICATION_FAILED = 'authentication_failed';
    public const SIGNATURE_PENDING = 'signature_pending';
    public const SIGNATURE_SUCCESS = 'signature_success';
    public const SIGNATURE_FAILED = 'signature_failed';
    public const API_ERROR = 'api_error';

    /**
     * IMCRM filter / dropdown (includes "All" — not persisted).
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function filterDropdown(): array
    {
        return [
            ['value' => 'All', 'label' => 'All'],
            ['value' => self::AUTHENTICATION_PENDING, 'label' => 'Authentication Pending'],
            ['value' => self::AUTHENTICATION_SUCCESS, 'label' => 'Authentication Success'],
            ['value' => self::AUTHENTICATION_FAILED, 'label' => 'Authentication Failed'],
            ['value' => self::SIGNATURE_PENDING, 'label' => 'Signature Pending'],
            ['value' => self::SIGNATURE_SUCCESS, 'label' => 'Signature Success'],
            ['value' => self::SIGNATURE_FAILED, 'label' => 'Signature Failed'],
            ['value' => self::API_ERROR, 'label' => 'API Error'],
        ];
    }

    public static function displayLabel(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($value) {
            self::AUTHENTICATION_PENDING => 'Authentication Pending',
            self::AUTHENTICATION_SUCCESS => 'Authentication Success',
            self::AUTHENTICATION_FAILED => 'Authentication Failed',
            self::SIGNATURE_PENDING => 'Signature Pending',
            self::SIGNATURE_SUCCESS => 'Signature Success',
            self::SIGNATURE_FAILED => 'Signature Failed',
            self::API_ERROR => 'API Error',
            default => (string) $value,
        };
    }

    public static function isStoredValue(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return in_array($value, self::getValues(), true);
    }
}
