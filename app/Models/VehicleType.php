<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
class VehicleType extends BaseModel
{
    use HasFactory;
    protected $table = 'vehicle_type';
    public $access = [

        'write' => ['advisor'],
        'update' => ['advisor'],
        'delete' => ['advisor'],
        'access' => [
            "pa" => [ 'id','category', 'text', 'is_active'],
            "invoicing" => [ 'id','category', 'text', 'is_active'],
            "advisor" => [ 'id','category', 'text', 'is_active'],
            "admin" => [ 'id','category', 'text', 'is_active' ],
        ],
        "list" => [
            "pa" => ['id','category', 'text', 'is_active' ],
            "invoicing" => ['id','category', 'text', 'is_active' ],
            "advisor" => [ 'id','category', 'text', 'is_active'],
            "admin" => [ 'id','category', 'text', 'is_active']
        ]
    ];

    public function relations() {
        return [];
    }

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, false);
    }

}
