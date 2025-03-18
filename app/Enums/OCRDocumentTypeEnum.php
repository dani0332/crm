<?php

namespace App\Enums;

use App\Models\DocumentType;

enum OCRDocumentTypeEnum: string
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

    public static function isOCREnabled(DocumentType $documentType, QuoteTypes $quoteType)
    {
        $documentType = self::getDocumentType($documentType);

        return $documentType?->isEnabled($quoteType);
    }

    public static function getEnabledTypes(QuoteTypes $quoteType)
    {
        return match ($quoteType) {
            QuoteTypes::CAR => [self::DRIVING_LICENSE, self::TAX_INVOICE],
            default => [],
        };
    }

    public function isEnabled(QuoteTypes $quoteType)
    {
        return in_array($this, self::getEnabledTypes($quoteType));
    }
}
