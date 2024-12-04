<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class InsuranceCompany extends BaseModel implements AuditableContract
{
    use Auditable,HasFactory;

    protected $table = 'insurance_companies';
    public $access = [
        'write' => ['advisor', 'oe'],
        'update' => ['advisor', 'oe'],
        'delete' => ['advisor', 'oe'],
        'access' => [
            'pa' => ['id', 'name', 'is_active'],
            'advisor' => ['id', 'name', 'is_active'],
            'oe' => ['id', 'name', 'is_active'],
            'admin' => ['id', 'name', 'is_active'],
            'invoicing' => ['id', 'name', 'is_active'],
            'payment' => ['id', 'name', 'is_active'],
        ],
        'list' => [
            'pa' => ['id', 'name', 'is_active'],
            'advisor' => ['id', 'name', 'is_active'],
            'oe' => ['id', 'name', 'is_active'],
            'admin' => ['id', 'name', 'is_active'],
            'invoicing' => ['id', 'name', 'is_active'],
            'payment' => ['id', 'name', 'is_active'],
        ],
    ];

    public function relations()
    {
        return [];
    }

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = env('DATETIME_FORMAT');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = env('DATETIME_FORMAT');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
}
