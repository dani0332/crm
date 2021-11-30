<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Auth;
use App\Jobs\FTCMailServiceJob;
use LookUpModel;
class CarQuotePayment extends BaseModel
{
    use HasFactory;
    protected $table = 'car_quote_payment';
    public $access = [
        'write'  => ['advisor'],
        'update' => ['advisor'],
        'delete' => [ 'advisor'],
        'access' => [
            "pa" => [],
            "production_approval_manager" => [],
            "advisor" => [ 'car_quote_id', 'mode_id', 'method', 'comment' ],
            "admin" => [ 'car_quote_id', 'mode_id', 'method', 'comment'],
            "invoicing" => []
        ],
        "list" => [
            "pa" => ['id', 'car_quote_id' , 'mode_id', 'method', 'comment'],
            "production_approval_manager" => ['id', 'car_quote_id' , 'mode_id', 'method', 'comment'],
            "advisor" => [ 'id', 'car_quote_id' , 'mode_id', 'method', 'comment'],
            "admin" => ['id', 'car_quote_id' , 'mode_id', 'method', 'comment'],
            "invoicing" => ['id', 'car_quote_id' , 'mode_id', 'method', 'comment']
        ]
    ];

    public function mode_id()
    {
        return $this->hasOne(FTCPaymentMode::class, 'id', 'mode_id')->select(['id', 'name']);
    }

    public function car_quote_id()
    {
        return $this->hasOne(CarQuote::class, 'id', 'car_quote_id')->select(['id','quote_status_id']);
    }

    public function relations() {
        return ['mode_id', 'car_quote_id.quote_status_id'];
    }

    public function processGetDSL($filters, $request) {
        return self::processGetBaseDSL($filters, false);
    }

    public function saveForm($request, $update = false) {

        try{
            if( Auth::user()->hasRole('advisor')) {
                $carQuote = CarQuote::where(['id' => $request->input('car_quote_id', -1)])->first();
                if($carQuote) {

                    if(parent::saveForm($request, $update)) {

                        $advisorEmail = $carQuote->advisor_id()->first()->email;
                        $templateParams = [
                            'notes' => $request->input('comment', ""),
                            "first_name" => $carQuote->first_name,
                            "last_name" => $carQuote->last_name,
                            "code" => $carQuote->code
                        ];
                        if($advisorEmail) {
                            $params = [
                                'to' => $advisorEmail,
                                'subject' => LookUpModel::subjectForFTCEmailCarQuote($carQuote),
                                'templateName' => 'notification',
                                'templateParams' => $templateParams
                            ];
                            dispatch(new FTCMailServiceJob($params));
                        }
                        
                        $carQuote->quote_status_id =  LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'AMLScreeningCleared']);
                        return $carQuote->save();
                    }
                }
            }
            return $this->APIController->respondData(["message" => "Something wrong"], 500);
        }
        catch(\Exception $e) {
            return $e->getMessage();
        }
    }
}
