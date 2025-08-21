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

            if (empty($processedData['driving_license_detail_fields'])) {
                LoggerService::warning('Driving License data processor - No valid data to process');

                return false;
            }

            DB::beginTransaction();

            // Update DrivingLicenseDetail fields
            $drivingLicenseDetailUpdated = false;
            if (! empty($processedData['driving_license_detail_fields'])) {
                $drivingLicenseDetailUpdated = $this->updateDrivingLicenseDetail($this->quote, $processedData['driving_license_detail_fields']);
            }

            DB::commit();

            LoggerService::info('Driving License data processing completed successfully');

            return $drivingLicenseDetailUpdated;

        } catch (Exception $e) {
            DB::rollback();

            LoggerService::error('Driving License data processor - Exception occurred', exception: $e);

            return false;
        }
    }

    private function updateDrivingLicenseDetail(CarQuote $quote, array $fieldsToUpdate): bool
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

            $drivingLicenseDetail = $quote->drivingLicenseDetail()->firstOrCreate(
                ['licensable_type' => CarQuote::class, 'licensable_id' => $quote->id],
                $fieldsToUpdate
            );

            // If record already existed, update with OCR data
            if (! $drivingLicenseDetail->wasRecentlyCreated) {
                $dataToUpdate = $this->getFieldsToUpdate($fieldsToUpdate);

                if (! empty($dataToUpdate)) {
                    $drivingLicenseDetail->update($dataToUpdate);

                    LoggerService::info('DrivingLicenseDetail updated successfully');
                } else {
                    LoggerService::info('DrivingLicenseDetail - No OCR data to update');
                }
            } else {
                LoggerService::info('DrivingLicenseDetail created successfully');
            }

            return true;

        } catch (Exception $e) {
            LoggerService::error('DrivingLicenseDetail update failed', exception: $e);

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
        $drivingLicenseDetail = $this->quote->drivingLicenseDetail;

        return [
            'status' => 'success',
            'quote_uuid' => $this->quote->uuid,
            'has_driving_license_detail' => $drivingLicenseDetail !== null,
            'driving_license_detail_data' => $drivingLicenseDetail ? [
                'license_number' => $drivingLicenseDetail->license_number,
                'license_issue_date' => $drivingLicenseDetail->license_issue_date,
                'license_expiry_date' => $drivingLicenseDetail->license_expiry_date,
                'license_issue_place' => $drivingLicenseDetail->license_issue_place,
                'traffic_code_number' => $drivingLicenseDetail->traffic_code_number,
                'first_name' => $drivingLicenseDetail->first_name,
                'last_name' => $drivingLicenseDetail->last_name,
                'dob' => $drivingLicenseDetail->dob,
                'gender' => $drivingLicenseDetail->gender,
                'nationality_id' => $drivingLicenseDetail->nationality_id,
                'nationality' => $drivingLicenseDetail->nationality?->text, // Get nationality name via relationship
            ] : null,
        ];
    }
}
