<?php

namespace App\Interfaces;

interface ExportDocumentInterface
{
    public function createProformaPaymentRequestPdf($quoteType, $quote, $request);
}
