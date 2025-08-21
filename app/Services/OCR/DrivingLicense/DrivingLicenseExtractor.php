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
            // Driving License Detail fields
            'license_number' => null,
            'license_issue_date' => null,
            'license_expiry_date' => null,
            'license_issue_place' => null,
            'traffic_code_number' => null,
            'first_name' => null,
            'last_name' => null,
            'dob' => null,
            'gender' => null,
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
            // Driving License Detail fields
            'license_number' => $data['licenseNumber'] ?? null,
            'license_issue_date' => $this->formatDate($data['issueDate'] ?? null),
            'license_expiry_date' => $this->formatDate($data['expiryDate'] ?? null),
            'license_issue_place' => $data['placeOfIssue'] ?? null,
            'traffic_code_number' => $data['trafficCodeNumber'] ?? null,

            // Personal information fields - split full name into first and last name
            'first_name' => $this->extractFirstName($personalInfo['fullName'] ?? null),
            'last_name' => $this->extractLastName($personalInfo['fullName'] ?? null),
            'dob' => $this->formatDate($personalInfo['dateOfBirth'] ?? null),
            'gender' => $this->formatGender($personalInfo['sex'] ?? null),
            'nationality_string' => $personalInfo['nationality'] ?? null,

            // Metadata
            'ocr_model' => $data['model'] ?? null,
            'ocr_provider' => $data['provider'] ?? null,
        ]));
    }

    public function getDrivingLicenseDetailFields(): array
    {
        return $this->getCleanData([
            'license_number' => $this->extractedData['license_number'] ?? null,
            'license_issue_date' => $this->extractedData['license_issue_date'] ?? null,
            'license_expiry_date' => $this->extractedData['license_expiry_date'] ?? null,
            'license_issue_place' => $this->extractedData['license_issue_place'] ?? null,
            'traffic_code_number' => $this->extractedData['traffic_code_number'] ?? null,
            'first_name' => $this->extractedData['first_name'] ?? null,
            'last_name' => $this->extractedData['last_name'] ?? null,
            'dob' => $this->extractedData['dob'] ?? null,
            'gender' => $this->extractedData['gender'] ?? null,
            'nationality_string' => $this->extractedData['nationality_string'] ?? null,
        ]);
    }

    public function getProcessedData(): array
    {
        return [
            'driving_license_detail_fields' => $this->getDrivingLicenseDetailFields(),
        ];
    }
}
