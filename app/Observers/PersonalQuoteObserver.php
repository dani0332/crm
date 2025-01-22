<?php

namespace App\Observers;

use App\Models\PersonalQuote;
use App\Observers\Traits\PersonalQuoteObservable;
use App\Traits\GenericQueriesAllLobs;

class PersonalQuoteObserver
{
    use GenericQueriesAllLobs, PersonalQuoteObservable;

    public function updating(PersonalQuote $quote): void
    {
        info("PersonalQuoteObserver - Updating - Ref ID: {$quote->uuid} | Time: ".now());
        if ($quote->isDirty('quote_status_id') && ! $quote->isDirty('quote_status_date')) {
            $quote->quote_status_date = now();
        }
    }

    /**
     * Handle the "updated" event.
     *
     * - Any changes that adds business logic should be enclosed in try-catch block or executed in queue.
     */
    public function updated(PersonalQuote $personalQuote): void
    {
        info("PersonalQuoteObserver - Updated - Ref ID: {$personalQuote->uuid} | Time: ".now());

        if ($personalQuote->isDirty('quote_status_id')) {
            $this->handleQuoteStatusChange($personalQuote);
        }

        if ($personalQuote->isDirty('advisor_id') && ! empty($personalQuote->advisor_id)) {
            $this->handleAdvisorChange($personalQuote);
            $this->handleIntroEmails($personalQuote);
        }
    }

}
