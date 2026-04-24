<?php

namespace App\Enums;

/**
 * UAE PASS digital signature: Emirates ID match vs policyholder / members.
 *
 * Persisted on health_quote_request.digital_signatory.
 */
enum HealthQuoteDigitalSignatory: string
{
    case PolicyHolder = 'policy_holder';
    case InsuredMember = 'insured_member';
    case SomeoneElse = 'someone_else';

    /** IMCRM list filter: not persisted on health_quote_request. */
    public const FILTER_ALL = 'All';

    /**
     * IMCRM filter / dropdown options (includes "All" — not persisted).
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function filterDropdown(): array
    {
        return [
            ['value' => self::FILTER_ALL, 'label' => 'All'],
            ['value' => self::PolicyHolder->value, 'label' => 'Policy Holder'],
            ['value' => self::InsuredMember->value, 'label' => 'Insured Member'],
            ['value' => self::SomeoneElse->value, 'label' => 'Someone Else'],
        ];
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

        return match ($case) {
            self::PolicyHolder => 'Policy Holder',
            self::InsuredMember => 'Insured Member',
            self::SomeoneElse => 'Someone Else',
        };
    }

    public static function isStoredValue(?string $value): bool
    {
        if ($value === null || $value === '' || $value === self::FILTER_ALL) {
            return false;
        }

        return self::tryFrom($value) !== null;
    }
}
