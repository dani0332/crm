<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use LookUpModel;

class CarQuotePaymentHistory extends BaseModel
{
    use HasFactory;

    protected $table = 'car_quote_payment_history';
    public $access = [
        'write' => ['payment'],
        'update' => ['payment'],
        'delete' => ['payment'],
        'access' => [
            'pa' => [],
            'production_approval_manager' => [],
            'advisor' => [],
            'oe' => [],
            'admin' => [],
            'invoicing' => [],
            'payment' => ['car_quote_id', 'status', 'notes'],
        ],
        'list' => [
            'pa' => ['id', 'car_quote_id', 'status', 'notes'],
            'production_approval_manager' => ['id', 'car_quote_id', 'status', 'notes'],
            'advisor' => ['id', 'car_quote_id', 'status', 'notes'],
            'oe' => ['id', 'car_quote_id', 'status', 'notes'],
            'admin' => ['id', 'car_quote_id', 'status', 'notes'],
            'invoicing' => ['id', 'car_quote_id', 'status', 'notes'],
            'payment' => ['id', 'car_quote_id', 'status', 'notes'],
        ],
    ];

    public function saveForm($request, $update = false)
    {
        try {
            if (Auth::user()->hasRole('payment') && $request->has('status')) {
                $carQuote = CarQuote::with(['advisor_id'])->where(['id' => $request->input('car_quote_id', -1), 'payment_id' => Auth::user()->id])->first();
                $statusVal = Str::replace(' ', '', $request->input('status'));

                if ($carQuote && ($statusVal === 'TransactionApproved' || $statusVal === 'TransactionDeclined')) {
                    $carQuote->quote_status_id = $statusVal === 'TransactionApproved' ? LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'transaction_approved']) : LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'transaction_declined']);
                    $carQuote->save();
                }

                return parent::saveForm($request, $update);
            } else {
                return $this->APIController->respondData(['message' => 'Something wrong'], 500);
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
