<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailStatus extends Model
{
    use HasFactory;

    protected $table = 'email_status';
    protected $fillable = [
        'quote_type_id',
        'quote_id',
        'email_address',
        'msg_id',
        'reason',
        'email_status',
        'email_subject',
        'template_id',
        'customer_id',
    ];

    public function getCreatedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::parse($date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    public function getUpdatedAtAttribute($date)
    {
        return (! empty($date)) ? Carbon::parse($date)->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }
}
