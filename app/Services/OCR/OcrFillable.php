<?php

namespace App\Services\OCR;

use App\Enums\ApplicationStorageEnums;
use App\Enums\DocumentTypeCategory;
use App\Enums\InsurerProviderEnum;
use App\Enums\OCRDocumentTypeEnum;
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
    private function isSupportedProvider(Model $quote): bool
    {
        return $quote->isProvider(InsurerProviderEnum::GIG_INSURANCE) ||
            $quote->isProvider(InsurerProviderEnum::SUKOON_OMAN_INSURANCE) ||
            $quote->isProvider(InsurerProviderEnum::QATAR_INSURANCE) ||
            $quote->isProvider(InsurerProviderEnum::LIVANA_INSURANCE) ||
            $quote->isProvider(InsurerProviderEnum::TOKIO_MARINE);
    }

    private function isEnabled(Model $quote, array $providers): bool
    {
        foreach ($providers as $provider) {
            if ($quote->isProvider($provider)) {
                return true;
            }
        }

        return false;
    }

    private function resolveProp($object, $prop)
    {
        if (is_object($object) && property_exists($object, $prop)) {
            return $object->$prop;
        }

        return null;
    }

    private function parseDate($date, $default = null, $format = 'Y-m-d')
    {
        try {
            return $date ? Carbon::parse($date)->format($format) : $default;
        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred during date parsing: ', exception: $e);

            return $default;
        }
    }

    private function fillTaxInvoice(Model $quote, object $data)
    {
        $providersWithPolicyIssuanceDate = [];

        $providersWithPriceVatApplicable = [
            InsurerProviderEnum::GIG_INSURANCE,
            InsurerProviderEnum::SUKOON_OMAN_INSURANCE,
            InsurerProviderEnum::QATAR_INSURANCE,
            InsurerProviderEnum::LIVANA_INSURANCE,
            InsurerProviderEnum::TOKIO_MARINE,
        ];

        $dataToUpdate = [];

        $price = $this->resolveProp($data, 'price');

        if ($this->isEnabled($quote, $providersWithPriceVatApplicable)) {
            $vatPercentage = app(ApplicationStorageService::class)->getValueByKey(ApplicationStorageEnums::VAT_VALUE);
            $priceVatApplicable = $this->resolveProp($price, 'baseAmount') ?? $quote->price_vat_applicable;
            $vatAmount = $priceVatApplicable * $vatPercentage / 100;
            $priceWithVat = $priceVatApplicable + $vatAmount;
            $dataToUpdate['price_with_vat'] = $priceWithVat;
            $dataToUpdate['vat'] = $vatAmount;
            $dataToUpdate['price_vat_applicable'] = $priceVatApplicable;
        }

        if ($this->isEnabled($quote, $providersWithPolicyIssuanceDate)) {
            $dataToUpdate['vat'] = $this->resolveProp($price, 'VAT') ?? $quote->vat;
            $dataToUpdate['price_with_vat'] = $this->resolveProp($price, 'totalAmount') ?? $quote->price_with_vat;
            $dataToUpdate['policy_issuance_date'] = $this->parseDate($this->resolveProp($data, 'issuanceDate'), $quote->policy_issuance_date);
        }

        if (! empty($dataToUpdate)) {
            $quote->update($dataToUpdate);
        }

        $providersWithInsurerInvoiceDate = [
            InsurerProviderEnum::GIG_INSURANCE,
            InsurerProviderEnum::SUKOON_OMAN_INSURANCE,
            InsurerProviderEnum::QATAR_INSURANCE,
            InsurerProviderEnum::LIVANA_INSURANCE,
            InsurerProviderEnum::TOKIO_MARINE,
        ];

        $providersWithInsurerTaxNumber = [
            InsurerProviderEnum::GIG_INSURANCE,
            InsurerProviderEnum::SUKOON_OMAN_INSURANCE,
            InsurerProviderEnum::QATAR_INSURANCE,
            InsurerProviderEnum::LIVANA_INSURANCE,
            InsurerProviderEnum::TOKIO_MARINE,
        ];

        $paymentDataToUpdate = [];

        if ($this->isEnabled($quote, $providersWithInsurerInvoiceDate)) {
            $paymentDataToUpdate['insurer_invoice_date'] = $this->parseDate($this->resolveProp($data, 'invoiceDate'), $quote->payment?->insurer_invoice_date);
        }

        if ($this->isEnabled($quote, $providersWithInsurerTaxNumber)) {
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
        $providersWithCommission = [];

        $providersWithTaxInvoiceNumber = [
            InsurerProviderEnum::GIG_INSURANCE,
            InsurerProviderEnum::SUKOON_OMAN_INSURANCE,
            InsurerProviderEnum::QATAR_INSURANCE,
            InsurerProviderEnum::LIVANA_INSURANCE,
            InsurerProviderEnum::TOKIO_MARINE,
        ];

        $providersWithCommissionVatApplicable = [
            InsurerProviderEnum::SUKOON_OMAN_INSURANCE,
            InsurerProviderEnum::QATAR_INSURANCE,
            InsurerProviderEnum::LIVANA_INSURANCE,
            InsurerProviderEnum::TOKIO_MARINE,
        ];

        $dataToUpdate = [];

        $commission = $this->resolveProp($data, 'commission');

        if ($this->isEnabled($quote, $providersWithCommission)) {
            $commissionVat = $this->resolveProp($commission, 'VAT') ?? ($quote->payment?->comission_vat ?: 0);
            $commissionPercentageDivisor = 1 + ($commissionVat > 0 ? .05 : 0);
            $commissionWithoutVat = $dataToUpdate['commission'] - $commissionVat;
            $premiumWithoutVat = $quote->payment->total_price / $commissionPercentageDivisor;
            $commissionPercentage = roundNumber((($commissionWithoutVat / $premiumWithoutVat) * 100)) ?? $quote->payment?->comission_percentage;
            $dataToUpdate['commission_vat'] = $commissionVat;
            $dataToUpdate['commission'] = $this->resolveProp($commission, 'totalAmount') ?? $quote->payment?->comission;
            $dataToUpdate['commmission_percentage'] = $commissionPercentage;
        }

        if ($this->isEnabled($quote, $providersWithTaxInvoiceNumber)) {
            $dataToUpdate['insurer_commmission_invoice_number'] = $this->resolveProp($data, 'taxInvoiceNumber') ?? $quote->payment?->insurer_commmission_invoice_number;
        }

        if ($this->isEnabled($quote, $providersWithCommissionVatApplicable) && ! $quote->payment?->commission_vat_applicable) {
            $dataToUpdate['commission_vat_applicable'] = $this->resolveProp($commission, 'baseAmount') ?? $quote->payment?->commission_vat_applicable;
        }

        if (! empty($dataToUpdate)) {
            $quote->payment?->update($dataToUpdate);
            (new SplitPaymentService)->updateCommissionSchedule($quote->payment);
        }

        return true;
    }

    private function fillCertificateOfIssuance(Model $quote, object $data)
    {
        $providersWithPolicyNumber = [
            InsurerProviderEnum::GIG_INSURANCE,
            InsurerProviderEnum::SUKOON_OMAN_INSURANCE,
            InsurerProviderEnum::QATAR_INSURANCE,
            InsurerProviderEnum::LIVANA_INSURANCE,
            InsurerProviderEnum::TOKIO_MARINE,
        ];

        $providersWithPolicyDates = [
            InsurerProviderEnum::GIG_INSURANCE,
            InsurerProviderEnum::SUKOON_OMAN_INSURANCE,
            InsurerProviderEnum::QATAR_INSURANCE,
            InsurerProviderEnum::LIVANA_INSURANCE,
            InsurerProviderEnum::TOKIO_MARINE,
        ];

        $dataToUpdate = [];

        if ($this->isEnabled($quote, $providersWithPolicyNumber)) {
            $dataToUpdate['policy_number'] = $this->resolveProp($data, 'policyNumber') ?? $quote->policy_number;
        }

        if ($this->isEnabled($quote, $providersWithPolicyDates)) {
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
        LoggerService::startQuoteLogging($quote);

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
        LoggerService::startQuoteLogging($quote);
        
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
        LoggerService::startQuoteLogging($quote);

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

    private function fill(
        Model $quote,
        OCRDocumentTypeEnum $documentType,
        object $data,
        $documentCategory
    ) {
        if (! $this->isSupportedProvider($quote) && $documentCategory != DocumentTypeCategory::QUOTE) {
            LoggerService::info(self::class.' - Not a Valid Provider');

            return false;
        }

        try {

            return match ($documentType) {
                OCRDocumentTypeEnum::TAX_INVOICE => $this->fillTaxInvoice($quote, $data),
                OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER => $this->fillTaxInvoiceRaisedByBuyer($quote, $data),
                OCRDocumentTypeEnum::CERTIFICATE_OF_ISSUANCE => $this->fillCertificateOfIssuance($quote, $data),
                OCRDocumentTypeEnum::MOTOR_INSURANCE_POLICY_SCHEDULE => $this->fillMotorInsurancePolicySchedule($quote, $data),
                OCRDocumentTypeEnum::ID_CARD => $this->fillEmiratesId($quote, $data),
                OCRDocumentTypeEnum::REGISTRATION_CERTIFICATE => $this->fillMulkiya($quote, $data),
                OCRDocumentTypeEnum::DRIVING_LICENSE => $this->fillDrivingLicense($quote, $data),
                default => false,
            };
        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred during data fill: ', exception: $e);

            return false;
        }
    }
}
