<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class QuoteTagEnums extends Enum
{
    public const DOCUMENTS_EMAIL_SENT_TO_CUSTOMER = 'DESTC';
}
