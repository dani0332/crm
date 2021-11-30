<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Auth;
use Illuminate\Support\Str;
use App\Jobs\FTCMailServiceJob;
use LookUpModel;

class CarQuotePaymentHistory extends BaseModel
{
    use HasFactory;
    protected $table = 'car_quote_payment_history';
    public $access = [
        'write'  => ['invoicing'],
        'update' => ['invoicing'],
        'delete' => ['invoicing'],
        'access' => [
            "pa" => [],
            "production_approval_manager" => [],
            "advisor" => [],
            "oe" => [],
            "admin" => [],
            "invoicing" => [ 'car_quote_id', 'status', 'notes' ]
        ],
        "list" => [
            "pa" => ['id', 'car_quote_id', 'status', 'notes'],
            "production_approval_manager" => ['id', 'car_quote_id', 'status', 'notes'],
            "advisor" => [ 'id', 'car_quote_id', 'status', 'notes'],
            "oe" => [ 'id', 'car_quote_id', 'status', 'notes'],
            "admin" => ['id', 'car_quote_id', 'status', 'notes'],
            "invoicing" => ['id', 'car_quote_id', 'status', 'notes' ]
        ]
    ];

    public function processGetDSL($filters, $request) {
        return self::processGetBaseDSL($filters);
    }

     public function saveForm($request, $update = false) {

        try{
            if( Auth::user()->hasRole('invoicing') && $request->has('status')) {

                $carQuote = CarQuote::with(["advisor_id"])->where(['id' => $request->input('car_quote_id', -1), 'invoicing' => Auth::user()->id])->first();
                $statusVal = Str::replace(' ', '', $request->input('status'));

                if($carQuote &&  ( $statusVal === 'TransactionApproved' ||  $statusVal === 'TransactionDeclined')) {
                    $row = $carQuote->toArray();
                    $row['notes'] =  $request->input('notes', "");
                    $paymentStatus =  $statusVal === 'TransactionApproved' ? "Approved" : "Declined";
                    $params = [
                        'to' => $row['advisor_id']["email"],
                        'subject' => LookUpModel::subjectForFTCEmailCarQuote($carQuote), //'Payment Status is updated to '.$paymentStatus.' - CDB-ID:'.$carQuote->code,
                        'templateName' => 'notification',
                        'templateParams' => $row
                    ];

                    dispatch(new FTCMailServiceJob($params));
                    $carQuote->quote_status_id =  $statusVal === 'TransactionApproved' ?  LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'transaction_approved']) : LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'transaction_declined']);
                    $carQuote->save();
                }
                return parent::saveForm($request, $update);
            }else{
                return $this->APIController->respondData(["message" => "Something wrong"], 500);
            }
        }
        catch(\Exception $e) {
            return $e->getMessage();
        }
    }
}
