<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static GENESIS()
 * @method static static PHOENIX()
 */
final class InsuranceProvidersTransitionEnum extends Enum
{
    public const GENESIS = 'Genesis';

    public const PHOENIX = 'Phoenix';

    /**
     * Return the transition tag for a given source insurance provider code (RSA -> GENESIS, TM -> PHOENIX).
     */
    public static function tagForSourceCode(?string $sourceCode): string
    {
        return match ($sourceCode) {
            InsuranceProvidersEnum::RSA => self::GENESIS,
            InsuranceProvidersEnum::TM => self::PHOENIX,
            default => '',
        };
    }
}
