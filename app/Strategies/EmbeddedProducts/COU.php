<?php

namespace App\Strategies\EmbeddedProducts;

use App\Enums\QuoteTypeId;
use Carbon\Carbon;

class COU extends EmbeddedProduct
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
        ];
    }

    protected function postFilterReportProcessing($dataset)
    {
        // Group transactions by quote_type_id
        $carTransactions = $dataset->where('quote_type_id', QuoteTypeId::Car);
        $bikeTransactions = $dataset->where('quote_type_id', QuoteTypeId::Bike);

        // Load car-specific relations in a single query for the car group
        if ($carTransactions->isNotEmpty()) {
            $carTransactions->loadMissing([
                'quoteRequest.carMake',
                'quoteRequest.carModel',
            ]);
        }

        // Load bike-specific relations in a single query for the bike group
        if ($bikeTransactions->isNotEmpty()) {
            $bikeTransactions->loadMissing([
                'quoteRequest.bikeQuote',
                'quoteRequest.bikeQuote.bikeMake',
                'quoteRequest.bikeQuote.bikeModel',
            ]);
        }

        return $dataset;
    }

    protected function processReportRecord($quoteObject, $item)
    {
        $item->lob = QuoteTypeId::getOptions()[$item->quote_type_id] ?? '';

        if ($item->quote_type_id == QuoteTypeId::Car) {
            $carMake = $quoteObject->carMake->text ?? '';
            $carModel = $quoteObject->carModel->text ?? '';
            $item->vehicle = $carMake.' '.$carModel;
        } elseif ($item->quote_type_id == QuoteTypeId::Bike) {
            $make = $quoteObject->bikeQuote->bikeMake->text ?? '';
            $model = $quoteObject->bikeQuote->bikeModel->text ?? '';
            $item->vehicle = $make.' '.$model;
        } else {
            $item->vehicle = 'N/A';
        }

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
