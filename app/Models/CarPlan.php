<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;

class CarPlan extends BaseModel
{
    use HasFactory;
    protected $table = 'car_plan';

    public $access = [

        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            "pa" => [ 'code', 'text'],
            "advisor" => [ 'code', 'text'],
            "oe" => [ 'code', 'text'],
            "admin" => [ 'code', 'text' ],
            "invoicing" => [ 'code', 'text']
        ],
        "list" => [
            "pa" => ['id','code', 'text', 'provider_id' ],
            "advisor" => [ 'id','code', 'text' , 'provider_id'],
            "oe" => [ 'id','code', 'text' , 'provider_id'],
            "admin" => [ 'id','code', 'text' , 'provider_id'],
            "invoicing" => [ 'code', 'text' , 'provider_id']
        ]
    ];

    public function provider_id()
    {
        return $this->hasOne(InsuranceProvider::class, 'id', 'provider_id');
    }

    public function relations() {
        return [];
    }

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, false);
    }

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
}
