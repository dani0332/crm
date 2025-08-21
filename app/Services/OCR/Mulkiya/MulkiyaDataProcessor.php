<?php

declare(strict_types=1);

namespace App\Services\OCR\Mulkiya;

use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Models\Nationality;
use App\Models\RegistrationCertificate;
use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrUtils;
use Exception;

use Illuminate\Support\Facades\DB;

class MulkiyaDataProcessor
{
    use OcrUtils;
    private MulkiyaExtractor $mulkiyaExtractor;

    public function __construct(
        private CarQuote $quote,
        private object $data,
    ) {
        $this->mulkiyaExtractor = new MulkiyaExtractor($this->data);
    }

    public function processMulkiyaData(): bool
    {
        try {
            $processedData = $this->mulkiyaExtractor->extractMulkiyaData()->getProcessedData();

            LoggerService::info('Mulkiya data processor started');

            if (empty($processedData['car_quote_detail_fields']) && empty($processedData['car_quote_fields']) && empty($processedData['registration_certificate_fields'])) {
                LoggerService::warning('Mulkiya data processor - No valid data to process');

                return false;
            }

            DB::beginTransaction();

            // Update CarQuoteRequestDetail fields
            $carQuoteDetailUpdated = false;
            if (! empty($processedData['car_quote_detail_fields'])) {
                LoggerService::info('Processing car quote detail fields');
                $carQuoteDetailUpdated = $this->updateCarQuoteRequestDetail($this->quote, $processedData['car_quote_detail_fields']);
            }

            // Update CarQuote fields
            $carQuoteUpdated = false;
            if (! empty($processedData['car_quote_fields'])) {
                LoggerService::info('Processing car quote fields');
                $carQuoteUpdated = $this->updateCarQuote($this->quote, $processedData['car_quote_fields']);
            }

            // Update RegistrationCertificate (morphic relation)
            $registrationCertificateUpdated = false;
            if (! empty($processedData['registration_certificate_fields'])) {
                LoggerService::info('Processing registration certificate fields');
                $registrationCertificateUpdated = $this->updateRegistrationCertificate($this->quote, $processedData['registration_certificate_fields']);
            }

            DB::commit();

            LoggerService::info('Mulkiya data processing completed successfully');

            return $carQuoteDetailUpdated || $carQuoteUpdated || $registrationCertificateUpdated;

        } catch (Exception $e) {
            DB::rollback();

            LoggerService::error('Mulkiya data processor - Exception occurred', exception: $e);

            return false;
        }
    }

    private function updateCarQuoteRequestDetail(CarQuote $quote, array $fieldsToUpdate): bool
    {
        try {
            $carQuoteDetail = $quote->carQuoteRequestDetail;

            if (! $carQuoteDetail) {
                LoggerService::warning('CarQuoteRequestDetail not found for quote');

                return false;
            }

            // Update all fields with OCR data
            $dataToUpdate = $this->getFieldsToUpdate($fieldsToUpdate);

            if (! empty($dataToUpdate)) {
                $carQuoteDetail->update($dataToUpdate);

                LoggerService::info('CarQuoteRequestDetail updated successfully');

                return true;
            }

            LoggerService::info('CarQuoteRequestDetail - No OCR data to update');

            return false;

        } catch (Exception $e) {
            LoggerService::error('CarQuoteRequestDetail update failed', exception: $e);

            return false;
        }
    }

    private function updateCarQuote(CarQuote $quote, array $fieldsToUpdate): bool
    {
        try {
            $dataToUpdate = $this->getFieldsToUpdate($fieldsToUpdate);

            if (! empty($dataToUpdate)) {
                LoggerService::info('CarQuote update - Data to be updated');

                $quote->update($dataToUpdate);

                LoggerService::info('CarQuote updated successfully');

                return true;
            }

            LoggerService::info('CarQuote - No OCR data to update');

            return false;

        } catch (Exception $e) {
            LoggerService::error('CarQuote update failed', exception: $e);

            return false;
        }
    }

    private function updateRegistrationCertificate(CarQuote $quote, array $fieldsToUpdate): bool
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
                    LoggerService::warning('Nationality could not be matched');
                    // Remove the nationality field since we can't match it
                    unset($fieldsToUpdate['nationality_string']);
                }
            }

            $registrationCertificate = $quote->registrationCertificate()->firstOrCreate(
                ['certificatable_type' => CarQuote::class, 'certificatable_id' => $quote->id],
                $fieldsToUpdate
            );

            // If record already existed, update with OCR data
            if (! $registrationCertificate->wasRecentlyCreated) {
                $dataToUpdate = $this->getFieldsToUpdate($fieldsToUpdate);

                if (! empty($dataToUpdate)) {
                    $registrationCertificate->update($dataToUpdate);

                    LoggerService::info('RegistrationCertificate updated successfully');
                } else {
                    LoggerService::info('RegistrationCertificate - No OCR data to update');
                }
            } else {
                LoggerService::info('RegistrationCertificate created successfully');
            }

            return true;

        } catch (Exception $e) {
            LoggerService::error('RegistrationCertificate update failed', exception: $e);

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
        $carQuoteDetail = $this->quote->carQuoteRequestDetail;
        $registrationCertificate = $this->quote->registrationCertificate;

        return [
            'status' => 'success',
            'quote_uuid' => $this->quote->uuid,
            'has_car_quote_detail' => $carQuoteDetail !== null,
            'has_registration_certificate' => $registrationCertificate !== null,
            'car_quote_data' => [
                'policy_expiry_date' => $this->quote->policy_expiry_date,
            ],
            'car_quote_detail_data' => $carQuoteDetail ? [
                'plate_number' => $carQuoteDetail->plate_number,
                'first_registration_date' => $carQuoteDetail->first_registration_date,
                'vehicle_color' => $carQuoteDetail->vehicle_color,
                'engine_number' => $carQuoteDetail->engine_number,
                'chassis_number' => $carQuoteDetail->chassis_number,
                'rta_plate_category' => $carQuoteDetail->rta_plate_category,
            ] : null,
            'registration_certificate_data' => $registrationCertificate ? [
                'place_of_issue' => $registrationCertificate->place_of_issue,
                'expiry_date' => $registrationCertificate->expiry_date?->format('Y-m-d'),
                'owner' => $registrationCertificate->owner,
                'nationality_id' => $registrationCertificate->nationality_id,
                'nationality' => $registrationCertificate->nationality?->text, // Get nationality name via relationship
                'mortgage_by' => $registrationCertificate->mortgage_by,
                'notes' => $registrationCertificate->notes,
                'insured_with' => $registrationCertificate->insured_with,
                'insurance_type' => $registrationCertificate->insurance_type,
                'model' => $registrationCertificate->model,
                'vehicle_class' => $registrationCertificate->vehicle_class,
                'vehicle_type' => $registrationCertificate->vehicle_type,
                'origin' => $registrationCertificate->origin,
                'number_of_passengers' => $registrationCertificate->number_of_passengers,
                'gross_vehicle_weight' => $registrationCertificate->gross_vehicle_weight,
                'empty_weight' => $registrationCertificate->empty_weight,
                'ocr_done_by' => $registrationCertificate->ocr_done_by,
                'doc_type' => $registrationCertificate->doc_type,
                'provider_id' => $registrationCertificate->provider_id,
                'traffic_code_number' => $registrationCertificate->traffic_code_number,
            ] : null,
        ];
    }
}
