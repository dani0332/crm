<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Describes why a lead's quote status changed, for lead history and customer-facing messaging.
 * Values are stable API keys (snake_case). {@see self::label()} formats the backing value for display
 * (the shared {@see Enumable} trait uses the case *name*, which does not fit snake_case values).
 */
enum StatusChangeActionEnum: string
{
    use Enumable;

    /**
     * Human-readable title derived from the snake_case backing value (e.g. for UI / notifications).
     */
    public function label(): string
    {
        return Str::title(Str::lower(str_replace('_', ' ', $this->value)));
    }

    /**
     * Sage book-policy job remained in "processing" past the threshold; the mark-failed command
     * marked the Sage process failed and aligned the lead to policy booking failed.
     */
    case SagePolicyBookingProcessTimeout = 'sage_policy_booking_process_timeout';
}
