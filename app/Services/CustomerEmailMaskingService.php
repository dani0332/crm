<?php

namespace App\Services;

/**
 * Masks customer email for policy communications (e.g. Travel WhatsApp template per FRD).
 *
 * Rules: first two + last two characters of the local part, middle replaced with asterisks;
 * full domain preserved. Usernames shorter than four characters use the edge-case rules.
 */
class CustomerEmailMaskingService
{
    /**
     * Apply masking for display in policy-ready templates (e.g. {MaskedEmail} on Bird).
     */
    public function maskPurchaseEmailForDisplay(?string $email): ?string
    {
        $trimmed = $email !== null ? trim($email) : '';

        if ($trimmed === '' || ! str_contains($trimmed, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', $trimmed, 2);
        $len = strlen($local);

        if ($len === 0) {
            return null;
        }

        $maskedLocal = match (true) {
            $len < 4 => $local[0].str_repeat('*', $len - 1),
            $len === 4 => $local[0].'***',
            default => substr($local, 0, 2).str_repeat('*', $len - 4).substr($local, -2),
        };

        return $maskedLocal.'@'.$domain;
    }
}
