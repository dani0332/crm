<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Auth;
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

    public function relations() {
        return ['mode_id'];
    }

    public function processGetDSL($filters, $request) {
        return self::processGetBaseDSL($filters, false);
    }

    public function saveForm($request, $update = false) {

        try{
            if( Auth::user()->hasRole('advisor')) {
                $carQuote = CarQuote::where(['id' => $request->input('car_quote_id', -1)])->first();
                if($carQuote) {
                    if(parent::saveForm($request, $update)){
                        $carQuote->quote_status_id =  13; // AML cleared
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
