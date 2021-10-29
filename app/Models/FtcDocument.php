<?php

namespace App\Models;
use App\Models\BaseModel;
use Auth;

class FtcDocument extends BaseModel
{

    protected $table = 'car_quote_ftc_documents';
    public $access = [
        'write' => ['advisor', 'admin'],
        'update' => ['advisor', 'admin'],
        'delete' => ['advisor', 'admin'],
        'access' => [
            "pa" => [ 'file_name' , 'document' , 'car_quote_id'],
            "production_approval_manager" => [ 'file_name' , 'document' , 'car_quote_id'],
            "invoicing" => [ 'file_name' , 'document' , 'car_quote_id'],
            "advisor" => [ 'file_name' , 'document' , 'car_quote_id' ],
            "admin" => [ 'file_name' , 'document' ],
        ],
        "list" => [
            "pa" => [ 'id' , 'file_name' , 'document'],
            "production_approval_manager" => [ 'id' , 'file_name' , 'document'],
            "invoicing" => [ 'id' , 'file_name' , 'document'],
            "advisor" => [ 'id' , 'file_name' , 'document'],
            "admin" => [ 'id' , 'file_name', 'document' ],
        ]
    ];

    public function document()
    {
        return $this->hasOne(CarQuoteDocuments::class, 'id', 'document')->select(['id', 'code','text']);
    }

    public function relations() {
        return ["document"];
    }

    public function processGetDSL($filters, $request) {
       return self::processGetBaseDSL($filters, false);
    }
}
