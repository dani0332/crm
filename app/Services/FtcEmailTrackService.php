<?php

namespace App\Services;

use App\Enums\FTCEmailTrackEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\FtcEmailTrack;

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
        if($trackEmail == null) {
            return null;
        }
        if($payload['status'] == FTCEmailTrackEnum::CLICKED) {
            $trackEmail->quoteTrackable->update(['quote_status_id' => QuoteStatusEnum::PaymentInitiated]);
        }

        return $trackEmail;
    }
}
