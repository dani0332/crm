<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;

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
            "advisor" => [ 'car_quote_id', 'mode', 'method', 'comment' ],
            "admin" => [ 'car_quote_id', 'mode', 'method', 'comment'],
            "invoicing" => []
        ],
        "list" => [
            "pa" => ['id', 'car_quote_id' , 'mode', 'method', 'comment'],
            "advisor" => [ 'id', 'car_quote_id' , 'mode', 'method', 'comment'],
            "admin" => ['id', 'car_quote_id' , 'mode', 'method', 'comment'],
            "invoicing" => ['id', 'car_quote_id' , 'mode', 'method', 'comment']
        ]
    ];

    public function processGetDSL($filters, $request) {
        return self::processGetBaseDSL($filters);
     }
}
