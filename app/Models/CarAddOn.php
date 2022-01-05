<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarAddOn extends Model
{
    use HasFactory;
    protected $table = 'car_addon';
    public $access = [

        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            "pa" => [ ],
            "advisor" => [ ],
            "oe" => [ ],
            "admin" => [  ],
            "invoicing" => [ ]
        ],
        "list" => [
            "pa" => ['id','text', 'description' ],
            "advisor" => ['id','text', 'description' ],
            "oe" => ['id','text', 'description' ],
            "admin" => ['id','text', 'description' ],
            "invoicing" => ['id','text', 'description' ],
        ]
    ];
   
    public function relations() {
        return [];
    }

    public function processGetDSL($filters, $update) {
        return self::processGetBaseDSL($filters, $update);
    }
}
