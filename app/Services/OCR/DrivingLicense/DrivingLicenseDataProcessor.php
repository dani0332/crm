<?php

declare(strict_types=1);

namespace App\Services\OCR\DrivingLicense;

use App\Models\CarQuote;
use App\Models\Nationality;
use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrUtils;
use Exception;
use Illuminate\Support\Facades\DB;

class DrivingLicenseDataProcessor
{
    use OcrUtils;

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
            $processedData = $this->drivingLicenseExtractor->extractDrivingLicenseData()->getProcessedData();

            LoggerService::info('Driving License data processor started');

            if (empty($processedData['vehicle_driver_detail_fields'])) {
                LoggerService::warning('Driving License data processor - No valid data to process');

                return false;
            }

            DB::beginTransaction();

            LoggerService::info('driving license data:'. json_encode($processedData['vehicle_driver_detail_fields']));
            // Update VehicleDriverDetail fields
            $vehicleDriverDetailUpdated = false;
            if (! empty($processedData['vehicle_driver_detail_fields'])) {
                $vehicleDriverDetailUpdated = $this->updateVehicleDriverDetail($this->quote, $processedData['vehicle_driver_detail_fields']);
            }

            DB::commit();

            LoggerService::info('Driving License data processing completed successfully');

            return $vehicleDriverDetailUpdated;

        } catch (Exception $e) {
            DB::rollback();

            LoggerService::error('Driving License data processor - Exception occurred', exception: $e);

            return false;
        }
    }

    private function updateVehicleDriverDetail(CarQuote $quote, array $fieldsToUpdate): bool
    {
        try {
            // Convert nationality string to nationality_id if nationality is provided
            if (! empty($fieldsToUpdate['nationality_string'])) {
                $nationalityId = $this->getNationalityId($fieldsToUpdate['nationality_string']);
                if ($nationalityId) {
                    $fieldsToUpdate['nationality_id'] = $nationalityId;
                    // Remove the nationality string since we only want to store the ID
                    unset($fieldsToUpdate['nationality_string']);
                } else {
                    LoggerService::warning('Driver nationality could not be matched');
                    // Remove the nationality field since we can't match it
                    unset($fieldsToUpdate['nationality_string']);
                }
            }

            $vehicleDriverDetail = $quote->vehicleDriverDetail()->firstOrCreate(
                ['quoteable_type' => CarQuote::class, 'quoteable_id' => $quote->id],
                $fieldsToUpdate
            );

            // If record already existed, update with OCR data
            if (! $vehicleDriverDetail->wasRecentlyCreated) {
                $dataToUpdate = $this->getFieldsToUpdate($fieldsToUpdate);

                if (! empty($dataToUpdate)) {
                    $vehicleDriverDetail->update($dataToUpdate);

                    LoggerService::info('VehicleDriverDetail updated successfully');
                } else {
                    LoggerService::info('VehicleDriverDetail - No OCR data to update');
                }
            } else {
                LoggerService::info('VehicleDriverDetail created successfully');
            }

            return true;

        } catch (Exception $e) {
            LoggerService::error('VehicleDriverDetail update failed', exception: $e);

            return false;
        }
    }

    private function getNationalityId(?string $nationality): ?int
    {
        if (empty($nationality)) {
            return null;
        }

        return Nationality::where('text', 'LIKE', '%'.$nationality.'%')
            ->orWhere('code', $nationality)
            ->value('id');
    }

    public function getProcessingSummary(): array
    {
        $vehicleDriverDetail = $this->quote->vehicleDriverDetail;

        return [
            'status' => 'success',
            'quote_uuid' => $this->quote->uuid,
            'has_vehicle_driver_detail' => $vehicleDriverDetail !== null,
            'vehicle_driver_detail_data' => $vehicleDriverDetail ? [
                'driver_license_number' => $vehicleDriverDetail->driver_license_number,
                'driver_license_issue_date' => $vehicleDriverDetail->driver_license_issue_date,
                'driver_license_expiry_date' => $vehicleDriverDetail->driver_license_expiry_date,
                'driver_license_issue_place' => $vehicleDriverDetail->driver_license_issue_place,
                'traffic_code_number' => $vehicleDriverDetail->traffic_code_number,
                'driver_first_name' => $vehicleDriverDetail->driver_first_name,
                'driver_last_name' => $vehicleDriverDetail->driver_last_name,
                'driver_dob' => $vehicleDriverDetail->driver_dob,
                'driver_gender' => $vehicleDriverDetail->driver_gender,
                'nationality_id' => $vehicleDriverDetail->nationality_id,
                'nationality' => $vehicleDriverDetail->nationality?->text, // Get nationality name via relationship
            ] : null,
        ];
    }
}
