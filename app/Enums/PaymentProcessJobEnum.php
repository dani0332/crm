<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentProcessJobEnum extends Enum
{
    const PENDING_STATUS = 'pending';
    const INPROCESS_STATUS = 'in-process';
    const FAILED_STATUS = 'failed';
    const SUCCESS_STATUS = 'success';
    const SUCCESS_MESSAGE = 'transaction completed successfully';
    
}
