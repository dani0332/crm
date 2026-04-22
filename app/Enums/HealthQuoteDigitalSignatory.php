<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * UAE PASS digital signature: Emirates ID match vs policyholder / members.
 *
 * Persisted on health_quote_request.digital_signatory.
 */
final class HealthQuoteDigitalSignatory extends Enum
{
    /** IMCRM list filter: not persisted on health_quote_request. */
    public const FILTER_ALL = 'All';

    public const POLICY_HOLDER = 'policy_holder';
    public const INSURED_MEMBER = 'insured_member';
    public const SOMEONE_ELSE = 'someone_else';

    /**
     * IMCRM filter / dropdown options (includes "All" — not persisted).
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function filterDropdown(): array
    {
        return [
            ['value' => self::FILTER_ALL, 'label' => 'All'],
            ['value' => self::POLICY_HOLDER, 'label' => 'Policy Holder'],
            ['value' => self::INSURED_MEMBER, 'label' => 'Insured Member'],
            ['value' => self::SOMEONE_ELSE, 'label' => 'Someone Else'],
        ];
    }

    public static function displayLabel(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($value) {
            self::POLICY_HOLDER => 'Policy Holder',
            self::INSURED_MEMBER => 'Insured Member',
            self::SOMEONE_ELSE => 'Someone Else',
            default => (string) $value,
        };
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
