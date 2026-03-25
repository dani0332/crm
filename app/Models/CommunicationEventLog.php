<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationEventLog extends Model
{
    use HasFactory;

    protected $table = 'communication_event_log';
    protected $fillable = [
        'quote_uuid',
        'quote_type_id',
        'event_channel',
        'communication_type',
        'action_event',
    ];

    public function getCreatedAtAttribute($date): string
    {
        return (! empty($date)) ? Carbon::parse($date)->timezone(config('app.timezone'))->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }

    public function getUpdatedAtAttribute($date): string
    {
        return (! empty($date)) ? Carbon::parse($date)->timezone(config('app.timezone'))->format(config('constants.DATETIME_DISPLAY_FORMAT')) : '';
    }
}
