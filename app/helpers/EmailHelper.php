<?php

namespace App\helpers;

class EmailHelper
{
    /**
     * Strip invisible Unicode characters and normalise whitespace from an
     * email address string. Brevo (Sendinblue) rejects addresses that contain
     * zero-width spaces (\u200b), zero-width non-joiners, BOM characters, and
     * other non-printable code-points that are invisible in most UIs but
     * cause "email is not valid" API errors.
     *
     * Returns null when the resulting string is not a valid RFC 5322 address
     * so callers can skip it cleanly.
     */
    public static function sanitize(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $cleaned = preg_replace('/[\x00-\x1F\x7F\x{AD}\x{200B}-\x{200D}\x{2060}\x{FEFF}]/u', '', $email);

        $cleaned = trim((string) $cleaned);

        if ($cleaned === '' || ! filter_var($cleaned, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $cleaned;
    }
}
