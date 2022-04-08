<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;
use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CarModelDetail extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable;
    protected $table = 'car_model_detail';

    public function carModel()
    {
        return $this->hasOne(CarModel::class, 'car_model_id', 'id');
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
    public function processGetDSL($filters) {
        return self::processGetBaseDSL($filters, 'car_model_detail', ['id', 'text', 'is_active', 'cylinder', 'seating_capacity', 'current_value', 'car_model_id', 'is_default']);
    }
}
