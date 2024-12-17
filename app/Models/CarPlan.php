<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CarPlan extends BaseModel implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'car_plan';
    protected $guarded = ['id'];
    public $access = [

        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            'pa' => ['code', 'text'],
            'advisor' => ['code', 'text'],
            'oe' => ['code', 'text'],
            'admin' => ['code', 'text'],
            'invoicing' => ['code', 'text'],
        ],
        'list' => [
            'pa' => ['id', 'code', 'text', 'provider_id'],
            'advisor' => ['id', 'code', 'text', 'provider_id'],
            'oe' => ['id', 'code', 'text', 'provider_id'],
            'admin' => ['id', 'code', 'text', 'provider_id'],
            'invoicing' => ['code', 'text', 'provider_id'],
        ],
    ];

    public function provider_id()
    {
        return $this->hasOne(InsuranceProvider::class, 'id', 'provider_id');
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'provider_id');
    }

    public function relations()
    {
        return [];
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

    public function carAddons()
    {
        return $this->belongsToMany(CarAddOn::class, 'car_plan_addon', 'plan_id', 'addon_id');
    }

}
