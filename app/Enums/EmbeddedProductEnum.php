<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EmbeddedProductEnum extends Enum
{
    const AP1 = 'Silver';
    const AP2 = 'Gold';
    const AP3 = 'Platinum';
    const TRAVEL = 'TRA';
    const COURIER = 'COU';
    const RDX = 'RDX';
    const MDX = 'MDX';
    const ECB = 'ECB';

    // used in report for source
    const SRC_CAR_EMBEDDED_PRODUCT = 'CAR_EMBEDDED_PRODUCT';
    const CAR_EP_RETARGETING_REMINDER_ALLOWED_EPS = [
        self::MDX,
        self::ECB,
    ];

    public static function getAlfredProtectCodes(): array
    {
        return [
            'AP1',
            'AP2',
            'AP3',
        ];
    }

    public static function getSukoonMedexCodes(): array
    {
        return [
            self::MDX,
            self::RDX,
        ];
    }

    /**
     * EP short codes eligible for Sage booking reversal after IMCRM refund (MEDEX, RDX, Excess Cashback).
     *
     * @return array<int, string>
     */
    public static function getSageReversableEpShortCodes(): array
    {
        return [
            self::MDX,
            self::RDX,
            self::ECB,
        ];
    }
}
