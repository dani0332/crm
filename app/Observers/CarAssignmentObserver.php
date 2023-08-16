<?php

namespace App\Observers;

use App\Events\AdvisorAssigned;
use App\Models\CarQuote;
use Illuminate\Support\Facades\Event;

class CarAssignmentObserver
{
    public function updated(CarQuote $lead)
    {
        $changes = [];

        foreach ($lead->getDirty() as $attribute => $value) {
            if ($lead->isDirty($attribute)) {
                $changes[] = $attribute;
            }
        }
        if ($lead->isDirty('advisor_id')) {
            Event::dispatch(new AdvisorAssigned($lead));
        }
    }
}
