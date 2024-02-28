<?php

namespace App\Strategies;

use App\Traits\GenericQueriesAllLobs;

abstract class EmbeddedProduct
{
    use GenericQueriesAllLobs;

    abstract function getPDFData($quoteObject, $certificate_number, $premium);

    abstract function getTransactionData($dataset);
}
