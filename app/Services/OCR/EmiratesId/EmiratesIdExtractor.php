<?php

declare(strict_types=1);

namespace App\Services\OCR\EmiratesId;

use App\Services\OCR\OcrUtils;

class EmiratesIdExtractor
{
    private array $extractedData = [];

    public function __construct(
        private object $data,
    ) {}

    public function extractEmiratesIdData(): self
    {
        $this->extractedData = [
            'eid_number' => null,
            'name' => null,
            'date_of_birth' => null,
            'nationality' => null,
            'sex' => null,

            'issuing_date' => null,
            'expiry_date' => null,
            'issuing_place' => null,

            'occupation' => null,
            'sponsor' => null,

            'country' => null,
            'card_type' => null,

            'ocr_processed_at' => now()->toDateTimeString(),
            'ocr_model' => null,
            'ocr_provider' => null,
        ];

        $ocrDataArray = [$this->data];

        foreach ($ocrDataArray as $ocrData) {
            if (!is_object($ocrData) && !is_array($ocrData)) {
                continue;
            }

            $data = OcrUtils::ensureArray($ocrData);

            $this->extractedData = array_merge($this->extractedData, OcrUtils::getCleanData([
                'eid_number' => $data['idNumber'] ?? null,
                'name' => $data['name'] ?? null,
                'date_of_birth' => OcrUtils::formatDate($data['dateOfBirth'] ?? null),
                'nationality' => $data['nationality'] ?? null,
                'sex' => OcrUtils::formatGender($data['sex'] ?? null),
                'issuing_date' => OcrUtils::formatDate($data['issuingDate'] ?? null),
                'expiry_date' => OcrUtils::formatDate($data['expiryDate'] ?? null),
                'issuing_place' => $data['issuingPlace'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'sponsor' => $data['sponsor'] ?? null,
                'country' => $data['country'] ?? null,
                'card_type' => $data['cardType'] ?? null,
                'ocr_model' => $data['model'] ?? null,
                'ocr_provider' => $data['provider'] ?? null,
            ]));
        }

        return $this;
    }

    public function extractSingleEmiratesId(): self
    {
        return $this->extractEmiratesIdData();
    }

    public function getExtractedData(): array
    {
        return $this->extractedData;
    }
}