<?php

declare(strict_types=1);

namespace App\Services\OCR;

use Carbon\Carbon;

class OcrUtils
{
    public static function getCleanData(array $data): array
    {
        return array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });
    }

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

    public static function formatDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function extractFirstName(?string $fullName): ?string
    {
        if (empty($fullName)) {
            return null;
        }

        $nameParts = explode(' ', trim($fullName));

        return $nameParts[0] ?? null;
    }

    public static function extractLastName(?string $fullName): ?string
    {
        if (empty($fullName)) {
            return null;
        }

        $nameParts = explode(' ', trim($fullName));
        if (count($nameParts) > 1) {
            // Join all parts except the first as last name
            return implode(' ', array_slice($nameParts, 1));
        }

        return null;
    }

    public static function formatGender(?string $gender): ?string
    {
        if (empty($gender)) {
            return null;
        }

        return match (strtoupper(trim($gender))) {
            'M', 'MALE' => 'Male',
            'F', 'FEMALE' => 'Female',
            default => $gender
        };
    }

    public static function ensureArray($data): array
    {
        if (is_object($data)) {
            return (array) $data;
        }

        return is_array($data) ? $data : [];
    }

    public static function extractPlateCodeNumber(?string $plateNumber): ?array
    {
        if (empty($plateNumber)) {
            return null;
        }

        $cleaned = trim($plateNumber);
        if (strpos($cleaned, '/') !== false) {
            $parts = explode('/', $cleaned, 2);
            return [
                'place_code' => trim($parts[0]),
                'plate_number' => trim($parts[1])
            ];
        }

        return [
            'place_code' => null,
            'plate_number' => null
        ];
    }
}
