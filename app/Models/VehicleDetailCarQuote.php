<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class VehicleDetailCarQuote extends BaseModel
{
    use HasFactory;
    protected $table = 'car_quote_vehicle_detail';
    public $access = [
        'write' => ['advisor'],
        'update' => ['advisor'],
        'delete' => ['advisor'],
        'access' => [
            "pa" => [ ],
            "invoicing" => [ ],
            "advisor" => ['car_quote_id','engine_capacity' , 'cylinder' , 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity','vehicle_modified', 'specs', 'current_cover', 'date_first_registration'  ],
            "admin" => ['car_quote_id','engine_capacity' , 'cylinder' , 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity','vehicle_modified', 'specs', 'current_cover', 'date_first_registration' ],
        ],
        "list" => [
            "pa" => [  'engine_capacity' , 'cylinder' , 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity','vehicle_modified','specs', 'current_cover', 'date_first_registration' ],
            "invoicing" => [  'engine_capacity' , 'cylinder' , 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity','vehicle_modified','specs', 'current_cover' , 'date_first_registration'],
            "advisor" => [ 'engine_capacity' , 'cylinder' , 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity','vehicle_modified','specs', 'current_cover', 'date_first_registration'],
            "admin" => [  'engine_capacity' , 'cylinder' , 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity','vehicle_modified','specs', 'current_cover', 'date_first_registration' ],
        ]
    ];

    public function relations() {
        return [];
    }

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, false);
    }
}
