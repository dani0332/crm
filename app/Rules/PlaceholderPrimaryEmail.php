<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PlaceholderPrimaryEmail implements ValidationRule
{
    private const PLACEHOLDER_EMAIL_PATTERN = '/^[^@\s]*noemail[^@\s]*@gmail\.com$/i';

    public static function isPlaceholder(?string $email): bool
    {
        if (! is_string($email) || trim($email) === '') {
            return false;
        }

        return preg_match(self::PLACEHOLDER_EMAIL_PATTERN, trim($email)) === 1;
    }

    public static function message(): string
    {
        return 'This action is blocked because the customer primary email is a noemail@gmail.com. Please update the primary email before proceeding.';
    }

    public static function hasPlaceholderPrimaryEmail(?object $quote): bool
    {
        if (! $quote) {
            return false;
        }

        if (method_exists($quote, 'loadMissing')) {
            $quote->loadMissing('customer:id,email');
        }

        return self::isPlaceholder(data_get($quote, 'email'))
            || self::isPlaceholder(data_get($quote, 'customer.email'));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::isPlaceholder(is_string($value) ? $value : null)) {
            $fail(self::message());
        }
    }
}
