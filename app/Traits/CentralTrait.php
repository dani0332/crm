<?php

namespace App\Traits;

use App\Enums\QuoteTypes;

trait CentralTrait
{
    use GenericQueriesAllLobs;

    public function getEcomQuoteLink(QuoteTypes $quoteType, string $uuid, ?object $plan = null): string
    {
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
                // if plan is not provided, then send ecom quote link
                $link = $afiaWebDomain.'/bike-insurance/quote/'.$uuid;
                if ($plan) {
                    // if plan is provided, then send buy now link
                    $link = $link.'/payment/?planId='.$plan->id.'&providerCode='.$plan->providerCode;
                }

                return $link;
        }
    }
}
