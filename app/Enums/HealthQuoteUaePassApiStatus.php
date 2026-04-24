<?php

namespace App\Enums;

/**
 * UAE PASS reporting status for IMCRM Health (labels align with product wording).
 *
 * Persisted on health_quote_request.uae_pass_api_status (snake_case values below).
 */
enum HealthQuoteUaePassApiStatus: string
{
    case AuthenticationSuccess = 'AUTHENTICATION_SUCCESS';
    case AuthenticationCancelled = 'AUTHENTICATION_CANCELLED';
    case NotEligible = 'NOT_ELIGIBLE';
    case DocSigned = 'DOC_SIGNED';
    case DocFailed = 'DOC_FAILED';
    case DocCancelled = 'DOC_CANCELLED';
    case DocsNotReceived = 'DOCS_NOT_RECEIVED';
    case FILTER_ALL = 'All';

    /**
     * IMCRM filter / dropdown (includes "All" — not persisted).
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function filterDropdown(): array
    {
        foreach (self::cases() as $case) {
            $rows[] = ['value' => $case->value, 'label' => $case->uiLabel()];
        }

        return $rows;
    }

    public function uiLabel(): string
    {
        return match ($this) {
            self::AuthenticationSuccess => 'UAE PASS – Authentication Success',
            self::AuthenticationCancelled => 'UAE PASS – Authentication Cancelled',
            self::NotEligible => 'UAE PASS – Not Eligible',
            self::DocSigned => 'UAE PASS – Doc Signed',
            self::DocFailed => 'UAE PASS – Doc Failed',
            self::DocCancelled => 'UAE PASS – Doc Cancelled',
            self::DocsNotReceived => 'UAE PASS – Docs not Received',
            self::FILTER_ALL => 'All',
        };
    }

    public static function displayLabel(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $case = self::tryFrom($value);

        if ($case === null) {
            return $value;
        }

        return $case->uiLabel();
    }

    public static function isStoredValue(?string $value): bool
    {
        if ($value === null || $value === '' || $value === self::FILTER_ALL->value) {
            return false;
        }

        return self::tryFrom($value) !== null;
    }
}
