<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * UAE PASS reporting status for IMCRM Health (labels align with product wording).
 *
 * Persisted on health_quote_request.uae_pass_api_status (snake_case values below).
 */
final class HealthQuoteUaePassApiStatus extends Enum
{
    /** IMCRM list filter: not persisted on health_quote_request. */
    public const FILTER_ALL = 'All';

    public const AUTHENTICATION_SUCCESS = 'AUTHENTICATION_SUCCESS';
    public const AUTHENTICATION_CANCELLED = 'AUTHENTICATION_CANCELLED';
    public const NOT_ELIGIBLE = 'NOT_ELIGIBLE';
    public const DOC_SIGNED = 'DOC_SIGNED';
    public const DOC_FAILED = 'DOC_FAILED';
    public const DOC_CANCELLED = 'DOC_CANCELLED';
    public const DOCS_NOT_RECEIVED = 'DOCS_NOT_RECEIVED';

    /**
     * Stored value => IMCRM / export label (not a BenSampo enum constant — avoids array in getValues()).
     *
     * @return array<string, string>
     */
    private static function labels(): array
    {
        return [
            self::AUTHENTICATION_SUCCESS => 'UAE PASS – Authentication Success',
            self::AUTHENTICATION_CANCELLED => 'UAE PASS – Authentication Cancelled',
            self::NOT_ELIGIBLE => 'UAE PASS – Not Eligible',
            self::DOC_SIGNED => 'UAE PASS – Doc Signed',
            self::DOC_FAILED => 'UAE PASS – Doc Failed',
            self::DOC_CANCELLED => 'UAE PASS – Doc Cancelled',
            self::DOCS_NOT_RECEIVED => 'UAE PASS – Docs not Received',
        ];
    }

    /**
     * IMCRM filter / dropdown (includes "All" — not persisted).
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function filterDropdown(): array
    {
        $rows = [
            ['value' => self::FILTER_ALL, 'label' => 'All'],
        ];

        foreach (self::labels() as $value => $label) {
            $rows[] = ['value' => $value, 'label' => $label];
        }

        return $rows;
    }

    public static function displayLabel(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return self::labels()[$value] ?? (string) $value;
    }

    public static function isStoredValue(?string $value): bool
    {
        if ($value === null || $value === '' || $value === self::FILTER_ALL) {
            return false;
        }

        $persisted = array_values(array_filter(
            self::getValues(),
            static fn (string $v): bool => $v !== self::FILTER_ALL
        ));

        return in_array($value, $persisted, true);
    }
}
