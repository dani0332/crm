<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use LookUpModel;

class CarQuotePayment extends BaseModel
{
    use HasFactory;

    protected $table = 'car_quote_payment';
    public $access = [
        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            'pa' => [],
            'production_approval_manager' => [],
            'advisor' => ['car_quote_id', 'mode_id', 'method', 'comment'],
            'oe' => ['car_quote_id', 'mode_id', 'method', 'comment'],
            'admin' => ['car_quote_id', 'mode_id', 'method', 'comment'],
            'invoicing' => [],
            'payment' => [],
        ],
        'list' => [
            'pa' => ['id', 'car_quote_id', 'mode_id', 'method', 'comment'],
            'production_approval_manager' => ['id', 'car_quote_id', 'mode_id', 'method', 'comment'],
            'advisor' => ['id', 'car_quote_id', 'mode_id', 'method', 'comment'],
            'oe' => ['id', 'car_quote_id', 'mode_id', 'method', 'comment'],
            'admin' => ['id', 'car_quote_id', 'mode_id', 'method', 'comment'],
            'invoicing' => ['id', 'car_quote_id', 'mode_id', 'method', 'comment'],
            'payment' => ['id', 'car_quote_id', 'mode_id', 'method', 'comment'],
        ],
    ];

    public function mode_id()
    {
        return $this->hasOne(FTCPaymentMode::class, 'id', 'mode_id')->select(['id', 'name']);
    }

    public function car_quote_id()
    {
        return $this->hasOne(CarQuote::class, 'id', 'car_quote_id')->select(['id', 'quote_status_id', 'payment_status_id', 'payment_gateway', 'payment_reference', 'is_ecommerce']);
    }

    public function relations()
    {
        return ['mode_id', 'car_quote_id.quote_status_id', 'car_quote_id.payment_status_id'];
    }

    public function saveForm($request, $update = false)
    {
        try {
            if (Auth::user()->hasRole('advisor') || Auth::user()->hasRole('oe')) {
                $carQuote = CarQuote::where(['id' => $request->input('car_quote_id', -1)])->first();
                if ($carQuote) {
                    if (parent::saveForm($request, $update)) {
                        // when transaction declined
                        $transcDeclinedId = LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'transaction_declined']);
                        if ($transcDeclinedId == $carQuote->quote_status_id) {
                            $carQuote->quote_status_id = LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'AMLScreeningCleared']);

                            return $carQuote->save();
                        } else {
                            return $this->APIController->respondData(['message' => 'Successfully saved'], 200);
                        }
                    }
                }
            }

            return $this->APIController->respondData(['message' => 'Something wrong'], 500);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
