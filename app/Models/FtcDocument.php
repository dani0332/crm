<?php

namespace App\Models;

use App\Models\BaseModel;

class FtcDocument extends BaseModel
{

    protected $table = 'car_quote_ftc_documents';
    public $access = [
        'write' => ['advisor'],
        'update' => ['advisor'],
        'delete' => ['advisor'],
        'access' => [
            "pa" => [ 'file_name' , 'document' , 'car_quote_id'],
            "invoicing" => [ 'file_name' , 'document' , 'car_quote_id'],
            "advisor" => [ 'file_name' , 'document' , 'car_quote_id' ],
            "admin" => [ 'file_name' , 'document' ],
        ],
        "list" => [
            "pa" => [ 'id' , 'file_name' ],
            "invoicing" => [ 'id' , 'file_name' ],
            "advisor" => [ 'id' , 'file_name' , 'document'],
            "admin" => [ 'id' , 'file_name', 'document' ],
        ]
    ];

    public function document()
    {
        return $this->hasOne(CarQuoteDocuments::class, 'id', 'document')->select(['id', 'code','text']);
    }

    public function relations() {

        $role = 'advisor';
        switch($role){
            case 'pa':
                return [];
            case 'admin':
            case 'advisor':
                return ["document"];
        }

        return [];
    }

    public function processGetDSL($filters, $request) {
       return self::processGetBaseDSL($filters, false);
    }
    public function saveForm($request, $update = false) {
        parent::saveForm( $request, $update );
    }
}
