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
        $basePath = '';
        switch ($quoteType->id()) {
            case QuoteTypes::BIKE->id():
                $basePath = '/bike-insurance/quote/';
                break;
            case QuoteTypes::HOME->id():
                $basePath = '/home-insurance/quote/';
                break;
            case QuoteTypes::CYBER->id():
                $basePath = '/cyber-insurance/quote/';
                break;
            case QuoteTypes::DEVICE->id():
                $basePath = '/smartphone-insurance/quote/';
                break;
            default:
                return '';
        }
        // Base link with trailing slash
        $link = rtrim($afiaWebDomain, '/').$basePath.$uuid.'/';

        // Append plan parameters if the plan is provided
        if ($plan) {
            $link .= 'payment/?planId='.$plan->id.'&providerCode='.$plan->providerCode;
        }

        return $link;
    }
}
