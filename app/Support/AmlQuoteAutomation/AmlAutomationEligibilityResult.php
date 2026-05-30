<?php

declare(strict_types=1);

namespace App\Support\AmlQuoteAutomation;

/**
 * Immutable result from {@see AmlAutomationEligibilityService::check()}.
 *
 * Callers should call {@see self::isEligible()} and, on failure, surface
 * {@see $reason} to logs / API responses and {@see $reasonCode} to structured
 * telemetry / test assertions.
 */
final class AmlAutomationEligibilityResult
{
    private function __construct(
        public readonly bool $eligible,
        /** Human-readable explanation returned to the caller (e.g. API response message). */
        public readonly string $reason,
        /** Machine-readable short code for logging and test assertions. */
        public readonly string $reasonCode,
        /** Suggested HTTP status for API callers; 200 when eligible. */
        public readonly int $httpStatus,
    ) {}

    public static function pass(): self
    {
        return new self(true, '', '', 200);
    }

    /**
     * @param  int  $httpStatus  Defaults to 422 (Unprocessable Entity).
     */
    public static function block(string $reason, string $reasonCode, int $httpStatus = 422): self
    {
        return new self(false, $reason, $reasonCode, $httpStatus);
    }

    public function isEligible(): bool
    {
        return $this->eligible;
    }
}
