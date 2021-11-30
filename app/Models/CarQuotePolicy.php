<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Auth;
use Illuminate\Support\Arr;
use App\Models\CarQuote;
use App\Jobs\FTCMailServiceJob;
use LookUpModel;

class CarQuotePolicy extends BaseModel
{
    use HasFactory;
    protected $table = 'car_quote_policy';
    public $access = [
        'write'  => ['pa'],
        'update' => ['pa'],
        'delete' => ['admin'],
        'access' => [
            "advisor" => [],
            "oe" => [],
            "production_approval_manager" => [],
            "pa" => [ 'car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ],
            "admin" => [ 'car_quote_id' , 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ],
            "invoicing" => []
        ],
        "list" => [
            "pa" => ['id', 'car_quote_id' , 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ],
            "production_approval_manager" => ['id', 'car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ],
            "advisor" => ['id', 'car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ],
            "oe" => ['id', 'car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ],
            "admin" => ['id', 'car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ],
            "invoicing" => ['id', 'car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date' ]
        ]
    ];

    public function transactions_id()
    {
        return $this->hasOne(Transaction::class, 'id', 'transactions_id');
    }

    public function relations() {
        return ['transactions_id', 'transactions_id.insurance_company_id', 'transactions_id.typeofinsurance', 'transactions_id.payment_mode_id', 'transactions_id.customer'];
    }

    public function processGetDSL($filters, $request) {

        if($request->form_id){
            return self::processGetBaseDSL($filters, false);
        }
        
        $response =  self::processGetBaseDSL($filters, false)->first();
        $collection = $response->toArray();
        if(Arr::exists($collection, 'transactions_id')){
            $response = array_merge( $collection['transactions_id'], $collection);
        }

        return $response;       
    }


    public function saveForm($request, $update = false) {

        if( Auth::user()->hasRole('pa') ) {
            $carQuote = CarQuote::where(['id' => $request->input('car_quote_id', -1), 'advisor_id' => Auth::user()->id])->get()->first();
            if($carQuote) {
                $templateParams = [
                    'notes' => "Policy issued",
                    "first_name" => $carQuote->first_name,
                    "last_name" => $carQuote->last_name,
                    "code" => $carQuote->code
                ];
                $advisorEmail = $carQuote->advisor_id()->first()->email;
                if($advisorEmail) {
                    $params = [
                        'to' => $advisorEmail,
                        'subject' => LookUpModel::subjectForFTCEmailCarQuote($carQuote),
                        'templateName' => 'notification',
                        'templateParams' => $templateParams
                    ];
                    dispatch(new FTCMailServiceJob($params));
                }
            }
        }

        return parent::saveForm($request, $update ); 
    }
}
