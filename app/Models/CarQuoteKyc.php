<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;

class CarQuoteKyc extends BaseModel
{
    use HasFactory;
    protected $table = 'car_quote_kyc';

    public $access = [
        'write'  => ['advisor', 'admin'],
        'update' => ['advisor', 'admin'],
        'delete' => [ 'admin'],
        'access' => [
            "pa" => [],
            "advisor" => [ 'car_quote_id', 'profession', 'organization', 'designation'],
            "admin" => [ 'car_quote_id', 'profession', 'organization' , 'designation'],
            "invoicing" => []
        ],
        "list" => [
            "pa" => ['id', 'car_quote_id' , 'profession', 'organization', 'designation'],
            "advisor" => [ 'id','car_quote_id' , 'profession', 'organization', 'designation'],
            "admin" => ['id', 'car_quote_id' , 'profession', 'organization', 'designation'],
            "invoicing" => ['id','car_quote_id' , 'profession', 'organization', 'designation']
        ]
    ];

    public function processGetDSL($filters, $request) {
        return self::processGetBaseDSL($filters);
     }
}
