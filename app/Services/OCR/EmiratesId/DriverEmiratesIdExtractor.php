<?php

declare(strict_types=1);

namespace App\Services\OCR\EmiratesId;

use App\Services\OCR\OcrUtils;

class DriverEmiratesIdExtractor
{
    use OcrUtils;

    private array $extractedData = [];

    public function __construct(
        private object $data,
    ) {}

    public function extractDriverEmiratesIdData(): self
    {
        $this->extractedData = [
            'eid_number' => null,
            'driver_name' => null,
            'date_of_birth' => null,
            'nationality' => null,
            'sex' => null,

            'ocr_processed_at' => now()->toDateTimeString(),
            'ocr_model' => null,
            'ocr_provider' => null,
        ];

        $ocrDataArray = [$this->data];

        foreach ($ocrDataArray as $ocrData) {
            if (! is_object($ocrData) && ! is_array($ocrData)) {
                continue;
            }

            $data = $this->ensureArray($ocrData);

            $this->extractedData = array_merge($this->extractedData, $this->getCleanData([
                'eid_number' => $data['idNumber'] ?? null,
                'driver_name' => $data['name'] ?? null,
                'date_of_birth' => $this->formatDate($data['dateOfBirth'] ?? null),
                'nationality' => $data['nationality'] ?? null,
                'sex' => $this->formatGender($data['sex'] ?? null),
                'ocr_model' => $data['metadata']?->model ?? null,
                'ocr_provider' => $data['metadata']?->provider ?? null,
            ]));
        }

        return $this;
    }

    public function getExtractedData(): array
    {
        return $this->extractedData;
    }
}
