<?php

namespace App\Interfaces;

interface ExportDocumentInterface
{
    public function exportProformaPaymentRequest($quoteType, $quote);
}
