<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Carbon\Carbon;

class CustomerAdditionalContact extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'customer_additional_contact';
    protected $fillable = ['key', 'value', 'customer_id'];

    public function getCreatedAtAttribute($date)
    {
        return (!empty($date)) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : "";
    }

    public function getUpdatedAtAttribute($date)
    {
        return (!empty($date)) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : "";
    }
}
