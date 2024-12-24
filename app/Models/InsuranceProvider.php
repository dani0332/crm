<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class InsuranceProvider extends BaseModel implements AuditableContract
{
    use Auditable, HasFactory;

    protected $connection = 'mysql';
    protected $table = 'insurance_provider';
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
            'pa' => ['id', 'code', 'text', 'insurance_company_id'],
            'advisor' => ['id', 'code', 'text', 'insurance_company_id'],
            'oe' => ['id', 'code', 'text', 'insurance_company_id'],
            'admin' => ['id', 'code', 'text', 'insurance_company_id'],
            'invoicing' => ['code', 'text', 'insurance_company_id'],
        ],
    ];

    public function relations()
    {
        return [];
    }

    public function scopeWithActive($query)
    {
        return $query->where('is_active', 1);
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

    public function quoteTypes()
    {
        return $this->belongsToMany(QuoteType::class, 'insurance_provider_quote_type');
    }
}
