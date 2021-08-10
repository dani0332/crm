<?php
namespace App\Services;

use App\Models\AML;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Enums\quoteTypeCode;
use Config;
use Mail;
use App\Models\QuoteType;
use App\Models\QuoteStatus;
use App\Models\CarQuote;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\BusinessQuote;
use App\Models\BikeQuote;
use App\Models\YachtQuote;
use App\Models\TravelQuote;

class QuoteStatusService
{
    public function updateQuoteStatus($quoteTypeId,$quoteRequestId,$quoteStatusType)
    {
        $quoteType = QuoteType::where('id', '=', $quoteTypeId)->get(array('code'));
        $quoteTypeCode = $quoteType[0]->code;

        $quoteStatus = QuoteStatus::where('code', '=', $quoteStatusType)->get(array('id','text'));
        $quoteStatusId = $quoteStatus[0]->id;
        $quoteStatusText = $quoteStatus[0]->text;

        if($quoteTypeCode && $quoteTypeCode != "") {

            if($quoteTypeCode == quoteTypeCode::Car) { $updateQuote = CarQuote::find($quoteRequestId); }
            if($quoteTypeCode == quoteTypeCode::Home) { $updateQuote = HomeQuote::find($quoteRequestId); }
            if($quoteTypeCode == quoteTypeCode::Health) { $updateQuote = HealthQuote::find($quoteRequestId); }
            if($quoteTypeCode == quoteTypeCode::Life) { $updateQuote = LifeQuote::find($quoteRequestId); }
            if($quoteTypeCode == quoteTypeCode::Business) { $updateQuote = BusinessQuote::find($quoteRequestId); }
            if($quoteTypeCode == quoteTypeCode::Bike) { $updateQuote = BikeQuote::find($quoteRequestId); }
            if($quoteTypeCode == quoteTypeCode::Yacht) { $updateQuote = YachtQuote::find($quoteRequestId); }
            if($quoteTypeCode == quoteTypeCode::Travel) { $updateQuote = TravelQuote::find($quoteRequestId); }

            $updateQuote->quote_status_id = $quoteStatusId;
            if($updateQuote->save()) {
                return $quoteStatusText;
            }
            else {
                return "false";
            }
        }
        else {
            return redirect()->back()->with('message', 'Quote Type not exist!');
        }
    }
}