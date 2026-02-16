<?php

namespace App\Services\OCR\Visa;

use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrUtils;

class VisaExtractor
{
    use OcrUtils;

    private array $extractedData = [];

    public function __construct(
        private object $data
    ) {}

    public function extractVisaData(): self
    {
        $ocrData = [$this->data];

        if (! is_object($ocrData) && ! is_array($ocrData)) {
            return $this;
        }

        $data = $this->ensureArray($ocrData[0]);
        LoggerService::info(self::class.' - Visa extractor data', extra: [
            'data' => $data,
        ]);

        $this->extractedData = array_merge($this->extractedData, $this->getCleanData([
            'name' => $data['name'] ?? null,
            'visa_number' => $data['visaNumber'] ?? null,
            'visa_file_number' => $data['fileNumber'] ?? null,
            'visa_type' => $data['visaType'] ?? null,
            'visa_issue_date' => $this->formatDate($data['issuingDate'] ?? null),
            'visa_expiry_date' => $this->formatDate($data['expiryDate'] ?? null),
            'visa_issuance_authority' => $data['issuingAuthority'] ?? null,
            'profession' => $data['profession'] ?? null,
            'sponsor' => $data['sponsor'] ?? null,
        ]));

        return $this;
    }

    public function getExtractedData(): array
    {
        LoggerService::info(self::class.' - Visa data extractor completed', extra: [
            'extracted_data' => $this->extractedData,
        ]);

        return $this->extractedData;
    }
}
