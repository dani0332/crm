<?php

declare(strict_types=1);

namespace App\Services\OCR\TaxInvoiceRaisedByBuyer;

use App\Services\Logger\LoggerService;
use App\Services\OCR\OcrUtils;
use App\Services\OCR\OcrValidator;
use App\Services\SplitPaymentService;
use Exception;
use Illuminate\Database\Eloquent\Model;

class TaxInvoiceRaisedByBuyerDataProcessor
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

    public function processTaxInvoiceRaisedByBuyerData(): bool
    {
        try {
            LoggerService::info(self::class . ': OCR fillTaxInvoiceRaisedByBuyer started');
            LoggerService::info(self::class . ': OCR Tax Invoice Raised By Buyer data received', extra: ['ocr_data' => json_decode(json_encode($this->data), true)]);

            if ($this->isSendUpdateEligibleForOCR) {
                $this->handleSendUpdate();
            } else {
                $this->handleRegularQuote();
            }
            
            LoggerService::info(self::class . ': OCR fillTaxInvoiceRaisedByBuyer completed', extra: [
                'ocr_values' => $this->ocr_values,
                'fields_updated' => $this->fields_updated,
                'is_send_update_eligible' => $this->isSendUpdateEligibleForOCR,
            ]);

            return true;

        } catch (Exception $e) {
            LoggerService::error(self::class . ': OCR fillTaxInvoiceRaisedByBuyer failed', exception: $e);
            return false;
        }
    }
    
    private function handleSendUpdate()
    {
        $dataToUpdate = [];
        $commission = $this->resolveProp($this->data, 'commission');
        
        if ($this->isFieldEnabled($this->providerCode, 'quote.insurer_commission_invoice_number')) {
            $invoiceNumber = $this->resolveProp($this->data, 'taxInvoiceNumber') ?? $this->quote->insurer_commission_invoice_number;
            $dataToUpdate['insurer_commission_invoice_number'] = $invoiceNumber;
            
            $this->ocr_values['insurer_commission_invoice_number'] = [
                'ocr_value' => $this->resolveProp($this->data, 'taxInvoiceNumber'),
                'previous_value' => $this->quote->insurer_commission_invoice_number,
                'final_value' => $invoiceNumber,
            ];
        }

        if ($this->isFieldEnabled($this->providerCode, 'quote.commission_vat_applicable')) {
            $commissionVatApplicable = $this->resolveProp($commission, 'baseAmount') ?? $this->quote->commission_vat_applicable;
            $dataToUpdate['commission_vat_applicable'] = $commissionVatApplicable;
            
            $this->ocr_values['commission_vat_applicable'] = [
                'ocr_value' => $this->resolveProp($commission, 'baseAmount'),
                'previous_value' => $this->quote->commission_vat_applicable,
                'final_value' => $commissionVatApplicable,
            ];
        }

        if (!empty($dataToUpdate)) {
            $this->quote->update($dataToUpdate);
            $this->fields_updated['quote'] = array_keys($dataToUpdate);
            LoggerService::info(self::class . ': OCR fillTaxInvoiceRaisedByBuyer updated quote fields (send update)', extra: ['fields_updated' => $this->fields_updated['quote']]);
        }
    }
    
    private function handleRegularQuote()
    {
        $paymentDataToUpdate = [];
        $commission = $this->resolveProp($this->data, 'commission');
        
        if ($this->isFieldEnabled($this->providerCode, 'quote.commmission_percentage') && $commission) {
            $commissionVat = $this->resolveProp($commission, 'VAT') ?? ($this->quote->payment?->comission_vat ?: 0);
            $commissionTotal = $this->resolveProp($commission, 'totalAmount') ?? $this->quote->payment?->comission;
            
            if ($commissionTotal) {
                $commissionPercentageDivisor = 1 + ($commissionVat > 0 ? .05 : 0);
                $commissionWithoutVat = $commissionTotal - $commissionVat;
                $premiumWithoutVat = $this->quote->payment->total_price / $commissionPercentageDivisor;
                $commissionPercentage = roundNumber((($commissionWithoutVat / $premiumWithoutVat) * 100)) ?? $this->quote->payment?->comission_percentage;
                
                $paymentDataToUpdate['commission_vat'] = $commissionVat;
                $paymentDataToUpdate['commission'] = $commissionTotal;
                $paymentDataToUpdate['commmission_percentage'] = $commissionPercentage;
                
                $this->ocr_values['commission_vat'] = [
                    'ocr_value' => $commissionVat,
                    'previous_value' => $this->quote->payment?->comission_vat,
                    'final_value' => $commissionVat,
                ];
                
                $this->ocr_values['commission'] = [
                    'ocr_value' => $commissionTotal,
                    'previous_value' => $this->quote->payment?->comission,
                    'final_value' => $commissionTotal,
                ];
                
                $this->ocr_values['commmission_percentage'] = [
                    'ocr_value' => "Calculated: (($commissionWithoutVat / $premiumWithoutVat) * 100)",
                    'previous_value' => $this->quote->payment?->comission_percentage,
                    'final_value' => $commissionPercentage,
                ];
            }
        }

        if ($this->isFieldEnabled($this->providerCode, 'quote.insurer_commmission_invoice_number')) {
            $invoiceNumber = $this->resolveProp($this->data, 'taxInvoiceNumber') ?? $this->quote->payment?->insurer_commmission_invoice_number;
            $paymentDataToUpdate['insurer_commmission_invoice_number'] = $invoiceNumber;
            
            $this->ocr_values['insurer_commmission_invoice_number'] = [
                'ocr_value' => $this->resolveProp($this->data, 'taxInvoiceNumber'),
                'previous_value' => $this->quote->payment?->insurer_commmission_invoice_number,
                'final_value' => $invoiceNumber,
            ];
        }

        if ($this->isFieldEnabled($this->providerCode, 'quote.commission_vat_applicable') && $commission) {
            $commissionVatApplicable = $this->resolveProp($commission, 'baseAmount') ?? $this->quote->payment?->commission_vat_applicable;
            $paymentDataToUpdate['commission_vat_applicable'] = $commissionVatApplicable;
            
            $this->ocr_values['commission_vat_applicable'] = [
                'ocr_value' => $this->resolveProp($commission, 'baseAmount'),
                'previous_value' => $this->quote->payment?->commission_vat_applicable,
                'final_value' => $commissionVatApplicable,
            ];
        }

        if (!empty($paymentDataToUpdate) && $this->quote->payment) {
            $this->quote->payment->update($paymentDataToUpdate);
            $this->fields_updated['payment'] = array_keys($paymentDataToUpdate);
            LoggerService::info(self::class . ': OCR fillTaxInvoiceRaisedByBuyer updated payment fields', extra: ['fields_updated' => $this->fields_updated['payment']]);
            
            LoggerService::info(self::class . ': OCR fillTaxInvoiceRaisedByBuyer updating commission schedule');
            (new SplitPaymentService)->updateCommissionSchedule($this->quote->payment);
        }
    }

    public function getProcessingSummary(): array
    {
        try {
            return [
                'status' => 'success',
                'document_type' => 'Tax Invoice Raised By Buyer',
                'ocr_values' => $this->ocr_values,
                'fields_updated' => $this->fields_updated,
                'is_send_update_eligible' => $this->isSendUpdateEligibleForOCR,
            ];
        } catch (Exception $e) {
            LoggerService::error(self::class . ': Failed to get Tax Invoice Raised By Buyer processing summary', exception: $e);

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve Tax Invoice Raised By Buyer processing summary',
            ];
        }
    }
}
