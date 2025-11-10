<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class BorStatusEnum extends Enum
{
    const SIGNATURE_REQUESTED = 'Signature Requested';
    const DOCUMENT_SIGNED = 'Document Signed';
    const DOCUMENT_UPLOADED = 'Document Uploaded';
    const COMPLETED = 'Completed';
    const CANCELLED = 'Cancelled';

    public static function allowsMarkingDone(string $status): bool
    {
        return in_array($status, [self::DOCUMENT_SIGNED, self::DOCUMENT_UPLOADED]);
    }
}
