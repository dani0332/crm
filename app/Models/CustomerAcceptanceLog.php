<?php

namespace App\Models;

use App\Models\Trait\HasDateTrait;
use Illuminate\Database\Eloquent\Model;

class CustomerAcceptanceLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    protected $guarded = [];

    public function getCreatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }

    public function getUpdatedAtAttribute($table)
    {
        $date_time_format = config('constants.datetime_format');

        return $this->asDateTime($table)->timezone(config('app.timezone'))->format($date_time_format);
    }
}
