<?php

declare(strict_types=1);

namespace App\Services\OCR\Mulkiya;

use App\Services\OCR\OcrUtils;
use Carbon\Carbon;

class MulkiyaExtractor
{
    private array $extractedData = [];

    public function __construct(
        private object $data,
    ) {}

    public function extractMulkiyaData(): self
    {
        $this->extractedData = [
            // Car Quote Detail fields
            'plate_number' => null,
            'traffic_code_number' => null,
            'first_registration_date' => null,
            'vehicle_color' => null,
            'engine_number' => null,
            'chassis_number' => null,
            'rta_plate_category' => null,

            // Car Quote fields
            'policy_expiry_date' => null,

            // Registration Certificate fields
            'place_of_issue' => null,
            'expiry_date' => null,
            'owner' => null,
            'nationality' => null,
            'mortgage_by' => null,
            'notes' => null,
            'insured_with' => null,
            'insurance_type' => null,
            'model' => null,
            'vehicle_class' => null,
            'vehicle_type' => null,
            'origin' => null,
            'number_of_passengers' => null,
            'gross_vehicle_weight' => null,
            'empty_weight' => null,
            'ocr_done_by' => null,
            'doc_type' => null,
            'provider_id' => null,

            // Metadata
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
                // Car Quote Detail fields
                'plate_number' => $data['trafficPlateNumber'] ?? $this->extractedData['plate_number'],
                'traffic_code_number' => $data['trafficCodeNumber'] ?? $this->extractedData['traffic_code_number'],
                'first_registration_date' => $this->formatDate($data['registrationDate'] ?? null) ?: $this->extractedData['first_registration_date'],
                'vehicle_color' => $data['vehicalColor'] ?? $this->extractedData['vehicle_color'],
                'engine_number' => $data['engineNumber'] ?? $this->extractedData['engine_number'],
                'chassis_number' => $data['chassisNumber'] ?? $this->extractedData['chassis_number'],
                'rta_plate_category' => $data['plateType'] ?? $this->extractedData['rta_plate_category'],

                // Car Quote fields
                'policy_expiry_date' => $this->formatDate($data['insuranceExpiryDate'] ?? null) ?: $this->extractedData['policy_expiry_date'],

                // Registration Certificate fields
                'place_of_issue' => $data['placeOfIssue'] ?? $this->extractedData['place_of_issue'],
                'expiry_date' => $this->formatDate($data['expiryDate'] ?? null) ?: $this->extractedData['expiry_date'],
                'owner' => $data['owner'] ?? $this->extractedData['owner'],
                'nationality' => $data['nationality'] ?? $this->extractedData['nationality'],
                'mortgage_by' => $data['mortageBy'] ?? $this->extractedData['mortgage_by'],
                'notes' => $data['notes'] ?? $this->extractedData['notes'],
                'insured_with' => $data['insuredWith'] ?? $this->extractedData['insured_with'],
                'insurance_type' => $data['insuranceType'] ?? $this->extractedData['insurance_type'],
                'model' => $data['model'] ?? $this->extractedData['model'],
                'vehicle_class' => $data['vehicalClass'] ?? $this->extractedData['vehicle_class'],
                'vehicle_type' => $data['vehicalType'] ?? $this->extractedData['vehicle_type'],
                'origin' => $data['origin'] ?? $this->extractedData['origin'],
                'number_of_passengers' => isset($data['numberOfPassengers']) ? (int) $data['numberOfPassengers'] : $this->extractedData['number_of_passengers'],
                'gross_vehicle_weight' => $data['grossVehicleWeight'] ?? $this->extractedData['gross_vehicle_weight'],
                'empty_weight' => $data['emptyWeight'] ?? $this->extractedData['empty_weight'],
                'ocr_done_by' => $data['ocr_done_by'] ?? $this->extractedData['ocr_done_by'],
                'doc_type' => $data['doc_type'] ?? $this->extractedData['doc_type'],
                'provider_id' => $data['provider'] ?? $this->extractedData['provider_id'],

                // Metadata
                'ocr_model' => $data['model'] ?? $this->extractedData['ocr_model'],
                'ocr_provider' => $data['provider'] ?? $this->extractedData['ocr_provider'],
            ]));
        }

        return $this;
    }

    public function extractSingleMulkiya(): self
    {
        return $this->extractMulkiyaData();
    }

    public function getCarQuoteDetailFields(): array
    {
        return OcrUtils::getCleanData([
            'plate_number' => $this->extractedData['plate_number'] ?? null,
            'traffic_code_number' => $this->extractedData['traffic_code_number'] ?? null,
            'first_registration_date' => $this->extractedData['first_registration_date'] ?? null,
            'vehicle_color' => $this->extractedData['vehicle_color'] ?? null,
            'engine_number' => $this->extractedData['engine_number'] ?? null,
            'chassis_number' => $this->extractedData['chassis_number'] ?? null,
            'rta_plate_category' => $this->extractedData['rta_plate_category'] ?? null,
        ]);
    }

    public function getCarQuoteFields(): array
    {
        return OcrUtils::getCleanData([
            'policy_expiry_date' => $this->extractedData['policy_expiry_date'] ?? null,
        ]);
    }

    public function getRegistrationCertificateFields(): array
    {
        return OcrUtils::getCleanData([
            'place_of_issue' => $this->extractedData['place_of_issue'] ?? null,
            'expiry_date' => $this->extractedData['expiry_date'] ?? null,
            'owner' => $this->extractedData['owner'] ?? null,
            'nationality' => $this->extractedData['nationality'] ?? null,
            'mortgage_by' => $this->extractedData['mortgage_by'] ?? null,
            'notes' => $this->extractedData['notes'] ?? null,
            'insured_with' => $this->extractedData['insured_with'] ?? null,
            'insurance_type' => $this->extractedData['insurance_type'] ?? null,
            'model' => $this->extractedData['model'] ?? null,
            'vehicle_class' => $this->extractedData['vehicle_class'] ?? null,
            'vehicle_type' => $this->extractedData['vehicle_type'] ?? null,
            'origin' => $this->extractedData['origin'] ?? null,
            'number_of_passengers' => $this->extractedData['number_of_passengers'] ?? null,
            'gross_vehicle_weight' => $this->extractedData['gross_vehicle_weight'] ?? null,
            'empty_weight' => $this->extractedData['empty_weight'] ?? null,
            'ocr_done_by' => $this->extractedData['ocr_done_by'] ?? null,
            'doc_type' => $this->extractedData['doc_type'] ?? null,
            'provider_id' => $this->extractedData['provider_id'] ?? null,
        ]);
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

    public function getProcessedData(): array
    {
        return [
            'car_quote_detail_fields' => $this->getCarQuoteDetailFields(),
            'car_quote_fields' => $this->getCarQuoteFields(),
            'registration_certificate_fields' => $this->getRegistrationCertificateFields(),
        ];
    }
}
