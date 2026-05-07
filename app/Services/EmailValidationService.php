<?php

declare(strict_types=1);

namespace App\Services;

class EmailValidationService
{
    /**
     * Whitespace plus markers often copied with pasted emails (wildcards, SQL/CRM
     * noise, quotes, brackets). Stripped from both ends only — never from the
     * middle — so local parts like user&name@ or user*name@ stay intact.
     */
    private const LEADING_TRAILING_JUNK = " \t\n\r\0\x0B\"<>[](){},;\\";

    /**
     * Characters removed from anywhere in the string (invisible controls, bidi,
     * exotic Unicode spaces). Same idea as stripping U+200B mid-address — these
     * should never be meaningful parts of an RFC 5322 address in this app.
     */
    private const UNICODE_NOISE_PATTERN = '/[\x00-\x1F\x7F\x{AD}\x{00A0}\x{2000}-\x{200D}\x{200E}-\x{200F}\x{2028}-\x{202E}\x{202F}\x{205F}\x{2060}-\x{2069}\x{FEFF}\x{3000}]/u';

    /**
     * CRM/imports sometimes store the *spelling* of an escape sequence (e.g. {@code u200b})
     * instead of the real character. Those literals are valid in the local part for
     * {@see filter_var} but break providers like Brevo.
     */
    private const LITERAL_INVISIBLE_LEADING_PATTERN = '/^(?:
        \\\\u[0-9a-f]{4}
        |\\\\U[0-9a-f]{4}
        |U\+[0-9a-f]{4,6}
        |u\+[0-9a-f]{4,6}
        |u(?:
            200[b-f]
            |202[8-9a-e]
            |202f
            |205f
            |206[0-9]
            |00a0
            |00ad
            |feff
            |3000
        )
        |&\#x200[b-f];?
        |&\#820[3-7];?
    )+/ix';

    /**
     * Matches a leading {@code mailto:} (case-insensitive) so pasted
     * {@code mailto:user@example.com} values are normalised back to the bare
     * address before further cleaning.
     */
    private const MAILTO_PREFIX_PATTERN = '/^mailto:/i';

    private const LITERAL_INVISIBLE_TRAILING_PATTERN = '/(?:
        \\\\u[0-9a-f]{4}
        |\\\\U[0-9a-f]{4}
        |U\+[0-9a-f]{4,6}
        |u\+[0-9a-f]{4,6}
        |u(?:
            200[b-f]
            |202[8-9a-e]
            |202f
            |205f
            |206[0-9]
            |00a0
            |00ad
            |feff
            |3000
        )
        |&\#x200[b-f];?
        |&\#820[3-7];?
    )+$/ix';

    /**
     * Strip invisible Unicode characters, normalise whitespace, and trim stray
     * wrapper characters from both ends. Brevo (Sendinblue) rejects addresses that
     * contain zero-width spaces (\u200b), zero-width non-joiners, BOM characters,
     * and other non-printable code-points that are invisible in most UIs but cause
     * "email is not valid" API errors.
     *
     * Also normalises a few copy-paste issues: fullwidth @ (U+FF20) and
     * fullwidth period (U+FF0E), {@link mailto:} prefixes, NBSP and other Unicode
     * spaces, and a trailing dot after the domain (sentence or DNS-style paste).
     * Leading {@link mailto:} (after whitespace trim only) is removed before
     * spelled-out invisible sequences are stripped from the local part so values
     * like {@code mailto:u200buser@example.com} are not left as {@code u200buser@…}.
     * Broader end-junk trimming runs after that so {@code &#…;} entities are not
     * broken by stripping {@code &} and {@code #} too early.
     *
     * Returns null when the resulting string is not a valid RFC 5322 address
     * so callers can skip it cleanly.
     */
    public static function sanitize(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $cleaned = str_replace(["\u{FF20}", "\u{FF0E}"], ['@', '.'], $email);

        $cleaned = preg_replace(self::UNICODE_NOISE_PATTERN, '', $cleaned);

        $cleaned = self::stripLeadingMailtoAfterWhitespaceTrim((string) $cleaned);

        $cleaned = self::stripLiteralInvisibleEscapesInLocalPart($cleaned);

        $cleaned = self::trimMailtoAndJunk($cleaned);

        $cleaned = rtrim($cleaned, '.');

        if ($cleaned === '' || ! filter_var($cleaned, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $cleaned;
    }

    private static function stripLiteralInvisibleEscapesInLocalPart(string $email): string
    {
        $atPos = strpos($email, '@');
        if ($atPos === false) {
            return $email;
        }

        $local = substr($email, 0, $atPos);
        $domain = substr($email, $atPos + 1);

        $previous = null;
        while ($previous !== $local) {
            $previous = $local;
            $local = preg_replace(self::LITERAL_INVISIBLE_LEADING_PATTERN, '', $local) ?? $local;
            $local = preg_replace(self::LITERAL_INVISIBLE_TRAILING_PATTERN, '', $local) ?? $local;
        }

        if ($domain === '') {
            return $email;
        }

        return $local.'@'.$domain;
    }

    /**
     * Remove {@link mailto:} after trimming ASCII/Unicode whitespace only. Used
     * before {@see stripLiteralInvisibleEscapesInLocalPart} so the local part is
     * not prefixed with {@code mailto:} (which would block literal-escape
     * patterns). Does not use {@see LEADING_TRAILING_JUNK} so {@code &#…;}
     * spellings stay intact for the next step.
     */
    private static function stripLeadingMailtoAfterWhitespaceTrim(string $value): string
    {
        $previous = null;

        while ($previous !== $value) {
            $previous = $value;
            $value = trim($value);

            if (preg_match(self::MAILTO_PREFIX_PATTERN, $value) === 1) {
                $value = (string) preg_replace(self::MAILTO_PREFIX_PATTERN, '', $value, 1);
            }
        }

        return $value;
    }

    private static function trimMailtoAndJunk(string $value): string
    {
        $previous = null;

        while ($previous !== $value) {
            $previous = $value;
            $value = trim($value, self::LEADING_TRAILING_JUNK);

            if (preg_match(self::MAILTO_PREFIX_PATTERN, $value) === 1) {
                $value = (string) preg_replace(self::MAILTO_PREFIX_PATTERN, '', $value, 1);
            }
        }

        return $value;
    }
}
