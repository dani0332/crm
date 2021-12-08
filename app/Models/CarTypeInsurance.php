<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Config;
use App\Models\BaseModel;

class CarTypeInsurance extends BaseModel implements AuditableContract
{
    use HasFactory, Auditable;
    protected $table = 'car_type_insurance';

    public $access = [

        'write' => ['advisor','oe'],
        'update' => ['advisor','oe'],
        'delete' => ['advisor','oe'],
        'access' => [
            "pa" => [ ],
            "advisor" => [ ],
            "oe" => [ ],
            "admin" => [  ],
            "invoicing" => [ ]

        ],
        "list" => [
            "pa" => ['id', 'text' ],
            "advisor" => [ 'id', 'text'],
            "oe" => [ 'id', 'text'],
            "admin" => ['id', 'text'],
            "invoicing" => [ 'id', 'text']
        ]
    ];

    public function relations() {
        return [];
    }

    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, false);
    }

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = Config::get('constants.datetime_format');
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
}
