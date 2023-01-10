<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotesForCustomer extends Model
{
    use HasFactory;

    protected $table = 'notes_for_customer';
    protected $guarded = [];

    public function getCreatedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    public function getUpdatedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::createFromFormat('Y-m-d H:i:s', $date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }
}
