<?php

declare(strict_types=1);

namespace App\Enums;

use App\Repositories\PersonalQuoteRepository;

/**
 * Describes why a lead's quote status changed, for lead history and customer-facing messaging.
 * Values are stable API keys (snake_case). {@see self::label()} formats the backing value for display
 * (the shared {@see Enumable} trait uses the case *name*, which does not fit snake_case values).
 */
enum StatusChangeActionEnum: string
{
    use Enumable;

    /**
     * After synchronous Bridger jobs for all insured members, no failures: lead log aligned to AML cleared.
     */
    case BridgerAmlMemberScreeningCleared = 'bridger_aml_member_screening_cleared';

    /**
     * After synchronous Bridger member screening, session or AML checks indicated failure / potential matches.
     */
    case BridgerAmlMemberScreeningFailed = 'bridger_aml_member_screening_failed';

    /**
     * Sage book-policy job remained in "processing" past the threshold; the mark-failed command
     * marked the Sage process failed and aligned the lead to policy booking failed.
     */
    case SagePolicyBookingProcessTimeout = 'sage_policy_booking_process_timeout';

    /**
     * Lead status updated through the personal quote API ({@see PersonalQuoteRepository}).
     */
    case PersonalQuoteLeadStatusUpdate = 'personal_quote_lead_status_update';

    /**
     * Send update was cancelled; main lead quote status was restored from pre-endorsement lead history.
     */
    case SendUpdateCancelled = 'send_update_cancelled';

    /**
     * Main lead quote status was aligned with send update booking workflow (e.g. cancellation pending, policy cancelled/booked).
     */
    case SendUpdateBookingSyncQuoteStatus = 'send_update_booking_sync_quote_status';

    /**
     * Human-readable title for UI / notifications.
     */
    public function label(): string
    {
        return match ($this) {
            self::BridgerAmlMemberScreeningCleared => 'AML screening cleared (Bridger, all members)',
            self::BridgerAmlMemberScreeningFailed => 'AML screening failed (Bridger, potential matches or failed checks)',
            self::SagePolicyBookingProcessTimeout => 'Sage Policy Booking Process Timeout',
            self::PersonalQuoteLeadStatusUpdate => 'Lead status updated (personal quote API)',
            self::SendUpdateCancelled => 'Send update cancelled',
            self::SendUpdateBookingSyncQuoteStatus => 'Send update booking: quote status synced',
        };
    }
}
