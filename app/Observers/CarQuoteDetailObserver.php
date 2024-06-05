<?php

namespace App\Observers;

use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Traits\PersonalQuoteSyncTrait;

class CarQuoteDetailObserver
{
    use PersonalQuoteSyncTrait;

    public function updated(CarQuoteRequestDetail $leadDetail)
    {
        $lead = CarQuote::find($leadDetail->car_quote_request_id);
        $this->syncQuote($lead, $leadDetail->getDirty());
    }
}
