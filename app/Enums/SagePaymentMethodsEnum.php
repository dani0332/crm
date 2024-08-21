<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class SagePaymentMethodsEnum extends Enum
{
    const SAGE_CREDIT_CARD = 'CC';
    const SAGE_BANK_TRANSFER = 'BT';
    const SAGE_CASH = 'CASH';
    const SAGE_CHEQUE = 'CHEQUE';
    const SAGE_POST_DATED_CHEQUE = 'PDC';
    const SAGE_INSURER_PAYMENT = 'IP';
    const SAGE_INSURER_NOW_PAY_LATER = 'INPL';
}
