<?php

namespace App\Models;

use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class TmLead extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'tm_leads';
    protected $guarded = [];

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

    public function tmcallstatus()
    {
        return $this->belongsTo(TmCallStatus::class, 'tm_call_statuses_id', 'id');
    }

    public function tmleadstatus()
    {
        return $this->belongsTo(TmLeadStatus::class, 'tm_lead_statuses_id', 'id');
    }

    public function tminsurancetype()
    {
        return $this->belongsTo(TmInsuranceType::class, 'tm_insurance_types_id', 'id');
    }

    public function nationality()
    {
        return $this->belongsTo(Nationality::class, 'nationality_id', 'id');
    }

    public function yearsofdriving()
    {
        return $this->belongsTo(UAELicenseHeldFor::class, 'years_of_driving_id', 'id');
    }

    public function carmake()
    {
        return $this->belongsTo(CarMake::class, 'car_make_id', 'id');
    }

    public function carmodel()
    {
        return $this->belongsTo(CarModel::class, 'car_model_id', 'id');
    }

    public function emiratesofregistration()
    {
        return $this->belongsTo(Emirate::class, 'emirates_of_registration_id', 'id');
    }

    public function tmuploadleads()
    {
        return $this->belongsTo(TmUploadLead::class, 'tm_upload_leads_id', 'id');
    }

    public function assignedto()
    {
        return $this->belongsTo(User::class, 'assigned_to_id', 'id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }

    public function updatedby()
    {
        return $this->belongsTo(User::class, 'modified_by_id', 'id');
    }

    public function tmleadtype()
    {
        return $this->belongsTo(TmLeadType::class, 'tm_lead_types_id', 'id');
    }

    public function cartypeofinsurance()
    {
        return $this->belongsTo(CarTypeInsurance::class, 'car_type_insurance_id', 'id');
    }

    public function additionalInformation()
    {
        return $this->hasMany(TmLeadContactInformation::class, 'tm_lead_id', 'id');
    }
    public function getAuditables()
    {
        return [
            'auditable_type' => self::class,
        ];
    }
}
