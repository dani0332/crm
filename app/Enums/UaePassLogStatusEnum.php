<?php

namespace App\Enums;

enum UaePassLogStatusEnum: string
{
    case PASSED = 'Passed';
    case FAILED = 'Failed';

    public function tagColor(): string
    {
        return match ($this) {
            self::PASSED => 'success',
            self::FAILED => 'error',
        };
    }

    /**
     * UI tag color (e.g. Vue `x-tag` `color` prop). Uses {@see tagColor()} when status maps to this enum, otherwise `secondary`.
     */
    public static function resolveTagColor(mixed $status): string
    {
        return self::tryFromStorage($status)?->tagColor() ?? 'secondary';
    }

    /**
     * Resolve a stored or API status string to the enum (handles casing like "passed" / "PASSED").
     */
    public static function tryFromStorage(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return self::tryFrom($trimmed)
            ?? self::tryFrom(ucfirst(strtolower($trimmed)));
    }
}
