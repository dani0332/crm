<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class QuoteJourneyEnum extends Enum
{
    public const COMPLETED = 'COMPLETED';
    public const IN_PROCESS = 'IN_PROCESS';
    public const PENDING = 'PENDING';
    public const POLICY_ISSUANCE = 'Policy issuance';
    public const CANCELLED = 'CANCELLED';
    public const DOCUMENT_UPLOADED = 'Documents uploaded';
}
