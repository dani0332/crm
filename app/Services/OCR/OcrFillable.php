<?php

namespace App\Services\OCR;

use App\Enums\DocumentTypeCategory;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Services\CustomerVerification\CustomerVerificationService;
use App\Services\Logger\LoggerService;
use App\Services\OCR\DrivingLicense\DrivingLicenseDataProcessor;
use App\Services\OCR\EmiratesId\EmiratesIdDataProcessor;
use App\Services\OCR\Mulkiya\MulkiyaDataProcessor;
use App\Services\OCR\PolicySchedule\PolicyScheduleDataProcessor;
use App\Services\OCR\TaxInvoice\TaxInvoiceDataProcessor;
use App\Services\OCR\TaxInvoiceRaisedByBuyer\TaxInvoiceRaisedByBuyerDataProcessor;
use Exception;
use Illuminate\Database\Eloquent\Model;

trait OcrFillable
{
    use OcrUtils , OcrValidator;

    private $providerCode = '';
    private $isSendUpdateEligibleForOCR = false;
    private $documentTypeCode = null;

    private function isEnabled(Model $quote, array $providers): bool
    {
        foreach ($providers as $provider) {
            if ($quote->isProvider($provider)) {
                return true;
            }
        }

        return false;
    }

    private function fillTaxInvoice(Model $quote, object $data)
    {
        try {
            // Create a single instance of the processor to reuse
            $processor = new TaxInvoiceDataProcessor(
                $quote,
                $data,
                $this->isSendUpdateEligibleForOCR,
                $this->providerCode
            );

            $success = $processor->processTaxInvoiceData();

            if ($success) {
                // Get processing summary from the same processor instance
                $summary = $processor->getProcessingSummary();

                LoggerService::info(self::class.' - Tax Invoice data processing completed successfully - Quote UUID: '.$quote->uuid, extra: [
                    'processing_summary' => $summary,
                ]);
            } else {
                LoggerService::warning(self::class.' - Tax Invoice data processing failed - Quote UUID: '.$quote->uuid);
            }

            return $success;

        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred during Tax Invoice data filling - Quote UUID: '.$quote->uuid, exception: $e);

            return false;
        }
    }

    private function fillTaxInvoiceRaisedByBuyer(Model $quote, object $data)
    {
        try {
            // Create a single instance of the processor to reuse
            $processor = new TaxInvoiceRaisedByBuyerDataProcessor(
                $quote,
                $data,
                $this->isSendUpdateEligibleForOCR,
                $this->providerCode
            );

            $success = $processor->processTaxInvoiceRaisedByBuyerData();

            if ($success) {
                // Get processing summary from the same processor instance
                $summary = $processor->getProcessingSummary();

                LoggerService::info(self::class.' - Tax Invoice Raised By Buyer data processing completed successfully - Quote UUID: '.$quote->uuid, extra: [
                    'processing_summary' => $summary,
                ]);
            } else {
                LoggerService::warning(self::class.' - Tax Invoice Raised By Buyer data processing failed - Quote UUID: '.$quote->uuid);
            }

            return $success;

        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred during Tax Invoice Raised By Buyer data filling - Quote UUID: '.$quote->uuid, exception: $e);

            return false;
        }
    }

    private function fillCertificateOfIssuance(Model $quote, object $data)
    {
        $dataToUpdate = [];

        if ($this->isFieldEnabled($this->providerCode, 'quote.policy_number')) {
            $dataToUpdate['policy_number'] = $this->resolveProp($data, 'policyNumber') ?? $quote->policy_number;
        }

        if ($this->isFieldEnabled($this->providerCode, 'quote.policy_start_date') && $this->isFieldEnabled($this->providerCode, 'quote.policy_expiry_date')) {
            $dataToUpdate['policy_start_date'] = $this->parseDate($this->resolveProp($data, 'policyStartDate'), $quote->policy_start_date);
            $dataToUpdate['policy_expiry_date'] = $this->parseDate($this->resolveProp($data, 'policyExpiryDate'), $quote->policy_expiry_date);
        }

        if (! $quote->policy_issuance_date) {
            $dataToUpdate['policy_issuance_date'] = now();
        }

        if (! empty($dataToUpdate)) {
            $quote->update($dataToUpdate);
        }

        return true;
    }

    private function fillMotorInsurancePolicySchedule(Model $quote, object $data)
    {
        $providersWithInsurerQuoteNumber = [];

        $dataToUpdate = [];

        if ($this->isEnabled($quote, $providersWithInsurerQuoteNumber)) {
            $dataToUpdate['insurer_quote_number'] = $this->resolveProp($data, 'insurerQuoteNumber') ?? $quote->insurer_quote_number;
        }

        if (! empty($dataToUpdate)) {
            $quote->update($dataToUpdate);
        }

        return true;
    }

    private function fillEmiratesId(Model $quote, object $data)
    {
        try {
            // Create a single instance of the processor to reuse
            $processor = new EmiratesIdDataProcessor($quote, $data);

            $success = $processor->processEmiratesIdData();

            if ($success) {
                // Get processing summary from the same processor instance
                $summary = $processor->getProcessingSummary();

                LoggerService::info(self::class.' - Emirates ID data processing completed successfully - Quote UUID: '.$quote->uuid, extra: [
                    'processing_summary' => $summary,
                ]);

                // Update customer verification details
                app(CustomerVerificationService::class)->processOcrVerification($quote, $data, $this->documentTypeCode);
            } else {
                LoggerService::warning(self::class.' - Emirates ID data processing failed - Quote UUID: '.$quote->uuid);
            }

            return $success;

        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred during Emirates ID data filling - Quote UUID: '.$quote->uuid, exception: $e);

            return false;
        }
    }

    private function fillMulkiya(Model $quote, object $data)
    {
        try {
            // Create a single instance of the processor to reuse
            $processor = new MulkiyaDataProcessor($quote, $data);

            $success = $processor->processMulkiyaData();

            if ($success) {
                // Get processing summary from the same processor instance
                $summary = $processor->getProcessingSummary();

                LoggerService::info(self::class.' - Mulkiya data processing completed successfully - Quote UUID: '.$quote->uuid, extra: [
                    'processing_summary' => $summary,
                ]);

                  // Update customer verification details
                  app(CustomerVerificationService::class)->processOcrVerification($quote, $data, $this->documentTypeCode);
            } else {
                LoggerService::warning(self::class.' - Mulkiya data processing failed - Quote UUID: '.$quote->uuid);
            }

            return $success;

        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred during Mulkiya data filling - Quote UUID: '.$quote->uuid, exception: $e);

            return false;
        }
    }

    private function fillDrivingLicense(Model $quote, object $data)
    {
        try {
            // Create a single instance of the processor to reuse
            $processor = new DrivingLicenseDataProcessor($quote, $data);

            $success = $processor->processDrivingLicenseData();

            if ($success) {
                // Get processing summary from the same processor instance
                $summary = $processor->getProcessingSummary();

                LoggerService::info(self::class.' - Driving License data processing completed successfully - Quote UUID: '.$quote->uuid, extra: [
                    'processing_summary' => $summary,
                ]);
            } else {
                LoggerService::warning(self::class.' - Driving License data processing failed - Quote UUID: '.$quote->uuid);
            }

            return $success;

        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred during Driving License data filling - Quote UUID: '.$quote->uuid, exception: $e);

            return false;
        }
    }

    private function fillPolicySchedule(Model $quote, object $data)
    {
        try {
            // Create a single instance of the processor to reuse
            $processor = new PolicyScheduleDataProcessor(
                $quote,
                $data,
                $this->isSendUpdateEligibleForOCR,
                $this->providerCode
            );

            $success = $processor->processPolicyScheduleData();

            if ($success) {
                // Get processing summary from the same processor instance
                $summary = $processor->getProcessingSummary();

                LoggerService::info(self::class.' - Policy Schedule data processing completed successfully - Quote UUID: '.$quote->uuid, extra: [
                    'processing_summary' => $summary,
                ]);
            } else {
                LoggerService::warning(self::class.' - Policy Schedule data processing failed - Quote UUID: '.$quote->uuid);
            }

            return $success;

        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred during Policy Schedule data filling - Quote UUID: '.$quote->uuid, exception: $e);

            return false;
        }
    }

    private function fill(
        Model $quote,
        OCRDocumentTypeEnum $documentType,
        object $data,
        $documentCategory,
        bool $isSendUpdateEligibleForOCR,
        QuoteTypes $quoteType
    ) {
        $this->providerCode = $this->getProvider($quote);
        $this->isSendUpdateEligibleForOCR = $isSendUpdateEligibleForOCR;

        if (! $this->isSupportedProvider($quoteType, $this->providerCode) && $documentCategory != DocumentTypeCategory::QUOTE) {
            LoggerService::info(self::class.' - Not a Valid Provider');

            return false;
        }

        LoggerService::startQuoteLogging($quote);

        try {
            // Use enum value for logging (e.g., 'IDC', 'RC', 'DL')
            $this->documentTypeCode = $documentType->value;
            
            return match ($documentType) {
                OCRDocumentTypeEnum::TAX_INVOICE => $this->fillTaxInvoice($quote, $data),
                OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER => $this->fillTaxInvoiceRaisedByBuyer($quote, $data),
                OCRDocumentTypeEnum::CERTIFICATE_OF_ISSUANCE => $this->fillCertificateOfIssuance($quote, $data),
                OCRDocumentTypeEnum::ID_CARD => $this->fillEmiratesId($quote, $data),
                OCRDocumentTypeEnum::REGISTRATION_CERTIFICATE => $this->fillMulkiya($quote, $data),
                OCRDocumentTypeEnum::DRIVING_LICENSE => $this->fillDrivingLicense($quote, $data),
                OCRDocumentTypeEnum::MOTOR_INSURANCE_POLICY_SCHEDULE => in_array($quoteType, [QuoteTypes::HOME, QuoteTypes::GROUP_MEDICAL], true)
                    ? $this->fillPolicySchedule($quote, $data)
                    : $this->fillMotorInsurancePolicySchedule($quote, $data),
                OCRDocumentTypeEnum::POLICY_SCHEDULE => $this->fillPolicySchedule($quote, $data), // for home and group medical policy schedule
                default => false,
            };
        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred during data fill: ', exception: $e);

            return false;
        }
    }
}
