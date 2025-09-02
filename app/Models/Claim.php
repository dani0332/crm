<?php

namespace App\Models;

use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Claim extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'claims';

    public function typeofinsurance()
    {
        return $this->belongsTo(TypeOfInsurance::class, 'type_of_insurances_id', 'id');
    }

    public function subtypeofinsurance()
    {
        return $this->belongsTo(SubTypeOfInsurance::class, 'sub_type_of_insurance_id', 'id');
    }

    public function carmake()
    {
        return $this->belongsTo(CarMake::class, 'car_make_id', 'id');
    }

    public function carmodel()
    {
        return $this->belongsTo(CarModel::class, 'car_model_id', 'id');
    }

    public function claimsstatus()
    {
        return $this->belongsTo(ClaimsStatus::class, 'claims_status_id', 'id');
    }

    public function carrepaircoverage()
    {
        return $this->belongsTo(CarRepairCoverage::class, 'car_repair_coverage_id', 'id');
    }

    public function carrepairtype()
    {
        return $this->belongsTo(CarRepairType::class, 'car_repair_type_id', 'id');
    }

    public function rentacar()
    {
        return $this->belongsTo(RentACar::class, 'rent_a_car_id', 'id');
    }

    public function assignedto()
    {
        return $this->belongsTo(User::class, 'assigned_to_id', 'id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }

    public function modifiedby()
    {
        return $this->belongsTo(User::class, 'modified_by_id', 'id');
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

    public function insuranceprovider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'insurance_provider_id', 'id');
    }

    public function claimsAttachments()
    {
        return $this->hasMany(ClaimsAttachments::class, 'claims_id', 'id');
    }

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($claim) {
            $claim->claimsAttachments()->delete();
        });
    }
}
