<?php

namespace App\Observers;

use App\Models\PersonalQuote;
use App\Observers\Traits\Observable;
use App\Observers\Traits\PersonalQuoteObservable;
use App\Traits\GenericQueriesAllLobs;

class PersonalQuoteObserver
{
    use GenericQueriesAllLobs, Observable, PersonalQuoteObservable;

    public function updating(PersonalQuote $quote): void
    {
        info("PersonalQuoteObserver - Updating - Ref ID: {$quote->uuid}");

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
        $this->printChangeLog($personalQuote);

        if ($personalQuote->isDirty('quote_status_id')) {
            $this->handleQuoteStatusChange($personalQuote);
        }

        if ($personalQuote->isDirty('advisor_id') && ! empty($personalQuote->advisor_id)) {
            $this->handleAdvisorChange($personalQuote);
            $this->handleIntroEmails($personalQuote);
        }
    }

}
