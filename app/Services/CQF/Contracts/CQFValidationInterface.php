<?php

declare(strict_types=1);

namespace App\Services\CQF\Contracts;

use Illuminate\Database\Eloquent\Model;

interface CQFValidationInterface
{
    /**
     * Validate quote for CQF renewal. Returns success flag and errors.
     *
     * @return array{success: bool, errors: array<string, string>}
     */
    public function validateQuote(Model $quote): array;

    /**
     * Check if quote is a duplicate (already has renewal lead created).
     */
    public function isDuplicateQuote(Model $quote): bool;

    /**
     * Check if quote is an Insly renewal and if the renewal criteria is met.
     */
    public function checkInslyRenewal(Model $quote): bool;

    /**
     * Custom validation messages keyed by rule.
     *
     * @return array<string, string>
     */
    public function getValidationMessages(): array;
}
