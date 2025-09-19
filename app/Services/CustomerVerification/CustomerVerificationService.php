<?php

declare(strict_types=1);

namespace App\Services\CustomerVerification;

use App\Enums\QuoteTypes;
use App\Models\CustomerVerificationDetail;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Exception;

class CustomerVerificationService
{
    use GenericQueriesAllLobs;

    private function handleUnsupportedQuoteType(QuoteTypes $quoteType): array
    {
        LoggerService::warning('Unsupported quote type for verification', extra: [
            'quote_type' => $quoteType->value,
        ]);

        return ['webForm' => [], 'customerVerified' => []];
    }

    private function getEmptyVerificationData(QuoteTypes $quoteType): array
    {
        return match ($quoteType) {
            QuoteTypes::CAR => $this->getEmptyCarVerificationData(),
            default => [],
        };
    }

    private function getEmptyCarVerificationData(): array
    {
        return [
            'nationality' => '',
            'carMakeAndModel' => '',
            'carModelYear' => '',
            'dob' => '',
            'emirateOfRegistration' => '',
            'uaeLicenseHeldFor' => '',
        ];
    }

    public function getVerificationData($record, QuoteTypes $quoteType): array
    {
        if (! $record || ! isset($record->id)) {
            LoggerService::warning('Invalid quote record provided for verification', extra: [
                'record' => $record ? get_class($record) : 'null',
                'quote_type' => $quoteType->value,
            ]);

            return ['webForm' => [], 'customerVerified' => []];
        }

        LoggerService::startQuoteLogging($record->code ?? null);

        return match ($quoteType) {
            QuoteTypes::CAR => $this->getCarVerificationData($record),
            default => $this->handleUnsupportedQuoteType($quoteType),
        };
    }

    private function getCarVerificationData($record): array
    {
        $webFormData = [
            'nationality' => $record->nationality_id_text ?? null,
            'carMakeAndModel' => trim(($record->car_make_id_text ?? '').' '.($record->car_model_id_text ?? '')),
            'carModelYear' => $record->year_of_manufacture ?? null,
            'dob' => $this->formatDateToDisplay($record->dob ?? null),
            'emirateOfRegistration' => $record->emirate_of_registration_id_text ?? null,
            'uaeLicenseHeldFor' => $record->uae_license_held_for_id_text ?? null,
        ];

        $customerVerifiedData = $this->getCustomerVerifiedDetails($record->id, QuoteTypes::CAR);

        return [
            'webForm' => $webFormData,
            'customerVerified' => $customerVerifiedData,
        ];
    }

    public function getCustomerVerifiedDetails(int $quoteId, QuoteTypes $quoteType): array
    {
        try {
            $verificationRecord = CustomerVerificationDetail::with([
                'nationality',
                'carMake',
                'carModel',
                'emirate',
                'uaeLicenseHeldFor',
            ])
                ->forQuotable($quoteType->modelClass(), $quoteId)
                ->where('quote_type_id', $quoteType->id())
                ->latest()
                ->first();

            if (! $verificationRecord) {
                LoggerService::info('No customer verification record found');

                return $this->getEmptyVerificationData($quoteType);
            }

            return [
                'nationality' => $verificationRecord->nationality?->text ?? '',
                'carMakeAndModel' => trim(($verificationRecord->carMake?->text ?? '').' '.($verificationRecord->carModel?->text ?? '')),
                'carModelYear' => $verificationRecord->year_of_manufacture ?? '',
                'dob' => $this->formatDateToDisplay($verificationRecord->date_of_birth),
                'emirateOfRegistration' => $verificationRecord->emirate?->text ?? '',
                'uaeLicenseHeldFor' => $verificationRecord->uaeLicenseHeldFor?->text ?? '',
            ];
        } catch (Exception $e) {
            LoggerService::warning('Error fetching customer verification details', extra: [
                'error' => $e->getMessage(),
            ]);

            return $this->getEmptyVerificationData($quoteType);
        }
    }
}
