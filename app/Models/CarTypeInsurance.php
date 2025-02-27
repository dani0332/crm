<?php

namespace App\Models;

use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CarTypeInsurance extends BaseModel implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'car_type_insurance';
    public $access = [

        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            'pa' => [],
            'advisor' => [],
            'oe' => [],
            'admin' => [],
            'invoicing' => [],
            'payment' => [],

        ],
        'list' => [
            'pa' => ['id', 'text'],
            'advisor' => ['id', 'text'],
            'oe' => ['id', 'text'],
            'admin' => ['id', 'text'],
            'invoicing' => ['id', 'text'],
            'payment' => ['id', 'text'],
        ],
    ];

    public function relations()
    {
        return [];
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

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
    }
}
