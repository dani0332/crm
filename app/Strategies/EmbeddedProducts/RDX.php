<?php

namespace App\Strategies\EmbeddedProducts;

class RDX extends MDX
{
    protected function getReportRelations()
    {
        return [
            'product.embeddedProduct',
            'quoteRequest.customer',
            'quoteRequest.customer.nationality',
            'quoteRequest.customer.customerInsured',
            'quoteRequest.customer.customerInsured.insured',
            'quoteRequest.quoteStatus',
            'quoteRequest.advisor',
            'quoteRequest.quoteRequestEntityMapping',
            'paymentStatus',
        ];
    }

    protected function postFilterReportProcessing($dataset)
    {
        return $this->loadVehicleRelations($dataset);
    }

    protected function processReportRecord($quoteObject, $item)
    {
        return $this->processCarBikeReportRecord($quoteObject, $item);
    }
}
