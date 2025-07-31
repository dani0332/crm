<?php

namespace App\Services;

use App\Enums\FTCEmailLogEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\FtcEmailLog;

class FtcEmailLogService
{
    /**
     * Create or retrieve an email tracking log
     *
     * @param  array  $payload
     * @return array{message: string, data: FtcEmailLog}
     */
    public function createTrackEmail($payload): array
    {
        $checkEmailFtcLog = FtcEmailLog::where('uuid', $payload['uuid'])->first();
        if ($checkEmailFtcLog) {
            return ['message' => 'Email log already created with this uuid', 'data' => $checkEmailFtcLog];
        }
        $trackEmail = FtcEmailLog::create($payload);

        return ['message' => 'Email log created successfully', 'data' => $trackEmail];
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
