<?php

namespace App\Services\OCR;

use App\Enums\OCRDocumentTypeEnum;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;

trait OcrFillable
{
    private function resolveProp($object, $prop)
    {
        if (property_exists($object, $prop)) {
            return $object->$prop;
        }

        return null;
    }

    private function fillTaxInvoice(Model $quote, object $data)
    {
        $quote->update([
            'price_vat_applicable' => $data->price?->baseAmount ?? $quote->price_vat_applicable,
            'vat' => $data->price?->VAT ?? $quote->vat,
            'price_with_vat' => $data->price?->totalAmount ?? $quote->price_with_vat,
            'policy_issuance_date' => $data->issuanceDate ? Carbon::parse($data->issuanceDate)->toDateTimeString() : $quote->policy_issuance_date,
        ]);

        $quote->payment?->update([
            // 'insurer_invoice_date' => $data->invoiceDate ? Carbon::parse($data->invoiceDate)->toDateTimeString() : $quote->payment?->insurer_invoice_date,
            'insurer_tax_number' => $data->taxInvoiceNumber ?? $quote->payment?->insurer_tax_number,
        ]);
    }

    private function fillTaxInvoiceRaisedByBuyer(Model $quote, object $data)
    {
        $quote->payment?->update([
            'commission_vat' => $data?->commission?->VAT ?? $quote->payment?->comission_vat,
            'commission' => $data?->commission?->baseAmount ?? $quote->payment?->comission,
            'insurer_commmission_invoice_number' => $data->taxInvoiceNumber ?? $quote->payment?->insurer_commmission_invoice_number,
            'commission_vat_applicable' => $data?->commission?->totalAmount ?? $quote->payment?->comission_vat_applicable,
        ]);
    }

    private function fillCertificateOfIssuance(Model $quote, object $data)
    {
        $quote->update([
            'policy_number' => $data->policyNumber ?? $quote->policy_number,
            'policy_start_date' => $data->policyStartDate ? Carbon::parse($data->policyStartDate)->toDateTimeString() : $quote->policy_start_date,
            'policy_expiry_date' => $data->policyExpiryDate ? Carbon::parse($data->policyExpiryDate)->toDateTimeString() : $quote->policy_expiry_date,
        ]);
    }

    private function fillMotorInsurancePolicySchedule(Model $quote, object $data)
    {
        $quote->update([
            'insurer_quote_number' => $data->insurerQuoteNumber ?? $quote->insurer_quote_number,
        ]);
    }

    private function fill(
        Model $quote,
        OCRDocumentTypeEnum $documentType,
        object $data
    ) {
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
            info(self::class." - Exception occurred during data fill: {$e->getMessage()}");

            return false;
        }
    }
}
