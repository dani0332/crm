<?php

declare(strict_types=1);

namespace App\Services\OCR;

use Illuminate\Database\Eloquent\Model;

class OcrUtils
{
    /**
     * Clean array by removing null and empty string values
     */
    public static function getCleanData(array $data): array
    {
        return array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * Determine which fields should be updated based on new value from OCR
     * Always updates with OCR data when available (overwrites existing data)
     */
    public static function getFieldsToUpdate(array $fieldsToUpdate): array
    {
        $dataToUpdate = [];
        foreach ($fieldsToUpdate as $field => $value) {
            if ($value !== null && $value !== '') {
                $dataToUpdate[$field] = $value;
            }
        }

        return $dataToUpdate;
    }

    /**
     * Legacy method: Only update fields that are currently empty or null
     * Kept for backward compatibility if needed
     */
    public static function getFieldsToUpdateOnlyEmpty(array $fieldsToUpdate, Model $model): array
    {
        $dataToUpdate = [];
        foreach ($fieldsToUpdate as $field => $value) {
            if ($value !== null && $value !== '' && (empty($model->$field) || $model->$field === null)) {
                $dataToUpdate[$field] = $value;
            }
        }

        return $dataToUpdate;
    }
}
