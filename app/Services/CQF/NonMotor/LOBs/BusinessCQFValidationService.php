<?php

declare(strict_types=1);

namespace App\Services\CQF\NonMotor\LOBs;

use App\Services\CQF\BaseCQFValidationService;

class BusinessCQFValidationService extends BaseCQFValidationService
{
    protected function getBaseRules(): array
    {
        return [
            ...parent::getBaseRules(),
            'business_type_of_insurance_id' => ['required'],
        ];
    }

    public function getValidationMessages(): array
    {
        return [
            ...parent::getValidationMessages(),
            'business_type_of_insurance_id.required' => 'Business type of insurance is required.',
        ];
    }
}
