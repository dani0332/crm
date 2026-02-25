<?php

declare(strict_types=1);

namespace App\Services\OCR\EmiratesId;

use App\Enums\CarRegistrationType;
use App\Enums\CarVehicleUse;
use App\Enums\DocumentTypeCode;
use App\Models\CarQuote;
use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrUtils;
use App\Services\OCR\Validators\OCRDocumentValidator;
use Exception;
use Illuminate\Support\Facades\DB;

class DriverEmiratesIdDataProcessor
{
    use OcrUtils;

    private DriverEmiratesIdExtractor $driverEmiratesIdExtractor;
    private array $extractedData = [];

    public function __construct(
        private CarQuote $quote,
        private object $data,
        private string $documentTypeCode
    ) {
        $this->driverEmiratesIdExtractor = new DriverEmiratesIdExtractor($this->data);
    }

    public function processDriverEmiratesIdData(): bool
    {
        try {
            DB::beginTransaction();

            $this->extractedData = $this->driverEmiratesIdExtractor->extractDriverEmiratesIdData()->getExtractedData();

            LoggerService::info('Driver Emirates ID data processor started');

            $vehicleDriverDetailUpdated = $this->updateVehicleDriverDetail();

            $carQuoteUpdated = false;
            if ($this->quote->registration_type == CarRegistrationType::COMPANY && $this->quote->vehicle_use == CarVehicleUse::PRIVATE) {
                $carQuoteUpdated = $this->updateCarQuoteDriverEid();
            }

            // Trigger OCR success validation
            $validator = app()->make(OCRDocumentValidator::class, [
                'quoteId' => $this->quote->id,
                'quoteableType' => get_class($this->quote),
            ]);
            $isOCRSuccess = $validator->validateDriverEidFields($this->quote);
            LoggerService::info('Driver Emirates ID data validation result for document type: '.$this->documentTypeCode.' is: '.($isOCRSuccess ? 'true' : 'false'), json_encode($this->extractedData));

            DB::commit();

            LoggerService::info('Driver Emirates ID data processing completed successfully');

            return $vehicleDriverDetailUpdated || $carQuoteUpdated;

        } catch (Exception $e) {
            DB::rollBack();

            LoggerService::error('Driver Emirates ID data processing failed', exception: $e);

            return false;
        }
    }

    private function updateVehicleDriverDetail(): bool
    {
        try {
            $fieldsToUpdate = $this->getCleanData([
                'driver_eid_number' => $this->extractedData['eid_number'] ?? null,
                'driver_gender' => $this->extractedData['sex'] ?? null,
            ]);

            if (empty($fieldsToUpdate)) {
                LoggerService::info('VehicleDriverDetail - No OCR data to update for Driver Emirates ID');

                return false;
            }

            $this->quote->vehicleDriverDetail()->updateOrCreate(
                ['quoteable_type' => CarQuote::class, 'quoteable_id' => $this->quote->id],
                $fieldsToUpdate
            );

            LoggerService::info('VehicleDriverDetail updated/created successfully for Driver Emirates ID');

            return true;

        } catch (Exception $e) {
            LoggerService::error('VehicleDriverDetail update failed for Driver Emirates ID', exception: $e);

            return false;
        }
    }

    private function updateCarQuoteDriverEid(): bool
    {
        try {
            $dataToUpdate = $this->getFieldsToUpdate([
                'driver_name' => $this->extractedData['driver_name'] ?? null,
                'dob' => $this->extractedData['date_of_birth'] ?? null,
                'nationality_id' => $this->getNationalityId($this->extractedData['nationality'] ?? null),
            ]);

            if (empty($dataToUpdate)) {
                LoggerService::info('CarQuote - No Driver Emirates ID data to update');

                return false;
            }

            $this->quote->update($dataToUpdate);

            LoggerService::info('CarQuote updated successfully with Driver Emirates ID');

            return true;
        } catch (Exception $e) {
            LoggerService::error('CarQuote update failed for Driver Emirates ID', exception: $e);

            return false;
        }
    }

    public function getProcessingSummary(): array
    {
        $vehicleDriverDetail = $this->quote->vehicleDriverDetail;

        return [
            'status' => 'success',
            'quote_uuid' => $this->quote->uuid,
            'document_type_code' => DocumentTypeCode::DRIVER_EMIRATES_ID,
            'car_quote_data' => [
                'dob' => $this->quote->dob ?? null,
                'nationality_id' => $this->quote->nationality_id ?? null,
                'driver_name' => $this->quote->driver_name ?? null,
            ],
            'vehicle_driver_detail_data' => $vehicleDriverDetail ? [
                'driver_eid_number' => $vehicleDriverDetail->driver_eid_number,
            ] : null,
        ];
    }
}
