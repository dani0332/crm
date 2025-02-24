<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FtcEmailTrack extends Model
{
    protected $fillable = [];
    protected $guarded = [];

    public function quoteTrackable(): MorphTo
    {
        return $this->morphTo();
    }
}
