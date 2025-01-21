<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentProcessJobEnum extends Enum
{
    const PENDING = 'pending';
    const IN_PROCESS = 'in-process';
    const FAILED = 'failed';
    const SUCCESS = 'success';
    const SUCCESS_MESSAGE = 'transaction completed successfully';
    const QUOTE_NOTFOUND_MESSAGE = 'quote not found';
    const QUEUED = 'queued';
}
