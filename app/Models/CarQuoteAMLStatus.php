<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Auth;

class CarQuoteAMLStatus extends BaseModel
{
    use HasFactory;
    protected $table = 'car_quote_aml_status';
    public $access = [
        'write' => ['pa', 'admin'],
        'update' => ['pa', 'admin'],
        'delete' => ['pa', 'admin'],
        'access' => [
            "pa" => [  'car_quote_id' , 'status', 'notes'],
            "advisor" => [  'car_quote_id' , 'status', 'notes'],
            "oe" => [  'car_quote_id' , 'status', 'notes'],
            "admin" => [  'car_quote_id' , 'status', 'notes'],
            "payment" => [  'car_quote_id' , 'status', 'notes'],
        ],
        "list" => [ 'id', 'status', 'notes', 'updated_at' ]
    ];

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, false);
    }

    public function saveForm($request, $update) {

        if( Auth::user()->hasRole('pa')  || Auth::user()->hasRole('production_approval_manager')) {
            if(parent::saveForm($request, $update)) {
                $carQuote = CarQuote::where(['id' => $request->input('car_quote_id', -1), 'pa_id' => Auth::user()->id])->get()->first();
                if($carQuote) {
                    $carQuote->aml_status = $request->input("status");
                    $carQuote->quote_status_id = strtolower($request->input("status")) == "approved" ? 13 : 18;
                    $carQuote->save();
                    return $this->APIController->respond(["data" => null], 200);
                }
            }
        }

        return $this->APIController->respondData(["message" => "Something wrong"], 500);
    }
}
