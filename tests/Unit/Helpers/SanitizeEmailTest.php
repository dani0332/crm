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
