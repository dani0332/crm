<?php

namespace App\Models;

use App\Traits\SpatieActivityLog;
use MongoDB\Laravel\Eloquent\Model;

class AlfredChat extends Model
{
    use SpatieActivityLog;

    protected $connection = 'alfredchatmongo';
    protected $table = 'chats';
    protected $casts = ['createdAt' => 'datetime', 'updatedAt' => 'datetime'];

    public function getCreatedAtAttribute($date)
    {
        $date_time_format = config('constants.datetime_format');

        return $this->asDateTime($date)->timezone(config('app.timezone'))->format($date_time_format);
    }
}
