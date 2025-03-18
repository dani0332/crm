<?php

namespace App\Services\OCR;

use App\Enums\QuoteTypes;
use App\Models\DocumentType;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;

trait OcrFillable
{
    private function fillTaxInvoice(Model $quote, object $data)
    {
        $quote->update([
            'price_vat_applicable' => $data->invoiceAmount?->amount ?? $quote->price_vat_applicable,
            'vat' => $data->invoiceVAT?->amount ?? $quote->vat,
            'price_with_vat' => $data->invoiceTotal?->amount ?? $quote->price_with_vat,
        ]);

        $quote->payment?->update([
            'insurer_invoice_date' => $data->invoiceDate ? Carbon::parse($data->invoiceDate)->toDateTimeString() : $quote->payment?->insurer_invoice_date,
            'insurer_tax_number' => $data->invoiceNumber ?? $quote->payment?->insurer_tax_number,
        ]);
    }

    private function fill(
        QuoteTypes|string $quoteType,
        Model $quote,
        DocumentType $documentType,
        object $data
    ) {
        try {
            if ($documentType->code === 'TI') {
                $this->fillTaxInvoice($quote, $data);
            }

            return true;
        } catch (Exception $e) {
            info(self::class." - Exception occurred during data fill: {$e->getMessage()}");

            return false;
        }
    }
}
