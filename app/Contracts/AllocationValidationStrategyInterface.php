<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Validation\Validator;

interface AllocationValidationStrategyInterface
{
    /**
     * Get validation rules for the specific LOB
     */
    public function getRules(): array;

    /**
     * Get validation messages for the specific LOB
     */
    public function getMessages(): array;

    /**
     * Perform custom validation logic
     */
    public function validate(Validator $validator, array $data): void;

    /**
     * Get default validated data structure
     */
    public function getValidatedDefaults(): array;
}
