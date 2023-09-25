<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Transaction extends Model implements AuditableContract
{
    use Auditable,HasFactory;

    protected $table = 'transactions';

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

    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }

    public function assignedto()
    {
        return $this->belongsTo(User::class, 'assigned_to_id', 'id');
    }

    public function type_of_insurance_id()
    {
        return $this->belongsTo(TypeOfInsurance::class, 'type_of_insurance_id', 'id');
    }

    public function typeofinsurance()
    {
        return $this->belongsTo(TypeOfInsurance::class, 'type_of_insurance_id', 'id');
    }

    public function insurance_company_id()
    {
        return $this->hasOne(InsuranceCompany::class, 'id', 'insurance_company_id');
    }

    public function payment_mode_id()
    {
        return $this->hasOne(PaymentMode::class, 'id', 'payment_mode_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }

    public function modifiedby()
    {
        return $this->belongsTo(User::class, 'modified_by_id', 'id');
    }
}
