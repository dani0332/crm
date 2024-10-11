<?php

namespace App\Traits;

use App\Enums\QuoteTypes;

trait CentralTrait
{
    use GenericQueriesAllLobs;

    public function getBuyNowLinkForQuote(QuoteTypes $quoteType, string $uuid, object $plan): string
    {
        info('Generating Buy Now link for quote.', [
            'quoteType' => $quoteType,
            'uuid' => $uuid,
        ]);
        if (! $quoteType || ! $uuid) {
            info('Failed to generate Buy Now link for quote.', [
                'quoteType' => $quoteType,
                'uuid' => $uuid,
            ]);

            return '';
        }
        $afiaWebDomain = config('constants.AFIA_WEBSITE_DOMAIN');
        switch ($quoteType->id()) {
            case QuoteTypes::BIKE->id():
                $buyNowLink = $afiaWebDomain.'/bike-insurance/quote/'.$uuid.'/payment/?planId='.$plan->id.'&providerCode='.$plan->providerCode;
                info('Generated Buy Now link for Bike quote: '.$buyNowLink);

                return $buyNowLink;
        }
    }

    public function getEcomQuoteLink(QuoteTypes $quoteType): string
    {
        info('Generating Ecom Quote link for quote.', [
            'quoteType' => $quoteType,
        ]);
        if (! $quoteType) {
            info('Failed to generate Ecom Quote link for quote.', [
                'quoteType' => $quoteType,
            ]);

            return '';
        }
        $afiaWebDomain = config('constants.AFIA_WEBSITE_DOMAIN');
        switch ($quoteType->id()) {
            case QuoteTypes::BIKE->id():
                $ecomQuoteLink = $afiaWebDomain.'/bike-insurance/quote/';
                info('Generated Ecom link for Bike quote: '.$ecomQuoteLink);

                return $ecomQuoteLink;
        }
    }
}
