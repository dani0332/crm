<?php

namespace App\Services;

class EmailValidationService
{
    /**
     * Whitespace plus markers often copied with pasted emails (wildcards, SQL/CRM
     * noise, quotes, brackets). Stripped from both ends only — never from the
     * middle — so local parts like user&name@ or user*name@ stay intact.
     */
    private const LEADING_TRAILING_JUNK = " \t\n\r\0\x0B%*^&'\"<>[](){}|~,;`";

    /**
     * Strip invisible Unicode characters, normalise whitespace, and trim stray
     * wrapper characters from both ends. Brevo (Sendinblue) rejects addresses that
     * contain zero-width spaces (\u200b), zero-width non-joiners, BOM characters,
     * and other non-printable code-points that are invisible in most UIs but cause
     * "email is not valid" API errors.
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

        $cleaned = trim((string) $cleaned, self::LEADING_TRAILING_JUNK);

        if ($cleaned === '' || ! filter_var($cleaned, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $cleaned;
    }
}
