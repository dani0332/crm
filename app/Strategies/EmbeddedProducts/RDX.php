<?php

namespace App\Strategies\EmbeddedProducts;

use App\Enums\quoteTypeCode;
use Carbon\Carbon;
use App\Enums\QuoteTypeId;

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
        $item->lob = QuoteTypeId::getOptions()[$item->quote_type_id] ?? '';
        if ($item->quote_type_id == QuoteTypeId::Car) {
            $carMake = $quoteObject->carMake->text ?? '';
            $carModel = $quoteObject->carModel->text ?? '';
            $item->vehicle = $carMake . ' ' . $carModel;
        } elseif ($item->quote_type_id == QuoteTypeId::Bike) {
            $make = $quoteObject->bikeQuote->bikeMake->text ?? '';
            $model = $quoteObject->bikeQuote->bikeModel->text ?? '';
            $item->vehicle = $make . ' ' . $model;
        } else {
            $item->vehicle = 'N/A';
        }

        $dateFormat = config('constants.DATE_DISPLAY_FORMAT');
        $item->advisor_name = $quoteObject?->advisor?->name ?? '';
        $item->dob = isset($quoteObject?->dob) ? Carbon::parse($quoteObject?->dob)->format($dateFormat) : '';
        $item->nationality = $quoteObject?->customer?->nationality?->text ?? '';
        $item->policy_issuance_date = $quoteObject?->policy_issuance_date ?? '';
        $item->age = isset($quoteObject?->dob) ?
            floor(Carbon::parse($quoteObject?->dob)->diffInYears(Carbon::now())).' Years'
            : '';

        return $item;
    }
}
