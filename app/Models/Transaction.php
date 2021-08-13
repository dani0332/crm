<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Customer;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;

class Transaction extends Model implements AuditableContract
{
    use HasFactory,Auditable;
    protected $table = 'transactions';

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = env("DATETIME_FORMAT");
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = env("DATETIME_FORMAT");
        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'customer_id', 'id');
    }

    public function assignedto()
    {
        return $this->belongsTo(User::class,'assigned_to_id','id');
    }
    public function createdby()
    {
        return $this->belongsTo(User::class,'created_by_id','id');
    }
    public function modifiedby()
    {
        return $this->belongsTo(User::class,'modified_by_id','id');
    }
}
