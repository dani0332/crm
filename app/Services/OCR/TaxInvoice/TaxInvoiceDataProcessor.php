<?php

declare(strict_types=1);

namespace App\Services\OCR\TaxInvoice;

use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrUtils;
use App\Services\OCR\OcrValidator;
use Exception;
use Illuminate\Database\Eloquent\Model;

class TaxInvoiceDataProcessor
{
    use OcrUtils, OcrValidator;

    private array $ocr_values = [];
    private array $fields_updated = [
        'quote' => [],
        'payment' => [],
    ];
    private bool $isSendUpdateEligibleForOCR = false;
    private string $providerCode = '';

    public function __construct(
        private Model $quote,
        private object $data,
        bool $isSendUpdateEligibleForOCR = false,
        string $providerCode = '',
    ) {
        $this->isSendUpdateEligibleForOCR = $isSendUpdateEligibleForOCR;
        $this->providerCode = $providerCode;
    }

    public function processTaxInvoiceData(): bool
    {
        try {
            LoggerService::info(self::class.': OCR fillTaxInvoice started');
            LoggerService::info(self::class.': OCR Tax Invoice data received', extra: ['ocr_data' => json_decode(json_encode($this->data), true)]);

            if ($this->isSendUpdateEligibleForOCR) {
                $this->handleSendUpdate();
            } else {
                $this->handleRegularQuote();
            }

            LoggerService::info(self::class.': OCR fillTaxInvoice completed', extra: [
                'ocr_values' => $this->ocr_values,
                'fields_updated' => $this->fields_updated,
                'is_send_update_eligible' => $this->isSendUpdateEligibleForOCR,
            ]);

            return true;

        } catch (Exception $e) {
            LoggerService::error(self::class.': OCR fillTaxInvoice failed', exception: $e);

            return false;
        }
    }

    private function handleSendUpdate()
    {
        $dataToUpdate = [];
        $price = $this->resolveProp($this->data, 'price');

        if ($this->isFieldEnabled($this->providerCode, 'quote.price_with_vat') &&
            $this->isFieldEnabled($this->providerCode, 'quote.price_vat_applicable')) {

            $priceVatApplicable = $this->resolveProp($price, 'baseAmount') ?? $this->quote->price_vat_applicable;
            $priceWithVat = $this->resolveProp($price, 'totalAmount') ?? $this->quote->price_with_vat;

            $dataToUpdate['price_with_vat'] = $priceWithVat;
            $dataToUpdate['price_vat_applicable'] = $priceVatApplicable;

            // Store original and new values for logging
            $this->ocr_values['price_with_vat'] = [
                'ocr_value' => $priceWithVat,
                'previous_value' => $this->quote->price_with_vat,
                'final_value' => $priceWithVat,
            ];

            $this->ocr_values['price_vat_applicable'] = [
                'ocr_value' => $priceVatApplicable,
                'previous_value' => $this->quote->price_vat_applicable,
                'final_value' => $priceVatApplicable,
            ];
        }

        if ($this->isFieldEnabled($this->providerCode, 'quote.policy_issuance_date')) {
            $dataToUpdate['price_with_vat'] = $this->resolveProp($price, 'totalAmount') ?? $this->quote->price_with_vat;
            $policyIssuanceDate = $this->parseDate($this->resolveProp($this->data, 'issuanceDate'), $this->quote->policy_issuance_date);
            $dataToUpdate['policy_issuance_date'] = $policyIssuanceDate;

            $this->ocr_values['policy_issuance_date'] = [
                'ocr_value' => $this->resolveProp($this->data, 'issuanceDate'),
                'previous_value' => $this->quote->policy_issuance_date,
                'final_value' => $policyIssuanceDate,
            ];
        }

        if (! empty($dataToUpdate)) {
            $this->quote->update($dataToUpdate);
            $this->fields_updated['quote'] = array_keys($dataToUpdate);
            LoggerService::info(self::class.': OCR fillTaxInvoice updated quote fields (send update)', extra: ['fields_updated' => $this->fields_updated['quote']]);
        }

        $this->handlePaymentUpdates();
    }

    private function handleRegularQuote()
    {
        $dataToUpdate = [];
        $price = $this->resolveProp($this->data, 'price');

        if ($this->isFieldEnabled($this->providerCode, 'quote.price_with_vat') &&
            $this->isFieldEnabled($this->providerCode, 'quote.price_vat_applicable')) {

            $priceVatApplicable = $this->resolveProp($price, 'baseAmount') ?? $this->quote->price_vat_applicable;
            $priceWithVat = $this->resolveProp($price, 'totalAmount') ?? $this->quote->price_with_vat;

            $dataToUpdate['price_with_vat'] = $priceWithVat;
            $dataToUpdate['price_vat_applicable'] = $priceVatApplicable;

            // Store original and new values for logging
            $this->ocr_values['price_with_vat'] = [
                'ocr_value' => $priceWithVat,
                'previous_value' => $this->quote->price_with_vat,
                'final_value' => $priceWithVat,
            ];

            $this->ocr_values['price_vat_applicable'] = [
                'ocr_value' => $priceVatApplicable,
                'previous_value' => $this->quote->price_vat_applicable,
                'final_value' => $priceVatApplicable,
            ];

            // Only update 'vat' column for regular quotes, not Send Update logs
            if ($this->isFieldEnabled($this->providerCode, 'quote.vat')) {
                $vatPercentage = app(\App\Services\ApplicationStorageService::class)->getValueByKey(\App\Enums\ApplicationStorageEnums::VAT_VALUE);
                $vatAmount = $priceVatApplicable * $vatPercentage / 100;
                $dataToUpdate['vat'] = $vatAmount;

                $this->ocr_values['vat'] = [
                    'ocr_value' => "Calculated: $priceVatApplicable * $vatPercentage% = $vatAmount",
                    'previous_value' => $this->quote->vat,
                    'final_value' => $vatAmount,
                ];
            }
        }

        if ($this->isFieldEnabled($this->providerCode, 'quote.policy_issuance_date')) {
            $dataToUpdate['price_with_vat'] = $this->resolveProp($price, 'totalAmount') ?? $this->quote->price_with_vat;
            $policyIssuanceDate = $this->parseDate($this->resolveProp($this->data, 'issuanceDate'), $this->quote->policy_issuance_date);
            $dataToUpdate['policy_issuance_date'] = $policyIssuanceDate;

            $this->ocr_values['policy_issuance_date'] = [
                'ocr_value' => $this->resolveProp($this->data, 'issuanceDate'),
                'previous_value' => $this->quote->policy_issuance_date,
                'final_value' => $policyIssuanceDate,
            ];

            $vatAmount = $this->resolveProp($price, 'VAT') ?? $this->quote->vat;
            $dataToUpdate['vat'] = $vatAmount;

            $this->ocr_values['vat'] = [
                'ocr_value' => $this->resolveProp($price, 'VAT'),
                'previous_value' => $this->quote->vat,
                'final_value' => $vatAmount,
            ];
        }

        if (! empty($dataToUpdate)) {
            $this->quote->update($dataToUpdate);
            $this->fields_updated['quote'] = array_keys($dataToUpdate);
            LoggerService::info(self::class.': OCR fillTaxInvoice updated quote fields (regular)', extra: ['fields_updated' => $this->fields_updated['quote']]);
        }

        $this->handlePaymentUpdates();
    }

    private function handlePaymentUpdates()
    {
        $paymentDataToUpdate = [];

        if ($this->isFieldEnabled($this->providerCode, 'payment.insurer_invoice_date')) {
            $invoiceDate = $this->parseDate($this->resolveProp($this->data, 'invoiceDate'), $this->quote->payment?->insurer_invoice_date);
            $paymentDataToUpdate['insurer_invoice_date'] = $invoiceDate;

            $this->ocr_values['insurer_invoice_date'] = [
                'ocr_value' => $this->resolveProp($this->data, 'invoiceDate'),
                'previous_value' => $this->quote->payment?->insurer_invoice_date,
                'final_value' => $invoiceDate,
            ];
        }

        if ($this->isFieldEnabled($this->providerCode, 'payment.insurer_tax_number') && $this->isFieldEnabled($this->providerCode, 'payment.tax_invoice_number')) {
            $taxInvoiceNumber = $this->resolveProp($this->data, 'taxInvoiceNumber');

            $paymentDataToUpdate['insurer_tax_number'] = $taxInvoiceNumber ?? $this->quote->payment?->insurer_tax_number;
            $paymentDataToUpdate['tax_invoice_number'] = $taxInvoiceNumber ?? $this->quote->payment?->tax_invoice_number;

            $this->ocr_values['insurer_tax_number'] = [
                'ocr_value' => $taxInvoiceNumber,
                'previous_value' => $this->quote->payment?->insurer_tax_number,
                'final_value' => $taxInvoiceNumber ?? $this->quote->payment?->insurer_tax_number,
            ];

            $this->ocr_values['tax_invoice_number'] = [
                'ocr_value' => $taxInvoiceNumber,
                'previous_value' => $this->quote->payment?->tax_invoice_number,
                'final_value' => $taxInvoiceNumber ?? $this->quote->payment?->tax_invoice_number,
            ];
        }

        if (! empty($paymentDataToUpdate) && $this->quote->payment) {
            $this->quote->payment->update($paymentDataToUpdate);
            $this->fields_updated['payment'] = array_keys($paymentDataToUpdate);
            LoggerService::info(self::class.': OCR fillTaxInvoice updated payment fields', extra: ['fields_updated' => $this->fields_updated['payment']]);
        }
    }

    public function getProcessingSummary(): array
    {
        try {
            return [
                'status' => 'success',
                'document_type' => 'Tax Invoice',
                'ocr_values' => $this->ocr_values,
                'fields_updated' => $this->fields_updated,
                'is_send_update_eligible' => $this->isSendUpdateEligibleForOCR,
            ];
        } catch (Exception $e) {
            LoggerService::error(self::class.': Failed to get Tax Invoice processing summary', exception: $e);

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve Tax Invoice processing summary',
            ];
        }
    }
}
