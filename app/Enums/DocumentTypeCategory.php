<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class DocumentTypeCategory extends Enum
{
    public const QUOTE = 'QUOTE';
    public const MEMBER = 'MEMBER';
    public const QUOTE_AND_ENDORSEMENT = 'QUOTE_AND_ENDORSEMENT';
    public const ISSUING_DOCUMENTS = 'ISSUING_DOCUMENTS';
    public const ENDORSEMENT_DOCUMENTS = 'ENDORSEMENT_DOCUMENTS';
}
