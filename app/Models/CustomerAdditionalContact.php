<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CustomerAdditionalContact extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    protected $table = 'customer_additional_contact';
    protected $fillable = ['key', 'value', 'customer_id'];

    public function getCreatedAtAttribute($date)
    {
        return $this->asDateTime($date)->timezone(config('app.timezone'))->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }

    public function getUpdatedAtAttribute($date)
    {
        return $this->asDateTime($date)->timezone(config('app.timezone'))->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }
}
