<?php

namespace App\Services\OCR;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCategory;
use App\Enums\InsurerProviderEnum;
use App\Enums\OCRDocumentTypeEnum;
use App\Enums\QuoteTypes;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;
use App\Services\OCR\DrivingLicense\DrivingLicenseDataProcessor;
use App\Services\OCR\EmiratesId\EmiratesIdDataProcessor;
use App\Services\OCR\Mulkiya\MulkiyaDataProcessor;
use App\Services\SplitPaymentService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;

trait OcrFillable
{
    use OcrUtils , OcrValidator;

    private $providerCode = '';
    private $isSendUpdateEligibleForOCR = false;

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
        $dataToUpdate = [];

        $price = $this->resolveProp($data, 'price');

        if ($this->isFieldEnabled($this->providerCode, 'quote.price_with_vat') &&
            $this->isFieldEnabled($this->providerCode, 'quote.price_vat_applicable')) {

            $priceVatApplicable = $this->resolveProp($price, 'baseAmount') ?? $quote->price_vat_applicable;
            $priceWithVat = $this->resolveProp($price, 'totalAmount') ?? $quote->price_with_vat;

            $dataToUpdate['price_with_vat'] = $priceWithVat;
            $dataToUpdate['price_vat_applicable'] = $priceVatApplicable;

            // Only update 'vat' column for regular quotes, not Send Update logs
            if (! $this->isSendUpdateEligibleForOCR && $this->isFieldEnabled($this->providerCode, 'quote.vat')) {
                $vatPercentage = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::VAT_VALUE);
                $vatAmount = $priceVatApplicable * $vatPercentage / 100;
                $dataToUpdate['vat'] = $vatAmount;
            }
        }

        if ($this->isFieldEnabled($this->providerCode, 'quote.policy_issuance_date')) {
            $dataToUpdate['price_with_vat'] = $this->resolveProp($price, 'totalAmount') ?? $quote->price_with_vat;
            $dataToUpdate['policy_issuance_date'] = $this->parseDate($this->resolveProp($data, 'issuanceDate'), $quote->policy_issuance_date);

            // Only update 'vat' column for regular quotes, not Send Update logs
            if (! $this->isSendUpdateEligibleForOCR) {
                $dataToUpdate['vat'] = $this->resolveProp($price, 'VAT') ?? $quote->vat;
            }
        }

        if (! empty($dataToUpdate)) {
            $quote->update($dataToUpdate);
        }

        $paymentDataToUpdate = [];

        if ($this->isFieldEnabled($this->providerCode, 'payment.insurer_invoice_date')) {
            $paymentDataToUpdate['insurer_invoice_date'] = $this->parseDate($this->resolveProp($data, 'invoiceDate'), $quote->payment?->insurer_invoice_date);
        }

        if ($this->isFieldEnabled($this->providerCode, 'payment.insurer_tax_number') && $this->isFieldEnabled($this->providerCode, 'payment.tax_invoice_number')) {
            $taxInvoiceNumber = $this->resolveProp($data, 'taxInvoiceNumber');

            $paymentDataToUpdate['insurer_tax_number'] = $taxInvoiceNumber ?? $quote->payment?->insurer_tax_number;
            $paymentDataToUpdate['tax_invoice_number'] = $taxInvoiceNumber ?? $quote->payment?->tax_invoice_number;
        }

        if (! empty($paymentDataToUpdate) && $quote->payment) {
            $quote->payment->update($paymentDataToUpdate);
        }

        return true;
    }

    private function fillTaxInvoiceRaisedByBuyer(Model $quote, object $data)
    {
        $dataToUpdate = [];
        $commission = $this->resolveProp($data, 'commission');

        if ($this->isSendUpdateEligibleForOCR) {
            // For Send Update logs, update only specific columns directly on the Send Update log
            if ($this->isFieldEnabled($this->providerCode, 'quote.insurer_commission_invoice_number')) {
                $dataToUpdate['insurer_commission_invoice_number'] = $this->resolveProp($data, 'taxInvoiceNumber') ?? $quote->insurer_commission_invoice_number;
            }

            if ($this->isFieldEnabled($this->providerCode, 'quote.commission_vat_applicable')) {
                $dataToUpdate['commission_vat_applicable'] = $this->resolveProp($commission, 'totalAmount') ?? $quote->commission_vat_applicable;
            }

            if (! empty($dataToUpdate)) {
                $quote->update($dataToUpdate);
            }
        } else {
            // Original logic for regular quotes
            if ($this->isFieldEnabled($this->providerCode, 'quote.commmission_percentage')) {
                $commissionVat = $this->resolveProp($commission, 'VAT') ?? ($quote->payment?->comission_vat ?: 0);
                $commissionPercentageDivisor = 1 + ($commissionVat > 0 ? .05 : 0);
                $commissionWithoutVat = $dataToUpdate['commission'] - $commissionVat;
                $premiumWithoutVat = $quote->payment->total_price / $commissionPercentageDivisor;
                $commissionPercentage = roundNumber((($commissionWithoutVat / $premiumWithoutVat) * 100)) ?? $quote->payment?->comission_percentage;
                $dataToUpdate['commission_vat'] = $commissionVat;
                $dataToUpdate['commission'] = $this->resolveProp($commission, 'totalAmount') ?? $quote->payment?->comission;
                $dataToUpdate['commmission_percentage'] = $commissionPercentage;
            }

            if ($this->isFieldEnabled($this->providerCode, 'quote.insurer_commmission_invoice_number')) {
                $dataToUpdate['insurer_commmission_invoice_number'] = $this->resolveProp($data, 'taxInvoiceNumber') ?? $quote->payment?->insurer_commmission_invoice_number;
            }

            if ($this->isFieldEnabled($this->providerCode, 'quote.commission_vat_applicable')) {
                if (! $quote->payment?->commission_vat_applicable) {
                    $dataToUpdate['commission_vat_applicable'] = $this->resolveProp($commission, 'baseAmount') ?? $quote->payment?->commission_vat_applicable;
                }
            }

            if (! empty($dataToUpdate)) {
                $quote->payment?->update($dataToUpdate);
                (new SplitPaymentService)->updateCommissionSchedule($quote->payment);
            }
        }

        return true;
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
            $success = (new EmiratesIdDataProcessor($quote, $data))->processEmiratesIdData();

            if ($success) {
                // we can remove after testing
                $summary = (new EmiratesIdDataProcessor($quote, $data))->getProcessingSummary();

                LoggerService::info(self::class.' - Emirates ID data processing completed successfully - Quote UUID: '.$quote->uuid, extra: [
                    'processing_summary' => $summary,
                ]);
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
            $success = (new MulkiyaDataProcessor($quote, $data))->processMulkiyaData();

            if ($success) {
                // we can remove after testing
                $summary = (new MulkiyaDataProcessor($quote, $data))->getProcessingSummary();

                LoggerService::info(self::class.' - Mulkiya data processing completed successfully - Quote UUID: '.$quote->uuid, extra: [
                    'processing_summary' => $summary,
                ]);
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
            $success = (new DrivingLicenseDataProcessor($quote, $data))->processDrivingLicenseData();

            if ($success) {
                // we can remove after testing
                $summary = (new DrivingLicenseDataProcessor($quote, $data))->getProcessingSummary();

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
        $dataToUpdate = [];

        if ($this->isSendUpdateEligibleForOCR) {
            // For Send Update logs, update only specific columns with correct column names
            if ($this->isFieldEnabled($this->providerCode, 'quote.policy_number')) {
                $dataToUpdate['policy_number'] = $this->resolveProp($data, 'policyNumber') ?? $quote->policy_number;
            }

			if ($this->isFieldEnabled($this->providerCode, 'quote.policy_start_date') && $this->isFieldEnabled($this->providerCode, 'quote.policy_expiry_date')) {
				$startDateValue = $this->resolveProp($data, 'policyStartDate') ?? $this->resolveProp($data, 'startDate');
				$expiryDateValue = $this->resolveProp($data, 'policyExpiryDate') ?? $this->resolveProp($data, 'expiryDate');
				$dataToUpdate['start_date'] = $this->parseDate($startDateValue, $quote->start_date);
				$dataToUpdate['expiry_date'] = $this->parseDate($expiryDateValue, $quote->expiry_date);
			}

            if (! empty($dataToUpdate)) {
                $quote->update($dataToUpdate);
            }
        }
        else{
            // for home and group medical policy schedule regular quotes
            if ($this->isFieldEnabled($this->providerCode, 'quote.policy_number')) {
                $dataToUpdate['policy_number'] = $this->resolveProp($data, 'policyNumber') ?? $quote->policy_number;
            }
    
			if ($this->isFieldEnabled($this->providerCode, 'quote.policy_start_date') && $this->isFieldEnabled($this->providerCode, 'quote.policy_expiry_date')) {
				$policyStartDateValue = $this->resolveProp($data, 'policyStartDate') ?? $this->resolveProp($data, 'startDate');
				$policyExpiryDateValue = $this->resolveProp($data, 'policyExpiryDate') ?? $this->resolveProp($data, 'expiryDate');
				$dataToUpdate['policy_start_date'] = $this->parseDate($policyStartDateValue, $quote->policy_start_date);
				$dataToUpdate['policy_expiry_date'] = $this->parseDate($policyExpiryDateValue, $quote->policy_expiry_date);
			}

            if (! empty($dataToUpdate)) {
                $quote->update($dataToUpdate);
            }
        }

        return true;
    }

    private function fill(
        Model $quote,
        OCRDocumentTypeEnum $documentType,
        object $data,
        $documentCategory,
        bool $isSendUpdateEligibleForOCR = false,
        QuoteTypes $quoteType
    ) {
        $this->providerCode = $this->getProvider($quote);
        $this->isSendUpdateEligibleForOCR = $isSendUpdateEligibleForOCR;

        // if (! $this->isSupportedProvider($quote) && $documentCategory != DocumentTypeCategory::QUOTE) {
        //     LoggerService::info(self::class.' - Not a Valid Provider');

        //     return false;
        // }

        if (! $this->isSupportedProvider($quoteType, $this->providerCode) && $documentCategory != DocumentTypeCategory::QUOTE) {
            LoggerService::info(self::class.' - Not a Valid Provider');

            return false;
        }

        LoggerService::startQuoteLogging($quote);

        try {

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
