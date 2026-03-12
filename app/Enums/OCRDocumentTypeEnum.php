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
    case ID_CARD = 'IDC'; // Emirates ID
    case DRIVER_EMIRATES_ID = 'DRIVER_EID'; // Driver Emirates ID
    case VISA = 'VI';
    case PASSPORT = 'PP';
    case REGISTRATION_CERTIFICATE = 'RC'; // Car Registration Certificate - Mulkiya
    case POLICY_SCHEDULE = 'PS';

    public static function getDocumentType(DocumentType $documentType): ?self
    {
        return match ($documentType->code) {
            'DL_CAR' => self::DRIVING_LICENSE,
            'DL' => self::DRIVING_LICENSE,
            'TI' => self::TAX_INVOICE,
            'CPC' => self::CERTIFICATE_OF_ISSUANCE,
            'CTIRBB' => self::TAX_INVOICE_RAISED_BY_BUYER,
            'CPS' => self::MOTOR_INSURANCE_POLICY_SCHEDULE,
            'CEID' => self::ID_CARD,
            'CAR_MULKIY' => self::REGISTRATION_CERTIFICATE,
            'EID_CAR' => self::ID_CARD,
            DocumentTypeCode::DEVICE_SMARTPHONE_EMIRATES_ID => self::ID_CARD,
            'SAV_EID' => self::ID_CARD,
            'CYB_EID' => self::ID_CARD,
            'MEEID' => self::ID_CARD,
            'MEPP' => self::PASSPORT,
            'MEV' => self::VISA,
            'DRIVER_EID' => self::DRIVER_EMIRATES_ID,

            'PS' => self::POLICY_SCHEDULE,
            'GH_PS' => self::MOTOR_INSURANCE_POLICY_SCHEDULE, // Group Health Policy Schedule
            // Send Update document types
            'SUTAXINV' => self::TAX_INVOICE, // Send Update Tax Invoice
            'SUTAXINVRB' => self::TAX_INVOICE_RAISED_BY_BUYER, // Send Update Tax Invoice Raised Buyer
            'SUPC' => self::CERTIFICATE_OF_ISSUANCE, // Send Update Policy Certificate
            'SUPS' => self::MOTOR_INSURANCE_POLICY_SCHEDULE, // Send Update Policy Schedule
            default => null,
        };
    }

    public static function isOCREnabled(DocumentType $documentType, QuoteTypes $quoteType)
    {
        $documentType = self::getDocumentType($documentType);

        if (! $documentType) {
            return false;
        }

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
                self::ID_CARD,
                self::DRIVER_EMIRATES_ID,
                self::REGISTRATION_CERTIFICATE,
                self::DRIVING_LICENSE,
            ],
            QuoteTypes::GROUP_MEDICAL => [
                self::TAX_INVOICE,
                self::TAX_INVOICE_RAISED_BY_BUYER,
                self::POLICY_SCHEDULE,
                self::MOTOR_INSURANCE_POLICY_SCHEDULE,
            ],
            QuoteTypes::HOME => [
                self::TAX_INVOICE,
                self::TAX_INVOICE_RAISED_BY_BUYER,
                self::POLICY_SCHEDULE,
                self::MOTOR_INSURANCE_POLICY_SCHEDULE,
            ],
            QuoteTypes::DEVICE => [
                self::ID_CARD,
            ],
            QuoteTypes::CYBER => [
                self::ID_CARD,
            ],
            QuoteTypes::SAVINGS => [
                self::ID_CARD,
            ],
            QuoteTypes::HEALTH => [
                self::ID_CARD,
                self::PASSPORT,
                self::VISA,
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
