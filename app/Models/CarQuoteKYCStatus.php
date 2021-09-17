<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Models\CarQuote;
use Auth;
use App\Jobs\FTCMailServiceJob;

class CarQuoteKYCStatus extends BaseModel
{
    use HasFactory;
    protected $table = 'car_quote_kyc_status';
    public $access = [
        'write' => ['pa', 'admin'],
        'update' => ['pa', 'admin'],
        'delete' => ['pa', 'admin'],
        'access' => [
            "pa" => [  'car_quote_id' , 'status', 'notes'],
            "advisor" => [  'car_quote_id' , 'status', 'notes'],
            "admin" => [  'car_quote_id' , 'status', 'notes'],
            "invoicing" => [  'car_quote_id' , 'status', 'notes'],
        ],
        "list" => [ 'id', 'status', 'notes', 'updated_at' ]
    ];

    public function status()
    {
        return $this->hasOne(KycStatus::class, 'id', 'status')->select(['id', 'text']);
    }

    public function relations() {
        return ['status'];
    }

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, false);
    }

    public function saveForm($request, $update = false) {

        $carQuote = CarQuote::where(['id' => $request->input('car_quote_id', -1)])->get()->first();
        if( Auth::user()->hasRole('pa') && $request->has('status')) {
            $status = $request->input('status', 0);
            if($status  == "1") { // request additional document
                $carQuote = CarQuote::where(['id' => $request->input('car_quote_id', -1), 'pa_id' => Auth::user()->id])->get()->first();
                if($carQuote) {

                    $templateParams = [
                        'notes' => $request->input('notes', ""),
                        "first_name" => $carQuote->first_name,
                        "last_name" => $carQuote->last_name,
                        "code" => $carQuote->code
                    ];

                    $advisorEmail = $carQuote->advisor_id()->get()->first()->email;
                    if($advisorEmail) {
                        $params = [
                            'to' => $advisorEmail,
                            'subject' => 'Required Additional Document - CDB-ID:'.$carQuote->code,
                            'templateName' => 'notification',
                            'templateParams' => $templateParams
                        ];
                        dispatch(new FTCMailServiceJob($params));
                    }
                }
            }
            if($carQuote){
                $carQuote->kyc_status_id = $status;
                $carQuote->save();
            }
            parent::saveForm($request, $update);
        }else{
            return ['data' => false];
        }
    }
}
