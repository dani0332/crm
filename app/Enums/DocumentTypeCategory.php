<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class DocumentTypeCategory extends Enum
{
    public const QUOTE = 'QUOTE';
    public const MEMBER = 'MEMBER';
    public const QUOTE_AND_ENDORSEMENT = 'QUOTE_AND_ENDORSEMENT';
}
