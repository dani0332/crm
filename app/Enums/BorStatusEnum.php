<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class BorStatusEnum extends Enum
{
    const PENDING_BOR_REQUEST = "PENDING_BOR_REQUEST";
    const SIGNATURE_REQUESTED = 'SIGNATURE_REQUESTED';
    const DOCUMENT_SIGNED = "DOCUMENT_SIGNED";
    const DOCUMENT_UPLOADED = "DOCUMENT_UPLOADED";
    const COMPLETED = "COMPLETED";
    const CANCELLED = "CANCELLED";
}
