<?php

declare(strict_types=1);

namespace App\Services\OCR\EmiratesId;

use App\Services\OCR\OcrUtils;
use Carbon\Carbon;

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
            if (! is_object($ocrData) && ! is_array($ocrData)) {
                continue;
            }

            $data = is_object($ocrData) ? (array) $ocrData : $ocrData;

            $this->extractedData = array_merge($this->extractedData, OcrUtils::getCleanData([
                'eid_number' => $data['idNumber'] ?? $this->extractedData['eid_number'],
                'name' => $data['name'] ?? $this->extractedData['name'],
                'date_of_birth' => $this->formatDate($data['dateOfBirth'] ?? null) ?: $this->extractedData['date_of_birth'],
                'nationality' => $data['nationality'] ?? $this->extractedData['nationality'],
                'sex' => $this->formatGender($data['sex'] ?? null) ?: $this->extractedData['sex'],
                'issuing_date' => $this->formatDate($data['issuingDate'] ?? null) ?: $this->extractedData['issuing_date'],
                'expiry_date' => $this->formatDate($data['expiryDate'] ?? null) ?: $this->extractedData['expiry_date'],
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

    private function formatDate(?string $date): ?string
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

    private function formatGender(?string $gender): ?string
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

}
