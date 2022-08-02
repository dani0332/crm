<?php

namespace App\Models;

use App\Jobs\FTCMailServiceJob;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Arr;
use LookUpModel;

class CarQuotePolicy extends BaseModel
{
    use HasFactory;

    protected $table = 'car_quote_policy';
    public $access = [
        'write' => ['pa'],
        'update' => ['pa'],
        'delete' => ['admin'],
        'access' => [
            'advisor' => [],
            'oe' => [],
            'production_approval_manager' => [],
            'pa' => ['car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date', 'policy_type'],
            'admin' => ['car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date', 'policy_type'],
            'invoicing' => [],
            'payment' => [],
        ],
        'list' => [
            'pa' => ['id', 'car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date', 'policy_type'],
            'production_approval_manager' => ['id', 'car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date', 'policy_type'],
            'advisor' => ['id', 'car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date', 'policy_type'],
            'oe' => ['id', 'car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date', 'policy_type'],
            'admin' => ['id', 'car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date', 'policy_type'],
            'invoicing' => ['id', 'car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date', 'policy_type'],
            'payment' => ['id', 'car_quote_id', 'transactions_id', 'quote_number', 'policy_number', 'issue_date', 'start_date', 'end_date', 'policy_type'],
        ],
    ];

    public function transactions_id()
    {
        return $this->hasOne(Transaction::class, 'id', 'transactions_id');
    }

    public function relations()
    {
        return ['transactions_id', 'transactions_id.insurance_company_id', 'transactions_id.type_of_insurance_id', 'transactions_id.payment_mode_id', 'transactions_id.customer'];
    }

    public function processGetDSL($filters, $request)
    {
        if ($request->form_id) {
            return self::processGetBaseDSL($filters, false);
        }

        $response = self::processGetBaseDSL($filters, false)->first();

        if ($response) {
            $collection = $response->toArray();
            if (Arr::exists($collection, 'transactions_id')) {
                $response = array_merge($collection['transactions_id'], $collection);
            }
        }

        return $response;
    }

    public function saveForm($request, $update = false)
    {
        if (Auth::user()->hasRole('pa')) {
            $carQuote = CarQuote::where(['id' => $request->input('car_quote_id', -1), 'pa_id' => Auth::user()->id])->get()->first();
            if ($carQuote) {
                if (parent::saveForm($request, $update)) {
                    $templateParams = [
                        'notes' => 'Policy issued',
                        'first_name' => $carQuote->first_name,
                        'last_name' => $carQuote->last_name,
                        'code' => $carQuote->code,
                    ];
                    $advisorEmail = $carQuote->advisor_id()->first()->email;
                    if ($advisorEmail) {
                        $params = [
                            'to' => $advisorEmail,
                            'subject' => LookUpModel::subjectForFTCEmailCarQuote($carQuote),
                            'templateName' => 'notification',
                            'templateParams' => $templateParams,
                        ];

                        $oeId = $carQuote->oe_id()->first();
                        if ($oeId && $oeId->email) {
                            $params['cc'] = $oeId->email;
                        }

                        dispatch(new FTCMailServiceJob($params));
                    }

                    $carQuote->quote_status_id = LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'policy_issued']);

                    return $carQuote->save();
                }
            }
        }

        return $this->APIController->respondData(['message' => 'Something wrong'], 500);
    }
}
