<?php

namespace App\Services;

use App\Enums\FTCEmailLogEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\FtcEmailLog;

class FtcEmailLogService
{
    public function createTrackEmail($payload)
    {
        $trackEmail = FtcEmailLog::create($payload);

        return $trackEmail;
    }

    public function updateTrackEmail($payload, $id, $uuid = null)
    {
        $trackEmail = $uuid == null ? FtcEmailLog::find($id) : FtcEmailLog::where('uuid', $uuid)->first();
        if ($trackEmail == null) {
            return null;
        }
        if ($payload['status'] == FTCEmailLogEnum::CLICKED) {
            $trackEmail->quoteTrackable->update(['quote_status_id' => QuoteStatusEnum::PaymentInitiated]);
        }
        $trackEmail->update($payload);

        return $trackEmail;
    }
}
