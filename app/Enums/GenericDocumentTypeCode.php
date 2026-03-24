<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Generic Document Type Code Enum
 *
 * Defines all generic document type codes used across the application
 */
enum GenericDocumentTypeCode: string
{
    case CLAIM_FORM = 'CLAIM_FORM';

    /**
     * Get all enum values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
