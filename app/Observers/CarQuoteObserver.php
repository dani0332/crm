<?php

namespace App\Observers;

use App\Events\CarQuoteAdvisorUpdated;
use App\Models\CarQuote;

class CarQuoteObserver
{
    public function updated(CarQuote $lead)
    {
        $changes = [];

        foreach ($lead->getDirty() as $attribute => $value) {
            if ($lead->isDirty($attribute)) {
                $changes[$attribute] = [
                    'old' => $lead->getOriginal($attribute),
                    'new' => $value,
                ];
            }
        }
        info('following properties were changes. '.json_encode($changes));
        if ($lead->isDirty('advisor_id') || $lead->isDirty('quote_status_id')) {
            event(new CarQuoteAdvisorUpdated($lead));
        }
    }
}
