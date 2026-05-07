<?php

use App\Services\EmailValidationService;

// ── null / empty input ────────────────────────────────────────────────────────

it('returns null for a null input', function (): void {
    expect(EmailValidationService::sanitize(null))->toBeNull();
});

it('returns null for an empty string', function (): void {
    expect(EmailValidationService::sanitize(''))->toBeNull();
});

it('returns null for a whitespace-only string', function (): void {
    expect(EmailValidationService::sanitize('   '))->toBeNull();
});

// ── clean, valid addresses (must pass through unchanged) ─────────────────────

it('accepts a plain lowercase address', function (): void {
    expect(EmailValidationService::sanitize('user@example.com'))->toBe('user@example.com');
});

it('accepts mixed-case address', function (): void {
    expect(EmailValidationService::sanitize('User.Name@Example.COM'))->toBe('User.Name@Example.COM');
});

it('trims leading and trailing whitespace from a valid address', function (): void {
    expect(EmailValidationService::sanitize('  user@example.com  '))->toBe('user@example.com');
});

it('trims common wrapper characters from both ends without touching the local part', function (string $dirty, string $expected): void {
    expect(EmailValidationService::sanitize($dirty))->toBe($expected);
})->with([
    'double quotes' => ['"user@example.com"', 'user@example.com'],
    'angle brackets' => ['<user@example.com>', 'user@example.com'],
    'parentheses' => ['(user@example.com)', 'user@example.com'],
    'square brackets' => ['[user@example.com]', 'user@example.com'],
    'comma list fragment' => [',user@example.com,', 'user@example.com'],
]);

it('does not silently alter valid emails by stripping valid RFC 5322 characters from the ends', function (string $email): void {
    expect(EmailValidationService::sanitize($email))->toBe($email);
})->with([
    'leading dollar sign' => ['$admin@example.com'],
    'trailing exclamation' => ['user!@example.com'],
    'leading hash' => ['#channel@example.com'],
]);

it('returns null for invalid addresses with junk characters that are no longer auto-stripped', function (string $invalid): void {
    expect(EmailValidationService::sanitize($invalid))->toBeNull();
})->with([
    'percent wildcards' => ['%zeeshan232@gmail.com%'],
    'asterisk' => ['*user@example.com*'],
    'caret' => ['^user@example.com^'],
    'ampersand' => ['&user@example.com&'],
    'pipe' => ['|user@example.com|'],
]);

// ── RFC 5322 special characters in the local part ────────────────────────────

it('accepts all RFC 5322 allowed special characters in the local part', function (string $email): void {
    expect(EmailValidationService::sanitize($email))->toBe($email);
})->with([
    'exclamation mark' => ['user!name@example.com'],
    'hash' => ['user#name@example.com'],
    'dollar sign' => ['user$name@example.com'],
    'percent' => ['user%name@example.com'],
    'ampersand' => ['user&name@example.com'],
    'single quote' => ["user'name@example.com"],
    'asterisk' => ['user*name@example.com'],
    'plus sign' => ['user+name@example.com'],
    'hyphen' => ['user-name@example.com'],
    'slash' => ['user/name@example.com'],
    'equals sign' => ['user=name@example.com'],
    'question mark' => ['user?name@example.com'],
    'caret' => ['user^name@example.com'],
    'underscore' => ['user_name@example.com'],
    'backtick' => ['user`name@example.com'],
    'opening brace' => ['user{name@example.com'],
    'pipe' => ['user|name@example.com'],
    'closing brace' => ['user}name@example.com'],
    'tilde' => ['user~name@example.com'],
]);

// ── dot rules in the local part ───────────────────────────────────────────────

it('accepts a dot in the middle of the local part', function (): void {
    expect(EmailValidationService::sanitize('first.last@example.com'))->toBe('first.last@example.com');
});

it('returns null when the local part starts with a dot', function (): void {
    expect(EmailValidationService::sanitize('.user@example.com'))->toBeNull();
});

it('returns null when the local part ends with a dot', function (): void {
    expect(EmailValidationService::sanitize('user.@example.com'))->toBeNull();
});

it('returns null when the local part has consecutive dots', function (): void {
    expect(EmailValidationService::sanitize('user..name@example.com'))->toBeNull();
});

// ── domain part rules ─────────────────────────────────────────────────────────

it('accepts a sub-domain address', function (): void {
    expect(EmailValidationService::sanitize('user@mail.example.com'))->toBe('user@mail.example.com');
});

it('accepts a hyphen in the domain', function (): void {
    expect(EmailValidationService::sanitize('user@my-company.com'))->toBe('user@my-company.com');
});

it('returns null when the domain is missing', function (): void {
    expect(EmailValidationService::sanitize('userexample.com'))->toBeNull();
});

it('returns null when the TLD is missing', function (): void {
    expect(EmailValidationService::sanitize('user@example'))->toBeNull();
});

it('returns null when there are spaces in the address', function (): void {
    expect(EmailValidationService::sanitize('user name@example.com'))->toBeNull();
});

// ── invisible / zero-width Unicode characters ─────────────────────────────────

it('strips a leading zero-width space (U+200B) and returns a valid address', function (): void {
    expect(EmailValidationService::sanitize("\u{200B}a.stratton@irefze.com"))->toBe('a.stratton@irefze.com');
});

it('strips a zero-width space embedded mid-address', function (): void {
    expect(EmailValidationService::sanitize("user\u{200B}@example.com"))->toBe('user@example.com');
});

it('strips a zero-width non-joiner (U+200C)', function (): void {
    expect(EmailValidationService::sanitize("\u{200C}user@example.com"))->toBe('user@example.com');
});

it('strips a zero-width joiner (U+200D)', function (): void {
    expect(EmailValidationService::sanitize("\u{200D}user@example.com"))->toBe('user@example.com');
});

it('strips a word-joiner (U+2060)', function (): void {
    expect(EmailValidationService::sanitize("\u{2060}user@example.com"))->toBe('user@example.com');
});

it('strips a UTF-8 BOM (U+FEFF)', function (): void {
    expect(EmailValidationService::sanitize("\u{FEFF}user@example.com"))->toBe('user@example.com');
});

it('strips a soft hyphen (U+00AD)', function (): void {
    expect(EmailValidationService::sanitize("\u{00AD}user@example.com"))->toBe('user@example.com');
});

it('strips multiple different invisible characters in one address', function (): void {
    $dirty = "\u{FEFF}\u{200B}a.stratton\u{200C}@irefze.com\u{200D}";
    expect(EmailValidationService::sanitize($dirty))->toBe('a.stratton@irefze.com');
});

it('returns null when only invisible characters remain after stripping', function (): void {
    expect(EmailValidationService::sanitize("\u{200B}\u{FEFF}"))->toBeNull();
});

it('returns null when invisible characters surround an otherwise invalid address', function (): void {
    expect(EmailValidationService::sanitize("\u{200B}notanemail\u{200B}"))->toBeNull();
});

// ── ASCII control characters ──────────────────────────────────────────────────

it('strips an ASCII null byte from the address', function (): void {
    expect(EmailValidationService::sanitize("\x00user@example.com"))->toBe('user@example.com');
});

it('strips a carriage-return character from the address', function (): void {
    expect(EmailValidationService::sanitize("user\r@example.com"))->toBe('user@example.com');
});

it('strips a newline character from the address', function (): void {
    expect(EmailValidationService::sanitize("user\n@example.com"))->toBe('user@example.com');
});

// ── real-world duplicate from the production log ──────────────────────────────

it('sanitizes the exact address seen in the Axiom production log', function (): void {
    $raw = json_decode('"\u200ba.stratton@irefze.com"');
    expect(EmailValidationService::sanitize($raw))->toBe('a.stratton@irefze.com');
});

// ── mailto, unicode spaces, fullwidth chars, trailing dot ─────────────────────

it('strips a case-insensitive mailto prefix and surrounding junk', function (string $dirty, string $expected): void {
    expect(EmailValidationService::sanitize($dirty))->toBe($expected);
})->with([
    'mailto lower' => ['mailto:user@example.com', 'user@example.com'],
    'MAILTO upper' => ['MAILTO:user@example.com', 'user@example.com'],
    'wrapped mailto' => ['<mailto:user@example.com>', 'user@example.com'],
    'spaces after scheme' => ['  mailto:  user@example.com  ', 'user@example.com'],
]);

it('strips non-breaking space (U+00A0) and narrow no-break space (U+202F) like normal spaces', function (): void {
    expect(EmailValidationService::sanitize("\u{00A0}user@example.com\u{00A0}"))->toBe('user@example.com');
    expect(EmailValidationService::sanitize("user\u{00A0}@example.com"))->toBe('user@example.com');
    expect(EmailValidationService::sanitize("user@\u{202F}example.com"))->toBe('user@example.com');
});

it('normalises fullwidth commercial at (U+FF20) and full stop (U+FF0E)', function (): void {
    expect(EmailValidationService::sanitize("user\u{FF20}example.com"))->toBe('user@example.com');
    expect(EmailValidationService::sanitize("first\u{FF0E}last@example.com"))->toBe('first.last@example.com');
});

it('removes a trailing dot after the domain (sentence or DNS-style paste)', function (): void {
    expect(EmailValidationService::sanitize('user@example.com.'))->toBe('user@example.com');
    expect(EmailValidationService::sanitize('user@mail.example.com...'))->toBe('user@mail.example.com');
});

it('strips line and paragraph separators (U+2028 / U+2029) embedded in the address', function (): void {
    expect(EmailValidationService::sanitize("user\u{2028}@example.com"))->toBe('user@example.com');
    expect(EmailValidationService::sanitize("user@\u{2029}example.com"))->toBe('user@example.com');
});

it('strips bidirectional marks that often wrap pasted RTL or mixed text', function (): void {
    expect(EmailValidationService::sanitize("\u{200E}user@example.com\u{200F}"))->toBe('user@example.com');
});

// ── literal escape spellings (stored as ASCII "u200b", not real U+200B) ─────────

it('strips literal u200b-style prefixes in the local part (Brevo log / CRM exports)', function (): void {
    expect(EmailValidationService::sanitize('u200bzeeshan.haider@myalfred.com'))->toBe('zeeshan.haider@myalfred.com');
    expect(EmailValidationService::sanitize('U200Buser@example.com'))->toBe('user@example.com');
});

it('removes mailto before stripping spelled-out invisible prefixes on the local part', function (): void {
    expect(EmailValidationService::sanitize('mailto:u200buser@example.com'))->toBe('user@example.com');
    expect(EmailValidationService::sanitize('  mailto:&#x200c;user@example.com  '))->toBe('user@example.com');
});

it('strips JSON-style and unicode-plus spellings of zero-width at the start of the local part', function (): void {
    expect(EmailValidationService::sanitize('\\u200buser@example.com'))->toBe('user@example.com');
    expect(EmailValidationService::sanitize('U+200Buser@example.com'))->toBe('user@example.com');
    expect(EmailValidationService::sanitize('u+200buser@example.com'))->toBe('user@example.com');
});

it('strips decimal and hex HTML entity spellings for U+200B–U+200F at the start of the local part', function (): void {
    expect(EmailValidationService::sanitize('&#8203;user@example.com'))->toBe('user@example.com');
    expect(EmailValidationService::sanitize('&#8204;user@example.com'))->toBe('user@example.com');
    expect(EmailValidationService::sanitize('&#8205;user@example.com'))->toBe('user@example.com');
    expect(EmailValidationService::sanitize('&#x200b;user@example.com'))->toBe('user@example.com');
    expect(EmailValidationService::sanitize('&#x200c;user@example.com'))->toBe('user@example.com');
    expect(EmailValidationService::sanitize('&#x200D;user@example.com'))->toBe('user@example.com');
    expect(EmailValidationService::sanitize('&#x200f;user@example.com'))->toBe('user@example.com');
});

it('does not strip a legitimate local part that happens to contain u200b as intentional characters in the middle', function (): void {
    expect(EmailValidationService::sanitize('theu200buser@example.com'))->toBe('theu200buser@example.com');
});
