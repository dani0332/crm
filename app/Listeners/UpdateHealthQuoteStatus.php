<?php

namespace App\Listeners;

use App\Enums\QuoteStatusEnum;
use App\Events\HealthQuoteUpdated;
use App\Models\HealthQuote;
class UpdateHealthQuoteStatus
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\HealthQuoteUpdated  $event
     * @return void
     */
    public function handle(HealthQuoteUpdated $event)
    {
        if($event->healthQuote) {
            $healthQuoteObject = $event->healthQuote;
            if($healthQuoteObject->health_team_type && $healthQuoteObject->quote_status_id != QuoteStatusEnum::Qualified && $healthQuoteObject->is_ecommerce == 1) {
                HealthQuote::find($healthQuoteObject->id)->update(['quote_status_id' => QuoteStatusEnum::Qualified]);
            }
        }
    }
}
