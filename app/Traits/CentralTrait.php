<?php

namespace App\Traits;

use App\Enums\QuoteTypes;

trait CentralTrait
{
    use GenericQueriesAllLobs;

    public function getBuyNowLinkForQuote(QuoteTypes $quoteType, string $uuid, object $plan = null): string
    {
        info('Generating Buy Now link for quote.', [
            'quoteType' => $quoteType,
            'uuid' => $uuid,
        ]);
        if (! $quoteType || ! $uuid) {
            info('Failed to generate Buy Now/Ecom Quote link for quote.', [
                'quoteType' => $quoteType,
                'uuid' => $uuid,
            ]);

            return '';
        }
        $afiaWebDomain = config('constants.AFIA_WEBSITE_DOMAIN');
        switch ($quoteType->id()) {
            case QuoteTypes::BIKE->id():
                if (! $plan) {
                    // if plan is not provided, then send ecom quote link
                    $link = $afiaWebDomain . '/bike-insurance/quote/';
                    info('Generated Ecom link for Bike quote: ' . $link);
                } else {
                    // if plan is provided, then send buy now link
                    $link = $afiaWebDomain . '/bike-insurance/quote/' . $uuid . '/payment/?planId=' . $plan->id . '&providerCode=' . $plan->providerCode;
                    info('Generated Buy Now link for Bike quote: ' . $link);
                }

                return $link;
        }
    }
}
