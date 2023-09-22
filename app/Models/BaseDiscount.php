<?php

namespace App\Models;

use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class BaseDiscount extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'discount_engine_base';
    protected $fillable = ['value_start', 'value_end', 'vehicle_type_id', 'comprehensive_discount', 'agency_discount'];

    public function VehicleTye()
    {
        return $this->hasOne(VehicleType::class, 'id', 'vehicle_type_id');
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
