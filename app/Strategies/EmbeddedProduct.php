<?php

namespace App\Strategies;

use App\Traits\GenericQueriesAllLobs;

abstract class EmbeddedProduct
{
    use GenericQueriesAllLobs;

    abstract public function getPDFData($quoteObject, $certificate_number, $premium);

    abstract public function getTransactionData($dataset);
}
