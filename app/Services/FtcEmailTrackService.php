<?php

namespace App\Services;

use App\Models\FtcEmailTrack;
use Illuminate\Http\Request;

class FtcEmailTrackService
{
    public function createTrackEmail($payload)
    {
        $trackEmail = FtcEmailTrack::create($payload);
        return $trackEmail;
    }

    public function updateTrackEmail($payload, $id, $link = null)
    {
        $trackEmail = $link == null ? FtcEmailTrack::find($id) : FtcEmailTrack::where('link', $link)->first();
        $trackEmail->update($payload);
        return $trackEmail;
    }
}