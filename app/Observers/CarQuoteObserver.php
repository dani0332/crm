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

        // $emailData = $this->buildEmailDataForLMSIntroEmail($userId, $carQuote);

        // $this->sendIntroEmailForLeadAssignment($carQuote->code, $emailData);

        if ($lead->isDirty('advisor_id')) {

                $oldAdvisorId = $changes['advisor_id'];
                $oldAssignmentType = $changes['assignment_type'];

            event(new CarQuoteAdvisorUpdated($lead, $oldAdvisorId, $oldAssignmentType));
        }
    }
}
