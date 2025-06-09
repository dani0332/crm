<?php

namespace App\Strategies\EmbeddedProducts;

use App\Enums\quoteTypeCode;

class RDX extends MDX
{
    protected function getReportRelations()
    {
        // NEED TO CHANGE TO LATEST INSURED
        return [
            'product.embeddedProduct',
            'quoteRequest.customer',
            'quoteRequest.customer.nationality',
            'quoteRequest.customer.customerInsured',
            'quoteRequest.customer.customerInsured.insured',
            'quoteRequest.bikeQuote.bikeMake',
            'quoteRequest.bikeQuote.bikeModel',
            'quoteRequest.quoteStatus',
            'quoteRequest.advisor',
            'quoteRequest.quoteRequestEntityMapping',
        ];
    }

    protected function processReportRecord($quoteObject, $item)
    {
        $item->lob = quoteTypeCode::Bike;
        $make = $quoteObject->bikeQuote->bikeMake->text ?? '';
        $model = $quoteObject->bikeQuote->bikeModel->text ?? '';
        $item->vehicle = $make.' '.$model;

        return $item;
    }
}
