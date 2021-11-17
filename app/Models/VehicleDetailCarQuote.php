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
            "production_approval_manager" => [ ],
            "invoicing" => [ ],
            "advisor" => ['car_quote_id','engine_capacity' , 'cylinder' , 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity','vehicle_modified', 'specs', 'current_cover', 'date_first_registration'  ],
            "admin" => ['car_quote_id','engine_capacity' , 'cylinder' , 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity','vehicle_modified', 'specs', 'current_cover', 'date_first_registration' ],
        ],
        "list" => [
            "pa" => [ 'car_quote_id', 'engine_capacity' , 'cylinder' , 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity','vehicle_modified','specs', 'current_cover', 'date_first_registration' ],
            "production_approval_manager" => [  'car_quote_id','engine_capacity' , 'cylinder' , 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity','vehicle_modified','specs', 'current_cover', 'date_first_registration' ],
            "invoicing" => [ 'car_quote_id', 'engine_capacity' , 'cylinder' , 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity','vehicle_modified','specs', 'current_cover' , 'date_first_registration'],
            "advisor" => [ 'car_quote_id','engine_capacity' , 'cylinder' , 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity','vehicle_modified','specs', 'current_cover', 'date_first_registration'],
            "admin" => [ 'car_quote_id', 'engine_capacity' , 'cylinder' , 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity','vehicle_modified','specs', 'current_cover', 'date_first_registration' ],
        ]
    ];

    public function car_quote_id()
    {
        return $this->hasOne(CarQuote::class, 'id', 'car_quote_id')->select(['id','quote_status_id']);
    }
    
    public function relations() {
        return ['car_quote_id.quote_status_id'];
    }

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, false);
    }
}
