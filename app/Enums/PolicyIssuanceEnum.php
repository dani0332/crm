<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class PolicyIssuanceEnum extends Enum
{
    const PENDING_STATUS = 'pending';
    const PROCESSING_STATUS = 'processing';
    const COMPLETED_STATUS = 'completed';
    const FAILED_STATUS = 'failed';
}
