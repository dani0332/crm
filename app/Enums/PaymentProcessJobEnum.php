<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentProcessJobEnum extends Enum
{
    const PENDING = 'pending';
    const INPROCESS = 'in-process';
    const FAILED = 'failed';
    const SUCCESS = 'success';
    const SUCCESS_MESSAGE = 'transaction completed successfully';
}
