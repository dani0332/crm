<?php

namespace App\Services\OCR;

use App\Enums\OCRDocumentTypeEnum;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;

trait OcrFillable
{
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
            info(self::class." - Exception occurred during date parsing: {$e->getMessage()}");

            return $default;
        }
    }

    private function fillTaxInvoice(Model $quote, object $data)
    {
        $price = $this->resolveProp($data, 'price');

        $dataToUpdate = [];

        if ($quote->isGIG() || $quote->isSukoon() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio()) {
            $data['price_vat_applicable'] = $this->resolveProp($price, 'baseAmount') ?? $quote->price_vat_applicable;
        }

        // if($quote->isGIG() || $quote->isSukoon() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio()) {
        //     $data['vat'] = $this->resolveProp($price, 'VAT') ?? $quote->vat;
        // }

        // if($quote->isGIG() || $quote->isSukoon() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio()) {
        //     $data['price_with_vat'] = $this->resolveProp($price, 'totalAmount') ?? $quote->price_with_vat;
        // }

        // if($quote->isGIG() || $quote->isSukoon() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio()) {
        //     $dataToUpdate['policy_issuance_date'] = $this->parseDate($this->resolveProp($data, 'issuanceDate'), $quote->policy_issuance_date);
        // }

        if (! empty($dataToUpdate)) {
            $quote->update($dataToUpdate);
        }

        $paymentDataToUpdate = [];

        if ($quote->isGIG() || $quote->isSukoon() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio()) {
            $paymentDataToUpdate['insurer_invoice_date'] = $this->parseDate($this->resolveProp($data, 'invoiceDate'), $quote->payment?->insurer_invoice_date);
        }

        if ($quote->isGIG() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio()) {
            $paymentDataToUpdate['insurer_tax_number'] = $this->resolveProp($data, 'taxInvoiceNumber') ?? $quote->payment?->insurer_tax_number;
        }

        if (! empty($paymentDataToUpdate)) {
            $quote->payment?->update($paymentDataToUpdate);
        }
    }

    private function fillTaxInvoiceRaisedByBuyer(Model $quote, object $data)
    {
        $commission = $this->resolveProp($data, 'commission');

        $dataToUpdate = [];

        // if ($quote->isGIG() || $quote->isSukoon() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio()) {
        //     $dataToUpdate['commission_vat'] = $this->resolveProp($commission, 'VAT') ?? $quote->payment?->comission_vat;
        // }

        // if ($quote->isGIG() || $quote->isSukoon() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio()) {
        //     $dataToUpdate['commission'] = $this->resolveProp($commission, 'totalAmount') ?? $quote->payment?->comission;
        // }

        if ($quote->isGIG() || $quote->isSukoon() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio()) {
            $dataToUpdate['insurer_commmission_invoice_number'] = $this->resolveProp($data, 'taxInvoiceNumber') ?? $quote->payment?->insurer_commmission_invoice_number;
        }

        if ($quote->isSukoon() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio()) {
            $dataToUpdate['commission_vat_applicable'] = $this->resolveProp($commission, 'baseAmount') ?? $quote->payment?->comission_vat_applicable;
        }

        if (! empty($dataToUpdate)) {
            $quote->payment?->update($dataToUpdate);
        }
    }

    private function fillCertificateOfIssuance(Model $quote, object $data)
    {
        $dataToUpdate = [];

        if ($quote->isGIG() || $quote->isSukoon() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio()) {
            $dataToUpdate['policy_number'] = $this->resolveProp($data, 'policyNumber') ?? $quote->policy_number;
        }

        if ($quote->isSukoon() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio()) {
            $dataToUpdate['policy_start_date'] = $this->parseDate($this->resolveProp($data, 'policyStartDate'), $quote->policy_start_date);
        }

        if ($quote->isSukoon() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio()) {
            $dataToUpdate['policy_expiry_date'] = $this->parseDate($this->resolveProp($data, 'policyExpiryDate'), $quote->policy_expiry_date);
        }

        if (! empty($dataToUpdate)) {
            $quote->update($dataToUpdate);
        }
    }

    private function fillMotorInsurancePolicySchedule(Model $quote, object $data)
    {
        $dataToUpdate = [];

        // if ($quote->isGIG() || $quote->isSukoon() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio()) {
        //     $dataToUpdate['insurer_quote_number'] = $this->resolveProp($data, 'insurerQuoteNumber') ?? $quote->insurer_quote_number;
        // }

        if (! empty($dataToUpdate)) {
            $quote->update($dataToUpdate);
        }
    }

    private function fill(
        Model $quote,
        OCRDocumentTypeEnum $documentType,
        object $data
    ) {
        if (! ($quote->isGIG() || $quote->isSukoon() || $quote->isQatar() || $quote->isLivana() || $quote->isTokio())) {
            LoggerService::info(self::class.' - Not a Valid Provider');

            return false;
        }

        try {
            if ($documentType === OCRDocumentTypeEnum::TAX_INVOICE) {
                $this->fillTaxInvoice($quote, $data);
            }

            if ($documentType === OCRDocumentTypeEnum::TAX_INVOICE_RAISED_BY_BUYER) {
                $this->fillTaxInvoiceRaisedByBuyer($quote, $data);
            }

            if ($documentType === OCRDocumentTypeEnum::CERTIFICATE_OF_ISSUANCE) {
                $this->fillCertificateOfIssuance($quote, $data);
            }

            if ($documentType === OCRDocumentTypeEnum::MOTOR_INSURANCE_POLICY_SCHEDULE) {
                $this->fillMotorInsurancePolicySchedule($quote, $data);
            }

            return true;
        } catch (Exception $e) {
            LoggerService::error(self::class.' - Exception occurred during data fill', exception: $e);

            return false;
        }
    }
}
