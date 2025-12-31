<?php

declare(strict_types=1);

namespace App\Services\OCR\DrivingLicense;

use App\Services\OCR\OcrUtils;

class DrivingLicenseExtractor
{
    use OcrUtils;

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
            // vehicle driver detail fields
            'driver_license_number' => null,
            'driver_license_issue_date' => null,
            'driver_license_expiry_date' => null,
            'driver_license_issue_place' => null,
            'traffic_code_number' => null,
            'driver_first_name' => null,
            'driver_last_name' => null,
            'driver_dob' => null,
            'driver_gender' => null,
            'nationality_string' => null,

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
            if (! is_object($ocrData) && ! is_array($ocrData)) {
                continue;
            }

            $data = $this->ensureArray($ocrData);
            $personalInfo = $this->getPersonalInfo($data);
            $this->updateExtractedData($data, $personalInfo);
        }
    }

    private function getPersonalInfo(array $data): array
    {
        $personalInfo = $data['personalInformation'] ?? [];

        return $this->ensureArray($personalInfo);
    }

    private function updateExtractedData(array $data, array $personalInfo): void
    {
        $this->extractedData = array_merge($this->extractedData, $this->getCleanData([
            // vehicle driver detail fields
            'driver_license_number' => $data['licenseNumber'] ?? null,
            'driver_license_issue_date' => $this->formatDate($data['issueDate'] ?? null),
            'driver_license_expiry_date' => $this->formatDate($data['expiryDate'] ?? null),
            'driver_license_issue_place' => $this->getIssuancePlaceCode($data['placeOfIssue'] ?? null),
            'traffic_code_number' => $data['trafficCodeNumber'] ?? null,

            // Personal information fields - split full name into first and last name
            'driver_first_name' => $this->extractFirstName($personalInfo['fullName'] ?? null),
            'driver_last_name' => $this->extractLastName($personalInfo['fullName'] ?? null),
            'driver_dob' => $this->formatDate($personalInfo['dateOfBirth'] ?? null),
            'driver_gender' => $this->formatGender($personalInfo['sex'] ?? null),
            'nationality_string' => $personalInfo['nationality'] ?? null,

            // Metadata
            'ocr_model' => $data['model'] ?? null,
            'ocr_provider' => $data['provider'] ?? null,
        ]));
    }

    public function getVehicleDriverDetailFields(): array
    {
        return $this->getCleanData([
            'driver_license_number' => $this->extractedData['driver_license_number'] ?? null,
            'driver_license_issue_date' => $this->extractedData['driver_license_issue_date'] ?? null,
            'driver_license_expiry_date' => $this->extractedData['driver_license_expiry_date'] ?? null,
            'driver_license_issue_place' => $this->extractedData['driver_license_issue_place'] ?? null,
            'traffic_code_number' => $this->extractedData['traffic_code_number'] ?? null,
            'driver_first_name' => $this->extractedData['driver_first_name'] ?? null,
            'driver_last_name' => $this->extractedData['driver_last_name'] ?? null,
            'driver_dob' => $this->extractedData['driver_dob'] ?? null,
            'driver_gender' => $this->extractedData['driver_gender'] ?? null,
            'nationality_string' => $this->extractedData['nationality_string'] ?? null,
        ]);
    }

    public function getProcessedData(): array
    {
        return [
            'vehicle_driver_detail_fields' => $this->getVehicleDriverDetailFields(),
        ];
    }
}
