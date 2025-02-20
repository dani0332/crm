<?php

namespace App\Http\Traits;

use App\Models\FtcEmailTrack;

trait HasFtcEmailTrack
{
    /**
     * Get all of the model's ftc email tracks.
     */
    public function ftcEmailTracks()
    {
        return $this->morphMany(FtcEmailTrack::class, 'quote_trackable');
    }
}