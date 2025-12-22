<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Activity Log Event Types Enum
 */
enum ActivityLogEventEnum: string
{
    case Accessed = 'accessed';
    case Updated = 'updated';
    case Created = 'created';
    case Deleted = 'deleted';

    /**
     * Get all event values as array
     *
     * @return array<string>
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get event options for select dropdown
     *
     * @return array<array{label: string, value: string}>
     */
    public static function getOptions(): array
    {
        return [
            ['label' => 'Accessed', 'value' => self::Accessed->value],
            ['label' => 'Updated', 'value' => self::Updated->value],
            ['label' => 'Created', 'value' => self::Created->value],
            ['label' => 'Deleted', 'value' => self::Deleted->value],
        ];
    }
}

