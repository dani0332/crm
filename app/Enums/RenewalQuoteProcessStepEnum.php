<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class RenewalQuoteProcessStepEnum extends Enum
{
    const CREATE = 'create';
    const UPDATE = 'update';
    const FETCH_PLANS = 'fetch_plans';
    const SEND_OCB_EMAIL = 'send_ocb_email';
    const MEMBERS_UPDATE = 'members_update';
    const PLAN_UPDATE = 'plan_update';
    const SELECT_PLAN = 'select_plan';
    const CREATE_PAYMENT = 'create_payment';
    const FAILED = 'failed';
}
