<?php

namespace App\Repositories;

use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Facades\Capi;
use App\Models\PersonalQuote;
use App\Models\QuoteDocument;
use App\Models\QuoteStatusLog;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class PersonalQuoteRepository extends BaseRepository
{
    public function model() {
        return PersonalQuote::class;
    }

    /**
     * @param $quoteId
     * @param $data
     * @return mixed
     */
    public function fetchUpdateStatus($quoteType, $quoteId, $data)
    {
       return DB::transaction(function() use($quoteType, $quoteId, $data)
       {
           $quote = $this->where('id', $quoteId)->firstOrFail();

           $previousStatusId = $quote->quote_status_id;

           $quoteData['quote_status_id'] = $data['quote_status_id'];

           if(!empty($data['notes'])) {
               $quoteData['notes'] = $data['notes'];
           }

           $quote->update($quoteData);

           QuoteStatusLog::create([
               'quote_type_id' => $quote->quote_type_id,
               'quote_request_id' => $quote->id,
               'current_quote_status_id' => $quote->quote_status_id,
               'previous_quote_status_id' => $previousStatusId,
               'created_at' => Carbon::now(),
               'updated_at' => Carbon::now(),
           ]);

           return $quote;
       });
    }
}
