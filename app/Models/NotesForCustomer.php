<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Config;

class NotesForCustomer extends Model
{
    use HasFactory;

    protected $table = 'notes_for_customer';
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
    public function createdby()
    {
        return $this->belongsTo(User::class,'created_by_id','id');
    }

}
