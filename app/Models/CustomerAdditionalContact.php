<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CustomerAdditionalContact extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'customer_additional_contact';
    protected $fillable = ['key', 'value', 'customer_id'];

    public function getCreatedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::parse($date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    public function getUpdatedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::parse($date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }
}
