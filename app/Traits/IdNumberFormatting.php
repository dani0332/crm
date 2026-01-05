<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Trait IdNumberFormatting
 *
 * Provides automatic formatting for Emirates ID numbers.
 * Formats as ###-####-#######-# when retrieving and stores without hyphens.
 */
trait IdNumberFormatting
{
    /**
     * Accessor for id_number: formats as ###-####-#######-# when id_type is emiratesId
     */
    protected function idNumber(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if (! $value) {
                    return $value;
                }

                // Only format if id_type is emiratesId
                if (($this->attributes['id_type'] ?? null) !== 'emiratesId') {
                    return $value;
                }

                // Remove any existing hyphens
                $clean = str_replace('-', '', $value);

                // Format as ###-####-#######-#
                if (strlen($clean) === 15) {
                    return formatEmiratesIdNumber($clean);
                }

                return $value;
            },
            set: function (?string $value): ?string {
                if (! $value) {
                    return $value;
                }

                // Only remove hyphens before saving if id_type is emiratesId
                if (($this->attributes['id_type'] ?? null) === 'emiratesId') {
                    return str_replace('-', '', $value);
                }

                return $value;
            }
        );
    }

    /**
     * Normalize Emirates ID in the given attributes array
     * Removes hyphens from id_number when id_type is emiratesId
     */
    protected static function normalizeEmiratesId(array $attributes): array
    {
        if (isset($attributes['id_type'], $attributes['id_number'])
            && $attributes['id_type'] === 'emiratesId'
            && is_string($attributes['id_number'])) {
            $attributes['id_number'] = str_replace('-', '', $attributes['id_number']);
        }

        return $attributes;
    }
}
