<?php

declare(strict_types=1);

namespace App\Services\OCR\EmiratesId;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Models\CustomerInsured;
use App\Models\Insured;
use App\Models\InsuredKyc;
use App\Models\Nationality;
use App\Exceptions\OCR\OcrProcessingException;
use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrUtils;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EmiratesIdDataProcessor
{
    private EmiratesIdExtractor $emiratesIdExtractor;
    private array $extractedData = [];

    public function __construct(
        private Model $quote,
        private object $data,
    ) {
        $this->emiratesIdExtractor = new EmiratesIdExtractor($this->data);
    }

    public function processEmiratesIdData(): bool
    {
        try {
            DB::beginTransaction();

            $this->extractedData = $this->emiratesIdExtractor->extractSingleEmiratesId()->getExtractedData();

            LoggerService::info('Emirates ID data processor started - Quote UUID: '.$this->quote->uuid, extra: [
                'quote_type' => class_basename($this->quote),
                'extracted_fields' => array_keys(array_filter($this->extractedData, fn ($v) => ! empty($v))),
            ]);

            $insured = $this->getOrCreateInsuredRecord();
            if (! $insured) {
                throw new OcrProcessingException('Failed to get or create Insured record for Emirates ID processing');
            }

            $insuredUpdated = $this->updateInsuredTable($insured);
            $kycUpdated = $this->updateInsuredKycTable($insured);

            DB::commit();

            LoggerService::info('Emirates ID data processing completed successfully - Quote UUID: '.$this->quote->uuid, extra: [
                'insured_id' => $insured->id,
                'insured_updated' => $insuredUpdated,
                'kyc_updated' => $kycUpdated,
            ]);

            return $insuredUpdated || $kycUpdated;

        } catch (Exception $e) {
            DB::rollBack();

            LoggerService::error('Emirates ID data processing failed - Quote UUID: '.$this->quote->uuid, exception: $e, extra: [
                'quote_type' => class_basename($this->quote),
            ]);

            return false;
        }
    }

    private function getOrCreateInsuredRecord(): ?Insured
    {
        try {
            $insured = $this->quote->latestInsured ?? null;

            if (! $insured && ! empty($this->extractedData['eid_number'])) {
                $insured = Insured::where('id_number', $this->extractedData['eid_number'])
                    ->where('id_type', 'emiratesId')
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
            LoggerService::error('Failed to get or create Insured record - Quote UUID: '.$this->quote->uuid, exception: $e);

            return null;
        }
    }

    private function createInsuredRecord(): Insured
    {
        $insuredData = OcrUtils::getCleanData([
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
            $updateData = [];

            if (! empty($this->extractedData['name'])) {
                $updateData['first_name'] = $this->extractFirstName($this->extractedData['name']);
                $updateData['last_name'] = $this->extractLastName($this->extractedData['name']);
            }

            if (! empty($this->extractedData['date_of_birth'])) {
                $updateData['dob'] = $this->extractedData['date_of_birth'];
            }

            if (! empty($this->extractedData['nationality'])) {
                $nationalityId = $this->getNationalityId($this->extractedData['nationality']);
                if ($nationalityId) {
                    $updateData['nationality_id'] = $nationalityId;
                }
            }

            if (! empty($this->extractedData['sex'])) {
                $updateData['gender'] = $this->formatGender($this->extractedData['sex']);
            }

            if (! empty($this->extractedData['eid_number'])) {
                $updateData['id_type'] = 'emiratesId';
                $updateData['id_number'] = $this->extractedData['eid_number'];
            }

            // Update all fields with OCR data
            $dataToUpdate = OcrUtils::getFieldsToUpdate($updateData);

            if (! empty($dataToUpdate)) {
                $insured->update($dataToUpdate);

                LoggerService::info('Insured table updated successfully - Quote UUID: '.$this->quote->uuid, extra: [
                    'insured_id' => $insured->id,
                    'updated_fields' => array_keys($dataToUpdate),
                ]);

                return true;
            }

            LoggerService::info('Insured table - No OCR data to update - Quote UUID: '.$this->quote->uuid, extra: [
                'insured_id' => $insured->id,
            ]);

            return false;

        } catch (Exception $e) {
            LoggerService::error('Failed to update Insured table - Quote UUID: '.$this->quote->uuid, exception: $e, extra: [
                'insured_id' => $insured->id,
            ]);

            return false;
        }
    }

    private function updateInsuredKycTable(Insured $insured): bool
    {
        try {
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

            // Sync insured table data to insured_kyc table
            $kycData['id_type'] = $insured->id_type;
            $kycData['id_number'] = $insured->id_number;
            $kycData['first_name'] = $insured->first_name;
            $kycData['last_name'] = $insured->last_name;

            $insuredKyc = $insured->insuredKyc;

            if ($insuredKyc) {
                // Update all fields with OCR data
                $dataToUpdate = OcrUtils::getFieldsToUpdate($kycData);

                if (! empty($dataToUpdate)) {
                    $insuredKyc->update($dataToUpdate);

                    LoggerService::info('InsuredKyc table updated successfully - Quote UUID: '.$this->quote->uuid, extra: [
                        'insured_id' => $insured->id,
                        'updated_fields' => array_keys($dataToUpdate),
                    ]);

                    return true;
                } else {
                    LoggerService::info('InsuredKyc table - No OCR data to update - Quote UUID: '.$this->quote->uuid, extra: [
                        'insured_id' => $insured->id,
                    ]);

                    return false;
                }
            } else {
                $kycData['insured_id'] = $insured->id;
                InsuredKyc::create($kycData);

                LoggerService::info('InsuredKyc table created successfully - Quote UUID: '.$this->quote->uuid, extra: [
                    'insured_id' => $insured->id,
                    'created_fields' => array_keys($kycData),
                ]);

                return true;
            }

        } catch (Exception $e) {
            LoggerService::error('Failed to update InsuredKyc table - Quote UUID: '.$this->quote->uuid, exception: $e, extra: [
                'insured_id' => $insured->id,
            ]);

            return false;
        }
    }

    private function getNationalityId(?string $nationality): ?int
    {
        if (empty($nationality)) {
            return null;
        }

        $nationalityRecord = Nationality::where('text', 'LIKE', '%'.$nationality.'%')
            ->orWhere('code', $nationality)
            ->orWhere('country_name', 'LIKE', '%'.$nationality.'%')
            ->first();

        return $nationalityRecord?->id;
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
            LoggerService::warning('CustomerInsured relationship creation skipped - missing required data - Quote UUID: '.$this->quote->uuid, extra: [
                'customer_id' => $this->quote->customer_id ?? 'null',
                'insured_id' => $insured->id ?? 'null',
                'quote_id' => $this->quote->id ?? 'null',
            ]);

            return;
        }

        try {
            $quoteTypeId = $this->getQuoteTypeId();

            $existingLink = CustomerInsured::where([
                'customer_id' => $this->quote->customer_id,
                'insured_id' => $insured->id,
                'quote_type_id' => $quoteTypeId,
                'quote_request_id' => $this->quote->id,
            ])->first();

            if (! $existingLink) {
                CustomerInsured::create([
                    'customer_id' => $this->quote->customer_id,
                    'insured_id' => $insured->id,
                    'quote_type_id' => $quoteTypeId,
                    'quote_request_id' => $this->quote->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                LoggerService::info('CustomerInsured relationship created - Quote UUID: '.$this->quote->uuid, extra: [
                    'customer_id' => $this->quote->customer_id,
                    'insured_id' => $insured->id,
                    'quote_type_id' => $quoteTypeId,
                    'quote_request_id' => $this->quote->id,
                ]);
            } else {
                LoggerService::info('CustomerInsured relationship already exists - skipping creation - Quote UUID: '.$this->quote->uuid, extra: [
                    'customer_id' => $this->quote->customer_id,
                    'insured_id' => $insured->id,
                    'quote_type_id' => $quoteTypeId,
                    'quote_request_id' => $this->quote->id,
                ]);
            }
        } catch (Exception $e) {
            LoggerService::error('Failed to create CustomerInsured relationship - Quote UUID: '.$this->quote->uuid, exception: $e, extra: [
                'customer_id' => $this->quote->customer_id ?? 'null',
                'insured_id' => $insured->id ?? 'null',
                'quote_id' => $this->quote->id ?? 'null',
            ]);
        }
    }

    private function getQuoteTypeId(): int
    {
        // Currently only supporting Car quotes for Emirates ID OCR
        return QuoteTypes::getId(QuoteTypes::CAR) ?? QuoteTypeId::Car;
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
                    'dob' => $insured->dob,
                    'nationality_id' => $insured->nationality_id,
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
}
