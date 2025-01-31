<?php

namespace App\Models;

use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use LookUpModel;

class CarQuoteKYCStatus extends BaseModel
{
    use HasFactory;

    protected $table = 'car_quote_kyc_status';
    public $access = [
        'write' => ['pa', 'admin'],
        'update' => ['pa', 'admin'],
        'delete' => ['pa', 'admin'],
        'access' => [
            'pa' => ['car_quote_id', 'status', 'notes'],
            'advisor' => ['car_quote_id', 'status', 'notes'],
            'oe' => ['car_quote_id', 'status', 'notes'],
            'admin' => ['car_quote_id', 'status', 'notes'],
            'invoicing' => ['car_quote_id', 'status', 'notes'],
            'payment' => ['car_quote_id', 'status', 'notes'],
        ],
        'list' => ['id', 'status', 'notes', 'updated_at'],
    ];

    public function status()
    {
        return $this->hasOne(KycStatus::class, 'id', 'status')->select(['id', 'text']);
    }

    public function relations()
    {
        return ['status'];
    }

    public function saveForm($request, $update = false)
    {
        if (Auth::user()->hasRole('pa') && $request->has('status')) {
            $carQuote = CarQuote::where(['id' => $request->input('car_quote_id', -1), 'pa_id' => Auth::user()->id])->first();
            $status = $request->input('status', 0);

            if ($carQuote) {
                $carQuote->kyc_status_id = $status;
                $quoteStatusId = 0;
                switch ($status) {
                    case '1':
                    case '3':
                        $quoteStatusId = LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'missing_documents_requested']); // 12;
                        $carQuote->pa_id = null;

                        break;
                    case '2':
                        $quoteStatusId = LookUpModel::getLookModel('QuoteStatus', ['code', '=', 'kyc_cleared']); // 11;
                        break;
                    default:
                        break;
                }

                $carQuote->quote_status_id = $quoteStatusId;
                $carQuote->save();
            }

            return parent::saveForm($request, $update);
        } else {
            return ['data' => false];
        }
    }
}
