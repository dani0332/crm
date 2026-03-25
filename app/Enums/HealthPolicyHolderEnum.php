<?php

namespace App\Enums;

enum HealthPolicyHolderEnum: string
{
    case ME = 'ME';
    case OTHER_ADULT_FAMILY_MEMBER = 'OTHER_ADULT_FAMILY_MEMBER';

    public function getLabel(): string
    {
        return match ($this) {
            self::ME => 'The customer',
            self::OTHER_ADULT_FAMILY_MEMBER => 'Another adult family member',
            default => '',
        };
    }
}
