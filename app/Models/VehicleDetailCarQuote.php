<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehicleDetailCarQuote extends BaseModel
{
    use HasFactory;

    protected $table = 'car_quote_vehicle_detail';
    public $access = [
        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            'pa' => [],
            'production_approval_manager' => [],
            'invoicing' => [],
            'payment' => [],
            'advisor' => ['car_quote_id', 'engine_capacity', 'cylinder', 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity', 'vehicle_modified', 'specs', 'current_cover', 'date_first_registration'],
            'oe' => ['car_quote_id', 'engine_capacity', 'cylinder', 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity', 'vehicle_modified', 'specs', 'current_cover', 'date_first_registration'],
            'admin' => ['car_quote_id', 'engine_capacity', 'cylinder', 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity', 'vehicle_modified', 'specs', 'current_cover', 'date_first_registration'],
        ],
        'list' => [
            'pa' => ['car_quote_id', 'engine_capacity', 'cylinder', 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity', 'vehicle_modified', 'specs', 'current_cover', 'date_first_registration', 'car_value'],
            'production_approval_manager' => ['car_quote_id', 'engine_capacity', 'cylinder', 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity', 'vehicle_modified', 'specs', 'current_cover', 'date_first_registration', 'car_value'],
            'invoicing' => ['car_quote_id', 'engine_capacity', 'cylinder', 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity', 'vehicle_modified', 'specs', 'current_cover', 'date_first_registration', 'car_value'],
            'payment' => ['car_quote_id', 'engine_capacity', 'cylinder', 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity', 'vehicle_modified', 'specs', 'current_cover', 'date_first_registration', 'car_value'],
            'advisor' => ['car_quote_id', 'engine_capacity', 'cylinder', 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity', 'vehicle_modified', 'specs', 'current_cover', 'date_first_registration', 'car_value'],
            'oe' => ['car_quote_id', 'engine_capacity', 'cylinder', 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity', 'vehicle_modified', 'specs', 'current_cover', 'date_first_registration', 'car_value'],
            'admin' => ['car_quote_id', 'engine_capacity', 'cylinder', 'chassis_number', 'engine_number', 'vehicle_color', 'seating_capacity', 'vehicle_modified', 'specs', 'current_cover', 'date_first_registration', 'car_value'],
        ],
    ];

    public function car_quote_id()
    {
        return $this->hasOne(CarQuote::class, 'id', 'car_quote_id')->select(['id', 'quote_status_id']);
    }

    public function relations()
    {
        return ['car_quote_id.quote_status_id'];
    }
}
