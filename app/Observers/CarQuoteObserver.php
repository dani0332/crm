<?php

namespace App\Observers;

use App\Events\CarQuoteAdvisorUpdated;
use App\Models\CarQuote;

class CarQuoteObserver
{
    public function updated(CarQuote $lead)
    {
        if ($lead->isDirty('advisor_id')) {
            event(new CarQuoteAdvisorUpdated($lead));
        }
    }
}
