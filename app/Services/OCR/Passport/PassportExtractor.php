<?php

namespace App\Services\OCR\Passport;

use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrUtils;

class PassportExtractor
{
    use OcrUtils;

    private array $extractedData = [];

    public function __construct(
        private object $data,
    ) {}

    public function extractPassportData(): self
    {
        $ocrData = [$this->data];

        if (! is_object($ocrData) && ! is_array($ocrData)) {
            return $this;
        }

        $data = $this->ensureArray($ocrData[0]);

        LoggerService::info(self::class.' - Passport extractor data', extra: [
            'data' => $data,
        ]);

        $this->extractedData = array_merge($this->extractedData, $this->getCleanData([
            'passport_number' => $data['passportNumber'] ?? null,
            'passport_country' => $data['issuingCountry'] ?? null,
            'passport_expiry_date' => $this->formatDate($data['expiryDate'] ?? null),
        ]));

        return $this;
    }

    public function getExtractedData(): array
    {
        LoggerService::info(self::class.' - Passport data extractor completed', extra: [
            'extracted_data' => $this->extractedData,
        ]);

        return $this->extractedData;
    }
}
