<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CarQuote;
use App\Jobs\FTCMailServiceJob;
use \Carbon\Carbon;
use App\Models\FTCHistory;
use App\Models\CarQuoteEmailUniqueLink;

class FTCAcKEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:FTCAckEmail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $getAckEmailWhichProcessedArr = $carQuoteUniqueEmail = CarQuoteEmailUniqueLink::where([['is_ack_email', '=', 0],['status', '=', 'Processed']])->select('id','car_quote_id','is_ack_email')->skip(0)->take(10)->get();
        foreach ($getAckEmailWhichProcessedArr as &$value) {
           \Log::info("Send Ack Email to CarQuote:".$value->car_quote_id);
            $row = CarQuote::with(["payment_detail","quote_status_id", "kyc_status_id", "insurance_coverage.insurance_company_id", "insurance_coverage.insurance_plan_id", "insurance_coverage.vehicle_type_id", "uae_license_held_for_id", "car_make_id", "car_model_id", "emirate_of_registration_id", "claim_history_id",  "nationality_id", "vehicle_detail_id", "pa_id", "car_quote_kyc"])->where("id",$value->car_quote_id)->first();
            $getQuote = new FTCHistory;
            $getQuote->sendFtcEmail($row,"motor_ack_email"); 

            $carQuoteUniqueLink = CarQuoteEmailUniqueLink::find($value->id);
            $carQuoteUniqueLink->is_ack_email = 1;
            $carQuoteUniqueLink->save();
        }
    }
}
