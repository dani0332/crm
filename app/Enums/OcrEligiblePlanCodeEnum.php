<?php

declare(strict_types=1);

namespace App\Enums;

enum OcrEligiblePlanCodeEnum: string
{
    case STF_158 = 'STF - 158';

    /**
     * Eligible plan codes per QuoteType -> DocType -> [planCode...]
     */
    public static function mapping(): array
    {
        return [
            QuoteTypes::SAVINGS->value => [
                OCRDocumentTypeEnum::PASSPORT->value => [
                    self::STF_158->value,
                ],
            ],
        ];
    }

    public static function asArray(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->name] = $case->value;
        }

        return $result;
    }
}
