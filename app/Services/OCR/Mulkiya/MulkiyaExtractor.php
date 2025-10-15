<?php

declare(strict_types=1);

namespace App\Services\OCR\Mulkiya;

use App\Enums\QuoteTypeId;
use App\Services\OCR\OcrUtils;

class MulkiyaExtractor
{
    use OcrUtils;

    private array $extractedData = [];

    public function __construct(
        private object $data,
        private ?int $providerId,
    ) {}

    public function extractMulkiyaData(): self
    {
        $this->extractedData = [
            // vehicle driver detail fields
            'vehicle_plate_code' => null,
            'vehicle_plate_number' => null,
            'first_registration_date' => null,
            'vehicle_color' => null,
            'vehicle_engine_number' => null,
            'rta_plate_category' => null,
            'bank_name' => null,

            // Car Quote Detail fields
            'chassis_number' => null,

            // Car Quote fields
            'policy_expiry_date' => null,

            // Registration Certificate fields
            'place_of_issue' => null,
            'expiry_date' => null,
            'owner' => null,
            'nationality_string' => null,
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
            'traffic_code_number' => null,

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

            $data = $this->ensureArray($ocrData);
            $plateInfo = $this->extractPlateCodeNumber($data['trafficPlateNumber'] ?? null);
            $plateCode = $plateInfo['place_code'] ?? null;

            $this->extractedData = array_merge($this->extractedData, $this->getCleanData([
                // vehicle driver detail fields
                'vehicle_plate_code' => $plateCode,
                'vehicle_plate_number' => $data['trafficPlateNumber'] ?? null,
                'first_registration_date' => $this->formatDate($data['registrationDate'] ?? null),
                'vehicle_color' => $this->getVehicleColorCode($data['vehicalColor'] ?? null, QuoteTypeId::Car, $this->providerId),
                'vehicle_engine_number' => $data['engineNumber'] ?? null,
                'bank_name' => $this->getBankCode($data['mortageBy'] ?? null, QuoteTypeId::Car, $this->providerId),

                // Car Quote Detail fields
                'chassis_number' => $data['chassisNumber'] ?? null,

                // Car Quote fields
                'policy_expiry_date' => $this->formatDate($data['insuranceExpiryDate'] ?? null),

                // Registration Certificate fields
                'place_of_issue' => $data['placeOfIssue'] ?? null,
                'expiry_date' => $this->formatDate($data['expiryDate'] ?? null),
                'owner' => $data['owner'] ?? null,
                'nationality_string' => $data['nationality'] ?? null,
                'mortgage_by' => $data['mortageBy'] ?? null,
                'notes' => $data['notes'] ?? null,
                'insured_with' => $data['insuredWith'] ?? null,
                'insurance_type' => $data['insuranceType'] ?? null,
                'model' => $data['vehicalModel'] ?? null,
                'vehicle_class' => $data['vehicalClass'] ?? null,
                'vehicle_type' => $data['vehicalType'] ?? null,
                'origin' => $data['origin'] ?? null,
                'number_of_passengers' => isset($data['numberOfPassengers']) ? (int) $data['numberOfPassengers'] : null,
                'gross_vehicle_weight' => $data['grossVehicleWeight'] ?? null,
                'empty_weight' => $data['emptyWeight'] ?? null,
                'ocr_done_by' => $data['ocr_done_by'] ?? null,
                'doc_type' => $data['doc_type'] ?? null,
                'provider_id' => $data['provider'] ?? null,
                'traffic_code_number' => $data['trafficCodeNumber'] ?? null,

                // Metadata
                'ocr_model' => $data['vehicalModel'] ?? null,
                'ocr_provider' => $data['provider'] ?? null,
            ]));
        }

        return $this;
    }

    public function getVehicleDriverDetailFields(): array
    {
        return $this->getCleanData([
            'vehicle_plate_number' => $this->extractedData['vehicle_plate_number'] ?? null,
            'vehicle_plate_code' => $this->extractedData['vehicle_plate_code'] ?? null,
            'first_registration_date' => $this->extractedData['first_registration_date'] ?? null,
            'vehicle_color' => $this->extractedData['vehicle_color'] ?? null,
            'vehicle_engine_number' => $this->extractedData['vehicle_engine_number'] ?? null,
            'bank_name' => $this->extractedData['bank_name'] ?? null,
        ]);
    }

    public function getCarQuoteDetailFields(): array
    {
        return $this->getCleanData([
            'chassis_number' => $this->extractedData['chassis_number'] ?? null,
        ]);
    }

    public function getCarQuoteFields(): array
    {
        return $this->getCleanData([
            'policy_expiry_date' => $this->extractedData['policy_expiry_date'] ?? null,
        ]);
    }

    public function getRegistrationCertificateFields(): array
    {
        return $this->getCleanData([
            'place_of_issue' => $this->extractedData['place_of_issue'] ?? null,
            'expiry_date' => $this->extractedData['expiry_date'] ?? null,
            'owner' => $this->extractedData['owner'] ?? null,
            'nationality_string' => $this->extractedData['nationality_string'] ?? null,
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
            'traffic_code_number' => $this->extractedData['traffic_code_number'] ?? null,
        ]);
    }

    public function getProcessedData(): array
    {
        return [
            'vehicle_driver_detail_fields' => $this->getVehicleDriverDetailFields(),
            'car_quote_detail_fields' => $this->getCarQuoteDetailFields(),
            'car_quote_fields' => $this->getCarQuoteFields(),
            'registration_certificate_fields' => $this->getRegistrationCertificateFields(),
        ];
    }
}
