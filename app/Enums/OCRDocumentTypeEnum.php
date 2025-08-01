<?php

namespace App\Enums;

use App\Models\DocumentType;

enum OCRDocumentTypeEnum: string
{
    case DRIVING_LICENSE = 'DL';
    case TAX_INVOICE = 'TI';
    case TAX_INVOICE_RAISED_BY_BUYER = 'TIB';
    case CERTIFICATE_OF_ISSUANCE = 'PC';
    case MOTOR_INSURANCE_POLICY_SCHEDULE = 'MPS';
    case ID_CARD = 'IDC';
    case VISA = 'VI';
    case PASSPORT = 'PP';

    public static function getDocumentType(DocumentType $documentType): ?self
    {
        return match ($documentType->code) {
            'DL_CAR' => self::DRIVING_LICENSE,
            'TI' => self::TAX_INVOICE,
            'CPC' => self::CERTIFICATE_OF_ISSUANCE,
            'CTIRBB' => self::TAX_INVOICE_RAISED_BY_BUYER,
            'CPS' => self::MOTOR_INSURANCE_POLICY_SCHEDULE,
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
            QuoteTypes::CAR => [
                self::TAX_INVOICE,
                self::TAX_INVOICE_RAISED_BY_BUYER,
                self::CERTIFICATE_OF_ISSUANCE,
                self::MOTOR_INSURANCE_POLICY_SCHEDULE,
            ],
            default => [],
        };
    }

    public function isEnabled(QuoteTypes $quoteType)
    {
        return in_array($this, self::getEnabledTypes($quoteType));
    }

    public static function asArray(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->name] = [
                'name' => $case->name,
                'value' => $case->value,
            ];
        }

        return $result;
    }

}
