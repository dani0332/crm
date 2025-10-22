<?php

namespace App\Strategies\EmbeddedProducts;

use App\Enums\quoteTypeCode;
use Carbon\Carbon;

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

        $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
        $item->advisor_name = $quoteObject?->advisor?->name ?? '';
        $item->dob = isset($quoteObject?->dob) ? Carbon::parse($quoteObject?->dob)->format($dateFormat) : '';
        $item->nationality = $quoteObject?->customer?->nationality?->text ?? '';
        $item->policy_issuance_date = $quoteObject?->policy_issuance_date ?? '';
        $item->age = isset($quoteObject?->dob) ?
            floor(Carbon::parse($quoteObject?->dob)->diffInYears(Carbon::now())) . ' Years'
            : '';

        return $item;
    }
}
