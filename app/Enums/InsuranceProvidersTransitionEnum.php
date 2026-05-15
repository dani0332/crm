<?php

namespace App\Enums;

class InsuranceProvidersTransitionEnum
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
