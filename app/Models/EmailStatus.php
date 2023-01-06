<?php

namespace App\Models;

use Config;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmailStatus extends Model
{
    use HasFactory;

    protected $table = 'email_status';

    public function getCreatedAtAttribute($date)
    {
        return (!empty($date)) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : "";
    }

    public function getUpdatedAtAttribute($date)
    {
        return (!empty($date)) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : "";
    }
}
