<?php

declare(strict_types=1);

namespace App\Services\OCR\DrivingLicense;

use App\Services\OCR\OcrUtils;

class DrivingLicenseExtractor
{
    private array $extractedData = [];

    public function __construct(
        private object $data,
    ) {}

    public function extractDrivingLicenseData(): self
    {
        $this->initializeExtractedData();
        $this->processOcrData();

        return $this;
    }

    private function initializeExtractedData(): void
    {
        $this->extractedData = [
            // Car Quote Request Detail fields (driving license related)
            'driver_license_number' => null,
            'driver_license_issue_date' => null,
            'driver_license_expiry_date' => null,
            'driver_license_issue_place' => null,
            'traffic_code_number' => null,
            'driver_first_name' => null,
            'driver_last_name' => null,
            'driver_dob' => null,
            'driver_gender' => null,
            'driver_nationality_string' => null,

            // Metadata
            'ocr_processed_at' => now()->toDateTimeString(),
            'ocr_model' => null,
            'ocr_provider' => null,
        ];
    }

    private function processOcrData(): void
    {
        $ocrDataArray = [$this->data];

        foreach ($ocrDataArray as $ocrData) {
            if (!is_object($ocrData) && !is_array($ocrData)) {
                continue;
            }

            $data = OcrUtils::ensureArray($ocrData);
            $personalInfo = $this->getPersonalInfo($data);
            $this->updateExtractedData($data, $personalInfo);
        }
    }

    private function getPersonalInfo(array $data): array
    {
        $personalInfo = $data['personalInformation'] ?? [];
        return OcrUtils::ensureArray($personalInfo);
    }

    private function updateExtractedData(array $data, array $personalInfo): void
    {
        $this->extractedData = array_merge($this->extractedData, OcrUtils::getCleanData([
            // Car Quote Request Detail fields
            'driver_license_number' => $data['licenseNumber'] ?? $this->extractedData['driver_license_number'],
            'driver_license_issue_date' => OcrUtils::formatDate($data['issueDate'] ?? null) ?: $this->extractedData['driver_license_issue_date'],
            'driver_license_expiry_date' => OcrUtils::formatDate($data['expiryDate'] ?? null) ?: $this->extractedData['driver_license_expiry_date'],
            'driver_license_issue_place' => $data['placeOfIssue'] ?? $this->extractedData['driver_license_issue_place'],
            'traffic_code_number' => $data['trafficCodeNumber'] ?? $this->extractedData['traffic_code_number'],

            // Personal information fields - split full name into first and last name
            'driver_first_name' => OcrUtils::extractFirstName($personalInfo['fullName'] ?? null) ?: $this->extractedData['driver_first_name'],
            'driver_last_name' => OcrUtils::extractLastName($personalInfo['fullName'] ?? null) ?: $this->extractedData['driver_last_name'],
            'driver_dob' => OcrUtils::formatDate($personalInfo['dateOfBirth'] ?? null) ?: $this->extractedData['driver_dob'],
            'driver_gender' => OcrUtils::formatGender($personalInfo['sex'] ?? null) ?: $this->extractedData['driver_gender'],
            'driver_nationality_string' => $personalInfo['nationality'] ?? $this->extractedData['driver_nationality_string'],

            // Metadata
            'ocr_model' => $data['model'] ?? $this->extractedData['ocr_model'],
            'ocr_provider' => $data['provider'] ?? $this->extractedData['ocr_provider'],
        ]));
    }

    public function extractSingleDrivingLicense(): self
    {
        return $this->extractDrivingLicenseData();
    }

    public function getCarQuoteDetailFields(): array
    {
        return OcrUtils::getCleanData([
            'driver_license_number' => $this->extractedData['driver_license_number'] ?? null,
            'driver_license_issue_date' => $this->extractedData['driver_license_issue_date'] ?? null,
            'driver_license_expiry_date' => $this->extractedData['driver_license_expiry_date'] ?? null,
            'driver_license_issue_place' => $this->extractedData['driver_license_issue_place'] ?? null,
            'traffic_code_number' => $this->extractedData['traffic_code_number'] ?? null,
            'driver_first_name' => $this->extractedData['driver_first_name'] ?? null,
            'driver_last_name' => $this->extractedData['driver_last_name'] ?? null,
            'driver_dob' => $this->extractedData['driver_dob'] ?? null,
            'driver_gender' => $this->extractedData['driver_gender'] ?? null,
            'driver_nationality_string' => $this->extractedData['driver_nationality_string'] ?? null,
        ]);
    }

    public function getProcessedData(): array
    {
        return [
            'car_quote_detail_fields' => $this->getCarQuoteDetailFields(),
        ];
    }
}