<?php

namespace App\Observers;

use App\Events\CarQuoteAdvisorUpdated;
use App\Models\CarQuote;
use App\Traits\PersonalQuoteSyncTrait;

class CarQuoteObserver
{
    use PersonalQuoteSyncTrait;

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

        if ($lead->isDirty('advisor_id')) {
            $oldAdvisorId = $changes['advisor_id']['old'];
            event(new CarQuoteAdvisorUpdated($lead, $oldAdvisorId));
        }

        $this->syncQuote($lead, $lead->getDirty());
    }
}
