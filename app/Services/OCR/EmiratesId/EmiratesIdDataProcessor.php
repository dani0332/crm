<?php

declare(strict_types=1);

namespace App\Services\OCR\EmiratesId;

use App\Enums\CustomerTypeEnum;
use App\Enums\KycSourceOfIncomeEnum;
use App\Enums\LookupsEnum;
use App\Exceptions\OCR\OcrProcessingException;
use App\Models\CustomerInsured;
use App\Models\CustomerMembers;
use App\Models\Insured;
use App\Models\InsuredKyc;
use App\Models\Lookup;
use App\Models\Nationality;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrUtils;
use App\Services\OCR\Validators\OCRDocumentValidator;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EmiratesIdDataProcessor
{
    use OcrUtils;

    private EmiratesIdExtractor $emiratesIdExtractor;
    private array $extractedData = [];

    public function __construct(
        private Model $quote,
        private object $data,
        private string $documentTypeCode,
        private int $memberDetailId
    ) {
        $this->emiratesIdExtractor = new EmiratesIdExtractor($this->data);
    }

    public function processEmiratesIdData(): bool
    {
        try {
            DB::beginTransaction();
            $isPrincipal = $insuredUpdated = $kycUpdated = $vehicleDriverDetailUpdated = $isMemberUpdated = false;

            $this->extractedData = $this->emiratesIdExtractor->extractEmiratesIdData()->getExtractedData();

            LoggerService::info('Emirates ID data processor started');

            // Check if member provided
            if ($this->memberDetailId != 0) {
                // Save member emirates data
                $isMemberUpdated = $this->saveHealthMembersEmiratesData($this->memberDetailId);

                // Check if it's principal
                $memberDetail = CustomerMembers::find($this->memberDetailId);

                if ($memberDetail && $memberDetail->is_principal) {
                    $isPrincipal = true;
                }
            }

            if ($this->memberDetailId == 0 || $isPrincipal) {
                $insured = $this->getOrCreateInsuredRecord();
                if (! $insured) {
                    throw new OcrProcessingException('Failed to get or create Insured record for Emirates ID processing');
                }

                if ($insured->customer_type !== CustomerTypeEnum::Individual) {
                    LoggerService::info('Skipping Emirates ID data processing for non-individual insured record', extra: [
                        'insured_id' => $insured->id,
                        'insured_customer_type' => $insured->customer_type,
                    ]);

                    DB::rollBack();

                    return false;
                }

                $insuredUpdated = $this->updateInsuredTable($insured);
                $kycUpdated = $this->updateInsuredKycTable($insured);
                $vehicleDriverDetailUpdated = $this->updateVehicleDriverDetail($this->quote);

                // Update insured details in personal quote and customer
                $this->updatePersonalQuoteInsuredId($insured);
                $this->updateInsuredDataInCustomer($insured);
            }

            // Update insured fields in customer table
            $this->updateCustomerTableInsuredFields($insured);

            // Trigger OCR success validation
            $ocrDocumentValidator = app()->make(OCRDocumentValidator::class, [
                'quoteId' => $this->quote->id,
                'quoteableType' => get_class($this->quote),
            ]);

            $isOCRSuccess = $ocrDocumentValidator->validateEIDFields($this->documentTypeCode);
            LoggerService::info('EmiratesId data validation result for document type: '.$this->documentTypeCode.' is: '.($isOCRSuccess ? 'true' : 'false'), json_encode($this->extractedData));

            DB::commit();

            LoggerService::info('Emirates ID data processing completed successfully');

            return $insuredUpdated || $kycUpdated || $vehicleDriverDetailUpdated || $isMemberUpdated;

        } catch (Exception $e) {
            DB::rollBack();

            LoggerService::error('Emirates ID data processing failed', exception: $e);

            return false;
        }
    }

    private function updateVehicleDriverDetail($quote): bool
    {
        try {
            $fieldsToUpdate = $this->getCleanData([
                'driver_gender' => $this->extractedData['sex'],
            ]);

            if (! empty($fieldsToUpdate)) {
                $quote->vehicleDriverDetail()->updateOrCreate(
                    ['quoteable_type' => get_class($quote), 'quoteable_id' => $quote->id],
                    $fieldsToUpdate
                );

                LoggerService::info('VehicleDriverDetail updated successfully');
            }

            return true;

        } catch (Exception $e) {
            LoggerService::error('VehicleDriverDetail update failed', exception: $e);

            return false;
        }
    }

    private function getOrCreateInsuredRecord(): ?Insured
    {
        try {
            $insured = $this->quote->latestInsured ?? null;

            if (! $insured && ! empty($this->extractedData['eid_number'])) {
                $insured = Insured::where('id_type', 'emiratesId')
                    ->where('customer_type', CustomerTypeEnum::Individual)
                    ->emiratesIdNumber($this->extractedData['eid_number'])
                    ->first();

                if ($insured) {
                    $this->createCustomerInsuredLink($insured);
                }
            }

            if (! $insured) {
                $insured = $this->createInsuredRecord();
            }

            return $insured;

        } catch (Exception $e) {
            LoggerService::error('Failed to get or create Insured record', exception: $e);

            return null;
        }
    }

    private function createInsuredRecord(): Insured
    {
        $insuredData = $this->getCleanData([
            'customer_type' => 'Individual',
            'first_name' => $this->extractFirstName($this->extractedData['name'] ?? ''),
            'last_name' => $this->extractLastName($this->extractedData['name'] ?? ''),
            'dob' => $this->extractedData['date_of_birth'],
            'nationality_id' => $this->getNationalityId($this->extractedData['nationality'] ?? null),
            'gender' => $this->formatGender($this->extractedData['sex']),
            'id_type' => 'emiratesId',
            'id_number' => $this->extractedData['eid_number'],
        ]);

        $insured = Insured::create($insuredData);

        $this->createCustomerInsuredLink($insured);

        return $insured;
    }

    private function updateInsuredTable(Insured $insured): bool
    {
        try {
            $updateData = [
                ...(! empty($this->extractedData['name'])
                  ? ['first_name' => $this->extractFirstName($this->extractedData['name']), 'last_name' => $this->extractLastName($this->extractedData['name'])]
                  : []),
                ...(! empty($this->extractedData['sex'])
                 ? ['gender' => $this->formatGender($this->extractedData['sex'])]
                 : []),
                ...(! empty($this->extractedData['eid_number'])
                 ? ['id_type' => 'emiratesId', 'id_number' => $this->extractedData['eid_number']]
                 : []),
                ...(! empty($this->extractedData['nationality'])
                 ? ['nationality_id' => $this->getNationalityId($this->extractedData['nationality'])]
                 : []),
                ...(! empty($this->extractedData['date_of_birth'])
                 ? ['dob' => $this->extractedData['date_of_birth']]
                 : []),
            ];

            // Update all fields with OCR data
            $dataToUpdate = $this->getFieldsToUpdate($updateData);

            if (! empty($dataToUpdate)) {
                $insured->update($dataToUpdate);

                LoggerService::info('Insured table updated successfully with following data:', json_encode($dataToUpdate));

                return true;
            }

            LoggerService::info('Insured table - No OCR data to update');

            return false;

        } catch (Exception $e) {
            LoggerService::error('Failed to update Insured table', exception: $e);

            return false;
        }
    }

    private function updateInsuredKycTable(Insured $insured): bool
    {
        try {
            $result = false;
            $kycData = [];

            if (! empty($this->extractedData['country'])) {
                $countryId = $this->getNationalityId($this->extractedData['country']);
                if ($countryId) {
                    $kycData['country_of_residence'] = $countryId;
                }
            }

            if (! empty($this->extractedData['nationality'])) {
                $nationalityId = $this->getNationalityId($this->extractedData['nationality']);
                if ($nationalityId) {
                    $kycData['place_of_birth'] = $nationalityId;
                }
            }

            if (! empty($this->extractedData['issuing_date'])) {
                $kycData['id_issuance_date'] = $this->extractedData['issuing_date'];
            }

            if (! empty($this->extractedData['expiry_date'])) {
                $kycData['id_expiry_date'] = $this->extractedData['expiry_date'];
            }

            if (! empty($this->extractedData['issuing_place'])) {
                $kycData['issuance_place'] = $this->extractedData['issuing_place'];
                // add residential_address by appending 'UAE' to issuance_place as per business request
                $kycData['residential_address'] = $this->extractedData['issuing_place'].', UAE';
            }

            // Map employment fields from Emirates ID OCR data
            if (! empty($this->extractedData['sponsor'])) {
                $kycData['employer_company_name'] = $this->extractedData['sponsor'];
                $kycData['source_of_income'] = KycSourceOfIncomeEnum::EMPLOYED->value;
                LoggerService::info('Emirates ID OCR: Auto-populated employer company name', [
                    'employer_company_name' => $this->extractedData['sponsor'],
                ]);
            }

            if (! empty($this->extractedData['occupation'])) {
                $mappedJobTitle = $this->mapOccupationToJobTitle($this->extractedData['occupation']);
                if ($mappedJobTitle) {
                    $kycData['job_title'] = $mappedJobTitle;
                }
            }

            // Sync insured table data to insured_kyc table
            $kycData['id_type'] = $insured->id_type;
            $kycData['id_number'] = $insured->id_number;
            $kycData['first_name'] = $insured->first_name;
            $kycData['last_name'] = $insured->last_name;

            $insuredKyc = $insured->insuredKyc;

            if ($insuredKyc) {
                // Update all fields with OCR data
                $dataToUpdate = $this->getFieldsToUpdate($kycData);

                if (! empty($dataToUpdate)) {
                    $insuredKyc->update($dataToUpdate);

                    LoggerService::info('InsuredKyc table updated successfully');

                    $result = true;
                } else {
                    LoggerService::info('InsuredKyc table - No OCR data to update');
                }
            } else {
                $kycData['insured_id'] = $insured->id;
                InsuredKyc::create($kycData);

                LoggerService::info('InsuredKyc table created successfully');

                $result = true;
            }

            return $result;

        } catch (Exception $e) {
            LoggerService::error('Failed to update InsuredKyc table', exception: $e);

            return false;
        }
    }

    private function updateCustomerTableInsuredFields(Insured $insured): void
    {
        try {
            $customer = $this->quote->customer;
            if (! $customer) {
                LoggerService::warning('Customer not found for quote UUID: '.$this->quote->uuid);

                return;
            }

            $customer->update([
                'insured_first_name' => $insured->first_name,
                'insured_last_name' => $insured->last_name,
                'emirates_id_number' => $insured->id_number,
                'emirates_id_expiry_date' => $insured->insuredKyc?->id_expiry_date,
            ]);

            LoggerService::info('Customer table insured fields updated successfully');
        } catch (Exception $e) {
            LoggerService::error('Failed to update Customer table insured fields', exception: $e);
        }
    }

    private function saveHealthMembersEmiratesData($memberDetailId): bool
    {
        try {
            LoggerService::info('Saving health members emirates data', [
                'data' => $this->extractedData,
                'memberDetailId' => $memberDetailId,
            ]);

            $updateData = array_filter([
                'emirates_id_issuance_date' => $this->extractedData['issuing_date'] ?? null,
                'emirates_id_expiry_date' => $this->extractedData['expiry_date'] ?? null,
                'emirates_id_number' => $this->extractedData['eid_number'] ?? null,
            ]);

            CustomerMembers::where('id', $memberDetailId)->update($updateData);

            return true;
        } catch (Exception $e) {
            LoggerService::error('Failed to save health members', exception: $e);

            throw $e;
        }
    }

    private function updatePersonalQuoteInsuredId(Insured $insured): void
    {
        try {
            $quoteType = get_class($this->quote);
            $affectedRow = PersonalQuote::where('uuid', $this->quote->uuid)
                ->update(['insured_id' => $insured->id]);

            if ($affectedRow > 0) {
                LoggerService::info('Personal quote insured ID updated successfully for quote UUID: '.$this->quote->uuid, [
                    'insured_id' => $insured->id,
                    'quote_type' => $quoteType,
                ]);
            } else {
                LoggerService::warning('Personal quote not found for quote UUID: '.$this->quote->uuid, [
                    'quote_type' => $quoteType,
                ]);
            }
        } catch (Exception $e) {
            LoggerService::error('Failed to update personal quote insured ID', exception: $e);
        }
    }

    private function updateInsuredDataInCustomer(Insured $insured): void
    {
        try {
            $quoteType = get_class($this->quote);
            $customer = $this->quote->customer;

            if (! $customer) {
                LoggerService::warning('Customer record not found for quote UUID: '.$this->quote->uuid, ['quote_type' => $quoteType]);

                return;
            }

            $dataToUpdate = $this->getFieldsToUpdate([
                'nationality_id' => $insured->nationality_id,
                'dob' => $insured->dob,
                'insured_first_name' => $insured->first_name,
                'insured_last_name' => $insured->last_name,
            ]);

            if (! empty($dataToUpdate)) {
                $customer->update($dataToUpdate);
                LoggerService::info('Customer record updated successfully for quote UUID: '.$this->quote->uuid, ['quote_type' => $quoteType]);
            }
        } catch (Exception $e) {
            LoggerService::error('Failed to update insured details in customer record', exception: $e);
        }
    }

    private function getNationalityName(?int $nationalityId): ?string
    {
        if (empty($nationalityId)) {
            return null;
        }

        $nationalityRecord = Nationality::find($nationalityId);

        // Return country_name if available, otherwise fall back to text
        return $nationalityRecord?->country_name ?? $nationalityRecord?->text ?? null;
    }

    private function extractFirstName(string $fullName): string
    {
        $nameParts = explode(' ', trim($fullName));

        return $nameParts[0] ?? '';
    }

    private function extractLastName(string $fullName): string
    {
        $nameParts = explode(' ', trim($fullName));
        if (count($nameParts) > 1) {
            array_shift($nameParts); // Remove first name

            return implode(' ', $nameParts);
        }

        return '';
    }

    private function formatGender(?string $gender): ?string
    {
        if (empty($gender)) {
            return null;
        }

        return match (strtoupper(trim($gender))) {
            'M', 'MALE' => 'Male',
            'F', 'FEMALE' => 'Female',
            default => $gender
        };
    }

    private function createCustomerInsuredLink(Insured $insured): void
    {
        if (! $this->quote->customer_id || ! $insured->id) {
            LoggerService::warning('CustomerInsured relationship creation skipped - missing required data');

            return;
        }

        try {
            $quoteTypeId = $this->getQuoteTypeId($this->quote);

            $existingLink = CustomerInsured::active()->where([
                'customer_id' => $this->quote->customer_id,
                'insured_id' => $insured->id,
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $this->quote->id,
            ])
                ->latest('updated_at')
                ->first();

            if (! $existingLink) {
                CustomerInsured::create([
                    'customer_id' => $this->quote->customer_id,
                    'insured_id' => $insured->id,
                    'quote_type_id' => $quoteTypeId,
                    'quote_request_id' => $this->quote->id,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                LoggerService::info('CustomerInsured relationship created');
            } else {
                LoggerService::info('CustomerInsured relationship already exists - skipping creation');
            }
        } catch (Exception $e) {
            LoggerService::error('Failed to create CustomerInsured relationship - Quote UUID: '.$this->quote->uuid, exception: $e);
        }
    }

    public function getProcessingSummary(): array
    {
        try {
            $insured = $this->quote->latestInsured;

            if (! $insured) {
                return [
                    'status' => 'no_insured_found',
                    'message' => 'No insured record found for this quote',
                ];
            }

            $insuredKyc = $insured->insuredKyc;

            return [
                'status' => 'success',
                'insured_id' => $insured->id,
                'has_kyc_data' => ! is_null($insuredKyc),
                'insured_data' => [
                    'name' => trim(($insured->first_name ?? '').' '.($insured->last_name ?? '')),
                    // 'dob' => $insured->dob,
                    // 'nationality_id' => $insured->nationality_id,
                    'gender' => $insured->gender,
                    'id_number' => $insured->id_number,
                    'id_type' => $insured->id_type,
                ],
                'kyc_data' => $insuredKyc ? [
                    'country_of_residence' => $insuredKyc->country_of_residence,
                    'country_of_residence_name' => $this->getNationalityName($insuredKyc->country_of_residence),
                    'place_of_birth' => $insuredKyc->place_of_birth,
                    'place_of_birth_name' => $this->getNationalityName($insuredKyc->place_of_birth),
                    'id_issuance_date' => $insuredKyc->id_issuance_date,
                    'id_expiry_date' => $insuredKyc->id_expiry_date,
                    'issuance_place' => $insuredKyc->issuance_place,
                    'residential_address' => $insuredKyc->residential_address,
                    'id_type' => $insuredKyc->id_type,
                    'id_number' => $insuredKyc->id_number,
                    'first_name' => $insuredKyc->first_name,
                    'last_name' => $insuredKyc->last_name,
                    'employer_company_name' => $insuredKyc->employer_company_name,
                    'job_title' => $insuredKyc->job_title,
                    'source_of_income' => $insuredKyc->source_of_income,
                ] : null,
            ];

        } catch (Exception $e) {
            LoggerService::error('Failed to get processing summary - Quote UUID: '.$this->quote->uuid, exception: $e);

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve processing summary',
            ];
        }
    }

    private function mapOccupationToJobTitle(string $occupation): ?string
    {
        $occupation = trim($occupation);

        $professionalTitle = Lookup::where('key', LookupsEnum::PROFESSIONAL_TITLE)
            ->where('code', $occupation)
            ->first();

        if ($professionalTitle) {
            LoggerService::info('Emirates ID OCR: Professional title matched', [
                'original_occupation' => $occupation,
                'matched_title' => $professionalTitle->text,
                'lookup_code' => $professionalTitle->code,
            ]);

            return $professionalTitle->code;
        }

        LoggerService::info('Emirates ID OCR: No professional title match found', [
            'original_occupation' => $occupation,
        ]);

        return null;
    }
}
