<?php

namespace App\Enums;

use App\Models\DocumentType;

enum OCRDocumentTypeEnum:string
{
    case DRIVING_LICENSE = 'DL';
    case TAX_INVOICE = 'TI';

    public static function getDocumentType(DocumentType $documentType): ?self
    {
        return match ($documentType->code) {
            'DL_CAR' => self::DRIVING_LICENSE,
            'TI' => self::TAX_INVOICE,
            default => null,
        };
    }

    public static function isOCREnabled(DocumentType $documentType)
    {
        $documentType = self::getDocumentType($documentType);

        return $documentType?->isEnabled() ?? false;
    }

    public function isEnabled()
    {
        return match($this) {
            self::DRIVING_LICENSE, self::TAX_INVOICE => true,
            default => false,
        };
    }
}
