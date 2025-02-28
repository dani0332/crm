<?php

namespace App\Observers;

use App\Models\CarQuote;
use App\Models\CarQuoteRequestDetail;
use App\Traits\PersonalQuoteSyncTrait;
use Exception;
use Illuminate\Support\Facades\DB;

class CarQuoteDetailObserver
{
    use PersonalQuoteSyncTrait;

    public function updating(CarQuoteRequestDetail $model)
    {
        DB::transaction(function () use ($model) {
            $existing = CarQuoteRequestDetail::where('car_quote_request_id', $model->car_quote_request_id)
                ->lockForUpdate()
                ->get();

            if ($existing->count() > 1) {
                throw new Exception('Duplicate entry on update.');
            }
        });
    }

    public function updated(CarQuoteRequestDetail $leadDetail)
    {
        $lead = CarQuote::find($leadDetail->car_quote_request_id);
        $this->syncQuote($lead, $leadDetail->getDirty());
    }
}
