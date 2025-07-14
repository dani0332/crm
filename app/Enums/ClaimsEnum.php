<?php

namespace App\Enums;

/**
 * Claims Enum
 *
 * Consolidates all claim-related constants, statuses, types, and configuration values
 * that were previously scattered across controllers, services, and models.
 */
enum ClaimsEnum: string
{
    // Reference ID Configuration
    case REF_ID_PREFIX = 'CLM-';

    // Claim Status Enums
    case STATUS_NEW = 'New';
    case STATUS_SEND_FOR_REGISTRATION = 'Send for Registration';
    case STATUS_AWAITING_APPROVAL = 'Awaiting Approval';
    case STATUS_APPROVED = 'Approved';
    case STATUS_REJECTED_BY_INSURER = 'Rejected by Insurer';
    case STATUS_IN_PROGRESS = 'In Progress';
    case STATUS_SETTLED = 'Settled';

    public function id(): string
    {
        return self::getId($this) ?? '';
    }

    public static function getId(self $value): ?int
    {
        return match ($value) {
            self::STATUS_NEW => 1,
            self::STATUS_SEND_FOR_REGISTRATION => 2,
            self::STATUS_AWAITING_APPROVAL => 3,
            self::STATUS_APPROVED => 4,
            self::STATUS_REJECTED_BY_INSURER => 5,
            self::STATUS_IN_PROGRESS => 6,
            self::STATUS_SETTLED => 7,
            default => null,
        };
    }

    public static function getName($value)
    {
        $statuses = self::getStatuses();

        return isset($statuses[$value]) ? $statuses[$value] : null;
    }

    public static function getIdFromValue(string $value): ?int
    {
        $claimStatusEnum = match (ucfirst($value)) {
            'New' => self::STATUS_NEW,
            'Send for Registration' => self::STATUS_SEND_FOR_REGISTRATION,
            'Awaiting Approval' => self::STATUS_AWAITING_APPROVAL,
            'Approved' => self::STATUS_APPROVED,
            'Rejected by Insurer' => self::STATUS_REJECTED_BY_INSURER,
            'In Progress' => self::STATUS_IN_PROGRESS,
            'Settled' => self::STATUS_SETTLED,
            default => null,
        };

        return $claimStatusEnum ? self::getId($claimStatusEnum) : null;
    }

    public static function getStatuses()
    {
        return [
            1 => self::STATUS_NEW->value,
            2 => self::STATUS_SEND_FOR_REGISTRATION->value,
            3 => self::STATUS_AWAITING_APPROVAL->value,
            4 => self::STATUS_APPROVED->value,
            5 => self::STATUS_REJECTED_BY_INSURER->value,
            6 => self::STATUS_IN_PROGRESS->value,
            7 => self::STATUS_SETTLED->value,
        ];

    }
}
