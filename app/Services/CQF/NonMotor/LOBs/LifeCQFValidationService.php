<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Services\CQF\BaseCQFValidationService;
use App\Services\CQF\NonMotor\Traits\PersonalQuoteDuplicateCheckTrait;

class LifeCQFValidationService extends BaseCQFValidationService
{
    use PersonalQuoteDuplicateCheckTrait;
}
