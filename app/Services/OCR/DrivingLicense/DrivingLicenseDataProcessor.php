<?php

declare(strict_types=1);

namespace App\Services\OCR\DrivingLicense;

use App\Models\CarQuote;
use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrUtils;
use Exception;
use Illuminate\Support\Facades\DB;

class DrivingLicenseDataProcessor
{
    private DrivingLicenseExtractor $drivingLicenseExtractor;

    public function __construct(
        private CarQuote $quote,
        private object $data,
    ) {
        $this->drivingLicenseExtractor = new DrivingLicenseExtractor($this->data);
    }

    public function processDrivingLicenseData(): bool
    {
        try {
            $processedData = $this->drivingLicenseExtractor->extractSingleDrivingLicense()->getProcessedData();

            LoggerService::info('Driving License data processor started - Quote UUID: '.$this->quote->uuid, extra: [
                'quote_type' => class_basename($this->quote),
                'extracted_fields' => array_keys(array_filter($processedData, fn ($v) => ! empty($v))),
            ]);

            if (empty($processedData['car_quote_detail_fields'])) {
                LoggerService::warning('Driving License data processor - No valid data to process - Quote UUID: '.$this->quote->uuid);

                return false;
            }

            DB::beginTransaction();

            // Update CarQuoteRequestDetail fields
            $carQuoteDetailUpdated = false;
            if (! empty($processedData['car_quote_detail_fields'])) {
                $carQuoteDetailUpdated = $this->updateCarQuoteRequestDetail($this->quote, $processedData['car_quote_detail_fields']);
            }

            DB::commit();

            LoggerService::info('Driving License data processing completed successfully - Quote UUID: '.$this->quote->uuid, extra: [
                'car_quote_detail_updated' => $carQuoteDetailUpdated,
            ]);

            return $carQuoteDetailUpdated;

        } catch (Exception $e) {
            DB::rollback();

            LoggerService::error('Driving License data processor - Exception occurred - Quote UUID: '.$this->quote->uuid, exception: $e);

            return false;
        }
    }

    private function updateCarQuoteRequestDetail(CarQuote $quote, array $fieldsToUpdate): bool
    {
        try {
            $carQuoteDetail = $quote->carQuoteRequestDetail;

            if (! $carQuoteDetail) {
                LoggerService::warning('CarQuoteRequestDetail not found for quote - Quote UUID: '.$this->quote->uuid);

                return false;
            }

            // Only update fields that have values and are not already filled
            $dataToUpdate = OcrUtils::getFieldsToUpdate($fieldsToUpdate, $carQuoteDetail);

            if (! empty($dataToUpdate)) {
                $carQuoteDetail->update($dataToUpdate);

                LoggerService::info('CarQuoteRequestDetail updated successfully with driving license data - Quote UUID: '.$this->quote->uuid, extra: [
                    'car_quote_detail_id' => $carQuoteDetail->id,
                    'updated_fields' => array_keys($dataToUpdate),
                ]);

                return true;
            }

            LoggerService::info('CarQuoteRequestDetail - No new driving license data to update - Quote UUID: '.$this->quote->uuid);

            return false;

        } catch (Exception $e) {
            LoggerService::error('CarQuoteRequestDetail update failed for driving license - Quote UUID: '.$this->quote->uuid, exception: $e);

            return false;
        }
    }

    public function getProcessingSummary(): array
    {
        $carQuoteDetail = $this->quote->carQuoteRequestDetail;

        return [
            'status' => 'success',
            'quote_uuid' => $this->quote->uuid,
            'has_car_quote_detail' => $carQuoteDetail !== null,
            'car_quote_detail_data' => $carQuoteDetail ? [
                'driver_license_number' => $carQuoteDetail->driver_license_number,
                'driver_license_issue_date' => $carQuoteDetail->driver_license_issue_date,
                'driver_license_expiry_date' => $carQuoteDetail->driver_license_expiry_date,
                'driver_license_issue_place' => $carQuoteDetail->driver_license_issue_place,
                'traffic_code_number' => $carQuoteDetail->traffic_code_number,
                'driver_first_name' => $carQuoteDetail->driver_first_name,
                'driver_last_name' => $carQuoteDetail->driver_last_name,
                'driver_dob' => $carQuoteDetail->driver_dob,
                'driver_gender' => $carQuoteDetail->driver_gender,
            ] : null,
        ];
    }
}
