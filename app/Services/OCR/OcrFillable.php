<?php

namespace App\Services\OCR;

use App\Enums\QuoteTypes;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Model;

trait OcrFillable
{
    private function getDocumentTypeCode(DocumentType $documentType): ?string
    {
        return match ($documentType->code) {
            'DL_CAR' => 'DL',
            'TI' => 'TI',
            default => null,
        };
    }

    private function fillTaxInvoice(Model $quote, object $data)
    {
        dd('Tax Invoice', $quote->payment, $data, $data->invoiceNumber);
    }

    private function fillDrivingLicense(Model $quote, object $data)
    {
        dd('Driving License', $data);
    }

    private function fill(
        QuoteTypes|string $quoteType,
        Model $quote,
        DocumentType $documentType,
        object $data
    ) {
        if ($documentType->code === 'TI') {
            $this->fillTaxInvoice($quote, $data);
        }

        if ($documentType->code === 'DL_CAR') {
            $this->fillDrivingLicense($quote, $data);
        }
    }
}
