<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CustomerAdditionalContact extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $table = 'customer_additional_contact';

    public function getCreatedAtAttribute($table)
    {
        $dateTimeFormat = config('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($dateTimeFormat);
    }

    public function getUpdatedAtAttribute($table)
    {
        $dateTimeFormat = config('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($dateTimeFormat);
    }
}
