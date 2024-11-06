<?php

namespace App\Models;

use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class RenewalsUploadLeads extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $fillable = ['file_name', 'file_path', 'total_records', 'cannot_upload', 'good', 'status', 'renewal_import_code', 'renewal_import_type', 'created_by_id', 'skip_plans', 'is_sic'];
    protected $table = 'renewals_upload_leads';

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

    public function createdby()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }
}
