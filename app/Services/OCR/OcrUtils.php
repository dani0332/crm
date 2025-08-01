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
     * Determine which fields should be updated based on new value and current model value
     */
    public static function getFieldsToUpdate(array $fieldsToUpdate, Model $model): array
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
