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
                'eid_number' => $data['idNumber'] ?? $this->extractedData['eid_number'],
                'name' => $data['name'] ?? $this->extractedData['name'],
                'date_of_birth' => OcrUtils::formatDate($data['dateOfBirth'] ?? null) ?: $this->extractedData['date_of_birth'],
                'nationality' => $data['nationality'] ?? $this->extractedData['nationality'],
                'sex' => OcrUtils::formatGender($data['sex'] ?? null) ?: $this->extractedData['sex'],
                'issuing_date' => OcrUtils::formatDate($data['issuingDate'] ?? null) ?: $this->extractedData['issuing_date'],
                'expiry_date' => OcrUtils::formatDate($data['expiryDate'] ?? null) ?: $this->extractedData['expiry_date'],
                'issuing_place' => $data['issuingPlace'] ?? $this->extractedData['issuing_place'],
                'occupation' => $data['occupation'] ?? $this->extractedData['occupation'],
                'sponsor' => $data['sponsor'] ?? $this->extractedData['sponsor'],
                'country' => $data['country'] ?? $this->extractedData['country'],
                'card_type' => $data['cardType'] ?? $this->extractedData['card_type'],
                'ocr_model' => $data['model'] ?? $this->extractedData['ocr_model'],
                'ocr_provider' => $data['provider'] ?? $this->extractedData['ocr_provider'],
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