<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Models\CarQuote;
use App\Models\CarQuoteEmailUniqueLink;
use Auth;
use App\Jobs\MailServiceJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class FTCHistory extends BaseModel
{
    use HasFactory;
    protected $table = 'ftc_history';
    public $access = [

        'write' => ['advisor'],
        'update' => ['advisor'],
        'delete' => ['advisor'],
        'access' => [
            "pa" => [],
            "invoicing" => [ ],
            "advisor" => ['car_quote_id', 'status', 'data'],
            "admin" => [ 'car_quote_id', 'status', 'data'],
        ],
        "list" => [
            "pa" => ['id' , 'status' , 'data', 'created_at' ],
            "invoicing" => ['id' , 'status' , 'data', 'created_at' ],
            "advisor" => [ 'id' , 'status', 'data' , 'created_at'],
            "admin" => ['id' , 'status' , 'data' , 'created_at']
        ]
    ];

    public function relations() {
        return [];
    }

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, false);
    }

    public function saveForm($request, $update = false) {

        if( Auth::user()->hasRole('advisor') ) {
            $carQuote = CarQuote::where(['id' => $request->input('car_quote_id', -1), 'advisor_id' => Auth::user()->id])->get()->first();
            if($carQuote) {

                if($request->input('status', '') == 'Resubmit for Approval') {

                    $carQuote->pa_id = null;
                    $carQuote->quote_status_id = 7;//6;
                    $carQuote->save();
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
                        $templateParams = collect($row)->toArray();

                        $params = [
                            'to' => $carQuote->email,
                            'subject' => 'Required Additional Document - CDB-ID:'.$carQuote->code,
                            'templateName' => 'ftc_mail',
                            'templateParams' => $templateParams
                        ];
                        dispatch(new MailServiceJob($params));
                    }else{

                    }
                }
            }
            return parent::saveForm($request, $update );
        }
    }
}

