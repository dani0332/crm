<?php

namespace App\Services;

use App\Models\FtcEmailTrack;
use Illuminate\Http\Request;

class FtcEmailTrackService
{
    public function createTrackEmail(Request $request)
    {
        $payload = [
            'email' => $request->email,
            'subject' => $request->subject,
            'status' => $request->status,
            'link' => $request->link,
            'quote_trackable_id' => $request->quote_trackable_id,
            'quote_trackable_type' => $request->quote_trackable_type,
        ];
        $trackEmail = FtcEmailTrack::create($payload);
        return $trackEmail;
    }

    public function updateTrackEmail(Request $request, $id)
    {
        $trackEmail = FtcEmailTrack::find($id);
        $payload = [
            'email' => $request->email,
            'subject' => $request->subject,
            'status' => $request->status,
            'link' => $request->link,
        ];
        $trackEmail->update($payload);
        return $trackEmail;
    }
}