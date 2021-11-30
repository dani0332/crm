<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Models\CarQuote;
use App\Models\FtcDocument;
use App\Models\QuoteStatus;
use App\Models\CarQuoteEmailUniqueLink;
use Auth;
use App\Jobs\FTCMailServiceJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use LookUpModel;
use \Carbon\Carbon;

class FTCHistory extends BaseModel
{
    use HasFactory;
    protected $table = 'ftc_history';
    public $access = [

        'write' => ['advisor','oe'],
        'update' => ['advisor','oe'],
        'delete' => ['advisor','oe'],
        'access' => [
            "pa" => [],
            "production_approval_manager" => [],
            "invoicing" => [ ],
            "advisor" => ['car_quote_id', 'status', 'data'],
            "oe" => ['car_quote_id', 'status', 'data'],
            "admin" => [ 'car_quote_id', 'status', 'data'],
        ],
        "list" => [
            "pa" => ['id' , 'status' , 'data', 'created_at' ],
            "production_approval_manager" => ['id' , 'status' , 'data', 'created_at' ],
            "invoicing" => ['id' , 'status' , 'data', 'created_at' ],
            "advisor" => [ 'id' , 'status', 'data' , 'created_at'],
            "oe" => [ 'id' , 'status', 'data' , 'created_at'],
            "admin" => ['id' , 'status' , 'data' , 'created_at']
        ]
    ];

    public function relations() {
        return [];
    }

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, false);
    }

    private function prefixAED($value){

        $sumInsured = "";
        if(isset($value)) {
            $sumInsured = $value;
            if(is_numeric($value))
                $sumInsured = 'AED '.$value;
        }
        return $sumInsured;
    }

    public function sendFtcEmail($row, $template){

        $templateParams = collect($row)->toArray();
        $templateParams["insurance_coverage"]["sum_insured"] = $this->prefixAED($templateParams["insurance_coverage"]["sum_insured"]);
        $templateParams["insurance_coverage"]["excess"] = $this->prefixAED($templateParams["insurance_coverage"]["excess"]);
        $templateParams["insurance_coverage"]["premium_price"] = $this->prefixAED($templateParams["insurance_coverage"]["premium_price"]);
        $templateParams["insurance_coverage"]["ancillary_excess"] = $this->prefixAED($templateParams["insurance_coverage"]["ancillary_excess"]);

        $params = [
            'to' => $row->email,
            'subject' => ucwords($row->first_name).' '.ucwords($row->last_name). '`s Car Insurance | InsuranceMarket.ae',
            'templateName' => $template,
            'templateParams' => $templateParams
        ];

        $attachment = FtcDocument::where(['car_quote_id' => $row->id, 'document' => 9])->get();
        if(sizeof($attachment) > 0){
            $params['templateParams']['attachment'] = [];
            foreach ($attachment as $model) {
                $params['templateParams']['attachment'][] = 'https://myalfreddev.blob.core.windows.net/myrewards/'.$model->file_name;
            }
        }
        dispatch(new FTCMailServiceJob($params));

    }

    public function saveForm($request, $update = false) {

        if( Auth::user()->hasRole('advisor') ) {
            $carQuote = CarQuote::where(['id' => $request->input('car_quote_id', -1), 'advisor_id' => Auth::user()->id])->get()->first();
            if($carQuote) {

                if($request->input('status', '') == 'resubmitForApproval' || $request->input('status', '') == 'ftc_pending') {
                    
                    $paEmail = null;
                    if($carQuote->pa_id()->first())
                        $paEmail =$carQuote->pa_id()->first()->email;
                    $carQuote->pa_id = null;
                    $carQuote->quote_status_id = LookUpModel::getLookModel('QuoteStatus', ['code', '=', $request->input('status')]);
                    $carQuote->save();

                    if($request->input('status') == 'resubmitForApproval' ) { 
                        if($paEmail) {
                            $templateParams = [
                                'notes' => $request->input('notes', ""),
                                "first_name" => $carQuote->first_name,
                                "last_name" => $carQuote->last_name,
                                "code" => $carQuote->code
                            ];

                            $params = [
                                'to' => $paEmail,
                                'subject' => LookUpModel::subjectForFTCEmailCarQuote($carQuote),
                                'templateName' => 'notification',
                                'templateParams' => $templateParams
                            ];
                            dispatch(new FTCMailServiceJob($params));
                        }
                    }
                }

                if($request->input('status', '') == 'FTC Sent') {

                    $carQuoteEmailLink = new CarQuoteEmailUniqueLink;
                    $carQuoteEmailLink->car_quote_id = $carQuote->id;
                    $carQuoteEmailLink->hash = Hash::make($carQuote->id."-".$carQuote->code);
                    $carQuoteEmailLink->status = "Pending";

                    if($carQuoteEmailLink->save()) {

                        $getQuote = new CarQuote;
                        $results = $getQuote->processGetBaseDSL(["id" => $request->input('car_quote_id')] , false);
                        $row = $results[0];
                        $row['generateLink'] = [ 'hash' => $carQuoteEmailLink->hash, 'quote' => $carQuote->id];
                        $row->dob = Carbon::parse($row->dob)->format('d F Y');
                        $this->sendFtcEmail($row,"ftc_mail");
                   }else{
                        return $this->APIController->respondData(["message" => "Something wrong"], 500);
                    }
                }
            }
            return parent::saveForm($request, $update );
        }
    }
}

