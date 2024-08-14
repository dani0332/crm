<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EndorsementStatusEnum extends Enum
{
    public const UPDATE_BOOKED = 'UPDATE_BOOKED';

    /**
     * ENDROSMENT CATEORGOIES
     */
    public const ENDORSEMENT_FINANCIAL_CODE = 'EF';

    public const CANCELLATION_FROM_INCEPTION = 'CI';
    public const CANCELLATION_FROM_INCEPTION_AND_REISSUANCE = 'CIR';
    public const CORRECTION_OF_POLICY_DETAILS = 'CPD';
}
